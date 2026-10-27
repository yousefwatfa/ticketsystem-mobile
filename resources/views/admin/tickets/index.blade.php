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
        <div class="d-flex justify-content-end align-items-center flex-wrap gap-3">

            <div class="d-flex gap-3 align-items-center flex-wrap">
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
</div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4">
                <div class="table-responsive">
                    {{ $dataTable->table(['class' => 'table table-hover align-middle table-row-bordered fs-6 gy-4 dataTable', 'id' => 'admin-tickets-table', 'style' => 'border-collapse: separate; border-spacing: 0;']) }}
                </div>
            </div>
        </div>
    </main>
</div>


<div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 bg-light rounded-top-4 p-8">
                <h5 class="modal-title fw-bolder fs-4 text-dark">تفاصيل التذكرة</h5>
            </div>
            <div class="modal-body p-4" id="ticketContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">جاري تحميل البيانات...</p>
                </div>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
 
    <script>
        $(document).ready(function() {
            let urlParams = new URLSearchParams(window.location.search);
            let currentStatus = urlParams.get('status') || 'open';

            $('.filter-btn').removeClass('active');
            $(`.filter-btn[data-status="${currentStatus}"]`).addClass('active');

            $('.filter-btn').on('click', function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');

                let status = $(this).data('status');
                let baseUrl = "{{ route('admin.tickets.index') }}";
                let newUrl = (status === 'all') ? baseUrl : baseUrl + "?status=" + status;

                window.LaravelDataTables["admin-tickets-table"].ajax.url(newUrl).load();

                window.history.pushState({ path: newUrl }, '', newUrl);
            });

            // Date and Department filter - auto apply on change
            $('#from_date, #to_date, #department_filter').on('change', function() {
                let fromDate = $('#from_date').val();
                let toDate = $('#to_date').val();
                let department = $('#department_filter').val();
                let table = window.LaravelDataTables["admin-tickets-table"];

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



            $(document).on('click', '.view-ticket-popup', function() {
                let ticketId = $(this).data('id');
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('ticketDetailModal'));
                let content = $('#ticketContent');

                content.html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">جاري التحميل...</p></div>');
                modal.show();

                $.ajax({
                    url: '/admin/tickets/' + ticketId + '/details',
                    type: 'GET',
                    success: function(response) {
                        if (response.status && response.data) {
                            let ticket = response.data;
                            let statusLabel = response.status_label || ticket.status;

                            let categoryName = ticket.category ? ticket.category.name : 'عام';
                            let userName = ticket.creator ? ticket.creator.fullname : 'غير محدد';

                            let createdAt = new Date(ticket.created_at).toLocaleString('en-US', {
                                month: 'short',
                                day: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: true
                            });

                            let html = `
                                <div class="container-fluid text-end" dir="rtl">
                                    <div class="row g-4">
                                        <div class="col-12 d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge  fs-6 p-2 px-3">رقم التذكرة: #${ticket.id}</span>
                                            <span class="badge  fs-6 p-2 px-3">الحالة: ${statusLabel}</span>
                                        </div>

                                        <div class="col-12 border-bottom pb-3">
                                            <label class="text-muted small fw-bold d-block mb-1">العنوان</label>
                                            <h4 class="fw-bolder text-dark">${ticket.title}</h4>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="p-2  bg-light mb-3">
                                                <label class="text-muted small fw-bold d-block">نوع المشكلة</label>
                                                <span class="fw-bold text-dark">${categoryName}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="p-2  bg-light mb-3">
                                                <label class="text-muted small fw-bold d-block">بواسطة الموظف</label>
                                                <span class="fw-bold text-dark">${userName}</span>
                                            </div>
                                        </div>

                                        <div class="col-12 bg-light p-3 rounded-3 mb-2">
                                            <label class="text-muted small fw-bold d-block mb-2">الوصف</label>
                                            <p class="mb-0 fs-6 lh-base">${ticket.description || 'لا يوجد وصف'}</p>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="p-2  bg-light mb-3">
                                                <label class="text-muted small fw-bold d-block">مكان العمل</label>
                                                <span class="fw-bold">${ticket.office_location || 'غير محدد'}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-6 text-start">
                                            <div class="p-2  bg-light mb-3 text-end">
                                                <label class="text-muted small fw-bold d-block">تاريخ الإنشاء</label>
                                                <span class="fw-bold">${createdAt}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                                content.html(html);
                            } else {
                                content.html('<div class="alert alert-warning text-center">لا توجد بيانات متاحة لهذا التذكرة.</div>');
                            }
                    },
                });
            });
        });
    </script>
@endpush
