<?php

namespace App\Data\CashExpense;

use App\Enums\PaymentMethod;
use Carbon\Carbon;

readonly class CreateCashExpenseData
{
    public function __construct(
        public ?int $expenseCategoryId,
        public ?string $clientReferenceId,
        public Carbon $spentAt,
        public float $amount,
        public string $description,
        public ?string $vendorName = null,
        public ?PaymentMethod $paymentMethod = null,
        public ?string $documentNumber = null,
        public ?string $notes = null,
        public ?array $location = null,
        public array $attachments = [],
        public ?array $ocrRead = null,
    ) {}
}
