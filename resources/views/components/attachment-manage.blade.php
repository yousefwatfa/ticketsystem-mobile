@props(['ticket'])

<div class="card shadow-sm mb-5">
    <div class="card-header">
        <h3 class="card-title fw-bolder">المرفقات (<span id="attachments-count">{{ $ticket->attachments->count() }}</span>)</h3>
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-icon btn-light-primary" onclick="$('#file-input').click()">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-5">
        <div class="d-flex flex-column" id="attachments-container">
            @forelse($ticket->attachments as $file)
            <div class="d-flex align-items-center mb-4 border border-dashed p-3 rounded">
                <div class="symbol symbol-40px me-4">
                    @if(in_array(strtolower($file->file_type), ['jpg', 'jpeg', 'png', 'gif']))
                    <img src="{{ asset('storage/' . $file->file_path) }}" class="rounded" style="object-fit: cover; width: 40px; height: 40px;" />
                    @else
                    <span class="symbol-label bg-light">
                        @php
                        $iconClass = match(strtolower($file->file_type)) {
                        'pdf' => 'bi-file-earmark-pdf text-danger',
                        'xlsx', 'xls' => 'bi-file-earmark-excel text-success',
                        'docx', 'doc' => 'bi-file-earmark-word text-primary',
                        default => 'bi-file-earmark text-gray-500',
                        };
                        @endphp
                        <i class="bi {{ $iconClass }} fs-2"></i>
                    </span>
                    @endif
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="text-gray-800 text-hover-primary fw-bolder fs-7 d-block text-truncate">
                        {{ $file->file_name }}
                    </a>
                    <span class="text-muted fw-bold fs-8">{{ strtoupper($file->file_type) }}</span>
                </div>
                <div class="d-flex">
                    <a href="{{ asset('storage/' . $file->file_path) }}" class="btn btn-icon btn-sm btn-light me-2" download="{{ $file->file_name }}">
                        <i class="bi bi-download fs-6"></i>
                    </a>
                    <form action="{{ route('tickets.file_destroy', $file->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد؟')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-icon btn-sm btn-light-danger">
                            <i class="bi bi-x-lg fs-6"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div id="no-files-msg" class="text-center py-5">
                <i class="bi bi-folder2-open fs-2 text-gray-300 mb-2 d-block"></i>
                <span class="text-muted">لا توجد مرفقات</span>
            </div>
            @endforelse
        </div>

        <div id="upload-status-area" class="text-center mt-2">
            <input type="file" id="file-input" class="d-none">
            <span class="text-muted fs-7" id="status-text"></span>
        </div>
    </div>
</div>
@push('scripts')
<script>
    $(document).ready(function() {
        $('#file-input').on('change', function() {
            let file = this.files[0];
            if (!file) return;

            let formData = new FormData();

            formData.append('attachment', file);
            formData.append('ticket_id', '{{ $ticket->id }}');
            formData.append('_token', '{{ csrf_token() }}');


            $.ajax({
                url: "{{ route('tickets.add_attachment', $ticket->id) }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'نجاح!',
                            text: "تم رفع المرفق بنجاح",
                            showConfirmButton: false,
                            timer: 2000,
                            customClass: {
                                popup: 'border-radius-15'
                            }
                        });
                        let getFileIcon = function(type) {
                            type = type.toLowerCase();
                            if (['jpg', 'jpeg', 'png', 'gif'].includes(type)) {
                                return `<img src="${response.url}" class="rounded" style="object-fit: cover; width: 40px; height: 40px;" />`;
                            }

                            let iconClass = 'bi-file-earmark text-gray-500';
                            if (type === 'pdf') iconClass = 'bi-file-earmark-pdf text-danger';
                            else if (['xlsx', 'xls'].includes(type)) iconClass = 'bi-file-earmark-excel text-success';
                            else if (['docx', 'doc'].includes(type)) iconClass = 'bi-file-earmark-word text-primary';

                            return `<span class="symbol-label bg-light">
                                    <i class="bi ${iconClass} fs-2"></i>
                                </span>`;
                        };

                        let newFileHtml = `
                        <div class="d-flex align-items-center mb-4 border border-dashed p-3 rounded">
                            <div class="symbol symbol-40px me-4">
                                ${getFileIcon(response.file_type)}
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <a href="${response.url}" target="_blank" class="text-gray-800 text-hover-primary fw-bolder fs-7 d-block text-truncate">
                                    ${response.file_name}
                                </a>
                                <span class="text-muted fw-bold fs-8">${response.file_type.toUpperCase()}</span>
                            </div>
                            <div class="d-flex">
                                <a href="${response.url}" class="btn btn-icon btn-sm btn-light me-2" download="${response.file_name}">
                                    <i class="bi bi-download fs-6"></i>
                                </a>
                                <form action="/tickets/${response.id}/file-destroy" method="POST" onsubmit="return confirm('هل أنت متأكد؟')">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-icon btn-sm btn-light-danger">
                                        <i class="bi bi-x-lg fs-6"></i>
                                    </button>
                                </form>
                            </div>
                        </div>`;

                        $('#attachments-container').find('.text-center.py-5').remove();
                        $('#attachments-container').prepend(newFileHtml);
                        $('#empty-state').addClass('d-none');

                        let countElem = $('#attachments-count');
                        countElem.text(parseInt(countElem.text()) + 1);

                        $('#file-input').val('');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'حدث خطأ ما، يرجى المحاولة لاحقاً';

                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors)[0][0];
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'info',
                        title: 'تنبيه',
                        text: errorMessage,
                        showConfirmButton: true,
                        confirmButtonText: 'فهمت',
                        customClass: {
                            popup: 'border-radius-15',
                            confirmButton: 'btn btn-info'
                        }

                    });
                    $('#file-input').val('');
                }
            });
        });
    });
</script>
@endpush