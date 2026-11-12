export const INTERNAL_ACTIVITY_ROLES = Object.freeze(['MARKETING', 'DIREKTUR', 'ADMINISTRASI', 'WAREHOUSE', 'FINANCE']);

export const ACTIVITY_ROLE_LABELS = Object.freeze({
  MARKETING: 'Marketing',
  DIREKTUR: 'Direktur',
  ADMINISTRASI: 'Administrasi',
  WAREHOUSE: 'Warehouse',
  FINANCE: 'Finance',
  CLIENT: 'Client',
  SUPPLIER: 'Supplier',
});

function activityTime(log) {
  const timestamp = Date.parse(String(log.timestamp || '').replace(' ', 'T'));
  return Number.isFinite(timestamp) ? timestamp : 0;
}

/** Match the dashboard's division access; the director retains the existing system audit view. */
export function getVisibleActivityLogs(logs, role) {
  if (!INTERNAL_ACTIVITY_ROLES.includes(role) || !Array.isArray(logs)) return [];
  return logs
    .filter(log => log && typeof log === 'object' && (role === 'DIREKTUR' || log.role === role))
    .sort((a, b) => activityTime(b) - activityTime(a));
}
