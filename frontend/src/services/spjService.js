import api from './api';

export const spjService = {
  // Ambil semua proyek SPJ
  async getProjects() {
    const res = await api.get('/spj/projects');
    return res.data;
  },

  // Ambil detail 1 proyek
  async getProject(projectId) {
    const res = await api.get(`/spj/projects/${projectId}`);
    return res.data;
  },

  // Perbarui data proyek SPJ (Header, Nilai, Pengadaan, PIC)
  async updateProject(projectId, payload) {
    const res = await api.put(`/spj/projects/${projectId}`, payload);
    return res.data;
  },

  // Ambil 15 dokumen SPJ proyek tertentu
  async getDocuments(projectId) {
    const res = await api.get(`/spj/projects/${projectId}/documents`);
    return res.data;
  },

  // Ambil detail form 1 dokumen
  async getDocument(projectId, docKey) {
    const res = await api.get(`/spj/projects/${projectId}/documents/${docKey}`);
    return res.data;
  },

  // Simpan form dokumen
  async updateDocument(projectId, docKey, payload) {
    const res = await api.put(`/spj/projects/${projectId}/documents/${docKey}`, payload);
    return res.data;
  },

  // Generate dokumen tunggal
  async generateDocument(projectId, docKey, formData = {}) {
    const res = await api.post(`/spj/projects/${projectId}/documents/${docKey}/generate`, {
      form_data: formData,
    });
    return res.data;
  },

  // Bulk generate dokumen terpilih
  async bulkGenerate(projectId, docKeys) {
    const res = await api.post(`/spj/projects/${projectId}/bulk-generate`, {
      doc_keys: docKeys,
    });
    return res.data;
  },

  // Upload dokumen eksternal
  async uploadExternal(projectId, docKey, file, metadata = {}) {
    const form = new FormData();
    form.append('file', file);
    if (metadata.doc_number) form.append('doc_number', metadata.doc_number);
    if (metadata.doc_date) form.append('doc_date', metadata.doc_date);
    if (metadata.notes) form.append('notes', metadata.notes);

    const res = await api.post(`/spj/projects/${projectId}/documents/${docKey}/upload-external`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return res.data;
  },

  // URL unduhan file
  getDownloadUrl(projectId, docKey) {
    const base = import.meta.env.VITE_API_BASE_URL || '/api';
    return `${base}/spj/projects/${projectId}/documents/${docKey}/download`;
  },
};

export default spjService;
