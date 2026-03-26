<?php

namespace App\Enums;

enum CashRequestStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case AWAITING_MANAGER_APPROVAL = 'awaiting_manager_approval';
    case MANAGER_APPROVED = 'manager_approved';
    case MANAGER_REJECTED = 'manager_rejected';
    case AWAITING_FINANCIAL_APPROVAL = 'awaiting_financial_approval';
    case FINANCIAL_APPROVED = 'financial_approved';
    case FINANCIAL_REJECTED = 'financial_rejected';
    case RELEASED = 'released';
    case PARTIALLY_ACCOUNTED = 'partially_accounted';
    case FULLY_ACCOUNTED = 'fully_accounted';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function isOpen(): bool
    {
        return in_array($this, [
            self::SUBMITTED,
            self::AWAITING_MANAGER_APPROVAL,
            self::MANAGER_APPROVED,
            self::AWAITING_FINANCIAL_APPROVAL,
            self::FINANCIAL_APPROVED,
            self::RELEASED,
            self::PARTIALLY_ACCOUNTED,
            self::FULLY_ACCOUNTED,
        ], true);
    }

    public function allowsExpenses(): bool
    {
        return in_array($this, [self::RELEASED, self::PARTIALLY_ACCOUNTED], true);
    }

    /**
     * @return list<string>
     */
    public static function openValues(): array
    {
        return array_map(
            static fn (self $status) => $status->value,
            array_filter(self::cases(), static fn (self $status) => $status->isOpen()),
        );
    }
}
