<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class RejectionReason extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'code',
        'name',
        'applies_to',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
