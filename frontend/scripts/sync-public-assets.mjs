import { cpSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

// Laravel serves these stable URLs independently of the Vite bundle.
cpSync(fileURLToPath(new URL('../public/', import.meta.url)),
  fileURLToPath(new URL('../../backend/public/', import.meta.url)), { recursive: true });
