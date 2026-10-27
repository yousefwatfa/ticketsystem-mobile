@extends('layouts.auth')
@section('content')
<form class="form w-100" novalidate="novalidate" method="POST" action="{{ route('login') }}">
    @csrf
    <div class="text-center mb-10">
        <h1 class="text-dark mb-3">تسجيل الدخول</h1>
        {{-- <div class="text-gray-400 fw-bold fs-4">جديد هنا؟
            <a href="{{ route('register') }}" class="link-primary fw-bolder">إنشاء حساب</a>
        </div> --}}
    </div>

    <div class="fv-row mb-10">
        <label class="form-label fs-6 fw-bolder text-dark">اسم المستخدم</label>
        <input class="form-control form-control-lg form-control-solid" type="text" name="username" autocomplete="off" required />
        @error('username') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="fv-row mb-10">
        <div class="d-flex flex-stack mb-2">
            <label class="form-label fw-bolder text-dark fs-6 mb-0">كلمة المرور</label>
        </div>
        <input class="form-control form-control-lg form-control-solid" type="password" name="password" autocomplete="off" required />
    </div>

    <div class="text-center">
        <button type="submit" class="btn btn-lg btn-primary w-100 mb-5">
            <span class="indicator-label">دخول</span>
        </button>
    </div>
</form>
<div class="mt-4 p-3 border border-primary rounded-3 d-flex flex-column align-items-start gap-2" style="background-color: #f0f7ff;">

    <div class="d-flex align-items-center border-bottom pb-2 mb-1 w-100 border-primary-subtle">
        <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#0d6efd" class="me-2">
            <path d="M440-280h80v-240h-80v240Zm40-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
        </svg>
        <strong class="text-primary mb-0" style="font-size: 1rem;">ملاحظة هامة</strong>
    </div>

    <p class="mb-0 text-primary-emphasis lh-base">
        يرجى استخدام <strong class="text-primary">يوزر الإنترنت</strong> الخاص بك لتتمكن من تسجيل الدخول للنظام بنجاح.
    </p>
</div>

@endsection
