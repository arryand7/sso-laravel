<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserImportRow extends Model
{
    protected $fillable = [
        'batch_id',
        'row_number',
        'identifier',
        'payload',
        'action',
        'status',
        'errors',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'errors' => 'array',
        ];
    }

    // ========== Relationships ==========

    public function batch()
    {
        return $this->belongsTo(UserImportBatch::class, 'batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========== Methods ==========

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isInvalid(): bool
    {
        return $this->status === 'invalid';
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }
}
