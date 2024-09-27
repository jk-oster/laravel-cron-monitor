<?php

namespace JkOster\CronMonitor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JkOster\CronMonitor\Models\Enums\CronMonitorStatus;
use JkOster\CronMonitor\Models\Presenters\CronMonitorPresenter;
use JkOster\CronMonitor\Models\Traits\SupportsCronHealthCheck;

class CronMonitor extends Model
{
    use CronMonitorPresenter;
    use HasFactory;
    use SupportsCronHealthCheck;

    protected $fillable = [
        'name',
        'description',
        'enabled',
        'public',
        'hash',
        'status',
        'frequency_in_minutes',
        'cron_expression',
        'next_due_date',
        'grace_period_in_minutes',
        'last_check_date',
        'last_check_failed_date',
        'failure_reason',
        'status_last_change_date',
        'last_ping_status',
        'timezone',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'public' => 'boolean',
        'last_check_date' => 'datetime',
        'last_check_failed_date' => 'datetime',
        'status_last_change_date' => 'datetime',
        'next_due_date' => 'datetime',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function isHealthy(): bool
    {
        if ($this->enabled && in_array($this->status, [CronMonitorStatus::DOWN, CronMonitorStatus::UNKNOWN])) {
            return false;
        }

        return true;
    }

    /**
     *  Setup model event hooks
     */
    public static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            $model->hash = (string) Str::uuid();
        });
    }
}
