<?php

namespace App\Models;

use App\Services\MikrotikService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class PppSecret extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'ppp_profile_id',
        'username',
        'password',
        'service',
        'remote_address',
        'local_address',
        'mikrotik_id',
        'is_active',
        'comment',
        'installation_date',
        'due_date',
        'auto_sync',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_sync' => 'boolean',
        'installation_date' => 'date',
        'due_date' => 'date',
    ];

    protected $attributes = [
        'auto_sync' => true,
        'service' => 'pppoe',
    ];

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-sync to MikroTik when secret is created
        static::created(function ($secret) {
            if ($secret->auto_sync && $secret->shouldSyncToMikrotik()) {
                try {
                    $mikrotikService = new MikrotikService();
                    $mikrotikService->pushPppSecret($secret);
                    
                    logger()->info('PPP secret auto-synced to MikroTik after creation', [
                        'secret_id' => $secret->id,
                        'username' => $secret->username
                    ]);
                } catch (Exception $e) {
                    logger()->error('Failed to auto-sync PPP secret to MikroTik after creation', [
                        'secret_id' => $secret->id,
                        'username' => $secret->username,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });

        // Auto-sync to MikroTik when secret is updated
        static::updated(function ($secret) {
            if ($secret->auto_sync && $secret->shouldSyncToMikrotik() && $secret->wasChanged()) {
                try {
                    $mikrotikService = new MikrotikService();
                    $mikrotikService->pushPppSecret($secret);
                    
                    logger()->info('PPP secret auto-synced to MikroTik after update', [
                        'secret_id' => $secret->id,
                        'username' => $secret->username,
                        'changed_fields' => array_keys($secret->getChanges())
                    ]);
                } catch (Exception $e) {
                    logger()->error('Failed to auto-sync PPP secret to MikroTik after update', [
                        'secret_id' => $secret->id,
                        'username' => $secret->username,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });

        // Auto-sync to MikroTik when secret is deleted
        static::deleting(function ($secret) {
            if ($secret->auto_sync && $secret->shouldSyncToMikrotik() && $secret->mikrotik_id) {
                try {
                    $mikrotikService = new MikrotikService();
                    $mikrotikService->deletePppSecret($secret);
                    
                    logger()->info('PPP secret auto-synced deletion to MikroTik', [
                        'secret_id' => $secret->id,
                        'username' => $secret->username
                    ]);
                } catch (Exception $e) {
                    logger()->error('Failed to auto-sync PPP secret deletion to MikroTik', [
                        'secret_id' => $secret->id,
                        'username' => $secret->username,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
    }

    /**
     * Check if this secret should be synced to MikroTik.
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
     * Get the customer that owns the PPP secret.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the profile that the PPP secret uses.
     */
    public function pppProfile()
    {
        return $this->belongsTo(PppProfile::class);
    }

    /**
     * Get all invoices for the PPP secret.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all usage logs for the PPP secret.
     */
    public function usageLogs()
    {
        return $this->hasMany(UsageLog::class);
    }

    /**
     * Get the latest usage log for the PPP secret.
     */
    public function latestUsageLog()
    {
        return $this->hasOne(UsageLog::class)->latest();
    }

    /**
     * Get unpaid invoices for the PPP secret.
     */
    public function unpaidInvoices()
    {
        return $this->invoices()->whereIn('status', ['unpaid', 'overdue']);
    }

    /**
     * Check if PPP secret has any unpaid invoices.
     */
    public function hasUnpaidInvoices()
    {
        return $this->unpaidInvoices()->count() > 0;
    }

    /**
     * Check if PPP secret is currently connected.
     */
    public function isConnected()
    {
        return $this->latestUsageLog && $this->latestUsageLog->connected_at && !$this->latestUsageLog->disconnected_at;
    }

    /**
     * Check if PPP secret is overdue.
     */
    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * Get the status of the PPP secret.
     */
    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'disabled';
        }

        if ($this->isConnected()) {
            return 'connected';
        }

        return 'disconnected';
    }

    /**
     * Get effective local address (from secret or profile if not set).
     */
    public function getEffectiveLocalAddressAttribute()
    {
        return $this->local_address ?: ($this->pppProfile ? $this->pppProfile->local_address : null);
    }

    /**
     * Get effective remote address (from secret or profile if not set).
     */
    public function getEffectiveRemoteAddressAttribute()
    {
        return $this->remote_address ?: ($this->pppProfile ? $this->pppProfile->remote_address : null);
    }

    /**
     * Get real-time connection status from MikroTik.
     */
    public function getRealTimeConnectionStatus()
    {
        try {
            // Get the active MikroTik setting
            $setting = MikrotikSetting::getActive();
            
            if (!$setting) {
                logger()->warning('No active MikroTik setting found for real-time status check');
                return null;
            }

            // Create service and set the setting
            $mikrotikService = new MikrotikService();
            $mikrotikService->setSetting($setting);
            
            // Try to connect
            $mikrotikService->connect();
            
            // Get active PPP connections using the proper method
            $activeConnections = $mikrotikService->getActivePppConnections();
            
            foreach ($activeConnections as $connection) {
                if (isset($connection['name']) && $connection['name'] === $this->username) {
                    return [
                        'status' => 'connected',
                        'address' => $connection['address'] ?? null,
                        'uptime' => $connection['uptime'] ?? null,
                        'caller_id' => $connection['caller-id'] ?? null,
                        'service' => $connection['service'] ?? null,
                    ];
                }
            }

            return ['status' => 'disconnected'];
        } catch (Exception $e) {
            // Log error and check if it's a timeout
            logger()->error('Failed to get real-time connection status', [
                'username' => $this->username,
                'error' => $e->getMessage()
            ]);
            
            // For timeout errors, return timeout status instead of null
            if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'timed out') !== false) {
                return ['status' => 'timeout'];
            }
            
            // For other errors, return null
            return null;
        }
    }
}