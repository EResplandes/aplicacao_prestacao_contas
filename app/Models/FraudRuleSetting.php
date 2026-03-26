<?php

namespace App\Models;

use App\Enums\FraudSeverity;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class FraudRuleSetting extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'code',
        'name',
        'severity',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'severity' => FraudSeverity::class,
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }
}
