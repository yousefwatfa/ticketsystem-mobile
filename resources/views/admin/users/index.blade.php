@extends('layouts.app')
@section('content')
<div class="min-vh-100 bg-light" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <header class="bg-primary rounded-bottom-4 bg-gradient px-4 py-5 text-white position-relative" style="height: 140px;">
        <div class="container-xxl d-flex justify-content-between align-items-center">
            <div class="text-end">
                <h1 class="display-6 fw-bold mb-0 text-white">إدارة المستخدمين</h1>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-light rounded-pill fw-bold shadow-lg d-flex align-items-center gap-2 text-primary px-4 py-2 text-decoration-none">
                <span class="material-symbols-rounded">add</span>
                إضافة مستخدم
            </a>
        </div>
    </header>

    <main class="container-xxl position-relative z-1 pb-5" style="margin-top: -40px;">



         <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4">
                <div class="table-responsive">
                    {{ $dataTable->table(['class' => 'table table-hover align-middle table-row-bordered fs-6 gy-4 dataTable', 'id' => 'users-table', 'style' => 'border-collapse: separate; border-spacing: 0;']) }}
                </div>
            </div>
        </div>

    </main>
</div>
@endsection


@push('scripts')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
@endpush
