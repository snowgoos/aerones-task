<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_FAILED = 3;

    protected $guarded = ['id'];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING     => 'pending',
            self::STATUS_IN_PROGRESS => 'in_progress',
            self::STATUS_COMPLETED   => 'completed',
            self::STATUS_FAILED      => 'failed',
        ];
    }

    public function getStatusNameAttribute(): string
    {
        return self::statuses()[$this->status] ?? 'unknown';
    }
}
