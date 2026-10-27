@extends('layouts.app')

@section('content')

<div class="container py-5">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">



        <div class="bg-primary bg-opacity-10 px-4 d-flex align-items-center rounded-3" style="height: 100px; border-right: 5px solid #4b86e6;">
            <div class="d-flex justify-content-between align-items-center w-100">
                <h3 class="fw-bolder mb-0 text-primary">البيانات الشخصية</h3>
                <div class="text-primary fw-bold fs-5">
                    <i class="bi bi-geo-alt-fill me-1 text-primary"></i>
                    @php
                        $officeLocationRaw = $user->office_location ?? auth()->user()->office_location ?? session('current_hospital_name');
                        $officeLocationLabel = \App\Support\OfficeLocationTranslator::toArabic($officeLocationRaw);
                    @endphp
                    {{ $officeLocationLabel }}
                </div>
            </div>
        </div>

        <div class="card-body mt-10 p-4 p-md-10">
            <div class="row g-8 mb-8">
                <div class="col-md-12">
                    <label class="form-label fw-bolder text-gray-800 fs-6">الاسم الكامل</label>
                    <div class="input-group input-group-solid">
                        <span class="input-group-text bg-light rounded-l-none! rounded-r-lg! border-0">
                            <i class="bi bi-person fs-4"></i>
                        </span>
                        <input type="text" class="form-control form-control-solid border-0" value="{{ auth()->user()->fullname }}" readonly>
                        <span class="input-group-text bg-light rounded-l-lg! rounded-r-none! border-0" data-bs-toggle="tooltip" title="لا يمكن تعديل الاسم">
                            <i class="bi bi-lock-fill text-muted"></i>
                        </span>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bolder text-gray-800 fs-6">البريد الإلكتروني</label>
                    <div class="input-group input-group-solid">
                        <span class="input-group-text bg-light rounded-l-none! rounded-r-lg! border-0">
                            <i class="bi bi-envelope fs-4"></i>
                        </span>
                        <input type="email" class="form-control form-control-solid border-0" value="{{ auth()->user()->email }}" readonly>
                        <span class="input-group-text bg-light rounded-l-lg! rounded-r-none! border-0" data-bs-toggle="tooltip" title="لا يمكن تعديل البريد">
                            <i class="bi bi-lock-fill text-muted"></i>
                        </span>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bolder text-gray-800 fs-6">المسمى الوظيفي</label>
                    <div class="input-group input-group-solid">

                        <input type="email" class="form-control form-control-solid rounded-r-lg! rounded-l-none! border-0" value="{{ auth()->user()->classification_name ?? 'لم يتم التحديد' }}" readonly>
                        <span class="input-group-text bg-light rounded-l-lg! rounded-r-none! border-0" data-bs-toggle="tooltip" title="لا يمكن تعديل المسمى الوظيفي">
                            <i class="bi bi-lock-fill text-muted"></i>
                        </span>
                    </div>
                </div>


        <div class="col-md-12">

            <label class="form-label fw-bolder text-gray-800 fs-6">مكان العمل</label>
            <div class="input-group input-group-solid">
                <input type="text" class="form-control form-control-solid rounded-r-lg! rounded-l-none! border-0"
                    value="{{ $officeLocationLabel }}" readonly>
                <span class="input-group-text bg-light rounded-l-lg! rounded-r-none! border-0" data-bs-toggle="tooltip" title="لا يمكن تعديل مكان العمل">
                    <i class="bi bi-lock-fill text-muted"></i>
                </span>
            </div>
        </div>

      <div class="col-md-12">
            <label class="form-label fw-bolder text-gray-800 fs-6">رقم الهوية</label>

            <div class="input-group input-group-solid">
                <input type="text"
                    class="form-control form-control-solid border-0"
                    value="{{ auth()->user()->national_id ?? 'لم يتم التحديد' }}"
                    readonly>

                <span class="input-group-text bg-light border-0"
                    data-bs-toggle="tooltip"
                    title="{{ $nationalIdEdits >= 2 ? 'التعديل محظور' : 'تعديل رقم الهوية' }}">

                    @if($nationalIdEdits < 2)
                        <button type="button"
                                class="btn btn-icon btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#editNationalIdModal">
                            <i class="bi bi-pencil-square text-primary fs-4"></i>
                        </button>
                    @else
                        <button type="button"
                                class="btn btn-icon btn-sm"
                                onclick="preventEditing()">
                            <i class="bi bi-lock-fill text-danger fs-4"></i>
                        </button>
                    @endif
                </span>
            </div>
        </div>
        <div class="col-md-6">
                <label class="form-label fw-bolder text-gray-800 fs-6">رقم الواتساب</label>
                <div class="input-group gap-3">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-whatsapp text-success"></i></span>
                    <input type="text" class="form-control fw-bold text-dark bg-light"
                        value="{{ auth()->user()->whatsapp_number ?? '---' }}" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bolder text-gray-800 fs-6">
                    رقم التيليجرام (Chat ID)
                    <i class="bi bi-info-circle text-muted fs-7" data-bs-toggle="tooltip" title="أرسل أي رسالة للبوت في التيليجرام للحصول على Chat ID الخاص بك"></i>
                </label>
                <div class="input-group gap-3">
                    <a href="https://t.me/nmcsystembot" target="_blank" class="input-group-text bg-light border-0" style="cursor: pointer; text-decoration: none;"><i class="bi bi-telegram text-primary"></i></a>
                    <input type="text" class="form-control fw-bold text-dark bg-light"
                        value="{{ auth()->user()->telegram_number ?? '---' }}" readonly>
                </div>
            </div>

            <div class="col-12 mt-8 d-flex gap-3">
                <button class="btn btn-outline btn-outline-primary fw-bolder flex-grow-1" data-bs-toggle="modal" data-bs-target="#editContactModal">
                    <i class="bi bi-pencil-square me-2"></i> تعديل بيانات التواصل
                </button>
                <form action="{{ route('profile.telegram.test') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info fw-bolder px-4">
                        <i class="bi bi-send me-2"></i> اختبار الإشعار
                    </button>
                </form>
            </div>
            </div>
        </div>

        {{-- Notifications on this device (web push) + Telegram one-tap linking --}}
        <div class="card-body border-top p-6 p-lg-10">
            <h3 class="fw-bolder text-dark fs-4 mb-6">الإشعارات</h3>
            <div class="row g-6">
                <div class="col-md-6">
                    <div class="d-flex align-items-start gap-4 p-5 rounded-3 bg-light h-100">
                        <i class="bi bi-phone-vibrate fs-1 text-primary" aria-hidden="true"></i>
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-6 mb-1">إشعارات هذا الجهاز</div>
                            <div class="text-gray-700 fs-7 mb-4" id="push-status" aria-live="polite">جاري التحقق...</div>
                            <button type="button" class="btn btn-primary btn-sm d-none" id="push-enable-btn">
                                <i class="bi bi-bell me-1" aria-hidden="true"></i> فعّل الإشعارات
                            </button>
                            <button type="button" class="btn btn-light-danger btn-sm d-none" id="push-disable-btn">
                                <i class="bi bi-bell-slash me-1" aria-hidden="true"></i> إيقاف الإشعارات
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="d-flex align-items-start gap-4 p-5 rounded-3 bg-light h-100">
                        <i class="bi bi-telegram fs-1 text-primary" aria-hidden="true"></i>
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-6 mb-1">بوت تيليجرام</div>
                            @if(auth()->user()->telegram_linked_at)
                                <div class="text-gray-700 fs-7 mb-4">الحساب مربوط منذ {{ auth()->user()->telegram_linked_at->diffForHumans() }}. يمكنك فتح التذاكر ومتابعتها من البوت.</div>
                            @else
                                <div class="text-gray-700 fs-7 mb-4">اربط حسابك لفتح التذاكر ومتابعتها من تيليجرام حتى خارج المستشفى.</div>
                            @endif

                            @if(auth()->user()->telegram_link_token)
                                <a href="https://t.me/{{ config('services.telegram-bot-api.username') }}?start={{ auth()->user()->telegram_link_token }}"
                                   target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                                    <i class="bi bi-box-arrow-up-left me-1" aria-hidden="true"></i> افتح البوت لإكمال الربط
                                </a>
                            @else
                                <form action="{{ route('profile.telegram-link.generate') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-light-primary btn-sm">
                                        <i class="bi bi-link-45deg me-1" aria-hidden="true"></i>
                                        {{ auth()->user()->telegram_linked_at ? 'إعادة الربط' : 'ربط الحساب' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-light-dark border-0 p-6 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-35px me-3">
                    <span class="symbol-label bg-white shadow-sm">
                        <i class="bi bi-clock-history text-primary"></i>
                    </span>
                </div>
                <div class="d-flex flex-column">
                    <span class="text-gray-400 fw-bold fs-8">آخر تحديث للملف</span>
                    <span class="text-gray-800 fw-bolder fs-7">{{ auth()->user()->updated_at->format('d M Y - H:i') }}</span>
                </div>
            </div>

        </div>
    </div>
</div>
<x-update-model
    editId="editContactModal"
    title="تعديل بيانات التواصل والعمل"
    action="{{ route('profile.update-phone') }}"
>


    <div class="mb-7">
        <label class="form-label fw-bold">رقم الواتساب</label>
        <input type="text" name="whatsapp_number" class="form-control form-control-solid"
               value="{{ auth()->user()->whatsapp_number }}" placeholder="00970XXXXXXXX">
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold">رقم التيليجرام (Chat ID)</label>
        <input type="text" name="telegram_number" class="form-control form-control-solid"
               value="{{ auth()->user()->telegram_number }}"
               placeholder="مثال: 123456789 أو -100123456789"
               oninput="this.value = this.value.replace(/[^0-9-]/g, '')">
        <div class="form-text text-muted mt-2">
            <i class="bi bi-lightbulb text-warning"></i>
            للحصول على Chat ID: أرسل أي رسالة لبوت النظام في التيليجرام، سيرد لك برقمك الخاص.
        </div>
    </div>
</x-update-model>

<x-update-model
    editId="editNationalIdModal"
    title="تعديل  رقم الهوية"
    action="{{ route('profile.update-national') }}"
>
    <div class="mb-7">
        <label class="form-label fw-bold">رقم الهوية (9 أرقام)</label>
        <input type="text" name="national_id"
               maxlength="9"
               class="form-control form-control-solid @error('national_id') is-invalid @enderror"
               value="{{ old('national_id', auth()->user()->national_id) }}"
               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
        @error('national_id')
            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
        @enderror
    </div>
</x-update-model>



@endsection


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function preventEditing() {
            Swal.fire({
                icon: 'info',
                title: 'تعديل محظور',
                html: 'لقد استنفدت مرات التعديل المسموحة لرقم الهوية .<br><br><b>يرجى مراجعة قسم الـ IT لإتمام أي تغييرات.</b>',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#009ef7',
                customClass: {
                    confirmButton: "btn btn-info fw-bolder px-4"
                }
            });
        }




    </script>
@endpush
