import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Realtime is optional: if Reverb is not configured (or fails to start) the rest of
// the bundle (alerts, select2, attachments, push) must keep working.
if (import.meta.env.VITE_REVERB_APP_KEY) {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
        console.log('Echo initialized for user:', window.userId);
    } catch (error) {
        console.warn('Realtime (Reverb) unavailable:', error);
    }
} else {
    console.warn('Realtime disabled: VITE_REVERB_APP_KEY is not set.');
}
