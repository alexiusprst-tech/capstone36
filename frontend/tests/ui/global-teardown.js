import { existsSync, readFileSync, unlinkSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

export default function teardown() {
  const hotFile = fileURLToPath(new URL('../../../backend/public/hot', import.meta.url));
  if (existsSync(hotFile) && readFileSync(hotFile, 'utf8').includes(':5175')) unlinkSync(hotFile);
}
