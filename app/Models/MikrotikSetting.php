<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikrotikSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'use_ssl',
        'is_active',
        'description',
        'last_connected_at',
        'last_disconnected_at',
    ];

    protected $casts = [
        'use_ssl' => 'boolean',
        'is_active' => 'boolean',
        'last_connected_at' => 'datetime',
        'last_disconnected_at' => 'datetime',
    ];

    /**
     * Get the active Mikrotik setting.
     */
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Get the connection parameters for RouterOS API.
     */
    public function getConnectionParams()
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->username,
            'pass' => $this->password,
            'ssl' => $this->use_ssl,
        ];
    }

    /**
     * Update the last connected timestamp.
     */
    public function updateLastConnected()
    {
        $this->last_connected_at = now();
        return $this->save();
    }

    /**
     * Update the last disconnected timestamp.
     */
    public function updateLastDisconnected()
    {
        $this->last_disconnected_at = now();
        return $this->save();
    }

    /**
     * Check if the Mikrotik setting was recently connected to.
     */
    public function wasRecentlyConnected($minutes = 5)
    {
        return $this->last_connected_at && $this->last_connected_at->diffInMinutes(now()) < $minutes;
    }

    /**
     * Get the connection status.
     */
    public function getConnectionStatusAttribute()
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->wasRecentlyConnected()) {
            return 'connected';
        }

        return 'unknown';
    }

    /**
     * Get the connection status.
     */
    public function getConnectionStatus()
    {
        return $this->connection_status;
    }
}