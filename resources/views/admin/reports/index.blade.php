@extends('layouts.app')

@section('content')
<div class="card shadow-sm">
    <div class="card-header border-0 pt-6">
        <div class="card-title d-flex flex-column">
            <h3 class="fw-bolder text-gray-800 mb-2">تقارير التذاكر</h3>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="badge badge-light-primary fs-7 fw-bold">
                    الإجمالي: <span id="results-count">0</span>
                </span>
                <span class="text-muted fs-7 fw-semibold">
                    @if(auth()->user()->is_root)
                    يمكن إنشاء تقرير مفصل لأي مستخدم ثم طباعته PDF.
                    @else
                    يمكنك إنشاء تقرير مفصل عن تذاكرك أو تذاكر القسم الخاص بك ثم طباعته PDF.
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div class="card-body border-bottom">
        <form id="reports-filter-form" class="row g-4" method="GET" action="{{ route('reports.pdf') }}">
            <div class="col-md-12">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-gray-700" for="title">العنوان / رقم التذكرة</label>
                        <input type="text" class="form-control form-control-solid" id="title" name="title" placeholder="أدخل عنوان التذكرة أو رقمها">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-gray-700" for="category">المشكلة</label>
                        <input type="text" class="form-control form-control-solid" id="category" name="category" placeholder="أدخل اسم المشكلة">
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold text-gray-700" for="user_id">المشرف المعين</label>
                <select id="user_id" name="user_id" class="form-select form-select-solid" data-control="select2" data-placeholder="اختر المستخدم">
                    <option value="all">كل المشرفين</option>
                    @foreach($users as $reportUser)
                    <option value="{{ $reportUser->id }}">{{ $reportUser->fullname }} ({{ $reportUser->username }})</option>
                    @endforeach
                </select>
            </div>
            @if(auth()->user()->is_root)
                <div class="col-md-4">
                    <label class="form-label fw-bold text-gray-700" for="main_department_filter">القسم الرئيسي</label>
                    <select id="main_department_filter" name="main_department_id" class="form-select form-select-solid" data-control="select2" {{ auth()->user()->is_root ? '' : '' }}>
                        <option value="all">كل الأقسام الرئيسية</option>
                        @foreach($mainDepartments as $main)
                        <option value="{{ $main->id }}">{{ $main->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

            <div class="col-md-4">
                <label class="form-label fw-bold text-gray-700" for="department_filter">القسم الفرعي</label>
                <select id="department_filter" name="department_id" class="form-select form-select-solid" data-control="select2">
                    <option value="all">كل الأقسام الفرعية</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-12" dir="rtl">
                <label class="form-label fw-bold text-gray-700">نطاق التاريخ</label>
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative w-100">
                        <input type="date" id="from_date" name="from_date" class="form-control form-control-solid" placeholder="من">
                    </div>
                    <span class="fw-bold text-gray-500">إلى</span>
                    <div class="position-relative w-100">
                        <input type="date" id="to_date" name="to_date" class="form-control form-control-solid" placeholder="إلى">
                    </div>
                </div>
            </div>
        </form>

        <div class="card-footer d-flex justify-content-end gap-2 bg-transparent border-0 pb-6 flex-wrap">
            <button type="button" class="btn btn-light-success fw-bold px-6" onclick="exportData('excel')">
                <i class="bi bi-file-earmark-spreadsheet fs-4 me-2"></i> Excel
            </button>

            <button type="button" class="btn btn-light-danger fw-bold px-6" onclick="submitDepartmentPdfReport()">
                <i class="bi bi-file-earmark-pdf fs-4 me-2"></i> تقرير قسم PDF
            </button>

            <button type="button" class="btn btn-outline-primary px-6 fw-bold" onclick="submitUserPdfReport()">
                <i class="bi bi-file-earmark-personal fs-4 me-2"></i> تقرير مستخدم PDF
            </button>

            <button type="button" class="btn btn-primary px-8 fw-bold" onclick="runSearch()">
                <i class="bi bi-search fs-4 me-2"></i> بحث
            </button>
        </div>
    </div>

    <div class="card-body py-4">
        <div class="table-responsive">
            {{ $dataTable->table() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{ $dataTable->scripts(attributes: ['type' => 'module']) }}
<script>
    function exportData(type) {
        const table = window.LaravelDataTables['admin-reports-table'];
        if (type === 'excel') {
            table.button('.buttons-excel').trigger();
        }
    }

    function submitDepartmentPdfReport() {
        const form = document.getElementById('reports-filter-form');
        const userField = document.getElementById('user_id');
        if (userField) userField.value = 'all';
        form.submit();
    }

    function submitUserPdfReport() {
        const userField = document.getElementById('user_id');
        const form = document.getElementById('reports-filter-form');

        if (!userField.value || userField.value === 'all') {
            Swal.fire({
                icon: 'info',
                title: 'تنبيه',
                text: 'الرجاء اختيار مستخدم محدد لاستخراج تقريره الشخصي، أو استخدم "تقرير قسم" للبحث العام.',
                confirmButtonText: 'فهمت'
            });
            return;
        }
        form.submit();
    }

    function runSearch() {
        const table = window.LaravelDataTables['admin-reports-table'];

        table.on('preXhr.dt', function(e, settings, data) {
            data.search_triggered = true;
            data.user_id = $('#user_id').val();
            data.main_department_id = $('#main_department_filter').val();
            data.department_id = $('#department_filter').val();
        });

        table.draw();
    }
</script>
@endpush