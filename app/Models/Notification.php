<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'message',
        'data',
        'icon',
        'color',
        'is_read',
        'read_at',
        'user_id'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Create a payment notification.
     */
    public static function createPaymentNotification($payment, $type = 'payment_received')
    {
        $customer = $payment->customer;
        $amount = number_format($payment->amount, 0, ',', '.');
        $method = ucwords(str_replace('_', ' ', $payment->payment_method));

        $titles = [
            'payment_received' => 'Pembayaran Diterima',
            'payment_verified' => 'Pembayaran Diverifikasi',
            'payment_overdue' => 'Pembayaran Terlambat'
        ];

        $messages = [
            'payment_received' => "Pembayaran sebesar Rp {$amount} dari {$customer->name} via {$method} telah diterima",
            'payment_verified' => "Pembayaran sebesar Rp {$amount} dari {$customer->name} telah diverifikasi",
            'payment_overdue' => "Pembayaran dari {$customer->name} telah melewati batas waktu"
        ];

        $colors = [
            'payment_received' => 'success',
            'payment_verified' => 'info',
            'payment_overdue' => 'danger'
        ];

        $icons = [
            'payment_received' => 'fas fa-money-check-alt',
            'payment_verified' => 'fas fa-check-circle',
            'payment_overdue' => 'fas fa-exclamation-triangle'
        ];

        return self::create([
            'type' => $type,
            'title' => $titles[$type] ?? 'Notifikasi Pembayaran',
            'message' => $messages[$type] ?? 'Ada update pembayaran',
            'data' => [
                'payment_id' => $payment->id,
                'customer_id' => $payment->customer_id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'invoice_number' => $payment->invoice->invoice_number ?? null
            ],
            'icon' => $icons[$type] ?? 'fas fa-money-bill-wave',
            'color' => $colors[$type] ?? 'info',
        ]);
    }

    /**
     * Get formatted time ago.
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}
