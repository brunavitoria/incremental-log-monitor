<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Log extends Model
{
    use SoftDeletes;

    public const CREATED_AT = 'processed_at';

    protected $fillable = [
        'consumer_id',
        'service_name',
        'latencies_proxy',
        'latencies_gateway',
        'latencies_request',
        'created_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'latencies_proxy' => 'integer',
            'latencies_gateway' => 'integer',
            'latencies_request' => 'integer',
            'created_at' => 'datetime',
            'processed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeForConsumer(Builder $query, string $consumerId): Builder
    {
        return $query->where('consumer_id', $consumerId);
    }

    public function scopeForService(Builder $query, string $serviceName): Builder
    {
        return $query->where('service_name', $serviceName);
    }
}
