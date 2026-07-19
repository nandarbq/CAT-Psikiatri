<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'nik',
        'username',
        'password',
        'code',
        'name',
        'birth_place',
        'birth_date',
        'gender',
        'phone',
        'address',
        'purpose',
        'login_token',
        'is_finished',
    ];

    protected $hidden = [
        'password',
        'code',
        'login_token',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_finished' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'participant_id');
    }
}
