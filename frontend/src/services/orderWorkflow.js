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
  async invoice(order, { invoiceDate, dueDate, paymentType, terminCount }) {
    return (await api.post(`/orders/${encodeURIComponent(order.orderNumber)}/invoice`, { invoiceDate, dueDate, paymentType, terminCount })).data;
  },
  async fetchInvoiceBlob(orderNumber, format = 'pdf', preview = false) {
    try {
      const response = await api.get(`/orders/${encodeURIComponent(orderNumber)}/invoice/download`, {
        params: preview ? { preview: 1 } : (format === 'docx' ? { format } : {}),
        responseType: 'blob',
      });
      const disposition = response.headers['content-disposition'] || '';
      const match = disposition.match(/filename="?([^";]+)"?/i);
      const filename = match ? match[1] : `Invoice_${orderNumber}.${format}`;
      const mime = format === 'docx'
        ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        : 'application/pdf';
      const blob = response.data instanceof Blob
        ? new Blob([response.data], { type: response.data.type || mime })
        : new Blob([response.data], { type: mime });
      const url = URL.createObjectURL(blob);
      return { blob, url, filename };
    } catch (error) {
      if (error.response?.data instanceof Blob) {
        try {
          const text = await error.response.data.text();
          const parsed = JSON.parse(text);
          if (parsed?.message) error.response.data = parsed;
        } catch {
          // keep original error if blob is not JSON
        }
      }
      throw error;
    }
  },
  async downloadInvoice(orderNumber, preview = false, format = 'pdf') {
    const previewTab = preview ? window.open('about:blank', '_blank') : null;
    if (preview && !previewTab) throw new Error('Izinkan tab pratinjau atau pilih Unduh PDF.');
    if (previewTab) previewTab.opener = null;
    let file;
    try {
      file = await this.fetchInvoiceBlob(orderNumber, format, preview);
    } catch (error) {
      previewTab?.close();
      throw error;
    }

    if (previewTab) {
      if (!previewTab.closed) previewTab.location.replace(file.url);
      setTimeout(() => URL.revokeObjectURL(file.url), 60000);
      return;
    }

    const link = document.createElement('a');
    link.href = file.url;
    link.download = file.filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(file.url), 10000);
  },
};

export function orderError(error) {
  const errors = error.response?.data?.errors;
  return errors ? Object.values(errors).flat().join(' ') : error.response?.data?.message || error.message || 'Pesanan gagal disimpan. Silakan coba lagi.';
}
