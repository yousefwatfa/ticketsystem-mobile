@extends('layouts.auth')

@section('content')
<div class="text-center mb-8">
    <img src="{{ asset('icons/icon-192.png') }}" alt="" aria-hidden="true" width="72" height="72" class="mb-4">
    <h2 class="fw-bolder text-dark mb-2">تثبيت التطبيق على هاتفك</h2>
    <p class="text-gray-700 fs-6 mb-0">يعمل التطبيق عند الاتصال بشبكة الـ Wi-Fi الداخلية للمستشفى.</p>
</div>

<section class="mb-8" aria-labelledby="install-android">
    <h3 id="install-android" class="fw-bold fs-4 mb-4"><i class="bi bi-android2 text-success me-2" aria-hidden="true"></i> أندرويد (Chrome)</h3>
    <ol class="fs-6 lh-lg text-gray-800 ps-0 pe-5">
        <li>افتح النظام من متصفح <b>Chrome</b>.</li>
        <li>اضغط على زر <b>تثبيت</b> الذي يظهر في الصفحة الرئيسية، أو افتح القائمة <b>⋮</b> واختر <b>تثبيت التطبيق</b>.</li>
        <li>سيظهر التطبيق على الشاشة الرئيسية لهاتفك.</li>
    </ol>
</section>

<section class="mb-8" aria-labelledby="install-ios">
    <h3 id="install-ios" class="fw-bold fs-4 mb-4"><i class="bi bi-apple me-2" aria-hidden="true"></i> آيفون (Safari)</h3>
    <ol class="fs-6 lh-lg text-gray-800 ps-0 pe-5">
        <li>افتح النظام من متصفح <b>Safari</b> (يتطلب iOS 16.4 أو أحدث للإشعارات).</li>
        <li>اضغط على زر <b>المشاركة</b> <i class="bi bi-box-arrow-up" aria-hidden="true"></i> أسفل الشاشة.</li>
        <li>اختر <b>إضافة إلى الشاشة الرئيسية</b> ثم <b>إضافة</b>.</li>
        <li>افتح التطبيق من الشاشة الرئيسية، ثم فعّل الإشعارات من صفحة <b>حسابي</b>.</li>
    </ol>
</section>

<a href="{{ route('dashboard') }}" class="btn btn-primary w-100 py-3 fw-bold">العودة إلى النظام</a>
@endsection
