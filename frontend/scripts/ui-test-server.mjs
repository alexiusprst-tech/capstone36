import { spawn, spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, openSync, readFileSync, unlinkSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const frontend = fileURLToPath(new URL('../', import.meta.url));
const backend = path.resolve(frontend, '../backend');
const scratch = path.join(frontend, '.ui-review/inertia-ui');
mkdirSync(scratch, { recursive: true });
const database = path.join(scratch, 'testing.sqlite');
writeFileSync(database, '');
const env = { ...process.env, APP_ENV: 'testing', APP_URL: 'http://localhost:5174',
  DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '', SESSION_DRIVER: 'database',
  CACHE_STORE: 'array', BROADCAST_CONNECTION: 'null', SANCTUM_STATEFUL_DOMAINS: 'localhost:5174,127.0.0.1:5174' };
for (const args of [['artisan', 'migrate', '--force', '--no-interaction'], [path.join(frontend, 'scripts/seed-ui-users.php')]]) {
  const result = spawnSync('php', args, { cwd: backend, env, stdio: 'inherit', windowsHide: true });
  if (result.status !== 0) process.exit(result.status || 1);
}
const children = [
  ...(process.env.UI_TEST_BUILD === '1' ? [] : [spawn(process.execPath, ['node_modules/vite/bin/vite.js', '--host', '127.0.0.1', '--port', '5175', '--strictPort'], { cwd: frontend, env, stdio: 'inherit', windowsHide: true })]),
  spawn('php', ['-S', '127.0.0.1:5174', path.join(backend, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(backend, 'public'), env, stdio: ['ignore', openSync(path.join(scratch, 'laravel.log'), 'a'), openSync(path.join(scratch, 'laravel.log'), 'a')], windowsHide: true }),
];
let stopping = false;
const stop = () => {
  if (stopping) return;
  stopping = true;
  children.forEach(child => child.kill());
  const hotFile = path.join(backend, 'public/hot');
  if (existsSync(hotFile) && readFileSync(hotFile, 'utf8').includes(':5175')) unlinkSync(hotFile);
};
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
children.forEach(child => child.on('exit', code => { stop(); process.exitCode = code || 0; }));
