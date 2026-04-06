<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessingState extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'last_processed_line',
        'last_processed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_processed_line' => 'integer',
            'last_processed_at' => 'datetime',
        ];
    }
}
