{{-- Shared add/edit modal for one generic portfolio entry (title/body/date/attachment).
     Filled via jQuery by .js-add-entry / .js-edit-entry buttons — see bottom script.
     Included from admin/portfolio/show.blade.php and teacher/portfolio.blade.php. --}}
<div class="modal fade" id="portfolioEntryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="portfolioEntryForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="entryMethodField" value="POST">
            <input type="hidden" name="section" id="entrySectionField">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="entryModalTitle">إضافة عنصر</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="إغلاق"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>العنوان</label>
                        <input type="text" name="title" id="entryTitleField" class="form-control" maxlength="255" required>
                    </div>
                    <div class="form-group">
                        <label>الوصف</label>
                        <textarea name="body" id="entryBodyField" class="form-control" rows="4" maxlength="5000"></textarea>
                    </div>
                    <div class="form-group">
                        <label>التاريخ</label>
                        <input type="date" name="entry_date" id="entryDateField" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>مرفق (اختياري)</label>
                        <input type="file" name="attachment" class="form-control-file">
                        <small id="entryCurrentAttachment" class="form-text text-muted"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $('.js-add-entry').on('click', function () {
        $('#entryModalTitle').text('إضافة عنصر');
        $('#portfolioEntryForm').attr('action', $(this).data('store-url'));
        $('#entryMethodField').val('POST');
        $('#entrySectionField').val($(this).data('section'));
        $('#entryTitleField').val('');
        $('#entryBodyField').val('');
        $('#entryDateField').val('');
        $('#entryCurrentAttachment').text('');
        $('#portfolioEntryModal').modal('show');
    });

    $('.js-edit-entry').on('click', function () {
        var d = $(this).data();
        $('#entryModalTitle').text('تعديل عنصر');
        $('#portfolioEntryForm').attr('action', d.updateUrl);
        $('#entryMethodField').val('PUT');
        $('#entrySectionField').val(d.section);
        $('#entryTitleField').val(d.title);
        $('#entryBodyField').val(d.body);
        $('#entryDateField').val(d.date);
        $('#entryCurrentAttachment').text(d.attachmentName ? ('المرفق الحالي: ' + d.attachmentName) : '');
        $('#portfolioEntryModal').modal('show');
    });
});
</script>
@endpush
