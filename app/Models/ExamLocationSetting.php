<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamLocationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_latitude',
        'center_longitude',
        'allowed_radius_meters',
    ];

    protected function casts(): array
    {
        return [
            'center_latitude' => 'float',
            'center_longitude' => 'float',
            'allowed_radius_meters' => 'integer',
        ];
    }
}
