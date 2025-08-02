<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'invoice_id',
        'customer_id',
        'payment_date',
        'amount',
        'payment_method',
        'proof',
        'status',
        'verified_by',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the invoice that the payment is for.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the customer that made the payment.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who verified the payment.
     */
    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Verify the payment.
     */
    public function verify($userId, $notes = null)
    {
        $this->status = 'verified';
        $this->verified_by = $userId;
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();

        // Update the invoice status
        $this->invoice->updateStatus();

        return $this;
    }

    /**
     * Reject the payment.
     */
    public function reject($userId, $notes = null)
    {
        $this->status = 'rejected';
        $this->verified_by = $userId;
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();

        return $this;
    }

    /**
     * Generate a unique payment number.
     */
    public static function generatePaymentNumber()
    {
        $prefix = 'PAY-' . date('Ym');
        $lastPayment = self::where('payment_number', 'like', $prefix . '%')
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}