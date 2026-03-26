<?php

return [
    'accountability_deadline_days' => env('CASH_ACCOUNTABILITY_DEADLINE_DAYS', 30),
    'default_block_new_request_when_open' => env('CASH_BLOCK_NEW_WHEN_OPEN', true),
    'fraud' => [
        'ocr_amount_tolerance' => env('CASH_FRAUD_OCR_AMOUNT_TOLERANCE', 5),
        'repeated_amount_window_hours' => env('CASH_FRAUD_REPEATED_WINDOW_HOURS', 24),
        'duplicate_document_window_days' => env('CASH_FRAUD_DUPLICATE_DOCUMENT_WINDOW_DAYS', 90),
    ],
];
