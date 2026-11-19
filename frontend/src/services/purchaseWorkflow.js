import api from './api';

export const purchaseWorkflow = {
  async list() { return (await api.get('/purchases')).data.data; },
  async create(payload) { return (await api.post('/purchases', payload)).data.data; },
  async progress(purchase, action) {
    const progress = Object.fromEntries([
      'supplier_status', 'supplier_confirmation_date', 'supplier_notes', 'delivery_info',
      'warehouse_arrived', 'discrepancy_reason', 'discrepancy_reported_at', 'discrepancy_media',
      'discrepancy_info', 'discrepancy_resolution', 'rejection_reason',
    ].filter(key => purchase[key] !== undefined).map(key => [key, purchase[key]]));
    return (await api.post(`/purchases/${encodeURIComponent(purchase.purchase_code)}/supplier-progress`, {
      action, version: purchase.progress_version || 0, progress,
    })).data.data;
  },
  async requests() { return (await api.get('/warehouse/requests')).data.data; },
  async request(payload) { return (await api.post('/warehouse/requests', payload)).data.data; },
  async cancelRequest(code) { return (await api.post(`/warehouse/requests/${encodeURIComponent(code)}/cancel`)).data.data; },
  async followup(purchase, { reference, notes }) {
    return (await api.post(`/purchases/${encodeURIComponent(purchase.purchase_code)}/supplier-progress`, {
      action: 'followup', version: purchase.progress_version || 0,
      progress: { followup_reference: reference, followup_notes: notes },
    })).data.data;
  },
  async receive(purchase, payload) {
    return (await api.post(`/purchases/${encodeURIComponent(purchase.purchase_code)}/receive`, payload)).data.data;
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
