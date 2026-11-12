import { reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

// Existing forms keep their URL/query interface; all visits are handled by Inertia.
export function useRoute() {
  const page = usePage();
  const url = () => new URL(page.url, window.location.origin);
  return reactive({
    get path() { return url().pathname; },
    get fullPath() { return page.url; },
    get query() { return Object.fromEntries(url().searchParams); },
    get params() { return page.props.navigation?.params || {}; },
    get meta() { return page.props.navigation || {}; },
    get name() { return page.props.navigation?.name; },
  });
}

export function useRouter() {
  const route = useRoute();
  const visit = (target, replace = false) => {
    const location = typeof target === 'string' ? { path: target } : target;
    const url = new URL(location.path || route.path, window.location.origin);
    if (url.origin !== window.location.origin) throw new Error('Navigasi harus menggunakan alamat aplikasi.');
    if (location.query) {
      url.search = '';
      for (const [key, value] of Object.entries(location.query)) {
        if (value == null) continue;
        for (const item of Array.isArray(value) ? value : [value]) url.searchParams.append(key, item);
      }
    }
    const samePage = url.pathname === route.path;
    router.visit(url.pathname + url.search + url.hash, {
      replace: replace || Boolean(location.replace),
      preserveState: samePage,
      preserveScroll: samePage,
    });
  };
  return { push: target => visit(target), replace: target => visit(target, true) };
}
