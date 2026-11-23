import { existsSync, readFileSync, unlinkSync, readdirSync, rmdirSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export default function teardown() {
  const hotFile = fileURLToPath(new URL('../../../backend/public/hot', import.meta.url));
  if (existsSync(hotFile) && readFileSync(hotFile, 'utf8').includes(':5175')) unlinkSync(hotFile);
  const scratch = fileURLToPath(new URL('../../.ui-review/inertia-ui/', import.meta.url));
  const pointer = path.join(scratch, 'mailbox-directory');
  if (!existsSync(pointer)) return;
  const name = readFileSync(pointer, 'utf8');
  if (!/^mail-[a-zA-Z0-9]+$/.test(name)) throw new Error('Unknown test mailbox directory');
  const directory = path.join(scratch, name);
  for (const file of readdirSync(directory)) {
    if (/^caps26-(desktop|mobile)@example\.test-\d+\.eml$/.test(file)) unlinkSync(path.join(directory, file));
  }
  rmdirSync(directory);
  unlinkSync(pointer);
}
