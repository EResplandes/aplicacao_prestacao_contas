<?php

namespace App\Data\CashRequest;

use App\Enums\RequestSource;
use Carbon\Carbon;

readonly class CreateCashRequestData
{
    public function __construct(
        public float $requestedAmount,
        public string $purpose,
        public string $justification,
        public ?int $departmentId,
        public ?int $costCenterId,
        public Carbon $plannedUseDate,
        public ?string $notes,
        public RequestSource $source,
        public ?string $clientReferenceId,
        public array $attachments = [],
    ) {}
}
