<?php

return [
    'page_title'           => 'Import Noor data',
    'breadcrumb'           => 'Import from Noor',
    'upload_card_title'    => 'Upload Excel file from Noor',
    'upload_help'          => 'Choose the school and academic year, upload the Excel file exported from Noor, then click "Read file" to preview the data before saving.',

    'school_label'         => 'School',
    'school_choose'        => 'Choose school',
    'year_label'           => 'Academic year',
    'year_choose'          => 'Choose academic year',
    'year_current'         => 'current',

    'import_type_label'    => 'Data type',
    'import_type_choose'   => 'Choose import type',
    'file_label'           => 'Noor Excel file',
    'file_hint'            => 'Allowed formats: xlsx, xls, csv — max 20MB',
    'read_file'            => 'Read file',
    'submit'               => 'Submit',
    'execute'              => 'Run import',
    'back_to_form'         => 'Import another file',

    'types' => [
        'students'           => 'Students',
        'students_academic'  => 'Update academic number',
        'teachers'           => 'Teachers',
        'admins'             => 'Administrators',
    ],

    'instructions_title'   => 'How to export the file from Noor',
    'instructions_students_title' => 'Importing students',
    'instructions_students' => [
        'Reports → Student Reports → Course Students Data → View → Export Excel',
        'Or from: Reports → Lists → Permanent Records',
        'Note: students from mixed schools will be imported as male; they can be edited later.',
    ],
    'instructions_teachers_title' => 'Importing teachers & administrators',
    'instructions_teachers' => [
        'Reports → Statistical Reports → Job Holders Data',
        'Pick user type (Teacher) → View → Export Excel',
    ],
    'instructions_note'    => 'The file must keep Noor headers unchanged.',

    'preview' => [
        'title'        => 'Preview data before saving',
        'table_title'  => 'File rows',
        'col_status'   => 'Status',
        'col_name'     => 'Name',
        'col_id'       => 'National ID',
        'col_grade'    => 'Grade',
        'col_class'    => 'Class',
        'col_parent'   => 'Guardian',
        'col_parent_id'=> 'Guardian ID',
        'reason_duplicate' => 'Duplicate within the same file',
        'nothing_to_import' => 'No valid rows to import.',
        'status' => [
            'new'       => 'New',
            'update'    => 'Update',
            'duplicate' => 'Duplicate',
            'invalid'   => 'Invalid',
        ],
    ],

    'result_title'         => 'Import result',
    'result_total'         => 'Total rows',
    'result_created'       => 'Created',
    'result_updated'       => 'Updated',
    'result_failed'        => 'Not imported',
    'result_errors_title'  => 'Failure reasons',
    'result_row'           => 'Row',
    'result_reason'        => 'Reason',
    'result_download_errors' => 'Download error report',
    'result_parents'       => 'Guardians: created :created, updated :updated, and linked to students.',

    'history_title'        => 'Previous import history',
    'history_file'         => 'File',
    'history_type'         => 'Type',
    'history_status'       => 'Status',
    'history_date'         => 'Date',
    'history_empty'        => 'No previous imports.',

    'errors' => [
        'missing_library'  => 'Excel reader library is not installed on the server. The file was saved for manual processing.',
        'invalid_file'     => 'Invalid file. Must be Excel (xlsx, xls, csv).',
        'parse_failed'     => 'Could not read the file, make sure it was exported from Noor.',
        'empty_file'       => 'File has no rows.',
        'missing_id'       => 'National ID missing.',
        'no_school'        => 'You must choose a school to attach users to.',
        'log_missing'      => 'Import operation not found.',
        'no_preview'       => 'No saved preview data for this operation, please upload the file again.',
    ],

    'status' => [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'previewed'  => 'Previewed',
        'completed'  => 'Completed',
        'failed'     => 'Failed',
    ],
];
