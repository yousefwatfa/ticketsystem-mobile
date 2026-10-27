@extends('layouts.app')

@section('content')

<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title">إضافة مستخدم جديد</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <div class="mb-10">
                <label class="form-label fw-bold fs-6">الاسم الكامل</label>
                <input type="text" name="fullname" class="form-control form-control-solid" value="{{ old('fullname') }}" required/>
                @error('fullname') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="mb-10">
                <label class="form-label fw-bold fs-6">اسم المستخدم</label>
                <input type="text" name="username" class="form-control form-control-solid" value="{{ old('username') }}" required/>
                @error('username') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="mb-10">
                <label class="form-label fw-bold fs-6">البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control form-control-solid" value="{{ old('email') }}" />
                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

             <div class="mb-10">
                <label class="form-label fw-bold fs-6">نوع المصادقة</label>
                <select name="auth_type" id="auth_type" class="form-select form-select-solid">
                    <option value="ldap" {{ old('auth_type') == 'ldap' ? 'selected' : '' }}>LDAP</option>
                    <option value="normal" {{ old('auth_type') == 'normal' ? 'selected' : '' }}>عادي (Normal)</option>
                </select>
                @error('auth_type') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="mb-10" id="password_wrapper" style="display: none;">
                <label class="form-label fw-bold fs-6">كلمة المرور</label>
                <input type="password" name="password" class="form-control form-control-solid" />
                @error('password') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="mb-10">
                <label class="form-label fw-bold">الدور:</label>
                <select name="role_id" id="role_select" class="form-select form-select-solid" data-control="select2">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" data-name="{{ strtolower($role->name) }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
                @error('role_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
       {{-- لا يظهر هذا الحقل إذا كان المستخدم المطلوب تعديله هو Root --}}
                        @if(!(isset($user) && $user->is_root))
                            <div class="mb-10" id="department_wrapper" style="display: none;">
                                <label class="form-label fw-bold">القسم المسؤول عنه:</label>
                                <select name="departments[]" class="form-select form-select-solid"
                                        data-control="select2"
                                        data-placeholder="اختر الأقسام..."
                                        data-close-on-select="false"
                                        multiple="multiple">
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{
                                                (collect(old('departments'))->contains($dept->id)) ||
                                                (isset($user) && $user->departments->contains($dept->id))
                                                ? 'selected' : ''
                                            }}>
                                            {{ $dept->mainDepartment ? $dept->mainDepartment->name . ' - ' : '' }} {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="text-muted fs-7 mt-2">اختر الأقسام التي سيكون لهذا المستخدم صلاحية الإدارة عليها.</div>
                            </div>
                            @endif
            @if(auth()->user()->is_root)
                <div class="my-10 p-4" id='admin_permissions_wrapper' style="display: none;">
                    <div class="row g-6">
                        <div class="col-md-6">
                            <div class="form-check form-switch d-flex align-items-center">
                                <input class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    name="can_reopen_tickets"
                                    value="1"
                                    style="width: 2.5em; height: 1.25em; cursor: pointer;"
                                    id="reopenSwitch"
                                    {{ old('can_reopen_tickets') ? 'checked' : '' }}>

                                <label class="form-check-label fw-bold text-gray-800 ms-[52px]" for="reopenSwitch" style="cursor: pointer;">
                                    سماح لهذا المستخدم بإعادة فتح التذاكر المغلقة
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch d-flex align-items-center">
                                <input class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    name="can_access_finished_tickets"
                                    value="1"
                                    style="width: 2.5em; height: 1.25em; cursor: pointer;"
                                    id="finishedTicketsSwitch"
                                    {{ old('can_access_finished_tickets') ? 'checked' : '' }}>

                                <label class="form-check-label fw-bold text-gray-800 ms-[52px]" for="finishedTicketsSwitch" style="cursor: pointer;">
                                    سماح لهذا المستخدم بعرض صفحة المهمة المنتهية
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="d-flex justify-content-end">
                <a href="{{ route('admin.users.index') }}" class="btn btn-light me-3">إلغاء</a>
                <button type="submit" class="btn btn-primary">
                    <span class="indicator-label">إضافة المستخدم</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const authTypeSelect = $('#auth_type');
        const passwordWrapper = $('#password_wrapper');
        const roleSelect = $('#role_select');
        const departmentWrapper = $('#department_wrapper');
        const adminPermissionsWrapper = $('#admin_permissions_wrapper');

        function togglePassword() {
            if (authTypeSelect.val() === 'normal') {
                passwordWrapper.stop().slideDown();
            } else {
                passwordWrapper.stop().slideUp();
            }
        }

        function toggleRoleFeatures() {
            const selectedOption = roleSelect.find('option:selected');
            const roleName = $.trim(selectedOption.data('name')).toLowerCase();

            console.log("الرتبة المختارة:", roleName);

            if (roleName === 'admin' || roleName === 'viewer') {
                departmentWrapper.stop().slideDown().css('display', 'block');
            } else {
                departmentWrapper.stop().slideUp();
            }

            if (adminPermissionsWrapper.length > 0) {
                if (roleName === 'admin') {
                    adminPermissionsWrapper.stop().slideDown().css('display', 'block');
                } else {
                    adminPermissionsWrapper.stop().slideUp();
                    $('#reopenSwitch').prop('checked', false);
                    $('#finishedTicketsSwitch').prop('checked', false);
                }
            }
        }

        authTypeSelect.on('change', togglePassword);
        roleSelect.on('change', toggleRoleFeatures);

        togglePassword();
        toggleRoleFeatures();
    });
</script>
@endpush
