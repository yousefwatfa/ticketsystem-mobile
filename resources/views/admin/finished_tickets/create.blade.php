@extends('layouts.app')
@section('content')

<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title">  إنشاء  مهمة منتهية </h3>
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
        <form action="{{ route('admin.finished_tickets.store') }}" method="POST">
            @csrf

            <div class="mb-10">
                <label class="form-label required fw-bold fs-6">العنوان</label>
                <input name="title" type="text" class="form-control form-control-solid @error('title') is-invalid @enderror" placeholder="أدخل عنوان التذكرة" value="{{ old('title') }}" required/>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

              <div class="mb-10">
                <div class="form-group">
                    <label for="user_id" class="form-label required fw-bold fs-6">عن مَن (الموظف صاحب المشكلة):</label>
                    <select name="user_id" id="user-autocomplete" class="form-control form-control-solid" required>
                        <option value="">ابحث عن اسم الموظف...</option>
                        </select>
                </div>
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



            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <span class="indicator-label btn-create-finished-ticket">إرسال المهمة</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection


@push('scripts')
<script>
    $(document).ready(function() {
        $('#user-autocomplete').select2({
            placeholder: "اكتب اسم الموظف...",
            allowClear: true,
            width: '100%',
            ajax: {
                url: '{{ route("admin.users.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            language: {
                inputTooShort: function() {
                    return "يرجى إدخال حرفين أو أكثر للبحث";
                },
                searching: function() {
                    return "جاري البحث...";
                },
                noResults: function() {
                    return "لا توجد نتائج مطابقة";
                }
            }
        });
    });
</script>
@endpush
