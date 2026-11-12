import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { handleIncomingNotification } from './realtime';

window.Pusher = Pusher;

let echoInstance = null;

export function initEcho() {
  if (echoInstance) return echoInstance;

  // Hanya inisialisasi WebSocket jika server Reverb diaktifkan secara eksplisit.
  // Jika tidak, sistem otomatis menggunakan engine native BroadcastChannel (realtime.js)
  // yang bekerja instan antar-tab tanpa perlu background server dan tanpa error koneksi di console.
  const isReverbEnabled =
    import.meta.env.VITE_ENABLE_REVERB === 'true' ||
    import.meta.env.VITE_REVERB_ENABLED === 'true';

  if (!isReverbEnabled) {
    return null;
  }

  try {
    const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
    const reverbPort = import.meta.env.VITE_REVERB_PORT || 8080;
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || 'reverb-capstone-key';

    echoInstance = new Echo({
      broadcaster: 'reverb',
      key: reverbKey,
      wsHost: reverbHost,
      wsPort: reverbPort,
      wssPort: reverbPort,
      forceTLS: false,
      enabledTransports: ['ws', 'wss'],
    });

    // Listen on public notifications channel
    echoInstance.channel('alpharo-notifications')
      .listen('.notification.created', (event) => {
        if (event && event.notification) {
          handleIncomingNotification(event.notification, false);
        }
      });

    console.info('✓ Laravel Echo Reverb listener registered');
  } catch (err) {
    console.debug('Laravel Echo running in offline/BroadcastChannel mode:', err);
  }

  return echoInstance;
}

export default { initEcho };

