import api from './api';

// Announce invalidation only; every tab still reads its own authorized server data.
const liveChannel = typeof window !== 'undefined' && typeof BroadcastChannel !== 'undefined'
  ? new BroadcastChannel('alpharo-procurement-updates') : null;
const invalidate = () => { if (typeof window !== 'undefined') window.dispatchEvent(new Event('procurement:updated')); };
liveChannel?.addEventListener('message', invalidate);
async function saveMutation(request) {
  const result = (await request).data.data;
  invalidate();
  liveChannel?.postMessage({ type: 'changed' });
  return result;
}

export const purchaseWorkflow = {
  async list() { return (await api.get('/purchases')).data.data; },
  async create(payload) { return saveMutation(api.post('/purchases', payload)); },
  async progress(purchase, action) {
    const progress = Object.fromEntries([
      'supplier_status', 'supplier_confirmation_date', 'supplier_notes', 'delivery_info',
      'warehouse_arrived', 'discrepancy_reason', 'discrepancy_reported_at', 'discrepancy_media',
      'discrepancy_info', 'discrepancy_resolution', 'rejection_reason',
    ].filter(key => purchase[key] !== undefined).map(key => [key, purchase[key]]));
    return saveMutation(api.post(`/purchases/${encodeURIComponent(purchase.purchase_code)}/supplier-progress`, {
      action, version: purchase.progress_version || 0, progress,
    }));
  },
  async requests() { return (await api.get('/warehouse/requests')).data.data; },
  async request(payload) { return saveMutation(api.post('/warehouse/requests', payload)); },
  async cancelRequest(code) { return saveMutation(api.post(`/warehouse/requests/${encodeURIComponent(code)}/cancel`)); },
  async followup(purchase, { reference, notes }) {
    return saveMutation(api.post(`/purchases/${encodeURIComponent(purchase.purchase_code)}/supplier-progress`, {
      action: 'followup', version: purchase.progress_version || 0,
      progress: { followup_reference: reference, followup_notes: notes },
    }));
  },
  async receive(purchase, payload) {
    return saveMutation(api.post(`/purchases/${encodeURIComponent(purchase.purchase_code)}/receive`, payload));
  },
};

export function mergePurchaseRecords(purchases, incoming) {
  for (const data of incoming) {
    const existing = purchases.find(purchase => purchase.purchase_code === data.purchase_code);
    if (!existing) { purchases.unshift(data); continue; }
    if ((existing.progress_version || 0) > (data.progress_version || 0)) continue;
    const legacy = !data.progress_persisted && !existing.progress_persisted;
    const preserved = legacy ? Object.fromEntries(Object.entries(existing).filter(([key]) =>
      key.startsWith('supplier_') || key.startsWith('discrepancy_') || ['delivery_info', 'warehouse_arrived', 'received_at', 'received_notes', 'rejection_reason'].includes(key))) : {};
    const merged = { ...existing, ...data, ...preserved };
    if (JSON.stringify(existing) !== JSON.stringify(merged)) Object.assign(existing, merged);
  }
}
