export const STORAGE_KEY = 'alpharo_integrated_system_v3';
export const LEGACY_CUSTOMERS_KEY = 'alpharo_customers_legacy_v1';

export function prepareStorageSnapshot(state) {
  // Never persist large product catalogs, master geographical datasets, or server-managed customer records in localStorage
  const productsToSave = (Array.isArray(state?.products) && state.products.length <= 50)
    ? state.products
    : [];

  return {
    currentUser: state?.currentUser,
    orders: state?.orders || [],
    warehouseRequests: state?.warehouseRequests || [],
    warehouseFulfillments: state?.warehouseFulfillments || [],
    purchases: state?.purchases || [],
    deliveryNotes: state?.deliveryNotes || [],
    shipments: state?.shipments || [],
    suppliers: state?.suppliers || [],
    products: productsToSave,
    stockMovements: Array.isArray(state?.stockMovements) ? state.stockMovements.slice(0, 100) : [],
    notifications: Array.isArray(state?.notifications) ? state.notifications.slice(0, 100) : [],
    activityLogs: Array.isArray(state?.activityLogs) ? state.activityLogs.slice(0, 100) : [],
  };
}

export function saveStateToStorage(state, preserveLegacyStorage = false) {
  if (preserveLegacyStorage) return;
  try {
    const payload = prepareStorageSnapshot(state);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
  } catch (e) {
    if (e?.name === 'QuotaExceededError' || e?.code === 22 || e?.code === 1014) {
      console.warn('Storage quota exceeded, compacting stored data...');
      try {
        const compactPayload = {
          currentUser: state?.currentUser,
          orders: (state?.orders || []).slice(0, 30),
          warehouseRequests: (state?.warehouseRequests || []).slice(0, 30),
          purchases: (state?.purchases || []).slice(0, 30),
          deliveryNotes: (state?.deliveryNotes || []).slice(0, 30),
          shipments: (state?.shipments || []).slice(0, 30),
          suppliers: state?.suppliers || [],
          notifications: (state?.notifications || []).slice(0, 20),
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(compactPayload));
      } catch (compactError) {
        console.warn('Storage compaction also exceeded quota; skipping local storage persistence.', compactError);
      }
    } else {
      console.warn('Storage save warning:', e);
    }
  }
}
