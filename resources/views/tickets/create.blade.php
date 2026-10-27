@extends('layouts.app')
@section('content')

<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title">إنشاء تذكرة جديدة</h3>
    </div>
            @if(session('error'))
                <div class="alert alert-dismissible bg-light-danger d-flex align-items-center p-4 mb-10 border border-danger border-dashed rounded-3 m-5">
                    <div class="d-flex align-items-center justify-content-center bg-danger rounded-circle me-3" style="width: 40px; height: 40px; min-width: 40px;">
                        <i class="bi bi-exclamation-octagon fs-3 text-white"></i>
                    </div>

                    <div class="d-flex flex-column">
                        <h5 class="fw-bold text-danger mb-0 fs-6">خطأ في الإرسال</h5>
                        <span class="text-dark opacity-75 fw-medium">{{ session('error') }}</span>
                    </div>

                </div>
            @endif
     <div class="card-body">
        <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" id="create-ticket-form">
            @csrf

            <input type="hidden" name="client_hostname" id="client-hostname" value="{{ old('client_hostname') }}">
            <input type="hidden" name="client_ip" id="client-ip" value="{{ old('client_ip') }}">
            <input type="hidden" name="client_ips" id="client-ips" value="{{ old('client_ips') }}">

            <div class="mb-10">
                <label class="form-label required fw-bold fs-6">العنوان</label>
                <input name="title" type="text" class="form-control form-control-solid @error('title') is-invalid @enderror" placeholder="أدخل عنوان التذكرة" value="{{ old('title') }}" required/>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-10">
                <label class="form-label required fw-bold fs-6">عنوان القسم</label>
                <input name="office_location" type="text" class="form-control form-control-solid @error('office_location') is-invalid @enderror" placeholder="أدخل  مكان العمل" value="{{ old('office_location') }}" required/>
                @error('office_location')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-10">
                <label class="form-label required fw-bold fs-6">نوع المشكلة</label>
                <select name="category_id" class="form-select form-select-solid @error('category_id') is-invalid @enderror" data-control="select2" data-placeholder="اختر نوع المشكلة" required>
                    <option></option>
                   @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                             {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-10">
                <label class="form-label required fw-bold fs-6">الوصف</label>
                <textarea name="description" class="form-control form-control-solid @error('description') is-invalid @enderror" rows="5" placeholder="اشرح المشكلة بالتفصيل..." required>{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-10">
                <label class="form-label fw-bold fs-6" for="attachments-input">المرفقات (اختياري)</label>

                {{-- Real field submitted to the server; the JS picker fills it with compressed files --}}
                <input id="attachments-input" name="attachments[]" type="file" class="form-control form-control-solid @if($errors->has('attachments.*')) is-invalid @endif" multiple accept=".jpg,.jpeg,.png,.pdf,.docx,.xlsx,.xls" />

                <div class="photo-picker d-none" id="photo-picker">
                    <label class="btn btn-light-primary photo-picker__btn mb-0">
                        <i class="bi bi-camera fs-3" aria-hidden="true"></i> التقاط صورة
                        <input type="file" accept="image/*" capture="environment" class="visually-hidden" id="camera-input">
                    </label>
                    <label class="btn btn-light photo-picker__btn mb-0">
                        <i class="bi bi-paperclip fs-3" aria-hidden="true"></i> اختيار ملفات
                        <input type="file" multiple accept="image/*,.pdf,.docx,.xlsx,.xls" class="visually-hidden" id="files-input">
                    </label>
                </div>
                <div class="photo-previews" id="photo-previews" aria-live="polite"></div>
                <div class="text-danger fs-7 mt-2" id="attachments-error" role="alert" hidden></div>
                <div class="form-text text-muted">يمكنك رفع صور أو ملفات Word/Excel (الحد الأقصى 2MB لكل ملف). يتم تصغير الصور تلقائياً.</div>

                @if($errors->has('attachments.*'))
                    <div class="invalid-feedback d-block">
                        @foreach($errors->get('attachments.*') as $messages)
                            @foreach($messages as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mb-8">
                <div id="local-device-status" class="alert alert-light-info py-3 px-4 mb-0 fs-7">
                    جاري محاولة جلب بيانات الجهاز تلقائياً...
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary w-100 w-md-auto" id="submit-ticket-btn">
                    <span class="indicator-label">إرسال التذكرة</span>
                    <span class="indicator-progress">جاري الإرسال...
                        <span class="spinner-border spinner-border-sm align-middle ms-2" aria-hidden="true"></span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const form = document.getElementById('create-ticket-form');
    const submitBtn = document.getElementById('submit-ticket-btn');
    const realInput = document.getElementById('attachments-input');

    // Enhanced picker (camera + compression) when the browser supports it
    if (window.TicketAttachments && typeof DataTransfer !== 'undefined') {
        realInput.classList.add('d-none');
        document.getElementById('photo-picker').classList.remove('d-none');
        window.TicketAttachments.initAttachmentPicker(form, {
            target: realInput,
            pickers: [document.getElementById('camera-input'), document.getElementById('files-input')],
            previews: document.getElementById('photo-previews'),
            error: document.getElementById('attachments-error'),
        });
    }

    form.addEventListener('submit', function (event) {
        if (event.defaultPrevented) return;
        submitBtn.setAttribute('data-kt-indicator', 'on');
        submitBtn.disabled = true;
    });

    const statusBox = document.getElementById('local-device-status');
    const hostnameInput = document.getElementById('client-hostname');
    const ipInput = document.getElementById('client-ip');
    const ipsInput = document.getElementById('client-ips');

    // Phones/tablets have no local device agent: skip the lookup instead of waiting 3s for a warning
    const isTouchDevice = window.matchMedia('(pointer: coarse)').matches && !window.matchMedia('(hover: hover)').matches;
    if (isTouchDevice) {
        statusBox.parentElement.classList.add('d-none');
        return;
    }

    const endpoint = 'http://127.0.0.1:10000';
    const abortController = new AbortController();
    const timeoutId = setTimeout(() => abortController.abort(), 3000);

    const isValidIp = (value) => {
        if (typeof value !== 'string') {
            return false;
        }

        const candidate = value.trim();
        if (!candidate) {
            return false;
        }

        // Supports IPv4 and most common IPv6 formats.
        const ipv4Pattern = /^(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}$/;
        const ipv6Pattern = /^[0-9a-fA-F:]+$/;
        return ipv4Pattern.test(candidate) || ipv6Pattern.test(candidate);
    };

    try {
        const response = await fetch(endpoint, {
            method: 'GET',
            mode: 'cors',
            cache: 'no-store',
            signal: abortController.signal,
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
            throw new Error('Local endpoint is unavailable');
        }

        const payload = await response.json();
        const hostname = typeof payload.hostname === 'string' ? payload.hostname.trim() : '';

        const ips = Array.isArray(payload.ips)
            ? payload.ips.filter((ip) => typeof ip === 'string' && isValidIp(ip.trim())).map((ip) => ip.trim())
            : [];

        const mainIp = isValidIp(payload.ip) ? payload.ip.trim() : (ips[0] || '');

        hostnameInput.value = hostname;
        ipInput.value = mainIp;
        ipsInput.value = ips.length > 0 ? JSON.stringify(ips) : '';

        statusBox.className = 'alert alert-light-success py-3 px-4 mb-0 fs-7';
        statusBox.textContent = hostname
            ? `تم جلب بيانات الجهاز بنجاح (${hostname} - ${mainIp || 'بدون IP رئيسي'})`
            : 'تم جلب بيانات الجهاز بنجاح.';
    } catch (error) {
        clearTimeout(timeoutId);
        statusBox.className = 'alert alert-light-warning py-3 px-4 mb-0 fs-7';
        statusBox.textContent = 'تعذر قراءة بيانات الجهاز من 127.0.0.1:10000. سيتم الاعتماد على IP القادم من السيرفر.';
    }
});
</script>
@endpush
