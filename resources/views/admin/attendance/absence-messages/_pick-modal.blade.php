{{-- تحديد الطلاب — absent-only table (checkbox col + select-all + status column
     that must ONLY ever show غائب; the JS builds every row from previewAbsent()
     which is already scoped to status=absent, period IS NULL). --}}
<div class="modal fade" id="pickStudentsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تحديد الطلاب الغائبين</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="pickCheckAll"></th>
                                <th>الطالب</th>
                                <th>رقم الهوية</th>
                                <th>الفصل</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="pickSendBtn"><x-svg-icon name="send" /> إرسال للمحددين</button>
            </div>
        </div>
    </div>
</div>
