@extends('layouts.app')
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3 p-3">
            <div class="card-header border-0 pt-6 pb-4">
                <div class="d-flex flex-column w-100 gap-5">

                    <div class="d-flex flex-stack flex-wrap gap-4">
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center gap-3 mb-1">
                                <h1 class="text-gray-900 fw-boldest m-0 fs-1 tracking-tight">{{ $ticket->title }}</h1>
                                <span class="badge badge-light-success fw-bold px-3 py-1 fs-8">#{{ $ticket->id }}</span>
                                <span class="badge badge-light-primary fw-bold px-3 py-1 fs-8">
                                    <i class="bi bi-diagram-3-fill me-1 text-primary"></i>
                                    {{ $ticket->category->department->name ?? 'غير مصنف' }}
                                </span>
                            </div>

                            <div class="d-flex flex-wrap align-items-center text-gray-500 fs-7 gap-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle me-1"></i>
                                    <span>بواسطة <b class="text-gray-700 text-hover-primary cursor-pointer view-user-details" data-user-id="{{ $ticket->creator->id }}">{{ $ticket->creator->fullname }}</b></span>
                                </div>
                                <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-geo-alt me-1 text-danger"></i>
                                    <span class="fw-semibold">{{ $ticket->office_location }}</span>
                                </div>
                                <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock me-1"></i>
                                    <span>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        @if($ticket->status !== 'closed' && $ticket->status !== 'finished')
                        <div class="d-flex align-items-center">
                            <form action="{{ route('admin.tickets.markAsFinishedTicket', $ticket) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="finished">
                                <button type="submit" class="btn btn-sm btn-light-danger hover-elevate-up fw-bold px-5">
                                    <i class="bi bi-check2-circle fs-4 me-1"></i> إنهاء التذكرة
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3 p-4 bg-light rounded-2 border border-dashed border-gray-300">
                        <div class="d-flex align-items-center me-5">
                            <span class="fs-8 fw-bold text-gray-500 text-uppercase me-2">Tag:</span>
                            <span class="badge badge-secondary font-monospace fs-8">{{ $ticketTag ?: '-' }}</span>
                        </div>

                        <div class="d-flex align-items-center me-5">
                            <span class="fs-8 fw-bold text-gray-500 text-uppercase me-2">IP:</span>
                            <span class="badge badge-secondary font-monospace fs-8">{{ $ticket->ip_address }}</span>
                        </div>

                        <div class="d-flex align-items-center">
                            <span class="fs-8 fw-bold text-gray-500 text-uppercase me-2">Host:</span>
                            <span class="badge badge-secondary font-monospace fs-8">{{ $ticket->host_name ?: '-' }}</span>
                        </div>
                    </div>

                </div>
            </div>
            <hr>
            @if($ticket->internalNotes->count() > 0)
            <div class="notes-list my-8 px-2">
                <h5 class="mb-6 fw-bolder text-gray-700">الملاحظات الداخلية</h5>

                @foreach($ticket->internalNotes as $note)
                <div class="d-flex mb-7" id="note-container-{{ $note->id }}">
                    <div class="symbol symbol-35px me-4">
                        <div class="symbol-label fs-5 fw-bold bg-light-primary text-primary">
                            {{ mb_substr($note->user->fullname, 0, 1) }}
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-row-fluid">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div>
                                <a href="#" class="text-gray-800 fw-bold me-2 fs-6">
                                    {{ $note->user->fullname }}
                                </a>
                                <span class="text-muted fw-semibold fs-7" title="{{ $note->created_at }}">
                                    {{ $note->created_at->diffForHumans() }}
                                </span>
                            </div>

                            @if(auth()->id() == $note->user_id)
                            <div class="dropdown" id="actions-{{ $note->id }}">
                                <button class="btn btn-sm btn-icon btn-color-light-dark border-0" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                    <i class="bi bi-three-dots fs-4"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 menu-rounded w-125px">
                                    <a href="javascript:void(0)"
                                        class="dropdown-item py-2 d-flex align-items-center gap-2 justify-content-start text-gray-700"
                                        onclick="enableEdit({{ $note->id }})">
                                        <i class="bi bi-pencil ms-2"></i>
                                        <span>تعديل</span>
                                    </a>

                                    <div class="separator border-gray-200 my-1"></div>

                                    <form action="{{ route('admin.tickets.internal-note.destroy', [$ticket->id, $note->id]) }}"
                                        method="POST" class="m-0">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="dropdown-item py-2 d-flex gap-2 align-items-center justify-content-start text-danger"
                                            onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                            <i class="bi bi-trash ms-2 text-danger"></i>
                                            <span>حذف</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="card border-0 bg-light-secondary mt-1 shadow-none rounded-3">
                            <div class="card-body p-4 text-gray-800 fs-6 lh-base">
                                <p class="mb-0 note-body-text" id="text-{{ $note->id }}">
                                    {{ $note->body }}
                                </p>

                                <form action="{{ route('admin.tickets.internal-note.update', [$ticket->id, $note->id]) }}"
                                    method="POST"
                                    class="d-none edit-note-form mt-2"
                                    id="form-{{ $note->id }}">
                                    @csrf @method('PUT')
                                    <div class="position-relative">
                                        <textarea name="body" class="form-control form-control-flush border-bottom border-gray-300 border-1 pb-2 mb-3" rows="2" id="input-{{ $note->id }}">{{ $note->body }}</textarea>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-sm btn-light" onclick="cancelEdit({{ $note->id }})">إلغاء</button>
                                            <button type="submit" class="btn btn-sm btn-primary">حفظ التغييرات</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            <div class="card-body">
                <p class="text-2xl">{{ $ticket->description }}</p>
                <hr>
                <h5 class="my-6">الرسائل</h5>

                @foreach($ticket->messages as $msg)
                @php
                $isOwner = ($msg->created_by == $ticket->created_by);
                @endphp

                <div class="d-flex mb-4">
                    <div class="d-flex  align-items-center justify-content-center rounded-circle text-white fw-bold shadow-sm {{ $isOwner ? 'me-3' : 'ms-3' }}"
                        style="width: 30px; height: 30px; min-width: 30px; background-color: {{ $isOwner ? '#a0a7ac' : '#0095e8' }}; font-size: 1.2rem;">
                        {{ mb_substr($msg->user->fullname, 0, 1) }}
                    </div>

                    <div class="p-4 bg-white border"
                        style="border-radius: 8px;

                                    border-top: 1px solid #dee2e6;
                                    border-bottom: 1px solid #dee2e6;
                                    border-left: 1px solid #dee2e6;

                                    max-width: 80%;
                                    min-width: 30%;
                                    box-shadow: 0 0.1rem 0.75rem 0.2rem rgba(0, 0, 0, 0.05);">

                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-dark me-2">{{ $msg->user->fullname }}</span>

                                <span class="badge badge-light-{{ $isOwner ? 'success' : 'primary' }} fw-bold" style="font-size: 0.65rem;">
                                    {{ $msg->user->role->name }}
                                </span>
                                @if($ticket->firstAdminReply && $msg->id == $ticket->firstAdminReply->id)
                                <span class="ms-2 badge rounded-pill bg-success text-white border border-success-subtle" style="font-size: 0.7rem;">
                                    الرد الأول
                                </span>
                                @endif
                            </div>

                            <div class="text-muted small ms-5">
                                {{ $msg->created_at->format('Y-m-d | h:i A') }}
                            </div>
                        </div>

                        <div class="message-body text-dark" style="line-height: 1.6; font-size: 0.95rem;">
                            {{ $msg->content }}
                        </div>
                    </div>
                </div>
                @endforeach
                <form action="{{ route('messages.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">

                    @if($ticket->status !== 'closed')
                    <form action="{{ route('messages.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                        <div class="mb-4">
                            <textarea name="content" class="form-control form-control-solid" rows="3" placeholder="اكتب ردك هنا..." required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">إرسال الرد</button>
                        </div>
                    </form>
                    @else
                    <div class="bg-light-secondary p-8 rounded text-center border border-dashed">
                        <i class="bi bi-lock-fill fs-2x text-muted mb-3 d-block"></i>
                        <span class="text-gray-600 fw-bolder fs-5">تم إغلاق هذه التذكرة. لا يمكن إضافة ردود جديدة.</span>
                    </div>
                    @endif

                </form>
            </div>
        </div>

        <button type="button" class="btn bg-white shadow-sm w-100 mt-3 notes-btn d-flex align-items-center gap-2">
            <i class="bi bi-chevron-down arrow-icon"></i>
            <span>اضافة ملاحظة داخلية</span>
        </button>

        <div class="card mt-5 d-none notes-content">
            <div class="card-header">
                <h3 class="card-title">ملاحظات داخلية </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.tickets.internal-note.store', $ticket->id) }}" method="POST">
                    @csrf
                    <textarea name="body" class="form-control mb-3" required placeholder="اكتب ملاحظة...">{{old('body')}}</textarea>
                    <button type="submit" class="btn btn-sm btn-primary">حفظ الملاحظة</button>
                </form>

                <hr>


            </div>
        </div>

    </div>
    <div class="col-md-4">
        <div class="mb-3" style="position: relative; display: inline-block; width: 100%;">
            <button class="btn btn-primary dropdown-toggle w-100" type="button" onclick="togglePrintMenu()" style="width: 100%;">
                <i class="bi bi-printer fs-5 me-2"></i> خيارات الطباعة
            </button>
            <div id="printDropdown" style="display: none; position: absolute; background-color: white; min-width: 100%; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; margin-top: 2px;">
                <a class="dropdown-item" href="{{ route('admin.tickets.print', $ticket->id) }}" target="_blank" style="color: black; padding: 12px 16px; text-decoration: none; display: block;">
                    <i class="bi bi-ticket-perforated me-2"></i> طباعة التذكرة فقط
                </a>
                <hr style="margin: 0;">
                <a class="dropdown-item" href="{{ route('admin.tickets.print', $ticket->id) }}?full=1" target="_blank" style="color: black; padding: 12px 16px; text-decoration: none; display: block;">
                    <i class="bi bi-file-earmark-medical me-2"></i> طباعة السجل الكامل
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">تعديل التذكرة</div>
            </div>
            <div class="card-body">
                @if($ticket->status === 'closed')
                <div class="mb-7">
                    <label class="fw-bold text-muted mb-2 d-block fs-7 text-uppercase">الحالة الحالية:</label>
                    <div class="d-flex align-items-center gap-2 w-100">
                        <div class="flex-grow-1">
                            <span class="badge {{ $ticket->status_badge_class }} d-block w-100 py-4 fs-6 text-center">
                                {{ $ticket->status_label }}
                            </span>
                        </div>

                        @if(auth()->user()->can_reopen_tickets)
                        <form action="{{route('admin.tickets.reopen', $ticket->id)}}" method="POST" id="reopenForm">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-sm btn-light-primary py-4 px-6">
                                فتح التذكرة
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endif

                <form action="{{ route('admin.tickets.update', $ticket) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @if($ticket->status !== 'closed')
                    <div class="mb-3 w-100">
                        <label class="form-label mb-2 fw-bold">الحالة</label>
                        <select name="status" class="form-select form-select-solid w-100">
                            <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>جديد</option>
                            <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>قيد العمل</option>
                            <option value="pending" {{ $ticket->status == 'pending' ? 'selected' : '' }}>معلقة</option>
                            <option value="finished" {{ $ticket->status == 'finished' ? 'selected' : '' }}>منتهية</option>
                        </select>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">تعيين لـ (اختر مستخدمين)</label>
                        <select name="assigned_users[]" class="form-select form-select-solid" data-control="select2" data-close-on-select="false" data-placeholder="اختر المستخدمين" multiple="multiple">
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $ticket->assignedUsers->contains($user->id) ? 'selected' : '' }}>
                                {{ $user->fullname }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <button class="btn btn-success w-100">تحديث</button>
                </form>
            </div>
        </div>

        <div class="mt-6">
            <x-attachment-manage :ticket="$ticket" />
        </div>

        <button type="button" class="btn bg-white shadow-sm w-100 mt-3 reassign-btn d-flex align-items-center gap-2">
            <i class="bi bi-chevron-down arrow-icon"></i>
            <span> تحويل نوع المشكلة </span>
        </button>
        <div class="reassign-content d-none  card mt-3">
            <div class="card-header bg-light-secondary">
                <div class="card-title text-black"></i> تحويل لنوع مشكلة آخر</div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.tickets.reassign', $ticket) }}" method="POST">
                    @csrf
                    @method('PUT')



                    <div class="mb-3">
                        <label class="form-label font-bold text-gray-700">نوع المشكلة الجديد</label>
                        <select name="category_id" class="form-select" required data-control="select2">
                            <option value="">اختر المشكلة ...</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-bold text-gray-700">سبب التحويل</label>
                        <textarea name="reassign_reason" class="form-control" rows="2" placeholder="لماذا تريد تحويلها؟" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 font-bold">
                        تأكيد التحويل
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <div class="card-title">سجل النشاطات</div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($ticket->ticketLogs as $log)
                    <li class="list-group-item">
                        <div class="d-flex flex-column">
                            <span class="text-gray-800 text-hover-primary fw-bold">{!! $log->content !!}</span>

                            <div class="d-flex align-items-center gap-1 ~">
                                {{-- <span>{{ $log->created_at->diffForHumans() }}</span>
                                <span>-</span> --}}
                                <span>{{ $log->created_at->format('Y-m-d h:i:s A') }}</span>
                                <span>بواسطة: <strong>{{ $log->user ? $log->user->fullname : 'النظام' }}</strong></span>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePrintMenu() {
        const dropdown = document.getElementById('printDropdown');
        if (dropdown.style.display === 'none' || dropdown.style.display === '') {
            dropdown.style.display = 'block';
        } else {
            dropdown.style.display = 'none';
        }
    }

    // إغلاق القائمة عند الضغط خارجها
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('printDropdown');
        const button = event.target.closest('button');

        if (!button || !button.textContent.includes('خيارات الطباعة')) {
            dropdown.style.display = 'none';
        }
    });



    $(document).ready(function() {


        $('.reassign-btn').on('click', function(e) {
            e.preventDefault();
            $('.reassign-content').toggleClass('d-none');
            $('.arrow-icon').toggleClass('rotate-180');
        });

        $('.notes-btn').on('click', function(e) {
            e.preventDefault();
            $('.notes-content').toggleClass('d-none');
            $('.arrow-icon').toggleClass('rotate-180');
        });
    });

    function enableEdit(noteId) {
        const textEl = document.getElementById('text-' + noteId);
        const actionsEl = document.getElementById('actions-' + noteId);

        if (textEl) textEl.classList.add('d-none');
        if (actionsEl) actionsEl.classList.add('d-none');

        const form = document.getElementById('form-' + noteId);
        if (form) {
            form.classList.remove('d-none');
            const input = document.getElementById('input-' + noteId);
            if (input) {
                input.focus();
                const val = input.value;
                input.value = '';
                input.value = val;
            }
        }
    }

    function cancelEdit(noteId) {
        document.getElementById('text-' + noteId).classList.remove('d-none');
        document.getElementById('actions-' + noteId).classList.remove('d-none');
        document.getElementById('form-' + noteId).classList.add('d-none');
    }
</script>
@endpush