<?php

return [
    'page_title'        => 'Import Questions from Excel',
    'breadcrumb'        => 'Import from Excel',
    'upload_card_title' => 'Import Questions from Excel File',
    'download_template' => 'Download Excel Template',
    'upload_help'       => 'Download the template, fill in your questions, then upload the file to preview before executing.',
    'file_label'        => 'Excel File',
    'file_hint'         => 'Accepted formats: .xlsx, .xls, .csv — Max size: 10 MB',
    'read_file'         => 'Read File & Preview',

    'columns_title' => 'Template Columns',
    'col_name'      => 'Column',
    'col_required'  => 'Required',
    'col_notes'     => 'Notes',

    'col_notes_code'        => 'Reference code for the question',
    'col_notes_text'        => 'Skipped if full-image question with a code',
    'col_notes_options'     => 'For MCQ questions only',
    'col_notes_explanation' => 'Answer explanation (optional)',
    'col_notes_grade'       => 'Grade level (number)',
    'col_notes_semester'    => 'Semester (number)',

    'history_title'    => 'Import History',
    'history_filename' => 'Filename',
    'history_status'   => 'Status',
    'history_total'    => 'Total',
    'history_imported' => 'Imported',
    'history_failed'   => 'Failed',
    'history_date'     => 'Date',

    'status' => [
        'pending'   => 'Pending',
        'previewed' => 'Previewed',
        'completed' => 'Completed',
        'failed'    => 'Failed',
    ],

    'preview' => [
        'title'          => 'Import Preview',
        'summary_valid'  => 'Valid',
        'summary_invalid'=> 'With Errors',
        'summary_total'  => 'Total',
        'summary_bank'   => 'Bank',
        'col_row'        => 'Row',
        'col_status'     => 'Status',
        'col_type'       => 'Type',
        'col_text'       => 'Question Text',
        'col_difficulty' => 'Difficulty',
        'col_errors'     => 'Errors',
        'back'           => 'Back',
        'execute'        => 'Execute Import',
        'nothing_valid'  => 'No valid rows to import',
        'status' => [
            'valid'   => 'Valid',
            'invalid' => 'Invalid',
        ],
    ],

    'result' => [
        'title'            => 'Import Result',
        'success'          => 'Questions imported successfully.',
        'partial'          => 'Import completed with some errors. Review the failed rows.',
        'total'            => 'Total',
        'imported'         => 'Imported',
        'failed'           => 'Failed',
        'download_errors'  => 'Download Errors File',
        'new_import'       => 'New Import',
        'back_to_questions'=> 'Back to Questions',
        'col_row'          => 'Row',
        'col_errors'       => 'Errors',
        'col_raw'          => 'Raw Data',
    ],

    'errors' => [
        'invalid_file'    => 'Unsupported file format. Use .xlsx, .xls or .csv',
        'bad_format'      => 'Wrong file format. Please use the official template.',
        'parse_failed'    => 'Failed to read the file.',
        'missing_library' => 'PhpSpreadsheet library is not installed.',
        'empty_file'      => 'The file is empty or contains no data.',
        'batch_missing'   => 'Import batch record not found.',
        'no_preview'      => 'No preview data found. Please re-upload the file.',
    ],

    'validation' => [
        'invalid_type'          => 'Invalid question type: :value',
        'invalid_content_type'  => 'Invalid content type: :value',
        'missing_text'          => 'Question text is required.',
        'invalid_difficulty'    => 'Invalid difficulty level: :value',
        'missing_correct_answer'=> 'Correct answer is required for this question type.',
    ],
];
