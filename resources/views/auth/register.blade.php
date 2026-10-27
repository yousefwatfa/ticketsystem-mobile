@extends('layouts.auth')
@section('content')
<form class="form w-100" novalidate="novalidate" method="POST" action="{{ route('register') }}">
    @csrf
    <div class="text-center mb-10">
        <h1 class="text-dark mb-3">حساب جديد</h1>
        <div class="text-gray-400 fw-bold fs-4">لديك حساب بالفعل؟
            <a href="{{ route('login') }}" class="link-primary fw-bolder">تسجيل الدخول</a>
        </div>
    </div>

    <div class="fv-row mb-7">
        <label class="form-label fw-bolder text-dark fs-6">اسم المستخدم</label>
        <input class="form-control form-control-lg form-control-solid" type="text" name="username" autocomplete="off" required />
        @error('username') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="fv-row mb-7">
        <label class="form-label fw-bolder text-dark fs-6">الاسم الكامل</label>
        <input class="form-control form-control-lg form-control-solid" type="text" name="fullname" autocomplete="off" required />
    </div>

    <div class="fv-row mb-7">
        <label class="form-label fw-bolder text-dark fs-6">البريد الإلكتروني</label>
        <input class="form-control form-control-lg form-control-solid" type="email" name="email" autocomplete="off" required />
        @error('email') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="mb-10 fv-row" data-kt-password-meter="true">
        <div class="mb-1">
            <label class="form-label fw-bolder text-dark fs-6">كلمة المرور</label>
            <div class="position-relative mb-3">
                <input class="form-control form-control-lg form-control-solid" type="password" name="password" autocomplete="off" required />
            </div>
             @error('password') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="fv-row mb-5">
        <label class="form-label fw-bolder text-dark fs-6">تأكيد كلمة المرور</label>
        <input class="form-control form-control-lg form-control-solid" type="password" name="password_confirmation" autocomplete="off" required />
    </div>

    <div class="text-center">
        <button type="submit" class="btn btn-lg btn-primary w-100 mb-5">
            <span class="indicator-label">تسجيل</span>
        </button>
    </div>
</form>
@endsection
