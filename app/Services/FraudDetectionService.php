<?php

namespace App\Services;

use App\Enums\FraudAlertStatus;
use App\Enums\FraudSeverity;
use App\Models\Attachment;
use App\Models\CashExpense;
use App\Models\ExpenseOcrRead;
use App\Models\FraudAlert;
use App\Models\FraudRuleSetting;
use Illuminate\Support\Collection;

class FraudDetectionService
{
    public function inspectExpense(CashExpense $expense, ?ExpenseOcrRead $ocrRead = null): Collection
    {
        $alerts = collect();

        if ($expense->document_number && $this->isDuplicateDocument($expense)) {
            $alerts->push($this->openAlert($expense, 'duplicate_document', 'Documento duplicado', 'Documento ja utilizado em outro lancamento.'));
        }

        if ($this->hasRepeatedAmount($expense)) {
            $alerts->push($this->openAlert($expense, 'repeated_amount_short_window', 'Mesmo valor em curto periodo', 'Mesmo valor registrado em curto intervalo de tempo.'));
        }

        if ($ocrRead && $ocrRead->parsed_amount !== null && abs((float) $ocrRead->parsed_amount - (float) $expense->amount) > (float) config('cash_management.fraud.ocr_amount_tolerance')) {
            $alerts->push($this->openAlert($expense, 'ocr_amount_divergence', 'Divergencia entre OCR e valor informado', 'Valor identificado pelo OCR difere do valor digitado.'));
        }

        if ($this->hasDuplicateAttachmentHash($expense)) {
            $alerts->push($this->openAlert($expense, 'duplicate_attachment_hash', 'Anexo duplicado', 'Foi identificado um comprovante muito parecido com outro ja enviado.'));
        }

        if ($this->isOutOfAllowedWindow($expense)) {
            $alerts->push($this->openAlert($expense, 'out_of_allowed_window', 'Gasto fora da janela esperada', 'A data da despesa foge da janela prevista para este caixa.'));
        }

        return $alerts->filter();
    }

    private function isDuplicateDocument(CashExpense $expense): bool
    {
        return CashExpense::query()
            ->where('user_id', $expense->user_id)
            ->where('document_number', $expense->document_number)
            ->whereKeyNot($expense->getKey())
            ->where('created_at', '>=', now()->subDays((int) config('cash_management.fraud.duplicate_document_window_days')))
            ->exists();
    }

    private function hasRepeatedAmount(CashExpense $expense): bool
    {
        return CashExpense::query()
            ->where('user_id', $expense->user_id)
            ->where('amount', $expense->amount)
            ->whereKeyNot($expense->getKey())
            ->where('created_at', '>=', now()->subHours((int) config('cash_management.fraud.repeated_amount_window_hours')))
            ->exists();
    }

    private function hasDuplicateAttachmentHash(CashExpense $expense): bool
    {
        $hashes = $expense->attachments->pluck('sha256')->filter();

        if ($hashes->isEmpty()) {
            return false;
        }

        return Attachment::query()
            ->whereIn('sha256', $hashes)
            ->where('attachable_type', $expense->getMorphClass())
            ->where('attachable_id', '!=', $expense->getKey())
            ->exists();
    }

    private function isOutOfAllowedWindow(CashExpense $expense): bool
    {
        $cashRequest = $expense->cashRequest;
        $plannedUseDate = $cashRequest->planned_use_date;
        $dueDate = $cashRequest->due_accountability_at;

        if (! $plannedUseDate || ! $dueDate) {
            return false;
        }

        return $expense->spent_at->lt($plannedUseDate->startOfDay()->subDay())
            || $expense->spent_at->gt($dueDate);
    }

    private function openAlert(CashExpense $expense, string $ruleCode, string $title, string $description): ?FraudAlert
    {
        $rule = FraudRuleSetting::query()->where('code', $ruleCode)->first();

        if ($rule && ! $rule->is_active) {
            return null;
        }

        return FraudAlert::query()->firstOrCreate(
            [
                'cash_expense_id' => $expense->id,
                'rule_code' => $ruleCode,
            ],
            [
                'cash_request_id' => $expense->cash_request_id,
                'status' => FraudAlertStatus::OPEN,
                'severity' => $rule?->severity ?? FraudSeverity::MEDIUM,
                'title' => $title,
                'description' => $description,
                'detected_at' => now(),
                'metadata' => [],
            ],
        );
    }
}
