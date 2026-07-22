<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasFactory;

    public const ACCESS_MODE_ONLINE = 'online';

    public const ACCESS_MODE_ONSITE_RADIUS = 'onsite_radius';

    protected $fillable = [
        'title',
        'module_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'access_mode',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'duration_minutes' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function isOnsiteRadiusOnly(): bool
    {
        return $this->access_mode === self::ACCESS_MODE_ONSITE_RADIUS;
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'session_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'session_id');
    }

    public function examTokens(): HasMany
    {
        return $this->hasMany(ExamToken::class, 'session_id');
    }
}
