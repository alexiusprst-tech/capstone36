const rolePaths = {
  MARKETING: '/marketing/dashboard',
  DIREKTUR: '/director/dashboard',
  ADMINISTRASI: '/administration/dashboard',
  WAREHOUSE: '/warehouse/dashboard',
  FINANCE: '/finance/dashboard',
  CLIENT: '/client/dashboard',
};

export function getRouteAccessDecision({ meta, fullPath, token, currentRole }) {
  if (meta.requiresAuth && !token) {
    return { type: 'redirect', destination: { name: 'login', query: { redirect: fullPath } } };
  }

  if (meta.guestOnly && token) {
    return {
      type: 'redirect',
      destination: rolePaths[currentRole] || rolePaths.MARKETING,
    };
  }

  if (meta.role && currentRole !== meta.role) {
    return { type: 'switch-role', role: meta.role };
  }

  return { type: 'allow' };
}
