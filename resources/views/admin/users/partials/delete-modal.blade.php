{{-- Shared password-confirm delete modal for user lists. Any button with class
     `js-user-delete` (carrying data-url = the DELETE route and data-name = the
     user's name) opens it; the controller's destroy() verifies confirm_password
     against the acting admin's own password before deleting. --}}
<div class="modal fade" id="userDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="userDeleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title mb-0">
                        <x-svg-icon name="exclamation-triangle-fill" :size="18" class="ic-danger" /> @lang('users.delete_confirm_title')
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p id="userDeleteBody" class="mb-3"></p>
                    <label class="font-weight-bold" for="userDeletePassword">@lang('users.delete_password_label')</label>
                    <input type="password" name="confirm_password" id="userDeletePassword" class="form-control" autocomplete="current-password" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('users.delete_cancel')</button>
                    <button type="submit" class="btn btn-danger">
                        <x-svg-icon name="trash3-fill" :size="16" /> @lang('users.delete_confirm_btn')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var bodyTpl = @json(__('users.delete_confirm_body', ['name' => '__NAME__']));
    document.querySelectorAll('.js-user-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('userDeleteForm').setAttribute('action', btn.dataset.url);
            document.getElementById('userDeleteBody').textContent = bodyTpl.replace('__NAME__', btn.dataset.name || '');
            document.getElementById('userDeletePassword').value = '';
            if (window.jQuery) jQuery('#userDeleteModal').modal('show');
        });
    });
})();
</script>
@endpush
