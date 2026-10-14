<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-6 animate-in fade-in duration-150"
    @click.self="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-6xl overflow-hidden my-auto animate-in zoom-in-95 duration-150"
    >
      <!-- MODAL HEADER (SESUAI GAMBAR) -->
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
        <div class="flex items-center gap-2 text-slate-800">
          <UserPlus class="w-5 h-5 text-slate-700" />
          <h2 class="text-base font-bold text-slate-900 tracking-tight">Tambah Customer</h2>
        </div>
        <button
          type="button"
          class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
          title="Tutup"
          @click="$emit('close')"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- MODAL BODY: 3-COLUMN CARDS GRID (SESUAI GAMBAR) -->
      <form class="p-6 space-y-6" @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-start">
          
          <!-- KOLOM 1: IDENTITAS CUSTOMER -->
          <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-[#0B4DA2]">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2 bg-slate-50/50">
              <User class="w-4 h-4 text-slate-700" />
              <h3 class="font-bold text-slate-800 text-xs sm:text-sm">Identitas Customer</h3>
            </div>
            
            <div class="p-4 space-y-3.5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                  Nama Customer <span class="text-rose-500">*</span>
                </label>
                <input
                  ref="namaInput"
                  v-model="form.name"
                  type="text"
                  required
                  placeholder="Contoh: SMAN 9 JAKARTA"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-[#0B4DA2] text-slate-800"
                  @input="onNameInput"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Organisasi Lengkap</label>
                <input
                  v-model="form.orgFullName"
                  type="text"
                  placeholder="Contoh: SMA Negeri 9 Jakarta"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-[#0B4DA2] text-slate-800"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Organisasi Singkat</label>
                <input
                  v-model="form.orgShortName"
                  type="text"
                  placeholder="Contoh: SMAN 9"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-[#0B4DA2] text-slate-800"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                  Alamat <span class="text-rose-500">*</span>
                </label>
                <textarea
                  v-model="form.address"
                  rows="3"
                  required
                  placeholder="Alamat lengkap instansi..."
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-[#0B4DA2] text-slate-800 resize-y"
                  @input="syncLetterheadAddress"
                ></textarea>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Kop Surat</label>
                <textarea
                  v-model="form.letterheadAddress"
                  rows="3"
                  placeholder="Alamat resmi untuk kop surat..."
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-[#0B4DA2] text-slate-800 resize-y"
                ></textarea>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">NPWP</label>
                <input
                  v-model="form.npwp"
                  type="text"
                  placeholder="01.234.567.8-001.000"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-[#0B4DA2] text-slate-800 font-mono"
                />
              </div>
            </div>
          </div>

          <!-- KOLOM 2: WILAYAH -->
          <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-[#0B4DA2]">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2 bg-slate-50/50">
              <MapPin class="w-4 h-4 text-slate-700" />
              <h3 class="font-bold text-slate-800 text-xs sm:text-sm">Wilayah</h3>
            </div>

            <div class="p-4 space-y-3.5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                  Provinsi <span class="text-rose-500">*</span>
                </label>
                <input
                  :value="form.province || 'DKI JAKARTA'"
                  type="text"
                  readonly
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-700 font-medium cursor-not-allowed select-none focus:outline-hidden"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                  Kota <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="form.city"
                  required
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-[#0B4DA2]"
                  @change="onCityChange"
                >
                  <option value="" disabled>Pilih kota</option>
                  <option v-for="c in cityOptions" :key="c" :value="c">{{ c }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                  Kecamatan <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="form.district"
                  required
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-[#0B4DA2]"
                  @change="onDistrictChange"
                >
                  <option value="" disabled>Pilih kecamatan</option>
                  <option v-for="d in districtOptions" :key="d" :value="d">{{ d }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                  Kelurahan <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="form.subdistrict"
                  required
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-[#0B4DA2]"
                  @change="onSubdistrictChange"
                >
                  <option value="" disabled>Pilih kelurahan</option>
                  <option v-for="sd in subdistrictOptions" :key="sd" :value="sd">{{ sd }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                  Kode Pos <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="form.postalCode"
                  type="text"
                  required
                  placeholder="Auto generate dari kelurahan"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-800 font-mono font-semibold focus:outline-hidden"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kota Kop Surat</label>
                <input
                  v-model="form.letterheadCity"
                  type="text"
                  placeholder="Contoh: Jakarta Timur"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-[#0B4DA2] text-slate-800"
                />
              </div>
            </div>
          </div>

          <!-- KOLOM 3: KONTAK -->
          <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-emerald-600">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2 bg-slate-50/50">
              <Phone class="w-4 h-4 text-slate-700" />
              <h3 class="font-bold text-slate-800 text-xs sm:text-sm">Kontak</h3>
            </div>

            <div class="p-4 space-y-3.5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                  Contact Person <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="form.contactPerson"
                  type="text"
                  required
                  placeholder="Contoh: Bpk. Supri / Bu Nis Kepsek"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                  No HP <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="form.phone"
                  type="text"
                  required
                  placeholder="Contoh: 081316673264"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800 font-mono"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Telepon Organisasi</label>
                <input
                  v-model="form.orgPhone"
                  type="text"
                  placeholder="Contoh: 021-8091234"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800 font-mono"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Faksimile</label>
                <input
                  v-model="form.fax"
                  type="text"
                  placeholder="Contoh: 021-8091235"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800 font-mono"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Website</label>
                <input
                  v-model="form.website"
                  type="text"
                  placeholder="https://sman9jkt.sch.id"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="info@sman9jkt.sch.id"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- ACTION BUTTONS: BATAL & SIMPAN (SESUAI GAMBAR) -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
          <button
            type="button"
            class="px-5 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-2xs transition-colors"
            @click="$emit('close')"
          >
            Batal
          </button>
          
          <button
            type="submit"
            class="px-5 py-2 text-xs font-bold text-white bg-[#059669] hover:bg-emerald-700 rounded-lg shadow-xs transition-all flex items-center gap-1.5"
          >
            <CheckSquare class="w-4 h-4" />
            <span>Simpan</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { UserPlus, User, MapPin, Phone, X, CheckSquare } from 'lucide-vue-next';
import { storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { KOTA_OPTIONS, getKecamatanList, getKelurahanList, getPostalCode } from '../../data/wilayahDki';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  initialData: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['close', 'saved']);
const toast = useToast();
const namaInput = ref(null);

const form = ref({
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

watch(
  () => props.show,
  (val) => {
    if (val) {
      if (props.initialData && Object.keys(props.initialData).length > 0) {
        form.value = { ...form.value, ...props.initialData };
      } else {
        resetForm();
      }
      nextTick(() => {
        namaInput.value?.focus();
      });
    }
  }
);

const resetForm = () => {
  form.value = {
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
  };
};

const onNameInput = () => {
  if (!form.value.orgFullName) {
    form.value.orgFullName = form.value.name;
  }
  if (!form.value.orgShortName && form.value.name.length <= 15) {
    form.value.orgShortName = form.value.name;
  }
};

const syncLetterheadAddress = () => {
  if (!form.value.letterheadAddress) {
    form.value.letterheadAddress = form.value.address;
  }
};

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

const handleSubmit = () => {
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

  const newClient = storeActions.addClient({
    ...form.value,
  });

  toast.success(`Customer ${newClient.name} (${newClient.code}) berhasil disimpan!`);
  emit('saved', newClient);
  emit('close');
};
</script>
