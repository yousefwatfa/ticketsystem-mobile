@props(['editId' => '', 'title' => '', 'action' => ''])

<div class="modal fade" id="{{$editId}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-sm">
            <div class="modal-header border-0 pb-0">
                <h3 class="fw-bolder text-dark">{{$title}}</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal">
                    <i class="bi bi-x fs-1"></i>
                </div>
            </div>

            <form action="{{$action}}" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-body py-5">
                    {{ $slot }}
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-9">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>
