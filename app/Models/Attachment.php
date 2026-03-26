<?php

namespace App\Models;

use App\Enums\AttachmentType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'attachable_type',
        'attachable_id',
        'type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'sha256',
        'uploaded_by_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => AttachmentType::class,
            'size_bytes' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
