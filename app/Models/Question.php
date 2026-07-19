<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'category_id',
        'question',
        'order_no',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ParticipantAnswer::class, 'question_id');
    }

    public function masterCategory(): BelongsTo
    {
        return $this->belongsTo(MasterCategory::class, 'category_id');
    }
}
