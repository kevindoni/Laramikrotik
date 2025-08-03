<?php

namespace App\Models;

use App\Services\MikrotikService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use RouterOS\Query;
use Exception;

class PppSecret extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'ppp_profile_id',
        'original_ppp_profile_id',
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
            // Check if database tables exist first
            if (!app()->bound('db') || !Schema::hasTable('mikrotik_settings')) {
                return false;
            }
            
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
     * Get the original profile before blocking.
     */
    public function originalPppProfile()
    {
        return $this->belongsTo(PppProfile::class, 'original_ppp_profile_id');
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
     * Get real-time connection status from MikroTik with caching.
     */
    public function getRealTimeConnectionStatus()
    {
        // Use cache to avoid frequent MikroTik queries
        $cacheKey = "ppp_status_{$this->username}";
        $cacheTimeout = 30; // 30 seconds cache
        
        return cache()->remember($cacheKey, $cacheTimeout, function() {
            return $this->fetchRealTimeConnectionStatus();
        });
    }
    
    /**
     * Fetch real-time connection status from MikroTik (actual query).
     */
    protected function fetchRealTimeConnectionStatus()
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
            
            // Get active PPP connections - REAL DATA ONLY
            $activeConnections = $mikrotikService->getActivePppConnections(true); // Always force real data
            
            // Check each connection for this username
            foreach ($activeConnections as $connection) {
                if (isset($connection['name']) && $connection['name'] === $this->username) {
                    return [
                        'status' => 'connected',
                        'address' => $connection['address'] ?? null,
                        'uptime' => $connection['uptime'] ?? null,
                        'caller_id' => $connection['caller-id'] ?? null,
                        'service' => $connection['service'] ?? null,
                        'data_source' => 'real_router_query',
                        'cached_at' => now()->toISOString(),
                    ];
                }
            }

            return [
                'status' => 'disconnected', 
                'data_source' => 'real_router_query',
                'cached_at' => now()->toISOString(),
            ];
        } catch (Exception $e) {
            // Log error and check if it's a timeout
            logger()->error('Failed to get real-time connection status', [
                'username' => $this->username,
                'error' => $e->getMessage()
            ]);
            
            // For timeout errors, return timeout status with helpful message
            if (strpos($e->getMessage(), 'timeout') !== false || 
                strpos($e->getMessage(), 'timed out') !== false ||
                strpos($e->getMessage(), 'Router response timeout') !== false) {
                return [
                    'status' => 'timeout',
                    'message' => 'Router is responding slowly. This may be due to high router load or network issues.',
                    'suggestion' => 'Try refreshing in a few moments or check router performance.',
                    'cached_at' => now()->toISOString(),
                ];
            }
            
            // For other errors, return null
            return null;
        }
    }
    
    /**
     * Clear the cached real-time connection status.
     */
    public function clearConnectionStatusCache()
    {
        $cacheKey = "ppp_status_{$this->username}";
        cache()->forget($cacheKey);
    }
    
    /**
     * Force refresh real-time connection status (bypass cache).
     * 
     * @param bool $aggressive Whether to use more aggressive timeout settings
     */
    public function refreshRealTimeConnectionStatus($aggressive = false)
    {
        $this->clearConnectionStatusCache();
        
        if ($aggressive) {
            // For aggressive refresh, temporarily modify MikroTik service behavior
            return $this->fetchRealTimeConnectionStatusAggressive();
        }
        
        return $this->getRealTimeConnectionStatus();
    }
    
    /**
     * Fetch real-time connection status with more aggressive settings.
     */
    protected function fetchRealTimeConnectionStatusAggressive()
    {
        try {
            logger()->info('Attempting aggressive real-time status check', [
                'username' => $this->username
            ]);
            
            // Get the active MikroTik setting
            $setting = MikrotikSetting::getActive();
            
            if (!$setting) {
                logger()->warning('No active MikroTik setting found for aggressive real-time status check');
                return null;
            }

            // Create service with longer timeout settings
            $mikrotikService = new MikrotikService();
            $mikrotikService->setSetting($setting);
            
            // Try to connect with extended timeout
            $mikrotikService->connect();
            
            // Use specific query for this user only to reduce load
            $query = new \RouterOS\Query('/ppp/active/print');
            $query->where('name', $this->username);
            
            $startTime = microtime(true);
            $result = $mikrotikService->getClient()->query($query)->read();
            $endTime = microtime(true);
            $queryTime = round(($endTime - $startTime) * 1000, 2);
            
            logger()->info('Aggressive status query completed', [
                'username' => $this->username,
                'query_time_ms' => $queryTime,
                'result_count' => count($result ?? [])
            ]);
            
            if (!empty($result)) {
                $connection = $result[0];
                return [
                    'status' => 'connected',
                    'address' => $connection['address'] ?? null,
                    'uptime' => $connection['uptime'] ?? null,
                    'caller_id' => $connection['caller-id'] ?? null,
                    'service' => $connection['service'] ?? null,
                    'query_time_ms' => $queryTime,
                    'method' => 'aggressive_direct_query',
                    'cached_at' => now()->toISOString(),
                ];
            } else {
                return [
                    'status' => 'disconnected',
                    'query_time_ms' => $queryTime,
                    'method' => 'aggressive_direct_query',
                    'cached_at' => now()->toISOString(),
                ];
            }
            
        } catch (Exception $e) {
            logger()->error('Aggressive real-time status check failed', [
                'username' => $this->username,
                'error' => $e->getMessage()
            ]);
            
            // Return timeout info with suggestion
            return [
                'status' => 'timeout',
                'message' => 'Router query timed out even with extended timeout.',
                'suggestion' => 'Router may be severely overloaded. Try again later.',
                'method' => 'aggressive_direct_query_failed',
                'cached_at' => now()->toISOString(),
            ];
        }
    }
}