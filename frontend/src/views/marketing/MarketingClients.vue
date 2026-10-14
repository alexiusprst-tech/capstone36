<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Data Client Rekanan</h1>
          <p class="text-sm text-slate-500 mt-1">
            Database customer sekolah, instansi, dan pemegang kontrak proyek terintegrasi.
          </p>
        </div>
        <router-link
          to="/marketing/clients/create"
          class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg text-sm shadow-xs transition-colors"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Client Baru</span>
        </router-link>
      </div>
    </template>

    <!-- SEARCH & FILTER BAR -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/90 mb-5 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-2xs">
      <div class="relative w-full sm:w-88">
        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari ID, nama customer, alamat, kontak, email..."
          class="w-full pl-9 pr-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800"
        />
      </div>

      <div class="flex items-center gap-3 w-full sm:w-auto">
        <select
          v-model="cityFilter"
          class="text-xs px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600"
        >
          <option value="">Semua Kota</option>
          <option v-for="c in availableCities" :key="c" :value="c">{{ c }}</option>
        </select>

        <button
          v-if="searchQuery || cityFilter"
          type="button"
          class="text-xs text-rose-600 hover:text-rose-700 font-semibold px-2 py-1"
          @click="resetFilters"
        >
          Reset Filter
        </button>
      </div>
    </div>

    <!-- DATA TABLE CONTAINER WITH TOP GREEN ACCENT -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-4 border-t-emerald-600">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-700 font-semibold text-[11px] tracking-normal select-none">
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100/60" @click="sortBy('code')">
                <div class="flex items-center gap-1">
                  <span>ID</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'code' ? (sortAsc ? '▲' : '▼') : '▲' }}
                  </span>
                </div>
              </th>
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100/60" @click="sortBy('name')">
                <div class="flex items-center gap-1">
                  <span>Nama Customer</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'name' ? (sortAsc ? '▲' : '▼') : '⇅' }}
                  </span>
                </div>
              </th>
              <th class="py-3 px-4">
                <span>Alamat</span>
              </th>
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100/60" @click="sortBy('city')">
                <div class="flex items-center gap-1">
                  <span>Kota</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'city' ? (sortAsc ? '▲' : '▼') : '⇅' }}
                  </span>
                </div>
              </th>
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100/60" @click="sortBy('contactPerson')">
                <div class="flex items-center gap-1">
                  <span>Contact Person</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'contactPerson' ? (sortAsc ? '▲' : '▼') : '⇅' }}
                  </span>
                </div>
              </th>
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100/60" @click="sortBy('phone')">
                <div class="flex items-center gap-1">
                  <span>No HP</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'phone' ? (sortAsc ? '▲' : '▼') : '⇅' }}
                  </span>
                </div>
              </th>
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100/60" @click="sortBy('email')">
                <div class="flex items-center gap-1">
                  <span>Email</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'email' ? (sortAsc ? '▲' : '▼') : '⇅' }}
                  </span>
                </div>
              </th>
              <th class="py-3 px-4 text-center">
                <span>Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="client in paginatedClients"
              :key="client.id"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <!-- ID -->
              <td class="py-3.5 px-4 font-bold text-[#1e40af] font-mono whitespace-nowrap">
                {{ client.code }}
              </td>

              <!-- Nama Customer -->
              <td class="py-3.5 px-4 text-slate-800 font-medium whitespace-nowrap">
                {{ client.name }}
              </td>

              <!-- Alamat -->
              <td class="py-3.5 px-4 text-slate-600 max-w-md leading-relaxed text-[11px]">
                {{ client.address }}
              </td>

              <!-- Kota -->
              <td class="py-3.5 px-4 text-slate-700 font-medium uppercase whitespace-nowrap text-xs">
                {{ client.city }}
              </td>

              <!-- Contact Person -->
              <td class="py-3.5 px-4 text-slate-700 whitespace-nowrap text-xs">
                {{ client.contactPerson }}
              </td>

              <!-- No HP -->
              <td class="py-3.5 px-4 text-slate-700 font-mono whitespace-nowrap text-xs">
                {{ client.phone }}
              </td>

              <!-- Email -->
              <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap text-xs">
                {{ client.email }}
              </td>

              <!-- Aksi -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <!-- Yellow/Amber Edit Button -->
                  <button
                    type="button"
                    class="p-1.5 bg-[#eab308] hover:bg-[#ca8a04] text-white rounded shadow-2xs transition-colors"
                    title="Edit Customer"
                    @click="goToEdit(client)"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                  </button>

                  <!-- Red Delete Button -->
                  <button
                    type="button"
                    class="p-1.5 bg-[#ef4444] hover:bg-[#dc2626] text-white rounded shadow-2xs transition-colors"
                    title="Hapus Customer"
                    @click="confirmDelete(client)"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredClients.length === 0">
              <td colspan="8" class="py-12 text-center text-slate-400">
                Tidak ada data customer yang cocok dengan pencarian.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION FOOTER (MATCHING SCREENSHOT 3) -->
      <div class="p-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
        <!-- Left: Menampilkan 1 - 10 dari 16 data -->
        <div>
          <span>
            Menampilkan
            <strong class="font-semibold text-slate-800">{{ totalItems > 0 ? startItemIndex : 0 }}</strong>
            -
            <strong class="font-semibold text-slate-800">{{ endItemIndex }}</strong>
            dari
            <strong class="font-semibold text-slate-800">{{ totalItems }}</strong>
            data
          </span>
        </div>

        <!-- Right: First | Prev | 1 | 2 | Next | Last | [10 data v] -->
        <div class="flex items-center gap-1.5 flex-wrap">
          <button
            type="button"
            :disabled="currentPage === 1"
            class="px-2.5 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium"
            @click="currentPage = 1"
          >
            First
          </button>
          <button
            type="button"
            :disabled="currentPage === 1"
            class="px-2.5 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium"
            @click="currentPage--"
          >
            Prev
          </button>

          <button
            v-for="page in totalPages"
            :key="page"
            type="button"
            :class="[
              'px-3 py-1 rounded font-semibold transition-colors',
              page === currentPage
                ? 'bg-[#1e40af] text-white'
                : 'border border-slate-300 text-slate-700 hover:bg-slate-50',
            ]"
            @click="currentPage = page"
          >
            {{ page }}
          </button>

          <button
            type="button"
            :disabled="currentPage === totalPages || totalPages === 0"
            class="px-2.5 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium"
            @click="currentPage++"
          >
            Next
          </button>
          <button
            type="button"
            :disabled="currentPage === totalPages || totalPages === 0"
            class="px-2.5 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium"
            @click="currentPage = totalPages"
          >
            Last
          </button>

          <!-- Per Page Select Dropdown -->
          <div class="ml-2">
            <select
              v-model="itemsPerPage"
              class="px-2.5 py-1 text-xs rounded border border-slate-300 bg-white text-slate-700 font-medium focus:outline-hidden focus:ring-1 focus:ring-slate-400"
              @change="currentPage = 1"
            >
              <option :value="5">5 data</option>
              <option :value="10">10 data</option>
              <option :value="25">25 data</option>
              <option :value="50">50 data</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- CONFIRM DELETE MODAL -->
    <Modal
      v-model="deleteModalOpen"
      title="Konfirmasi Hapus Customer"
      subtitle="Tindakan ini akan menghapus data client secara permanen"
      size="sm"
    >
      <div v-if="clientToDelete" class="space-y-4">
        <p class="text-xs text-slate-600">
          Apakah Anda yakin ingin menghapus data customer
          <strong class="font-bold text-slate-900">{{ clientToDelete.code }} - {{ clientToDelete.name }}</strong>?
        </p>

        <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-700 flex items-start gap-2">
          <AlertCircle class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
          <span>Data yang dihapus tidak dapat dipulihkan kembali.</span>
        </div>

        <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
          <button
            type="button"
            class="px-3.5 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg"
            @click="deleteModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-xs"
            @click="executeDelete"
          >
            Ya, Hapus Customer
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '../../components/layout/AppLayout.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { Search, Plus, Edit3, Trash2, AlertCircle } from 'lucide-vue-next';

const router = useRouter();
const toast = useToast();

const searchQuery = ref('');
const cityFilter = ref('');

// Sorting
const sortCol = ref('code');
const sortAsc = ref(true);

// Pagination
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Delete Modal
const deleteModalOpen = ref(false);
const clientToDelete = ref(null);

const availableCities = computed(() => {
  const cities = new Set(store.clients.map((c) => c.city).filter(Boolean));
  return Array.from(cities).sort();
});

const sortBy = (col) => {
  if (sortCol.value === col) {
    sortAsc.value = !sortAsc.value;
  } else {
    sortCol.value = col;
    sortAsc.value = true;
  }
};

const resetFilters = () => {
  searchQuery.value = '';
  cityFilter.value = '';
  currentPage.value = 1;
};

const filteredClients = computed(() => {
  let list = store.clients.filter((c) => {
    const q = searchQuery.value.toLowerCase().trim();
    const matchSearch =
      !q ||
      (c.code && c.code.toLowerCase().includes(q)) ||
      (c.name && c.name.toLowerCase().includes(q)) ||
      (c.address && c.address.toLowerCase().includes(q)) ||
      (c.city && c.city.toLowerCase().includes(q)) ||
      (c.contactPerson && c.contactPerson.toLowerCase().includes(q)) ||
      (c.phone && c.phone.toLowerCase().includes(q)) ||
      (c.email && c.email.toLowerCase().includes(q));

    const matchCity = !cityFilter.value || c.city === cityFilter.value;
    return matchSearch && matchCity;
  });

  // Sort
  list.sort((a, b) => {
    let valA = a[sortCol.value] || '';
    let valB = b[sortCol.value] || '';

    if (sortCol.value === 'code') {
      const numA = parseInt(String(valA).replace(/\D/g, ''), 10) || 0;
      const numB = parseInt(String(valB).replace(/\D/g, ''), 10) || 0;
      return sortAsc.value ? numA - numB : numB - numA;
    }

    if (typeof valA === 'string') valA = valA.toLowerCase();
    if (typeof valB === 'string') valB = valB.toLowerCase();

    if (valA < valB) return sortAsc.value ? -1 : 1;
    if (valA > valB) return sortAsc.value ? 1 : -1;
    return 0;
  });

  return list;
});

const totalItems = computed(() => filteredClients.value.length);
const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage.value) || 1);

const startItemIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value + 1);
const endItemIndex = computed(() => Math.min(currentPage.value * itemsPerPage.value, totalItems.value));

const paginatedClients = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return filteredClients.value.slice(start, start + itemsPerPage.value);
});

const goToEdit = (client) => {
  router.push(`/marketing/clients/${client.id}/edit`);
};

const confirmDelete = (client) => {
  clientToDelete.value = client;
  deleteModalOpen.value = true;
};

const executeDelete = () => {
  if (clientToDelete.value) {
    storeActions.deleteClient(clientToDelete.value.id);
    toast.success(`Customer ${clientToDelete.value.code} (${clientToDelete.value.name}) berhasil dihapus.`);
    deleteModalOpen.value = false;
    clientToDelete.value = null;
    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value;
    }
  }
};
</script>
