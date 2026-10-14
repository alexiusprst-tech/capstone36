<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Dokumen & Template Surat</h1>
          <p class="text-sm text-slate-500 mt-1">
            Repositori sentral arsip SPJ, pembuatan surat otomatis dari data order, dan pengelolaan master template.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
            @click="openGenerateModal()"
          >
            <Sparkles class="w-4 h-4" />
            <span>Generate Surat Otomatis</span>
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
            @click="openUploadModal"
          >
            <Upload class="w-4 h-4" />
            <span>Unggah Scan Basah</span>
          </button>
        </div>
      </div>
    </template>

    <!-- NAVIGATION TABS -->
    <div class="mb-6 flex items-center gap-2 border-b border-slate-200">
      <button
        type="button"
        :class="[
          'px-4 py-2.5 text-xs font-bold border-b-2 transition-colors flex items-center gap-2',
          activeTab === 'documents'
            ? 'border-alpharo-500 text-alpharo-600 bg-blue-50/50'
            : 'border-transparent text-slate-500 hover:text-slate-800'
        ]"
        @click="activeTab = 'documents'"
      >
        <FileText class="w-4 h-4" />
        <span>Arsip Berkas Proyek & SPJ ({{ allDocRows.length }})</span>
      </button>

      <button
        type="button"
        :class="[
          'px-4 py-2.5 text-xs font-bold border-b-2 transition-colors flex items-center gap-2',
          activeTab === 'templates'
            ? 'border-alpharo-500 text-alpharo-600 bg-blue-50/50'
            : 'border-transparent text-slate-500 hover:text-slate-800'
        ]"
        @click="activeTab = 'templates'"
      >
        <FolderArchive class="w-4 h-4" />
        <span>Master Template Surat ({{ templateList.length }})</span>
      </button>
    </div>

    <!-- TAB 1: ARSIP DOKUMEN PROYEK & SPJ -->
    <div v-if="activeTab === 'documents'">
      <!-- DOCUMENT COMPLETENESS PROGRESS CARDS -->
      <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="order in store.orders.filter(o => o.status !== 'Draft')"
          :key="order.id"
          class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-xs flex flex-col justify-between"
        >
          <div>
            <div class="flex items-start justify-between mb-2">
              <div>
                <span class="font-mono font-bold text-slate-900 text-sm">Order #{{ order.orderNumber }}</span>
                <p class="text-xs text-slate-600 font-medium truncate max-w-xs mt-0.5">{{ order.clientName }}</p>
              </div>
              <StatusBadge :status="getCompleteCount(order) === 4 ? 'Lengkap' : 'Diproses'" />
            </div>

            <!-- Document Checklist Lines -->
            <div class="my-3 space-y-1.5 text-xs">
              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                  <span :class="order.documents?.po?.status === 'Lengkap' ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                    {{ order.documents?.po?.status === 'Lengkap' ? '✓' : '○' }}
                  </span>
                  <span>Purchase Order (PO)</span>
                </span>
                <span class="font-mono text-[11px] text-slate-500">{{ order.documents?.po?.number || '-' }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                  <span :class="order.documents?.suratJalan?.status === 'Lengkap' ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                    {{ order.documents?.suratJalan?.status === 'Lengkap' ? '✓' : '○' }}
                  </span>
                  <span>Surat Jalan</span>
                </span>
                <span class="font-mono text-[11px] text-slate-500">{{ order.documents?.suratJalan?.number || '-' }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                  <span :class="order.documents?.bast?.status === 'Lengkap' ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                    {{ order.documents?.bast?.status === 'Lengkap' ? '✓' : '○' }}
                  </span>
                  <span>BAST</span>
                </span>
                <span class="font-mono text-[11px] text-slate-500">{{ order.documents?.bast?.number || '-' }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                  <span :class="order.documents?.faktur?.status === 'Lengkap' ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                    {{ order.documents?.faktur?.status === 'Lengkap' ? '✓' : '○' }}
                  </span>
                  <span>Faktur Pajak</span>
                </span>
                <span class="font-mono text-[11px] text-slate-500">{{ order.documents?.faktur?.number || '-' }}</span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">
              Progress: {{ getCompleteCount(order) }} / 4 Berkas
            </span>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="text-xs font-bold text-indigo-600 hover:text-indigo-800"
                @click="openGenerateModal(order)"
              >
                + Buat Surat
              </button>
              <button
                type="button"
                class="text-xs font-bold text-alpharo-500 hover:text-alpharo-700"
                @click="previewOrderDocs(order)"
              >
                Arsip →
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- CENTRAL DOCUMENTS TABLE -->
      <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
          <span class="font-bold text-slate-900 uppercase">Seluruh Berkas Dokumen Proyek</span>
          <span class="text-slate-500">Total {{ allDocRows.length }} Dokumen Tersimpan</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                <th class="py-3 px-4">Nama Dokumen</th>
                <th class="py-3 px-4">Tipe Berkas</th>
                <th class="py-3 px-4">No. Order</th>
                <th class="py-3 px-4">Client</th>
                <th class="py-3 px-4">Tanggal Arsip</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(doc, idx) in allDocRows" :key="idx" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4">
                  <span class="font-mono font-bold text-slate-900 block">{{ doc.number }}</span>
                  <span class="text-[11px] text-slate-400">{{ doc.fileName }}</span>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-700">{{ doc.type }}</td>
                <td class="py-3.5 px-4 font-mono text-slate-600">{{ doc.orderNumber }}</td>
                <td class="py-3.5 px-4 font-bold text-slate-800">{{ doc.clientName }}</td>
                <td class="py-3.5 px-4 text-slate-500">{{ doc.date }}</td>
                <td class="py-3.5 px-4 text-center"><StatusBadge :status="doc.status" /></td>
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <button type="button" class="p-1.5 text-alpharo-500 hover:bg-blue-50 rounded" title="Pratinjau" @click="previewFile(doc)">
                      <Eye class="w-4 h-4" />
                    </button>
                    <button type="button" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded" title="Unduh" @click="downloadFile(doc)">
                      <Download class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: MASTER TEMPLATE SURAT & SPJ -->
    <div v-else-if="activeTab === 'templates'">
      <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
          <div class="flex items-center gap-2">
            <span class="font-bold text-slate-900 uppercase">Daftar Master Template Surat (.docx)</span>
            <span class="px-2 py-0.5 bg-blue-100 text-alpharo-700 rounded-full font-mono text-[10px] font-bold">
              {{ filteredTemplates.length }} Template
            </span>
          </div>

          <!-- Category Filter -->
          <div class="flex items-center gap-2">
            <span class="text-slate-500 font-medium">Kategori:</span>
            <select v-model="selectedCategory" class="px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-medium">
              <option value="ALL">Semua Kategori</option>
              <option value="BAST">BAST</option>
              <option value="PO">Purchase Order (PO)</option>
              <option value="SURAT_JALAN">Surat Jalan</option>
              <option value="FAKTUR">Faktur</option>
              <option value="SPK">SPK</option>
              <option value="PENAWARAN">Penawaran (SPH)</option>
              <option value="SPJ">SPJ & Tender</option>
              <option value="FINANCE">Finance</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                <th class="py-3 px-4">Nama Template</th>
                <th class="py-3 px-4">Kategori</th>
                <th class="py-3 px-4">Metode Penggunaan</th>
                <th class="py-3 px-4">File Master</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-right">Aksi & Pengelolaan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="tpl in filteredTemplates" :key="tpl.code" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4">
                  <div class="flex items-start gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-alpharo-600 flex items-center justify-center shrink-0 mt-0.5">
                      <FileSpreadsheet class="w-4 h-4" />
                    </div>
                    <div>
                      <span class="font-bold text-slate-900 block text-sm">{{ tpl.name }}</span>
                      <span class="text-[11px] text-slate-500">{{ tpl.description }}</span>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4">
                  <span class="px-2 py-1 bg-slate-100 text-slate-700 font-mono font-bold rounded text-[10px]">
                    {{ tpl.category }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-bold text-[10px]',
                      tpl.type === 'AUTO_FILL'
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                        : 'bg-amber-50 text-amber-700 border border-amber-200'
                    ]"
                  >
                    <Sparkles v-if="tpl.type === 'AUTO_FILL'" class="w-3 h-3" />
                    <Download v-else class="w-3 h-3" />
                    {{ tpl.type === 'AUTO_FILL' ? 'Auto-Fill Data Order' : 'Unduh Kosong (Manual)' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                  {{ tpl.file_name }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold text-[10px]">
                    Aktif
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <!-- 1. Tombol Unduh Template Kosong -->
                    <button
                      type="button"
                      class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded text-[11px] flex items-center gap-1"
                      title="Unduh Template Kosong"
                      @click="downloadBlankTemplate(tpl)"
                    >
                      <Download class="w-3.5 h-3.5" />
                      <span>Unduh Kosong</span>
                    </button>

                    <!-- 2. Tombol Lihat Placeholder Variabel -->
                    <button
                      v-if="tpl.available_variables?.length"
                      type="button"
                      class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded"
                      title="Lihat Placeholder Variabel"
                      @click="viewTemplateVariables(tpl)"
                    >
                      <Code class="w-4 h-4" />
                    </button>

                    <!-- 3. Tombol Generate Langsung -->
                    <button
                      v-if="tpl.type === 'AUTO_FILL'"
                      type="button"
                      class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded text-[11px] flex items-center gap-1"
                      title="Generate Surat Otomatis dari Template Ini"
                      @click="openGenerateModal(null, tpl)"
                    >
                      <Sparkles class="w-3.5 h-3.5" />
                      <span>Generate</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL 1: GENERATE DOKUMEN OTOMATIS -->
    <Modal v-model="generateModalOpen" title="Generate Dokumen Surat Otomatis" subtitle="Otomatis mengisi template .docx dengan data transaksi order" size="lg">
      <form class="space-y-4 text-xs" @submit.prevent="handleGenerateDoc">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Order Terkait *</label>
            <select v-model="generateForm.orderNumber" required class="w-full px-3 py-2 border rounded-lg bg-white" @change="onOrderChange">
              <option value="" disabled>-- Pilih Order --</option>
              <option v-for="o in store.orders" :key="o.id" :value="o.orderNumber">
                Order #{{ o.orderNumber }} - {{ o.clientName }}
              </option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Template Dokumen *</label>
            <select v-model="generateForm.templateId" required class="w-full px-3 py-2 border rounded-lg bg-white">
              <option value="" disabled>-- Pilih Template --</option>
              <option v-for="t in templateList.filter(x => x.type === 'AUTO_FILL')" :key="t.id" :value="t.id">
                [{{ t.category }}] {{ t.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Nomor Surat Resmi</label>
            <input
              v-model="generateForm.docNumber"
              type="text"
              placeholder="Contoh: 001/AAJ/BAST/X/2026"
              class="w-full px-3 py-2 border rounded-lg bg-white font-mono"
            />
          </div>
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Tanggal Surat</label>
            <input
              v-model="generateForm.variables.tanggal"
              type="text"
              placeholder="Contoh: 12 Oktober 2026"
              class="w-full px-3 py-2 border rounded-lg bg-white"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Nama Klien / Sekolah</label>
            <input
              v-model="generateForm.variables.nama_sekolah"
              type="text"
              placeholder="Nama Klien atau Sekolah"
              class="w-full px-3 py-2 border rounded-lg bg-white"
            />
          </div>
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Nilai Kontrak / Total</label>
            <input
              v-model="generateForm.variables.nilai_kontrak"
              type="text"
              placeholder="Rp ..."
              class="w-full px-3 py-2 border rounded-lg bg-white"
            />
          </div>
        </div>

        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-slate-700 text-[11px] leading-relaxed">
          <p class="font-bold text-alpharo-800 mb-1 flex items-center gap-1.5">
            <Sparkles class="w-3.5 h-3.5" />
            Alur Otomatisasi:
          </p>
          Dokumen akan diproses oleh mesin backend dari master template di <code class="bg-blue-100 px-1 py-0.5 rounded font-mono">storage/app/templates/master/</code>. Hasil akhir akan disimpan ke <code class="bg-blue-100 px-1 py-0.5 rounded font-mono">storage/app/generated_docs/</code> dan langsung diunduh ke browser Anda.
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="generateModalOpen = false">
            Batal
          </button>
          <button type="submit" :disabled="isGenerating" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg flex items-center gap-1.5">
            <Sparkles class="w-4 h-4" />
            <span>{{ isGenerating ? 'Memproses Dokumen...' : 'Generate Surat (.docx)' }}</span>
          </button>
        </div>
      </form>
    </Modal>

    <!-- MODAL 2: LIHAT PLACEHOLDER / TAG VARIABEL -->
    <Modal v-model="variablesModalOpen" :title="`Variabel Tag: ${selectedTemplateForVars?.name || ''}`" subtitle="Tag placeholder yang dapat disematkan di file Microsoft Word (.docx)" size="md">
      <div v-if="selectedTemplateForVars" class="space-y-4 text-xs">
        <p class="text-slate-600">
          Gunakan format tag berikut di dalam file Word untuk digantikan otomatis oleh sistem:
        </p>

        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2 max-h-60 overflow-y-auto font-mono text-[11px]">
          <div
            v-for="v in selectedTemplateForVars.available_variables"
            :key="v"
            class="flex items-center justify-between p-2 bg-white rounded border border-slate-200"
          >
            <span class="text-indigo-600 font-bold">&#36;&#123;{{ v }}&#125;</span>
            <span class="text-slate-400 text-[10px] font-sans">atau &#123;&#123;{{ v }}&#125;&#125;</span>
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-200">
          <button type="button" class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg" @click="variablesModalOpen = false">
            Tutup
          </button>
        </div>
      </div>
    </Modal>

    <!-- PREVIEW MODAL -->
    <Modal v-model="previewModalOpen" :title="`Pratinjau Dokumen: ${previewDoc?.number || ''}`" :subtitle="previewDoc?.type || ''" size="md">
      <div v-if="previewDoc" class="space-y-4 text-xs">
        <div class="p-8 bg-slate-100 rounded-xl border border-slate-200 text-center space-y-3">
          <div class="w-16 h-16 rounded-full bg-blue-100 text-alpharo-500 mx-auto flex items-center justify-center">
            <FileText class="w-8 h-8" />
          </div>
          <div>
            <h4 class="font-bold text-sm text-slate-900">{{ previewDoc.fileName }}</h4>
            <p class="text-[11px] text-slate-500">Format: Microsoft Word / PDF &bull; Tervalidasi Legal</p>
          </div>
          <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[10px]">
            Dokumen Sah CV Alpharo Artha Jaya
          </span>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="previewModalOpen = false">Tutup</button>
          <button type="button" class="px-4 py-1.5 bg-alpharo-500 text-white font-bold rounded-lg" @click="downloadFile(previewDoc)">Unduh Berkas</button>
        </div>
      </div>
    </Modal>

    <!-- UPLOAD SCAN MODAL -->
    <Modal v-model="uploadModalOpen" title="Unggah Arsip Scan Basah Dokumen" subtitle="Simpan berkas scan stempel/tanda tangan basah ke arsip terpusat" size="md">
      <form class="space-y-4 text-xs" @submit.prevent="handleUpload">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Order Terkait *</label>
          <select required class="w-full px-3 py-2 border rounded-lg bg-white">
            <option v-for="o in store.orders" :key="o.id">{{ o.orderNumber }} - {{ o.clientName }}</option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Jenis Dokumen *</label>
          <select required class="w-full px-3 py-2 border rounded-lg bg-white">
            <option>Purchase Order (PO)</option>
            <option>Surat Jalan Pengiriman</option>
            <option>Berita Acara Serah Terima (BAST)</option>
            <option>Faktur Pajak</option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Pilih File PDF / Scan *</label>
          <input type="file" required class="w-full px-3 py-2 border rounded-lg bg-slate-50" />
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="uploadModalOpen = false">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-alpharo-500 text-white font-bold rounded-lg">Upload Arsip</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import { store } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import api from '../../services/api';
import {
  Upload, Eye, Download, FileText, Sparkles, FolderArchive,
  FileSpreadsheet, Code
} from 'lucide-vue-next';

const toast = useToast();

const activeTab = ref('documents'); // 'documents' | 'templates'
const selectedCategory = ref('ALL');

const previewModalOpen = ref(false);
const uploadModalOpen = ref(false);
const generateModalOpen = ref(false);
const variablesModalOpen = ref(false);

const previewDoc = ref(null);
const selectedTemplateForVars = ref(null);
const isGenerating = ref(false);

// Form data for document auto-generation
const generateForm = ref({
  orderNumber: '',
  templateId: '',
  docNumber: '',
  variables: {
    tanggal: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
    nama_sekolah: '',
    nilai_kontrak: '',
  },
});

// Master templates list
const templateList = ref([
  {
    id: 1,
    name: 'Berita Acara Serah Terima (BAST) - Sekolah',
    code: 'BAST_SEKOLAH',
    category: 'BAST',
    description: 'Format resmi serah terima barang/jasa untuk instansi sekolah dan dinas pendidikan.',
    file_name: 'BAST-Sekolah-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_surat', 'hari', 'tanggal', 'bulan', 'tahun', 'nama_sekolah', 'alamat_sekolah', 'nama_kepala_sekolah', 'nip_kepala_sekolah', 'nama_perusahaan', 'nama_direktur', 'nomor_order', 'nilai_kontrak', 'terbilang'],
  },
  {
    id: 2,
    name: 'Berita Acara Serah Terima (BAST) - Rekanan Perusahaan',
    code: 'BAST_COMPANY',
    category: 'BAST',
    description: 'Format serah terima pekerjaan/pengadaan material untuk client perusahaan swasta.',
    file_name: 'BAST-Company-Alpharo-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_surat', 'tanggal', 'nama_client', 'alamat_client', 'pic_client', 'nama_project', 'nomor_order', 'nilai_pekerjaan'],
  },
  {
    id: 3,
    name: 'Surat Pesanan / Purchase Order (PO)',
    code: 'SURAT_PESANAN',
    category: 'PO',
    description: 'Surat pesanan resmi pengadaan barang/jasa dari client rekanan.',
    file_name: 'Surat-Pesanan-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_po', 'tanggal_po', 'nama_klien', 'alamat_klien', 'nomor_order', 'daftar_barang', 'total_nominal', 'syarat_pembayaran'],
  },
  {
    id: 4,
    name: 'Surat Jalan Pengiriman Barang',
    code: 'SURAT_JALAN',
    category: 'SURAT_JALAN',
    description: 'Dokumen pengantar pengiriman material dari gudang menuju lokasi penerima.',
    file_name: 'Surat-Jalan-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_surat_jalan', 'tanggal_kirim', 'nama_penerima', 'alamat_tujuan', 'plat_kendaraan', 'nama_supir', 'rincian_barang'],
  },
  {
    id: 5,
    name: 'Faktur Tagihan & Pajak',
    code: 'FAKTUR',
    category: 'FAKTUR',
    description: 'Faktur resmi penagihan pembayaran termin/lunas beserta kalkulasi PPN/PPh.',
    file_name: 'Faktur-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_faktur', 'tanggal_faktur', 'jatuh_tempo', 'nama_klien', 'npwp_klien', 'subtotal', 'ppn', 'grand_total', 'terbilang'],
  },
  {
    id: 6,
    name: 'Surat Perintah Kerja (SPK)',
    code: 'SPK',
    category: 'SPK',
    description: 'Dokumen instruksi dan perjanjian pelaksanaan pekerjaan pengadaan.',
    file_name: 'SPK-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_spk', 'tanggal_spk', 'pihak_pertama', 'pihak_kedua', 'lingkup_pekerjaan', 'waktu_pelaksanaan', 'nilai_spk'],
  },
  {
    id: 7,
    name: 'Surat Penawaran Harga (SPH)',
    code: 'PENAWARAN',
    category: 'PENAWARAN',
    description: 'Surat penawaran harga resmi CV Alpharo Artha Jaya kepada calon client.',
    file_name: 'Penawaran-Alpharo-001-AAJ-SPH-X-2026-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_sph', 'tanggal_sph', 'tujuan_instansi', 'perihal', 'tabel_harga', 'masa_berlaku', 'total_penawaran'],
  },
  {
    id: 8,
    name: 'Harga Perkiraan Sendiri (HPS)',
    code: 'HPS',
    category: 'SPJ',
    description: 'Dokumen kalkulasi HPS untuk kelengkapan administrasi SPJ tender sekolah.',
    file_name: 'HPS-HPS20261001133304-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_hps', 'tanggal_hps', 'nama_kegiatan', 'satuan_kerja', 'total_hps'],
  },
  {
    id: 9,
    name: 'Berita Acara Hasil Pemeriksaan Barang',
    code: 'HASIL_PEMERIKSAAN',
    category: 'SPJ',
    description: 'Berita acara pemeriksaan fisik dan spesifikasi barang di lokasi.',
    file_name: 'Hasil-Pemeriksaan-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_surat', 'tanggal_periksa', 'tim_pemeriksa', 'kondisi_barang', 'catatan_hasil'],
  },
  {
    id: 10,
    name: 'Berita Acara Klarifikasi & Negosiasi Harga',
    code: 'KLARIFIKASI_NEGO',
    category: 'SPJ',
    description: 'Dokumen kesepakatan negosiasi harga pengadaan bersama PPK/panitia.',
    file_name: 'Klarifikasi-Nego-KN20261001134628-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_surat', 'tanggal_nego', 'harga_awal', 'harga_kesepakatan', 'hasil_klarifikasi'],
  },
  {
    id: 11,
    name: 'Kuitansi Pembayaran Resmi',
    code: 'KUITANSI',
    category: 'FINANCE',
    description: 'Kuitansi tanda terima uang pembayaran resmi berstempel dan bermaterai.',
    file_name: 'Kuitansi-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_kuitansi', 'terima_dari', 'jumlah_uang', 'untuk_pembayaran', 'terbilang'],
  },
  {
    id: 12,
    name: 'Surat Permintaan Penawaran Harga (SPPH)',
    code: 'SPPH',
    category: 'SPJ',
    description: 'Surat permohonan penawaran harga pengadaan dari pihak sekolah/klien.',
    file_name: 'SPPH-PR00001-TEMPLATE.docx',
    type: 'AUTO_FILL',
    available_variables: ['nomor_spph', 'tanggal_spph', 'nama_kegiatan', 'batas_waktu'],
  },
  {
    id: 13,
    name: 'Lampiran Dokumentasi & Foto Serah Terima',
    code: 'LAMPIRAN_DOKUMENTASI',
    category: 'SPJ',
    description: 'Format lembar lampiran dokumentasi foto barang dan serah terima fisik.',
    file_name: 'Lampiran-Dokumentasi-TEMPLATE.docx',
    type: 'BLANK_DOWNLOAD',
    available_variables: ['nomor_order', 'nama_project', 'tanggal_kegiatan'],
  },
]);

// Filtered templates
const filteredTemplates = computed(() => {
  if (selectedCategory.value === 'ALL') {
    return templateList.value;
  }
  return templateList.value.filter(t => t.category === selectedCategory.value);
});

// Fetch backend templates on mount if available
onMounted(async () => {
  try {
    const res = await api.get('/document-templates');
    if (res.data?.success && res.data.data?.length) {
      templateList.value = res.data.data;
    }
  } catch (err) {
    // Keep local default list if backend is offline
  }
});

const getCompleteCount = (order) => {
  let count = 0;
  if (order.documents?.po?.status === 'Lengkap') count++;
  if (order.documents?.suratJalan?.status === 'Lengkap') count++;
  if (order.documents?.bast?.status === 'Lengkap') count++;
  if (order.documents?.faktur?.status === 'Lengkap') count++;
  return count;
};

const allDocRows = computed(() => {
  const list = [];
  store.orders.forEach((o) => {
    if (o.documents?.po?.exists) {
      list.push({ number: o.documents.po.number, fileName: o.documents.po.file || `${o.documents.po.number}.pdf`, type: 'Purchase Order', orderNumber: o.orderNumber, clientName: o.clientName, date: o.documents.po.date, status: o.documents.po.status });
    }
    if (o.documents?.suratJalan?.exists) {
      list.push({ number: o.documents.suratJalan.number, fileName: o.documents.suratJalan.file || `${o.documents.suratJalan.number}.pdf`, type: 'Surat Jalan', orderNumber: o.orderNumber, clientName: o.clientName, date: o.documents.suratJalan.date, status: o.documents.suratJalan.status });
    }
    if (o.documents?.bast?.exists) {
      list.push({ number: o.documents.bast.number, fileName: o.documents.bast.file || `${o.documents.bast.number}.pdf`, type: 'BAST', orderNumber: o.orderNumber, clientName: o.clientName, date: o.documents.bast.date, status: o.documents.bast.status });
    }
    if (o.documents?.faktur?.exists) {
      list.push({ number: o.documents.faktur.number, fileName: o.documents.faktur.file || `${o.documents.faktur.number}.pdf`, type: 'Faktur Pajak', orderNumber: o.orderNumber, clientName: o.clientName, date: o.documents.faktur.date, status: o.documents.faktur.status });
    }
  });
  return list;
});

const previewOrderDocs = (order) => {
  previewDoc.value = {
    number: `BERKAS-${order.orderNumber}`,
    fileName: `BUNDEL_DOKUMEN_${order.orderNumber}.pdf`,
    type: 'Bundel Dokumen Proyek',
  };
  previewModalOpen.value = true;
};

const previewFile = (doc) => {
  previewDoc.value = doc;
  previewModalOpen.value = true;
};

const downloadFile = (doc) => {
  toast.success(`Berkas ${doc.fileName} berhasil diunduh.`);
};

// Download blank template
const downloadBlankTemplate = (tpl) => {
  window.open(`/api/document-templates/${tpl.id}/download-blank`, '_blank');
  toast.success(`Mengunduh template kosong: ${tpl.name}`);
};

// View placeholder tags
const viewTemplateVariables = (tpl) => {
  selectedTemplateForVars.value = tpl;
  variablesModalOpen.value = true;
};

// Open generate modal
const openGenerateModal = (order = null, template = null) => {
  if (order) {
    generateForm.value.orderNumber = order.orderNumber;
    generateForm.value.variables.nama_sekolah = order.clientName;
    generateForm.value.variables.nilai_kontrak = `Rp ${(order.totalAmount || 0).toLocaleString('id-ID')}`;
  } else if (!generateForm.value.orderNumber && store.orders.length) {
    generateForm.value.orderNumber = store.orders[0].orderNumber;
    generateForm.value.variables.nama_sekolah = store.orders[0].clientName;
  }

  if (template) {
    generateForm.value.templateId = template.id;
  } else if (!generateForm.value.templateId) {
    generateForm.value.templateId = templateList.value[0]?.id || 1;
  }

  generateModalOpen.value = true;
};

const onOrderChange = () => {
  const ord = store.orders.find(o => o.orderNumber === generateForm.value.orderNumber);
  if (ord) {
    generateForm.value.variables.nama_sekolah = ord.clientName;
    generateForm.value.variables.nilai_kontrak = `Rp ${(ord.totalAmount || 0).toLocaleString('id-ID')}`;
  }
};

const handleGenerateDoc = async () => {
  isGenerating.value = true;
  try {
    const res = await api.post('/documents/generate', {
      template_id: generateForm.value.templateId,
      order_number: generateForm.value.orderNumber,
      doc_number: generateForm.value.docNumber || `DOC-${Date.now()}`,
      variables: generateForm.value.variables,
    });

    toast.success('Surat berhasil dibuat secara otomatis!');
    generateModalOpen.value = false;

    // Trigger download if download_url returned
    if (res.data?.data?.id) {
      window.open(`/api/documents/${res.data.data.id}/download`, '_blank');
    }
  } catch (err) {
    toast.error('Gagal generate dokumen: ' + (err.response?.data?.message || err.message));
  } finally {
    isGenerating.value = false;
  }
};

const openUploadModal = () => {
  uploadModalOpen.value = true;
};

const handleUpload = () => {
  toast.success('Berkas scan berhasil diunggah ke arsip sistem.');
  uploadModalOpen.value = false;
};
</script>
