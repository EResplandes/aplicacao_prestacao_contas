<?php

namespace App\Data\CashRequest;

use App\Enums\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

readonly class ReleaseCashRequestData
{
    public function __construct(
        public float $amount,
        public PaymentMethod $paymentMethod,
        public ?string $accountReference,
        public ?string $referenceNumber,
        public Carbon $releasedAt,
        public ?string $notes = null,
        public ?UploadedFile $receipt = null,
    ) {}
}
