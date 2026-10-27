@extends('layouts.app')

@section('content')
<div class="min-vh-100 bg-light" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <header class="bg-primary rounded-top-4 bg-gradient px-4 py-5 text-white position-relative dashboard-hero">
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

    <main class="container-xxl position-relative z-1 pb-5 dashboard-main">

        {{-- On phones the status chips above the list replace these cards --}}
        <div class="row g-3 mb-4 row-cols-1 row-cols-sm-3 row-cols-lg-5 d-none d-md-flex">
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


    @include('partials._install_banner')

    @if($recentTickets->count() > 0)
    {{-- Mobile: card list with status chips (DataTables stays for tablet/desktop) --}}
    <section class="d-md-none" aria-label="قائمة تذاكري">
        <div class="status-chips mb-3" role="group" aria-label="تصفية حسب الحالة">
            @foreach($statusConfig as $status => $data)
                <button type="button" class="status-chip" data-chip-status="{{ $status }}"
                        aria-pressed="{{ request('status', 'all') == $status ? 'true' : 'false' }}">
                    {{ $data['label'] }} <span class="status-chip__count">({{ $data['count'] }})</span>
                </button>
            @endforeach
        </div>

        <div id="mobile-ticket-list">
            @foreach($recentTickets as $ticket)
                <a href="{{ route('tickets.show', $ticket) }}" class="ticket-card" data-ticket-status="{{ $ticket->status }}">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <span class="text-muted fw-bold fs-7">#{{ $ticket->id }}</span>
                        <span class="badge {{ $ticket->status_badge_class }}">{{ $ticket->status_label }}</span>
                    </div>
                    <div class="ticket-card__title">{{ $ticket->title }}</div>
                    <div class="ticket-card__meta">
                        <span><i class="bi bi-tag" aria-hidden="true"></i> {{ $ticket->category->name ?? '—' }}</span>
                        <span><i class="bi bi-clock" aria-hidden="true"></i> {{ $ticket->created_at->diffForHumans() }}</span>
                        @if($ticket->assignedUsers->isNotEmpty())
                            <span><i class="bi bi-person-check" aria-hidden="true"></i> {{ $ticket->assignedUsers->pluck('fullname')->join('، ') }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
            <p id="mobile-ticket-empty" class="text-muted text-center py-6" hidden>لا توجد تذاكر بهذه الحالة</p>
        </div>
    </section>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden d-none d-md-block">
        <div class="card-body p-4">
            <div class="table-responsive">
                {{ $dataTable->table([
                    'class' => 'table table-hover align-middle table-row-bordered fs-6 gy-4 dataTable',
                    'id'    => 'tickets-table',
                    'style' => 'border-collapse: separate; border-spacing: 0;'
                ]) }}
            </div>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="text-center py-5">
                <span class="material-symbols-rounded fs-1 text-muted d-block mb-3">inbox</span>
                <p class="text-muted mb-3">لا توجد تذاكر حالياً</p>
                <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm rounded-pill px-4">
                    إنشاء تذكرة جديدة
                </a>
            </div>
        </div>
    </div>
@endif


        </main>
    </div>






@endsection
@push('scripts')
{{ $dataTable->scripts(attributes: ['type' => 'module']) }}
<script>
    $(document).ready(function() {
        const tableId = 'tickets-table';
        const searchInput = document.getElementById('ticketSearch');

        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                if (window.LaravelDataTables && window.LaravelDataTables[tableId]) {
                    window.LaravelDataTables[tableId].search(this.value).draw();
                }
            });
        }

        $(document).on('click', '.action-btn', function() {
            $(this).siblings().removeClass('active');
            $(this).addClass('active');
        });

        // Mobile card list: filter client-side by status chip
        function filterMobileTickets(status) {
            let visible = 0;
            $('#mobile-ticket-list .ticket-card').each(function() {
                const show = status === 'all' || $(this).data('ticket-status') === status;
                $(this).toggleClass('d-none', !show);
                if (show) visible++;
            });
            $('#mobile-ticket-empty').prop('hidden', visible > 0);
            $('.status-chip').attr('aria-pressed', 'false');
            $(`.status-chip[data-chip-status="${status}"]`).attr('aria-pressed', 'true');
        }

        $('.status-chip').on('click', function() {
            filterMobileTickets($(this).data('chip-status'));
        });

        let urlParams = new URLSearchParams(window.location.search);
        let currentStatus = urlParams.get('status') || 'all';
        filterMobileTickets(currentStatus);

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
