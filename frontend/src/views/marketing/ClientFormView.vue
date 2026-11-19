<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <Link
              href="/marketing/clients"
              class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors"
            >
              <ArrowLeft class="w-3.5 h-3.5" />
              <span>Kembali ke Data Client</span>
            </Link>
          </div>
          <div class="flex items-center gap-2">
            <UserPlus class="w-6 h-6 text-slate-800" />
            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">
              {{ isEdit ? 'Edit Customer' : 'Tambah Customer' }}
            </h1>
          </div>
          <p class="text-sm text-slate-500 mt-0.5">
            {{ isEdit ? `Perbarui informasi ${form.code} - ${form.name}` : 'Lengkapi identitas customer, data wilayah, dan kontak resmi instansi' }}
          </p>
        </div>
      </div>
    </template>

    <p v-if="clientSync.error" role="alert" class="mb-4 text-sm text-amber-800">{{ clientSync.error }}</p>
    <form class="space-y-6 max-w-7xl mx-auto pb-12" @submit.prevent="handleSubmit">
      <!-- 3-COLUMN CARDS GRID (SESUAI DOKUMEN TANGKAPAN LAYAR) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
        
        <!-- CARD 1: IDENTITAS CUSTOMER -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-slate-200">
          <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2.5 bg-slate-50/50">
            <User class="w-4 h-4 text-slate-700" />
            <h2 class="font-semibold text-slate-800 text-sm">Identitas Customer</h2>
          </div>
          
          <div class="p-5 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-name">
                Nama Customer <span class="text-rose-500">*</span>
              </label>
              <input id="client-name"
                ref="namaInput"
                v-model="form.name"
                type="text"
                required
                placeholder="Contoh: SMAN 9 JAKARTA"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/30 focus:border-alpharo-600 text-slate-800"
                @input="onNameInput"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-orgFullName">Nama Organisasi Lengkap</label>
              <input id="client-orgFullName"
                v-model="form.orgFullName" @input="identitySync.edit('orgFullName')"
                type="text"
                placeholder="Contoh: SMA Negeri 9 Jakarta"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/30 focus:border-alpharo-600 text-slate-800"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-orgShortName">Nama Organisasi Singkat</label>
              <input id="client-orgShortName"
                v-model="form.orgShortName" @input="identitySync.edit('orgShortName')"
                type="text"
                placeholder="Contoh: SMAN 9"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/30 focus:border-alpharo-600 text-slate-800"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-address">
                Alamat <span class="text-rose-500">*</span>
              </label>
              <textarea id="client-address"
                v-model="form.address"
                rows="3"
                required
                placeholder="Alamat lengkap instansi..."
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/30 focus:border-alpharo-600 text-slate-800 resize-y"
                @input="syncLetterheadAddress"
              ></textarea>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-letterheadAddress">Alamat Kop Surat</label>
              <textarea id="client-letterheadAddress"
                v-model="form.letterheadAddress" @input="identitySync.edit('letterheadAddress')"
                rows="3"
                placeholder="Alamat resmi untuk kop surat..."
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/30 focus:border-alpharo-600 text-slate-800 resize-y"
              ></textarea>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-npwp">NPWP</label>
              <input id="client-npwp"
                v-model="form.npwp"
                type="text"
                placeholder="01.234.567.8-001.000"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/30 focus:border-alpharo-600 text-slate-800 font-mono"
              />
            </div>
          </div>
        </div>

        <!-- CARD 2: WILAYAH -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-slate-200">
          <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2.5 bg-slate-50/50">
            <MapPin class="w-4 h-4 text-slate-700" />
            <h2 class="font-semibold text-slate-800 text-sm">Wilayah</h2>
          </div>

          <div class="p-5 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Provinsi <span class="text-rose-500">*</span>
              </label>
              <input
                :value="form.province || 'DKI JAKARTA'"
                type="text"
                readonly
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-700 font-medium cursor-not-allowed select-none focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-city">
                Kota <span class="text-rose-500">*</span>
              </label>
              <select id="client-city"
                v-model="form.city"
                required
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/30 focus:border-alpharo-600"
                @change="onCityChange"
              >
                <option value="" disabled>Pilih kota</option>
                <option v-for="c in cityOptions" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-district">
                Kecamatan <span class="text-rose-500">*</span>
              </label>
              <select id="client-district"
                v-model="form.district"
                required
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/30 focus:border-alpharo-600"
                @change="onDistrictChange"
              >
                <option value="" disabled>Pilih kecamatan</option>
                <option v-for="d in districtOptions" :key="d" :value="d">{{ d }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-subdistrict">
                Kelurahan <span class="text-rose-500">*</span>
              </label>
              <select id="client-subdistrict"
                v-model="form.subdistrict"
                required
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/30 focus:border-alpharo-600"
                @change="onSubdistrictChange"
              >
                <option value="" disabled>Pilih kelurahan</option>
                <option v-for="sd in subdistrictOptions" :key="sd" :value="sd">{{ sd }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-postalCode">
                Kode Pos <span class="text-rose-500">*</span>
              </label>
              <input id="client-postalCode"
                v-model="form.postalCode"
                type="text"
                required
                placeholder="Auto generate dari kelurahan"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-800 font-mono font-semibold focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-letterheadCity">Kota Kop Surat</label>
              <input id="client-letterheadCity"
                v-model="form.letterheadCity"
                type="text"
                placeholder="Contoh: Jakarta Timur"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/30 focus:border-alpharo-600 text-slate-800"
              />
            </div>
          </div>
        </div>

        <!-- CARD 3: KONTAK -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-slate-200">
          <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2.5 bg-slate-50/50">
            <Phone class="w-4 h-4 text-slate-700" />
            <h2 class="font-semibold text-slate-800 text-sm">Kontak</h2>
          </div>

          <div class="p-5 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-contactPerson">
                Contact Person <span class="text-rose-500">*</span>
              </label>
              <input id="client-contactPerson"
                v-model="form.contactPerson"
                type="text"
                required
                placeholder="Contoh: Bpk. Supri / Bu Nis Kepsek"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-400/30 focus:border-alpharo-400 text-slate-800"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-phone">
                No HP <span class="text-rose-500">*</span>
              </label>
              <input id="client-phone"
                v-model="form.phone"
                type="text"
                required
                placeholder="Contoh: 081316673264"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-400/30 focus:border-alpharo-400 text-slate-800 font-mono"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-orgPhone">Telepon Organisasi</label>
              <input id="client-orgPhone"
                v-model="form.orgPhone"
                type="text"
                placeholder="Contoh: 021-8091234"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-400/30 focus:border-alpharo-400 text-slate-800 font-mono"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-fax">Faksimile</label>
              <input id="client-fax"
                v-model="form.fax"
                type="text"
                placeholder="Contoh: 021-8091235"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-400/30 focus:border-alpharo-400 text-slate-800 font-mono"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-website">Website</label>
              <input id="client-website"
                v-model="form.website"
                type="text"
                placeholder="https://sman9jkt.sch.id"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-400/30 focus:border-alpharo-400 text-slate-800"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="client-email">Email</label>
              <input id="client-email"
                v-model="form.email"
                type="email"
                placeholder="info@sman9jkt.sch.id"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-400/30 focus:border-alpharo-400 text-slate-800"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- ACTION BUTTONS: BATAL & SIMPAN (SESUAI GAMBAR) -->
      <div class="flex items-center justify-end gap-3 pt-3">
        <Link
          href="/marketing/clients"
          class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-2xs transition-colors"
        >
          Batal
        </Link>
        
        <button
          type="submit" :disabled="saving || clientSync.loading || (isEdit && !form.version)"
          class="px-6 py-2.5 text-xs font-semibold text-white bg-alpharo-600 hover:bg-alpharo-700 rounded-lg shadow-xs transition-all flex items-center gap-2"
        >
          <CheckSquare class="w-4 h-4" />
          <span>Simpan</span>
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from '../../navigation';
import AppLayout from '../../components/layout/AppLayout.vue';
import { store, storeActions, clientSync } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { customerIdentitySync } from '../../services/customerIdentity';
import { orderError } from '../../services/orderWorkflow';
import { UserPlus, User, MapPin, Phone, ArrowLeft, CheckSquare } from 'lucide-vue-next';
import { KOTA_OPTIONS, getKecamatanList, getKelurahanList, getPostalCode } from '../../data/wilayahDki';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const namaInput = ref(null);

const isEdit = computed(() => Boolean(route.params.id));
const clientId = computed(() => route.params.id);

const form = ref({
  code: '',
  name: '',
  orgFullName: '',
  orgShortName: '',
  address: '',
  npwp: '',
  province: 'DKI JAKARTA',
  city: 'JAKARTA TIMUR',
  district: 'Makasar',
  subdistrict: 'Kebon Pala',
  postalCode: '13650',
  letterheadAddress: '',
  letterheadCity: 'Jakarta Timur',
  contactPerson: '',
  phone: '',
  orgPhone: '',
  fax: '',
  website: '',
  email: '',
  status: 'Aktif',
});

const identitySync = customerIdentitySync(form);
const onNameInput = identitySync.nameChanged;
const syncLetterheadAddress = identitySync.addressChanged;

// Location options (DKI Jakarta Seeder: 5 Kota, 44 Kecamatan, 267 Kelurahan)
const cityOptions = computed(() => KOTA_OPTIONS);

const districtOptions = computed(() => {
  return getKecamatanList(form.value.city);
});

const subdistrictOptions = computed(() => {
  return getKelurahanList(form.value.city, form.value.district);
});

const onCityChange = () => {
  form.value.district = districtOptions.value[0] || 'Makasar';
  form.value.letterheadCity = form.value.city;
  onDistrictChange();
};

const onDistrictChange = () => {
  form.value.subdistrict = subdistrictOptions.value[0] || 'Kebon Pala';
  onSubdistrictChange();
};

const onSubdistrictChange = () => {
  form.value.postalCode = getPostalCode(form.value.subdistrict);
};

onMounted(async () => {
  await storeActions.loadClients();
  if (isEdit.value) {
    const existing = store.clients.find((c) => c.id === Number(clientId.value) || c.code === String(clientId.value));
    if (existing) {
      form.value = { ...form.value, ...existing };
      identitySync.reset(existing);
    }
  }
});

const saving = ref(false);
const handleSubmit = async () => {
  if (saving.value || (isEdit.value && !form.value.version)) return;
  if (!form.value.name.trim()) {
    toast.error('Nama Customer wajib diisi.');
    return;
  }
  if (!form.value.contactPerson.trim()) {
    toast.error('Contact Person wajib diisi.');
    return;
  }
  if (!form.value.phone.trim()) {
    toast.error('Nomor HP wajib diisi.');
    return;
  }

  saving.value = true;
  try {
  if (isEdit.value) {
    await storeActions.updateClient(clientId.value, form.value);
    toast.success(`Data Customer ${form.value.name} berhasil diperbarui!`);
  } else {
    const newClient = await storeActions.addClient(form.value);
    toast.success(`Customer ${newClient.name} (${newClient.code}) berhasil disimpan!`);
  }

  router.push('/marketing/clients');
  } catch (error) { toast.error(orderError(error)); } finally { saving.value = false; }
};
</script>
