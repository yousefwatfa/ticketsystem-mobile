@extends('layouts.app')

@section('content')
<div class="min-vh-100 bg-light" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <header class="bg-primary rounded-bottom-4 bg-gradient px-4 py-5 text-white position-relative" style="height: 192px;">
        <div class="container-xxl d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                  <h1 class="display-6 fw-bold mb-0 text-white">التذاكر</h1>
                </div>
            </div>

        </div>
    </header>

    <main class="container-xxl position-relative z-1 pb-5" style="margin-top: -64px;">

        <div class="row g-3 mb-4">
              <div class="col-12 col-sm-4 col-lg-2">
                <div class="card border-0 shadow-sm p-3 h-100 rounded-4 hover-lift border border-primary border-opacity-10 filter-btn" data-status="all">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-bold">الإجمالي</span>
                        <div class="p-2 bg-primary bg-opacity-10 rounded-3 text-primary d-flex">
                            <span class="material-symbols-rounded">description</span>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1" id='all-count'>{{ $counts['allTickets'] }}</h2>
                          <div class="d-flex align-items-center gap-1 text-success small fw-bold">
                            <span class="material-symbols-rounded fs-6">trending_up</span> نشط
                        </div>
                </div>
            </div>
            <div class="col-12 col-sm-4 col-lg-2">
                <div class="card border-0 shadow-sm p-3 h-100 rounded-4 hover-lift filter-btn active" data-status="open">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-bold">جديد</span>
                        <div class="p-2 bg-primary rounded-3 text-white  rounded-3  d-flex">
                            <span class="material-symbols-rounded ">fiber_new</span>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"  id="count-open">{{ $counts['openTickets'] ?? 0 }}</h2>
                    <div class="d-flex align-items-center gap-1 text-primary small fw-bold">
                             <span class="badge rounded-circle bg-primary p-1"> </span>  بانتظار المراجعة
                        </div>

                </div>
            </div>

            <div class="col-12 col-sm-4 col-lg-2">
                <div class="card border-0 shadow-sm p-3 h-100 rounded-4 hover-lift filter-btn" data-status="in_progress">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-bold">قيد العمل</span>
                        <div class="p-2 bg-warning bg-opacity-10 rounded-3 text-warning d-flex">
                            <span class="material-symbols-rounded">schedule</span>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"  id='count-in_progress'>{{ $counts['inProgressTickets'] }}</h2>
                  <div class="d-flex align-items-center gap-1 text-warning small fw-bold">
                        <span class="badge rounded-circle bg-warning p-1"> </span> جاري العمل
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-4 col-lg-2">
                <div class="card border-0 shadow-sm p-3 h-100 rounded-4 hover-lift filter-btn" data-status="pending">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-bold">معلقة</span>
                        <div class="p-2 bg-secondary bg-opacity-10 rounded-3 text-secondary d-flex">
                            <span class="material-symbols-rounded">pause_circle</span>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"  id='count-pending'>{{ $counts['pendingTickets'] }}</h2>
                    <div class="d-flex align-items-center gap-1 text-secondary small fw-bold">
                        <span class="badge rounded-circle bg-secondary p-1"> </span> بانتظار رد
                    </div>

                </div>
            </div>

            <div class="col-12 col-sm-4 col-lg-2">
                <div class="card border-0 shadow-sm p-3 h-100 rounded-4 hover-lift filter-btn" data-status="finished">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-bold">منتهية</span>
                        <div class="p-2 bg-success bg-opacity-10 rounded-3 text-success d-flex">
                            <span class="material-symbols-rounded">task_alt</span>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"  id='count-finished'>{{ $counts['finishedTickets'] ?? 0 }}</h2>
                         <div class="d-flex align-items-center gap-1 text-muted small fw-bold">
                        <span class="badge rounded-circle bg-secondary p-1"> </span> بانتظار الإغلاق
                    </div>

                </div>
            </div>

            <div class="col-12 col-sm-4 col-lg-2">
                <div class="card border-0 shadow-sm p-3 h-100 rounded-4 hover-lift filter-btn" data-status="closed">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-bold">مكتملة</span>
                        <div class="p-2 badge bg-success text-white rounded-3  d-flex">
                            <span class="material-symbols-rounded">check_circle</span>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"  id='count-closed'>{{ $counts['closedTickets'] }}</h2>
                         <div class="d-flex align-items-center gap-1 text-success small fw-bold">
                        <span class="badge rounded-circle bg-success p-1"> </span> تم الإنجاز
                    </div>
                </div>
            </div>


        </div>

        <!-- Search and Filter Section -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    <div class="text-muted small fw-bold" style="white-space: nowrap;">
                        حلل الأقسام
                    </div>
                    <div class="d-flex gap-2 align-items-center bg-light rounded-3 px-3 py-2" style="min-width: 300px;">
                        <input type="date" id="from_date" class="form-control form-control-sm border-0 bg-transparent" placeholder="من" style="max-width: 140px;">
                        <span class="text-muted">إلى</span>
                        <input type="date" id="to_date" class="form-control form-control-sm border-0 bg-transparent" placeholder="إلى" style="max-width: 140px;">
                    </div>
                    <div style="min-width: 200px;">
                        <select id="department_filter" class="form-select form-select-sm border-light bg-light" data-control="select2">
                            <option value="all" selected>كل الأقسام</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4">
                <div class="table-responsive">
                    {{ $dataTable->table(['class' => 'table align-middle table-row-dashed fs-6 gy-5', 'id' => 'viewer-tickets-table']) }}
                </div>
            </div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
    <script>
        $(document).ready(function() {
            let urlParams = new URLSearchParams(window.location.search);
            let currentStatus = urlParams.get('status') || 'all';

            $('.filter-btn').removeClass('active');
            $(`.filter-btn[data-status="${currentStatus}"]`).addClass('active');

            $('.filter-btn').on('click', function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');

                let status = $(this).data('status');
                let baseUrl = "{{ route('viewer.tickets.index') }}";
                let newUrl = (status === 'all') ? baseUrl : baseUrl + "?status=" + status;

                window.LaravelDataTables["viewer-tickets-table"].ajax.url(newUrl).load();

                window.history.pushState({ path: newUrl }, '', newUrl);
            });

            // Date and Department filter - auto apply on change
            $('#from_date, #to_date, #department_filter').on('change', function() {
                let fromDate = $('#from_date').val();
                let toDate = $('#to_date').val();
                let department = $('#department_filter').val();
                let table = window.LaravelDataTables["viewer-tickets-table"];
                
                let currentUrl = table.ajax.url();
                let url = new URL(currentUrl);
                
                if (fromDate) {
                    url.searchParams.set('from_date', fromDate);
                } else {
                    url.searchParams.delete('from_date');
                }
                
                if (toDate) {
                    url.searchParams.set('to_date', toDate);
                } else {
                    url.searchParams.delete('to_date');
                }
                
                if (department && department !== 'all') {
                    url.searchParams.set('department', department);
                } else {
                    url.searchParams.delete('department');
                }
                
                table.ajax.url(url.toString()).load();
            });
        });
    </script>
@endpush
