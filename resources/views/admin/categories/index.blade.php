@extends('layouts.app')

@section('content')
<div class="card shadow-sm">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h2>إدارة المشاكل</h2>
        </div>
    </div>

    <div class="card-body">
        <div class="p-6 mb-10">
        <form action="{{ isset($category) ? route('admin.categories.update', $category->id) : route('admin.categories.store') }}" method="POST">
            @csrf

            @if(isset($category))
                @method('PUT')
            @endif

            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fs-6 fw-bolder text-dark"> المشكلة</label>
                    <input type="text" name="name" class="form-control form-control-solid"
                        value="{{ old('name', $category->name ?? '') }}" placeholder="المشكلة..." required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-6 fw-bolder text-dark">القسم المسؤول</label>
                    <select name="department_id" class="form-select form-select-solid" data-control="select2" data-placeholder="اختر القسم...">
                        <option value=""></option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}"
                                {{ (old('department_id', $category->department_id ?? '') == $department->id) ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ki-duotone ki-plus fs-2"></i>
                            {{ isset($category) ? 'تعديل' : 'إضافة' }}
                        </button>

                        @if(isset($category))
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-light">إلغاء</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
        </div>

        <div class="table-responsive">
            {{ $dataTable->table(['class' => 'table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer']) }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
@endpush
