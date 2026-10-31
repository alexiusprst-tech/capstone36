import assert from 'node:assert/strict';
import { beforeEach, test } from 'node:test';
import { getRouteAccessDecision } from '../src/router/access.js';

class MemoryStorage {
  values = new Map();

  getItem(key) {
    return this.values.get(key) ?? null;
  }

  setItem(key, value) {
    this.values.set(key, String(value));
  }

  removeItem(key) {
    this.values.delete(key);
  }
}

globalThis.localStorage = new MemoryStorage();

const { store, storeActions } = await import('../src/stores/mockData.js');

beforeEach(() => {
  storeActions.resetToDefaults();
  localStorage.removeItem('token');
});

function createMarketingOrder(product, qty) {
  return storeActions.createOrder({
    clientId: 1,
    projectName: 'Uji integrasi persediaan',
    submitDirectly: false,
    items: [{
      productId: product.id,
      sku: product.sku,
      name: product.name,
      qty,
      unit: product.unit,
      price: 1000,
    }],
  });
}

test('Marketing route requires login and allows an authenticated Marketing user', () => {
  const route = {
    meta: { requiresAuth: true, role: 'MARKETING' },
    fullPath: '/marketing/orders',
  };
  assert.deepEqual(getRouteAccessDecision({ ...route, token: null, currentRole: 'MARKETING' }), {
    type: 'redirect',
    destination: { name: 'login', query: { redirect: '/marketing/orders' } },
  });
  assert.deepEqual(getRouteAccessDecision({ ...route, token: 'mock-token-marketing', currentRole: 'MARKETING' }), {
    type: 'allow',
  });
  assert.deepEqual(getRouteAccessDecision({ ...route, token: 'mock-token-finance', currentRole: 'FINANCE' }), {
    type: 'switch-role',
    role: 'MARKETING',
  });
});

test('Marketing order submission, approval, and warehouse fulfillment stay in sync with inventory', () => {
  const product = store.products.find((candidate) => candidate.sku === 'MAT-004');
  product.stock = 85;

  const order = createMarketingOrder(product, 10);
  assert.equal(storeActions.getOrderStockReadiness(order.items).ready, true);
  assert.equal(storeActions.submitOrderForVerification(order.id), true);

  storeActions.switchRole('DIREKTUR');
  assert.equal(storeActions.approveOrder(order.id), true);
  const fulfillment = store.warehouseFulfillments.find((item) => item.orderNumber === order.orderNumber);
  assert.equal(fulfillment.status, 'Diproses');
  assert.equal(fulfillment.items[0].availQty, 85);
  assert.equal(fulfillment.items[0].fulfilledQty, 0);

  storeActions.switchRole('WAREHOUSE');
  assert.equal(storeActions.confirmFulfillment(fulfillment.id), true);
  assert.equal(fulfillment.status, 'Siap Dikirim');
  assert.equal(fulfillment.items[0].fulfilledQty, 10);
  assert.equal(order.fulfillmentStatus, 'Siap Dikirim');
  assert.equal(product.stock, 75);
  assert.equal(store.stockMovements[0].reference, order.orderNumber);
  assert.equal(store.stockMovements[0].qty, 10);

  assert.equal(storeActions.confirmFulfillment(fulfillment.id), false);
  assert.equal(product.stock, 75);
});

test('Marketing cannot submit insufficient stock and approval rechecks changed inventory', () => {
  const product = store.products.find((candidate) => candidate.sku === 'MAT-004');
  product.stock = 5;
  assert.equal(storeActions.createOrder({
    clientId: 1,
    submitDirectly: true,
    items: [{ productId: product.id, sku: product.sku, name: product.name, qty: 10, price: 1000 }],
  }), null);

  product.stock = 85;
  const order = createMarketingOrder(product, 10);
  assert.equal(storeActions.submitOrderForVerification(order.id), true);
  product.stock = 5;

  storeActions.switchRole('DIREKTUR');
  assert.equal(storeActions.approveOrder(order.id), false);
  assert.equal(order.status, 'Menunggu Verifikasi');
  assert.equal(store.warehouseFulfillments.some((item) => item.orderNumber === order.orderNumber), false);
});

test('pending warehouse fulfillment reserves stock against another approval', () => {
  const product = store.products.find((candidate) => candidate.sku === 'MAT-004');
  product.stock = 85;
  const firstOrder = createMarketingOrder(product, 60);
  const secondOrder = createMarketingOrder(product, 30);
  assert.equal(storeActions.submitOrderForVerification(firstOrder.id), true);
  assert.equal(storeActions.submitOrderForVerification(secondOrder.id), true);

  storeActions.switchRole('DIREKTUR');
  assert.equal(storeActions.approveOrder(firstOrder.id), true);
  assert.equal(storeActions.getOrderStockReadiness(secondOrder.items).ready, false);
  assert.equal(storeActions.approveOrder(secondOrder.id), false);
  assert.equal(store.warehouseFulfillments.filter((item) => item.status === 'Diproses' && item.items.some((line) => line.sku === product.sku)).length, 1);
});

test('warehouse cannot confirm fulfillment after available stock drops below the order quantity', () => {
  const product = store.products.find((candidate) => candidate.sku === 'MAT-004');
  product.stock = 85;
  const order = createMarketingOrder(product, 10);
  storeActions.submitOrderForVerification(order.id);
  storeActions.switchRole('DIREKTUR');
  storeActions.approveOrder(order.id);

  const fulfillment = store.warehouseFulfillments.find((item) => item.orderNumber === order.orderNumber);
  product.stock = 5;
  storeActions.switchRole('WAREHOUSE');
  assert.equal(storeActions.confirmFulfillment(fulfillment.id), false);
  assert.equal(fulfillment.status, 'Diproses');
  assert.equal(product.stock, 5);
  assert.equal(order.fulfillmentStatus, 'Diproses');
});
