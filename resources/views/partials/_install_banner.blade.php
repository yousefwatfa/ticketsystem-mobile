{{-- "Install the app" banner: shown on phones only when the PWA is not installed yet --}}
<div class="install-banner d-lg-none" id="pwa-install-banner" role="region" aria-label="تثبيت التطبيق" hidden>
    <img src="{{ asset('icons/icon-192.png') }}" alt="" aria-hidden="true" width="40" height="40" class="rounded-3">
    <div class="flex-grow-1">
        <div class="fw-bold">ثبّت التطبيق على هاتفك</div>
        <div class="fs-7 text-gray-700">وصول أسرع وإشعارات فورية بالتذاكر</div>
    </div>
    <button type="button" class="btn btn-sm btn-primary" id="pwa-install-btn">تثبيت</button>
    <button type="button" class="btn btn-sm btn-icon btn-light" id="pwa-install-dismiss" aria-label="إخفاء">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
</div>

@push('scripts')
<script>
(function () {
    const banner = document.getElementById('pwa-install-banner');
    if (!banner) return;

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    let dismissed = false;
    try { dismissed = localStorage.getItem('pwa_install_dismissed') === '1'; } catch (e) {}
    if (isStandalone || dismissed) return;

    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
    let deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredPrompt = event;
        banner.hidden = false;
    });

    // iOS has no install prompt: send users to the instructions page instead
    if (isIos) banner.hidden = false;

    document.getElementById('pwa-install-btn').addEventListener('click', async function () {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
            banner.hidden = true;
        } else {
            window.location.href = @json(route('pwa.install'));
        }
    });

    document.getElementById('pwa-install-dismiss').addEventListener('click', function () {
        banner.hidden = true;
        try { localStorage.setItem('pwa_install_dismissed', '1'); } catch (e) {}
    });
})();
</script>
@endpush
