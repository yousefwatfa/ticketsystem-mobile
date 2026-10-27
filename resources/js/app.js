import './bootstrap';
import $ from 'jquery';
window.$ = window.jQuery = $;

import select2 from 'select2';
select2(window, $);

import Swal from 'sweetalert2';
window.Swal = Swal;

import './clock';
import './attachments';
import './push';
import 'laravel-datatables-vite';

$(document).ready(function() {
    $('[data-control="select2"]').select2({
        width: '100%'
    });

    function checkRole() {
        const roleSelect = $('#role_select');
        if (!roleSelect.length) return;

        const selectedRole = roleSelect.find('option:selected').text().trim().toLowerCase();

        if (selectedRole === 'admin' || selectedRole === 'viewer') {
            $('#department_wrapper').fadeIn();
        } else {
            $('#department_wrapper').fadeOut();
            $('#department_wrapper select').val(null).trigger('change');
        }
    }

    checkRole();

    $(document).on('change', '#role_select', function() {
        checkRole();
    });

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        let form = $(this).closest('form');

        Swal.fire({
            title: "هل أنت متأكد؟",
            text: "لن تتمكن من استعادة هذا العنصر بعد الحذف!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#f1416c",
            cancelButtonColor: "#808080",
            confirmButtonText: "نعم، احذف!",
            cancelButtonText: "إلغاء",
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    $(document).on('click', '.finish-ticket-btn', function (e) {
        e.preventDefault();
        let ticketId = $(this).data('id');
        let form = $('#finish-ticket-form-' + ticketId);

        Swal.fire({
            title: 'هل تم حل المشكلة فعلاً؟',
            text: "عند تأكيد الحل، سيتم إغلاق التذكرة نهائياً!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، تم الحل',
            cancelButtonText: 'إلغاء',
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'جاري الإغلاق...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                form.submit();
            }
        });
    });




});
