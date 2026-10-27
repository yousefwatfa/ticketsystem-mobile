@extends('layouts.app')

@section('content')
<div class="min-vh-100 bg-light" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <header class="bg-primary rounded-top-4 bg-gradient px-4 py-5 text-white position-relative" style="height: 192px;">
        <div class="container-xxl d-flex justify-content-between align-items-start">
            <div class="text-end">
                <h1 class="display-6 fw-bold mb-0 text-white">تذاكري</h1>
            </div>
            <a href="{{ route('tickets.create') }}" class="btn btn-light rounded-pill fw-bold shadow-lg d-flex align-items-center gap-2 text-primary px-4 py-2 text-decoration-none btn-create-ticket">
                <span class="material-symbols-rounded">add</span>
                تذكرة جديدة
            </a>
        </div>
    </header>

    <main class="container-xxl position-relative z-1 pb-5" style="margin-top: -64px;">

        <div class="row g-3 mb-4 row-cols-1 row-cols-sm-3 row-cols-lg-5">
            @php
                $statusConfig = [
                    'all' => ['label' => 'الإجمالي', 'icon' => 'description', 'color' => 'primary', 'count' => $counts['allTickets'], 'sub' => 'نشط'],
                    'in_progress' => ['label' => 'قيد العمل', 'icon' => 'schedule', 'color' => 'warning', 'count' => $counts['inProgressTickets'], 'sub' => 'جاري العمل'],
                    'pending' => ['label' => 'معلقة', 'icon' => 'pause_circle', 'color' => 'secondary', 'count' => $counts['pendingTickets'], 'sub' => 'بانتظار رد'],
                    'finished' => ['label' => 'منتهية', 'icon' => 'task_alt', 'color' => 'success', 'count' => $counts['finishedTickets'] ?? 0, 'sub' => 'بانتظار الإغلاق'],
                    'closed' => ['label' => 'مكتملة', 'icon' => 'check_circle', 'color' => 'success', 'count' => $counts['closedTickets'], 'sub' => 'تم الإنجاز'],
                ];
            @endphp

            @foreach($statusConfig as $status => $data)
            <div class="col">
                <div class="card border-0 shadow-sm p-3 h-100 rounded-4 hover-lift filter-btn {{ request('status', 'all') == $status ? 'active' : '' }}" data-status="{{ $status }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-bold">{{ $data['label'] }}</span>
                        <div class="p-2 bg-{{ $data['color'] }} bg-opacity-10 rounded-3 text-{{ $data['color'] }} d-flex">
                            <span class="material-symbols-rounded">{{ $data['icon'] }}</span>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1">{{ $data['count'] }}</h2>
                    <div class="d-flex align-items-center gap-1 text-{{ $data['color'] }} small fw-bold">
                        <span class="badge rounded-circle bg-{{ $data['color'] }} p-1"> </span> {{ $data['sub'] }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>


        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4">
                @if($recentTickets->count() > 0)
                    <div class="table-responsive">
                        {{ $dataTable->table(['class' => 'table table-hover align-middle w-100', 'id' => 'tickets-table']) }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <span class="material-symbols-rounded fs-1 text-muted d-block mb-3">inbox</span>
                        <p class="text-muted mb-3">لا توجد تذاكر حالياً</p>
                        <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm rounded-pill px-4">إنشاء تذكرة جديدة</a>
                    </div>
                @endif
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
            let params = new URLSearchParams(window.location.search);
            let baseUrl = "{{ route('dashboard') }}";

            if (status === 'all') {
                params.delete('status');
            } else {
                params.set('status', status);
            }

            let query = params.toString();
            let newUrl = query ? baseUrl + "?" + query : baseUrl;

            if (window.LaravelDataTables && window.LaravelDataTables[tableId]) {
                window.LaravelDataTables[tableId].ajax.url(newUrl).load();
                window.history.pushState({}, '', newUrl);
            } else {
                window.location.href = newUrl;
            }
        });
    });
</script>
@endpush
