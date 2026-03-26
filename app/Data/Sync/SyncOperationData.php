<?php

namespace App\Data\Sync;

readonly class SyncOperationData
{
    public function __construct(
        public string $operationUuid,
        public string $type,
        public array $payload,
    ) {}
}
