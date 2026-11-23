export const SHIPPING_STATUS = Object.freeze({
  PACKING: 'Sedang Dipersiapkan & Dikemas (Packing)',
  DISPATCHED: 'Kurir Mengambil Barang (Dispatched)',
  OUT_FOR_DELIVERY: 'Menuju Alamat Gudang CV Alpharo (Out for Delivery)',
  ARRIVED: 'Armada Tiba di Area Bongkar Muat Gudang',
  DELAYED: 'Penundaan Pengiriman / Kendala Rute (Delayed)',
  REPLACEMENT: 'Barang Pengganti Sedang Dikirim (Retur)',
});

export const SHIPPING_STATUS_OPTIONS = Object.freeze([
  { value: SHIPPING_STATUS.PACKING, label: '📦 Sedang Dipersiapkan & Dikemas' },
  { value: SHIPPING_STATUS.DISPATCHED, label: '🚚 Kurir Mengambil Barang (Dispatched)' },
  { value: SHIPPING_STATUS.OUT_FOR_DELIVERY, label: '📍 Menuju Alamat Gudang (Out for Delivery)' },
  { value: SHIPPING_STATUS.ARRIVED, label: '🏁 Armada Tiba di Area Bongkar Muat' },
  { value: SHIPPING_STATUS.DELAYED, label: '⚠️ Penundaan Pengiriman / Kendala Rute' },
  { value: SHIPPING_STATUS.REPLACEMENT, label: '🚚 Barang Pengganti Sedang Dikirim (Retur)' },
]);

/**
 * Opsi status pengiriman khusus untuk Supplier di Portal Supplier.
 * Status kedatangan / tiba di gudang ("SHIPPING_STATUS.ARRIVED") dihilangkan dari supplier
 * karena konfirmasi fisik kedatangan barang sepenuhnya merupakan wewenang Tim Gudang.
 * Status transit antar kota juga ditiadakan agar alur pengiriman lebih ringkas & to-the-point.
 */
export const SUPPLIER_SHIPPING_STATUS_OPTIONS = Object.freeze([
  { value: SHIPPING_STATUS.PACKING, label: '📦 Sedang Dipersiapkan & Dikemas' },
  { value: SHIPPING_STATUS.DISPATCHED, label: '🚚 Kurir Mengambil Barang (Dispatched)' },
  { value: SHIPPING_STATUS.OUT_FOR_DELIVERY, label: '📍 Menuju Alamat Gudang (Out for Delivery)' },
  { value: SHIPPING_STATUS.DELAYED, label: '⚠️ Penundaan Pengiriman / Kendala Rute' },
  { value: SHIPPING_STATUS.REPLACEMENT, label: '🚚 Barang Pengganti Sedang Dikirim (Retur)' },
]);

export function normalizeShippingStatus(status) {
  if (!status) return SHIPPING_STATUS.DISPATCHED;
  if (status === 'Kurir Menjemput Paket (Dispatched)') return SHIPPING_STATUS.DISPATCHED;
  if (status === 'Dalam Perjalanan Transit Antar Kota' || status === 'Dalam Perjalanan Transit Antar Kota (In Transit)' || String(status).includes('Transit Antar Kota')) {
    return SHIPPING_STATUS.DISPATCHED;
  }
  return status;
}

/** Use the same icon and wording as the Supplier dropdown without changing stored values. */
export function presentShippingStatus(status, fallback = 'Belum tersedia') {
  const text = String(status ?? '').trim();
  if (!text) return { icon: 'ℹ️', label: fallback };
  const normalized = normalizeShippingStatus(text);
  const option = SHIPPING_STATUS_OPTIONS.find(item => item.value === normalized || item.label === text);
  if (!option) return { icon: 'ℹ️', label: text };
  const separator = option.label.indexOf(' ');
  return { icon: option.label.slice(0, separator), label: option.label.slice(separator + 1) };
}
