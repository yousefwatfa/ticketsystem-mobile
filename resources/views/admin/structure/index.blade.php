@extends('layouts.app')

@section('content')
<div class="card mb-8">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h2 class="fw-bold">الهيكلية</h2>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-primary d-flex align-items-center p-5 mb-8">
            <i class="bi bi-diagram-3-fill fs-2hx text-primary me-4"></i>
            <div class="d-flex flex-column">
                <h4 class="mb-1 text-dark">عرض الهيكلية الإدارية</h4>
                <span class="text-gray-700">توضح الصفحة الأقسام الرئيسية وما يتبعها من أقسام فرعية.</span>
            </div>
        </div>

        @if($mainDepartments->isEmpty())
            <div class="text-center py-10 text-gray-600 fw-bold">لا توجد أقسام رئيسية بعد.</div>
        @else
            <div class="row g-6">
                @foreach($mainDepartments as $mainDepartment)
                    <div class="col-12 col-lg-6">
                        <div class="card border border-primary border-dashed h-100">
                            <div class="card-header bg-light-primary border-0 min-h-60px">
                                <h3 class="card-title fw-bolder text-primary mb-0">
                                    <i class="bi bi-building me-2"></i>
                                    {{ $mainDepartment->name }}
                                </h3>
                                <div class="badge badge-light-primary fw-bold">
                                    {{ $mainDepartment->departments->count() }} قسم
                                </div>
                            </div>

                            <div class="card-body pt-5">
                                @if($mainDepartment->departments->isEmpty())
                                    <div class="text-gray-500">لا توجد أقسام فرعية مرتبطة.</div>
                                @else
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($mainDepartment->departments as $department)
                                            <span class="badge badge-light-success fs-7 px-4 py-2">
                                                <i class="bi bi-diagram-2 me-1"></i>
                                                {{ $department->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="separator separator-dashed my-10"></div>

        <h4 class="fw-bolder mb-4">أقسام غير مرتبطة</h4>
        @if($unlinkedDepartments->isEmpty())
            <div class="text-gray-600">جميع الأقسام الفرعية مرتبطة بأقسام رئيسية.</div>
        @else
            <div class="d-flex flex-wrap gap-2">
                @foreach($unlinkedDepartments as $department)
                    <span class="badge badge-light-warning fs-7 px-4 py-2">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        {{ $department->name }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
