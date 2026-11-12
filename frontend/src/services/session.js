import { router } from '@inertiajs/vue3';
import api from './api';
import { storeActions } from '../stores/mockData';

export async function login(credentials) {
  const { data } = await api.post('/login', credentials, { baseURL: '' });
  localStorage.setItem('token', data.token);
  storeActions.setAuthenticatedUser(data.user);
  return data.user;
}

export async function logout() {
  await api.post('/logout', {}, { baseURL: '' });
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  router.clearHistory();
  router.visit('/login', { replace: true });
}
