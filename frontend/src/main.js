import { createApp, h } from 'vue';
import { createInertiaApp, Link } from '@inertiajs/vue3';
import RootLayout from './App.vue';
import './style.css';
import './services/realtime';
import { initEcho } from './services/echo';

const pages = import.meta.glob('./views/**/*.vue');

createInertiaApp({
  title: title => `${title || 'Sistem Informasi Terintegrasi'} ? CV Alpharo Artha Jaya`,
  resolve: async name => {
    const load = pages[`./views/${name}.vue`];
    if (!load) throw new Error(`Halaman tidak ditemukan: ${name}`);
    const page = await load();
    page.default.layout = RootLayout;
    return page;
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .component('Link', Link)
      .mount(el);
  },
  progress: { color: '#2563eb' },
});

initEcho();
