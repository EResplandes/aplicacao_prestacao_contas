<?php

namespace App\Support;

use BackedEnum;
use Illuminate\Support\Str;

class AdminLabel
{
    public static function role(mixed $value): string
    {
        return self::translate($value, [
            'admin' => 'Administrador',
            'manager' => 'Gestor',
            'finance' => 'Financeiro',
            'requester' => 'Solicitante',
            'sem perfil' => 'Sem perfil',
        ]);
    }

    public static function cashRequestStatus(mixed $value): string
    {
        return self::translate($value, [
            'draft' => 'Rascunho',
            'submitted' => 'Enviada',
            'awaiting_manager_approval' => 'Aguardando aprovação do gestor',
            'manager_approved' => 'Aprovada pelo gestor',
            'manager_rejected' => 'Reprovada pelo gestor',
            'awaiting_financial_approval' => 'Aguardando aprovação do financeiro',
            'financial_approved' => 'Aprovada pelo financeiro',
            'financial_rejected' => 'Reprovada pelo financeiro',
            'released' => 'Liberada',
            'partially_accounted' => 'Prestação parcial',
            'fully_accounted' => 'Prestação concluída',
            'closed' => 'Fechada',
            'cancelled' => 'Cancelada',
        ]);
    }

    public static function cashExpenseStatus(mixed $value): string
    {
        return self::translate($value, [
            'pending' => 'Pendente',
            'submitted' => 'Enviado',
            'approved' => 'Aprovado',
            'rejected' => 'Reprovado',
            'flagged' => 'Sinalizado',
        ]);
    }

    public static function approvalStage(mixed $value): string
    {
        return self::translate($value, [
            'manager' => 'Gestor',
            'financial' => 'Financeiro',
        ]);
    }

    public static function approvalDecision(mixed $value): string
    {
        return self::translate($value, [
            'approved' => 'Aprovado',
            'rejected' => 'Reprovado',
            'adjustment_requested' => 'Ajuste solicitado',
        ]);
    }

    public static function paymentMethod(mixed $value): string
    {
        return self::translate($value, [
            'pix' => 'Pix',
            'bank_transfer' => 'Transferência bancária',
            'cash' => 'Dinheiro',
            'corporate_card' => 'Cartão corporativo',
            'other' => 'Outro',
        ]);
    }

    public static function statementEntryType(mixed $value): string
    {
        return self::translate($value, [
            'credit' => 'Crédito',
            'debit' => 'Débito',
            'adjustment' => 'Ajuste',
            'reversal' => 'Estorno',
        ]);
    }

    public static function fraudSeverity(mixed $value): string
    {
        return self::translate($value, [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'critical' => 'Crítica',
        ]);
    }

    public static function fraudAlertStatus(mixed $value): string
    {
        return self::translate($value, [
            'open' => 'Aberto',
            'under_review' => 'Em análise',
            'resolved' => 'Resolvido',
            'dismissed' => 'Descartado',
        ]);
    }

    public static function securityEventType(mixed $value): string
    {
        return self::translate($value, [
            'login_failed' => 'Falha de login',
            'login_lockout' => 'Bloqueio por força bruta',
            'rate_limited' => 'Limite de requisições excedido',
            'untrusted_origin_blocked' => 'Origem não confiável bloqueada',
            'suspicious_probe' => 'Sondagem suspeita',
        ]);
    }

    public static function securityChannel(mixed $value): string
    {
        return self::translate($value, [
            'web' => 'Painel web',
            'api' => 'API',
        ]);
    }

    public static function securitySeverity(mixed $value): string
    {
        return self::translate($value, [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'critical' => 'Crítica',
        ]);
    }

    public static function scopeType(mixed $value): string
    {
        return self::translate($value, [
            'user' => 'Usuário',
            'department' => 'Departamento',
            'cost_center' => 'Centro de custo',
            'company' => 'Empresa',
        ]);
    }

    public static function auditEvent(mixed $value): string
    {
        return self::translate($value, [
            'cash_request.created' => 'Solicitação de caixa criada',
            'cash_request.decision' => 'Decisão da solicitação de caixa',
            'cash_request.released' => 'Liberação de caixa',
            'cash_request.rejection_response' => 'Resposta à reprovação do caixa',
            'cash_expense.created' => 'Gasto lançado',
            'user_payout_account' => 'Cadastro de conta de recebimento',
            'approval_rule' => 'Regra de aprovação',
            'company' => 'Empresa',
            'cash_limit_rule' => 'Regra de limite de caixa',
            'department' => 'Departamento',
            'expense_category' => 'Categoria de despesa',
            'user' => 'Usuário',
            'cost_center' => 'Centro de custo',
            'rejection_reason' => 'Motivo de reprovação',
            'fraud_rule_setting' => 'Regra de fraude',
            'auth.login' => 'Login',
            'auth.refresh' => 'Renovação de sessão',
            'auth.logout' => 'Logout',
        ]);
    }

    public static function auditAction(mixed $value): string
    {
        return self::translate($value, [
            'create' => 'Cadastro',
            'update' => 'Atualização',
            'approved' => 'Aprovação',
            'rejected' => 'Reprovação',
            'release' => 'Liberação',
            'respond' => 'Resposta',
            'login' => 'Entrou',
            'refresh' => 'Renovou sessão',
            'logout' => 'Saiu',
        ]);
    }

    private static function translate(mixed $value, array $map): string
    {
        $normalized = self::normalize($value);

        if ($normalized === null || $normalized === '') {
            return '-';
        }

        return $map[$normalized]
            ?? Str::of($normalized)
                ->replace(['_', '.'], ' ')
                ->headline()
                ->toString();
    }

    private static function normalize(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        return null;
    }
}
