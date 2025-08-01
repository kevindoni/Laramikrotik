<?php

namespace App\Models;

use App\Services\MikrotikService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class PppProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'local_address',
        'remote_address',
        'rate_limit',
        'parent_queue',
        'only_one',
        'description',
        'price',
        'billing_cycle_day',
        'billing_period',
        'is_active',
        'mikrotik_id',
        'auto_sync',
    ];

    protected $casts = [
        'only_one' => 'boolean',
        'is_active' => 'boolean',
        'auto_sync' => 'boolean',
        'price' => 'decimal:2',
    ];

    protected $attributes = [
        'auto_sync' => true,
    ];

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-sync to MikroTik when profile is created
        static::created(function ($profile) {
            if ($profile->auto_sync && $profile->shouldSyncToMikrotik()) {
                try {
                    $mikrotikService = new MikrotikService();
                    $mikrotikService->pushPppProfile($profile);
                    
                    logger()->info('PPP profile auto-synced to MikroTik after creation', [
                        'profile_id' => $profile->id,
                        'profile_name' => $profile->name
                    ]);
                } catch (Exception $e) {
                    logger()->error('Failed to auto-sync PPP profile to MikroTik after creation', [
                        'profile_id' => $profile->id,
                        'profile_name' => $profile->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });

        // Auto-sync to MikroTik when profile is updated
        static::updated(function ($profile) {
            if ($profile->auto_sync && $profile->shouldSyncToMikrotik() && $profile->wasChanged()) {
                try {
                    $mikrotikService = new MikrotikService();
                    $mikrotikService->pushPppProfile($profile);
                    
                    logger()->info('PPP profile auto-synced to MikroTik after update', [
                        'profile_id' => $profile->id,
                        'profile_name' => $profile->name,
                        'changed_fields' => array_keys($profile->getChanges())
                    ]);
                } catch (Exception $e) {
                    logger()->error('Failed to auto-sync PPP profile to MikroTik after update', [
                        'profile_id' => $profile->id,
                        'profile_name' => $profile->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });

        // Auto-sync to MikroTik when profile is deleted
        static::deleting(function ($profile) {
            if ($profile->auto_sync && $profile->shouldSyncToMikrotik() && $profile->mikrotik_id) {
                try {
                    $mikrotikService = new MikrotikService();
                    $mikrotikService->deletePppProfile($profile->name, $profile->mikrotik_id);
                    
                    logger()->info('PPP profile auto-synced deletion to MikroTik', [
                        'profile_id' => $profile->id,
                        'profile_name' => $profile->name
                    ]);
                } catch (Exception $e) {
                    logger()->error('Failed to auto-sync PPP profile deletion to MikroTik', [
                        'profile_id' => $profile->id,
                        'profile_name' => $profile->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
    }

    /**
     * Check if this profile should be synced to MikroTik.
     */
    protected function shouldSyncToMikrotik()
    {
        try {
            $activeSetting = MikrotikSetting::getActive();
            return $activeSetting && $activeSetting->getConnectionStatus() === 'connected';
        } catch (Exception $e) {
            logger()->warning('Could not check MikroTik connection status for sync', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all PPP secrets using this profile.
     */
    public function pppSecrets()
    {
        return $this->hasMany(PppSecret::class);
    }

    /**
     * Get active PPP secrets using this profile.
     */
    public function activePppSecrets()
    {
        return $this->pppSecrets()->where('is_active', true);
    }

    /**
     * Get the download and upload speeds from rate_limit.
     */
    public function getSpeedsAttribute()
    {
        if (empty($this->rate_limit)) {
            return ['download' => null, 'upload' => null];
        }

        $parts = explode('/', $this->rate_limit);
        return [
            'download' => $parts[0] ?? null,
            'upload' => $parts[1] ?? null,
        ];
    }

    /**
     * Set the rate_limit from download and upload speeds.
     */
    public function setSpeeds($download, $upload)
    {
        $this->rate_limit = $download . '/' . $upload;
        return $this;
    }

    /**
     * Format the rate limit for display.
     */
    public function getFormattedRateLimitAttribute()
    {
        if (empty($this->rate_limit)) {
            return 'Unlimited';
        }

        $speeds = $this->speeds;
        return $this->formatSpeed($speeds['download']) . ' / ' . $this->formatSpeed($speeds['upload']);
    }

    /**
     * Format speed value with appropriate units.
     */
    protected function formatSpeed($speed)
    {
        if (empty($speed)) {
            return 'Unlimited';
        }

        // Check if speed already has a unit
        if (preg_match('/[a-zA-Z]/', $speed)) {
            return $speed;
        }

        // Convert to appropriate unit
        $speed = (int) $speed;
        if ($speed >= 1000000) {
            return round($speed / 1000000, 2) . ' Gbps';
        } elseif ($speed >= 1000) {
            return round($speed / 1000, 2) . ' Mbps';
        } else {
            return $speed . ' Kbps';
        }
    }

    /**
     * Get the next billing date for this profile.
     */
    public function getNextBillingDate($fromDate = null)
    {
        if (!$this->billing_cycle_day) {
            return null;
        }

        $fromDate = $fromDate ? \Carbon\Carbon::parse($fromDate) : \Carbon\Carbon::now();
        
        // If we're past this month's billing day, next billing is next month
        if ($fromDate->day > $this->billing_cycle_day) {
            return $fromDate->copy()->addMonth()->day($this->billing_cycle_day);
        } else {
            return $fromDate->copy()->day($this->billing_cycle_day);
        }
    }

    /**
     * Get formatted billing cycle information.
     */
    public function getBillingCycleTextAttribute()
    {
        if (!$this->billing_cycle_day) {
            return 'No billing cycle set';
        }

        return "Monthly on day {$this->billing_cycle_day}";
    }
}