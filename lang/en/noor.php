<?php

return [
    'page_title'           => 'Import from Noor Excel file',
    'breadcrumb'           => 'Import from Noor',
    'upload_card_title'    => 'Upload Excel file from Noor',
    'upload_help'          => 'Please choose an Excel file exported from Noor system',
    'import_type_label'    => 'Data type',
    'import_type_choose'   => 'Choose import type',
    'file_label'           => 'Noor Excel file',
    'file_hint'            => 'Allowed formats: xlsx, xls, csv — max 20MB',
    'submit'               => 'Submit',

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

    'result_title'         => 'Import result',
    'result_total'         => 'Total rows',
    'result_created'       => 'Created',
    'result_updated'       => 'Updated',
    'result_failed'        => 'Failed',
    'result_errors_title'  => 'Failure reasons',
    'result_row'           => 'Row',
    'result_reason'        => 'Reason',
    'result_download_errors' => 'Download error log',
    'back_to_form'         => 'Import another file',

    'errors' => [
        'missing_library'  => 'Excel reader library is not installed on the server. The file was saved for manual processing.',
        'invalid_file'     => 'Invalid file. Must be Excel (xlsx, xls, csv).',
        'parse_failed'     => 'Could not read the file, make sure it was exported from Noor.',
        'empty_file'       => 'File has no rows.',
        'missing_id'       => 'National ID missing.',
        'no_school'        => 'No school is linked to your account to attach users to.',
    ],

    'status' => [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'completed'  => 'Completed',
        'failed'     => 'Failed',
    ],
];
