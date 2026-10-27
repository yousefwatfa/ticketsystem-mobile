/**
 * Web push subscription UI (profile page).
 * iOS only allows push from an installed PWA (Add to Home Screen, iOS 16.4+),
 * and every browser requires the permission request to come from a user tap.
 */
function urlBase64ToUint8Array(base64) {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function send(method, body) {
    const response = await fetch('/push-subscriptions', {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify(body),
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
}

document.addEventListener('DOMContentLoaded', async () => {
    const status = document.getElementById('push-status');
    const enableBtn = document.getElementById('push-enable-btn');
    const disableBtn = document.getElementById('push-disable-btn');
    if (!status || !enableBtn || !disableBtn) return;

    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
    const show = (text, mode) => {
        status.textContent = text;
        enableBtn.classList.toggle('d-none', mode !== 'off');
        disableBtn.classList.toggle('d-none', mode !== 'on');
    };

    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        show(isIos && !isStandalone
            ? 'على الآيفون: ثبّت التطبيق على الشاشة الرئيسية أولاً ثم افتحه من هناك لتفعيل الإشعارات.'
            : 'هذا المتصفح لا يدعم الإشعارات.', 'none');
        return;
    }
    if (!vapidKey) {
        show('الإشعارات غير مفعّلة على الخادم بعد.', 'none');
        return;
    }
    if (Notification.permission === 'denied') {
        show('تم حظر الإشعارات لهذا الموقع. فعّلها من إعدادات المتصفح.', 'none');
        return;
    }

    const registration = await navigator.serviceWorker.ready;
    let subscription = await registration.pushManager.getSubscription();
    show(subscription ? 'الإشعارات مفعّلة على هذا الجهاز.' : 'الإشعارات غير مفعّلة على هذا الجهاز.', subscription ? 'on' : 'off');

    enableBtn.addEventListener('click', async () => {
        enableBtn.disabled = true;
        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                show('لم يتم السماح بالإشعارات.', permission === 'denied' ? 'none' : 'off');
                return;
            }
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            });
            const json = subscription.toJSON();
            await send('POST', {
                endpoint: json.endpoint,
                keys: json.keys,
                contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
            });
            show('الإشعارات مفعّلة على هذا الجهاز.', 'on');
        } catch (error) {
            console.error(error);
            show('تعذر تفعيل الإشعارات. تأكد من الاتصال بشبكة المستشفى وحاول مجدداً.', 'off');
        } finally {
            enableBtn.disabled = false;
        }
    });

    disableBtn.addEventListener('click', async () => {
        disableBtn.disabled = true;
        try {
            if (subscription) {
                await send('DELETE', { endpoint: subscription.endpoint });
                await subscription.unsubscribe();
                subscription = null;
            }
            show('الإشعارات غير مفعّلة على هذا الجهاز.', 'off');
        } catch (error) {
            console.error(error);
            show('تعذر إيقاف الإشعارات. حاول مجدداً.', 'on');
        } finally {
            disableBtn.disabled = false;
        }
    });
});
