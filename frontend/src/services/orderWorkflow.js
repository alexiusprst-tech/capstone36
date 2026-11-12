import api from './api';

export const orderWorkflow = {
  async list() { return (await api.get('/orders')).data.data; },
  async save(order) {
    return (await api.put(`/orders/${encodeURIComponent(order.orderNumber)}`, {
      version: order.serverManaged ? order.version : 0,
      details: order,
      items: order.items,
    })).data.data;
  },
  async remove(order) { await api.delete(`/orders/${encodeURIComponent(order.orderNumber)}`); },
  async submit(order) { return (await api.post(`/orders/${encodeURIComponent(order.orderNumber)}/submit`, { version: order.version })).data.data; },
  async stockCheck(order) { return (await api.post(`/orders/${encodeURIComponent(order.orderNumber)}/stock-check`)).data.data; },
  async approve(order, notes) { return (await api.post(`/orders/${encodeURIComponent(order.orderNumber)}/approve`, { notes })).data.data; },
  async invoice(order, data) { return (await api.post(`/orders/${encodeURIComponent(order.orderNumber)}/invoice`, data)).data.data; },
  async downloadInvoice(orderNumber, preview = false) {
    const response = await api.get(`/orders/${encodeURIComponent(orderNumber)}/invoice/download`, {
      params: preview ? { preview: 1 } : {},
      responseType: 'blob',
    });
    const disposition = response.headers['content-disposition'] || '';
    const match = disposition.match(/filename="?([^";]+)"?/i);
    const filename = match ? match[1] : `Invoice_${orderNumber}.pdf`;
    const url = URL.createObjectURL(response.data);

    if (preview) {
      window.open(url, '_blank');
      setTimeout(() => URL.revokeObjectURL(url), 60000);
      return;
    }

    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 10000);
  },
};

export function orderError(error) {
  const errors = error.response?.data?.errors;
  return errors ? Object.values(errors).flat().join(' ') : error.response?.data?.message || error.message || 'Pesanan gagal disimpan. Silakan coba lagi.';
}
