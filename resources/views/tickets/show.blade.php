@extends('layouts.app')

@section('content')
<div class="row g-5">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-5">
            <div class="card-header">
                <div>
                    <h3 class="card-title fw-bolder text-dark mb-1">{{ $ticket->title }}</h3>
                    <span class="badge badge-light-primary">رقم التذكرة: #{{ $ticket->id }}</span>
                </div>
                <div class="card-toolbar d-lg-none">
                    <span class="badge {{ $ticket->status_badge_class }}">{{ $ticket->status_label }}</span>
                </div>
                <div class="d-flex align-items-center">
                    <div class="d-flex flex-column">
                        <span class="text-gray-900 fw-bolder fs-6">{{ $ticket->office_location }}</span>
                    </div>
                    <div class="symbol symbol-40px symbol-circle me-3">
                        <div class="symbol-label bg-light-danger">
                            <i class="bi bi-geo-alt-fill fs-2 text-danger"></i>
                        </div>
                    </div>

                </div>
            </div>

            @if($ticket->status === 'closed' && session('success'))
                <div class="alert alert-dismissible bg-light-success d-flex align-items-center p-4 m-5 border border-success border-dashed rounded-3">
                    <div class="d-flex align-items-center justify-content-center bg-success rounded-circle me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-check-lg fs-3 text-white"></i>
                    </div>
                    <div class="d-flex flex-column">
                        <h5 class="fw-bold text-success mb-0 fs-6">تمت العملية</h5>
                        <span class="text-dark opacity-75 fw-medium">{{ session('success') }}</span>
                    </div>
                </div>
            @endif


            <div class="card-body">
                <div class="mb-10">
                    <h5 class="fw-bold text-dark mb-4">تفاصيل التذكرة:</h5>
                    <div class="text-gray-800 fs-6 bg-light p-5 rounded border border-gray-200 lh-lg">
                        {{ $ticket->description }}
                    </div>
                </div>



                @if($ticket->status === 'finished' && auth()->id() == $ticket->created_by)
                    <div class="notice d-flex bg-light-success rounded border-success border border-dashed mb-9 p-6">
                        <i class="bi bi-check-circle-fill fs-2tx text-success me-4"></i>
                        <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
                            <div class="mb-3 mb-md-0 fw-bold">
                                <h4 class="text-gray-900 fw-bolder">هل تم حل المشكلة؟</h4>
                                <div class="fs-6 text-gray-700 pe-7">إذا حصلت على المساعدة المطلوبة، يرجى تأكيد إغلاق التذكرة.</div>
                            </div>
                            <form action="{{ route('tickets.finish', $ticket->id) }}" method="POST">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-success fw-bold">
                                    <i class="bi bi-check-all fs-3"></i> تأكيد الحل
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="separator separator-dashed my-10"></div>

                <h3 class="fw-bolder text-dark mb-7">سجل المحادثة</h3>
                <div class="mb-7" id="chat-history">
                    @foreach($ticket->messages as $msg)
                        @php $isCurrentUser = $msg->created_by == Auth::id(); @endphp
                        <div class="d-flex mb-5 {{ $isCurrentUser ? 'justify-content-start' : 'justify-content-end' }}">
                            <div class="d-flex flex-column {{ $isCurrentUser ? 'align-items-start' : 'align-items-end' }}" style="max-width: 85%;">
                                <div class="d-flex align-items-center mb-2">
                                    @if(!$isCurrentUser)
                                        <div class="symbol symbol-35px symbol-circle me-3">
                                            <span class="symbol-label bg-light-info text-info fs-6 fw-bolder">{{ mb_substr($msg->user->fullname ?? 'U', 0, 1) }}</span>
                                        </div>
                                        <div class="fw-bold fs-6 text-gray-900 me-2">{{ $msg->user->fullname }}</div>
                                    @else
                                        <div class="fw-bold fs-6 text-gray-900 ms-2">أنت</div>
                                        <div class="symbol symbol-35px symbol-circle ms-3">
                                            <span class="symbol-label bg-light-primary text-primary fs-6 fw-bolder">{{ mb_substr(Auth::user()->fullname ?? 'M', 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <span class="text-muted fs-8 ms-2 me-2">{{ $msg->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="p-4 rounded fw-bold fs-6 text-dark lh-base {{ $isCurrentUser ? 'bg-light-primary' : 'bg-light-secondary border border-gray-200' }}">
                                    {{ $msg->content }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-10 {{ $ticket->status !== 'closed' ? 'reply-dock' : '' }}">
                    @if($ticket->status !== 'closed')
                        <form action="{{ route('messages.store') }}" method="POST" class="d-flex flex-column flex-lg-column gap-3">
                            @csrf
                            <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                            <label for="reply-content" class="visually-hidden">اكتب ردك</label>
                            <div class="d-flex align-items-end gap-2">
                                <textarea id="reply-content" name="content" class="form-control form-control-solid" rows="2" placeholder="اكتب ردك هنا..." required></textarea>
                                <button type="submit" class="btn btn-primary btn-icon d-lg-none flex-shrink-0" style="width:48px;height:48px" aria-label="إرسال الرد">
                                    <i class="bi bi-send-fill fs-4" style="transform: scaleX(-1)" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="text-end d-none d-lg-block">
                                <button type="submit" class="btn btn-primary">إرسال الرد</button>
                            </div>
                        </form>
                    @else
                        <div class="bg-light-secondary p-8 rounded text-center border border-dashed">
                            <i class="bi bi-lock-fill fs-2x text-muted mb-3 d-block"></i>
                            <span class="text-gray-600 fw-bolder fs-5">تم إغلاق هذه التذكرة. لا يمكن إضافة ردود جديدة.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-5">
            <div class="card-header">
                <h3 class="card-title fw-bolder">معلومات التذكرة</h3>
            </div>
            <div class="card-body">
                <div class="mb-7">
                    <label class="fw-bold text-muted mb-1 d-block">الحالة الحالية:</label>
                    <span class="badge {{ $ticket->status_badge_class }} w-100 py-3 fs-6">{{ $ticket->status_label }}</span>
                </div>
                <div class="separator separator-dashed my-5"></div>
                <div class="d-flex flex-stack mb-3">
                    <span class="fw-bold text-gray-400">أنشئت في:</span>
                    <span class="fw-bolder text-gray-800 text-end">{{ $ticket->created_at->format('Y-m-d') }}</span>
                </div>
            </div>
        </div>
        @if($ticket->status !== 'closed')
            <x-attachment-manage :ticket="$ticket" />
        @endif

    </div>
</div>
@endsection

