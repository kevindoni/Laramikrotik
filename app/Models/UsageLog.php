<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ppp_secret_id',
        'caller_id',
        'uptime',
        'bytes_in',
        'bytes_out',
        'ip_address',
        'connected_at',
        'disconnected_at',
        'session_id',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    /**
     * Get the PPP secret that the usage log is for.
     */
    public function pppSecret()
    {
        return $this->belongsTo(PppSecret::class);
    }

    /**
     * Check if the session is active.
     */
    public function isActive()
    {
        return $this->connected_at && !$this->disconnected_at;
    }

    /**
     * Get the duration of the session.
     */
    public function getDurationAttribute()
    {
        if (!$this->connected_at) {
            return null;
        }

        $end = $this->disconnected_at ?? now();
        return $this->connected_at->diff($end);
    }

    /**
     * Format the duration for display.
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration) {
            return 'N/A';
        }

        $duration = $this->duration;
        $parts = [];

        if ($duration->d > 0) {
            $parts[] = $duration->d . 'd';
        }

        if ($duration->h > 0) {
            $parts[] = $duration->h . 'h';
        }

        if ($duration->i > 0) {
            $parts[] = $duration->i . 'm';
        }

        if ($duration->s > 0 || count($parts) === 0) {
            $parts[] = $duration->s . 's';
        }

        return implode(' ', $parts);
    }

    /**
     * Format bytes for display.
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        if ($bytes === null || $bytes === '') {
            return 'N/A';
        }

        $bytes = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Format duration (seconds) for display.
     */
    public static function formatDuration($seconds)
    {
        if ($seconds === null || $seconds === '') {
            return 'N/A';
        }

        $seconds = (int) $seconds;
        
        if ($seconds < 60) {
            return $seconds . 's';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $minutes . 'm ' . $remainingSeconds . 's';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours < 24) {
            return $hours . 'h ' . $remainingMinutes . 'm';
        }
        
        $days = floor($hours / 24);
        $remainingHours = $hours % 24;
        
        return $days . 'd ' . $remainingHours . 'h';
    }

    /**
     * Get formatted bytes in.
     */
    public function getFormattedBytesInAttribute()
    {
        return self::formatBytes($this->bytes_in);
    }

    /**
     * Get formatted bytes out.
     */
    public function getFormattedBytesOutAttribute()
    {
        return self::formatBytes($this->bytes_out);
    }

    /**
     * Get total bytes (in + out).
     */
    public function getTotalBytesAttribute()
    {
        if ($this->bytes_in === null || $this->bytes_out === null) {
            return null;
        }

        return (float) $this->bytes_in + (float) $this->bytes_out;
    }

    /**
     * Get formatted total bytes.
     */
    public function getFormattedTotalBytesAttribute()
    {
        return self::formatBytes($this->total_bytes);
    }
}