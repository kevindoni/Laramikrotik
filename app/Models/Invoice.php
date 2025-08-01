<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'ppp_secret_id',
        'invoice_date',
        'due_date',
        'amount',
        'tax',
        'total_amount',
        'status',
        'notes',
        'paid_date',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the customer that owns the invoice.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the PPP secret that the invoice is for.
     */
    public function pppSecret()
    {
        return $this->belongsTo(PppSecret::class);
    }

    /**
     * Get all payments for the invoice.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get verified payments for the invoice.
     */
    public function verifiedPayments()
    {
        return $this->payments()->where('status', 'verified');
    }

    /**
     * Calculate the total amount paid for the invoice.
     */
    public function getTotalPaidAttribute()
    {
        return $this->verifiedPayments()->sum('amount');
    }

    /**
     * Get total paid amount (alternative method for better performance).
     */
    public function totalPaid()
    {
        return $this->verifiedPayments()->sum('amount');
    }

    /**
     * Calculate the remaining amount to be paid for the invoice.
     */
    public function getRemainingAmountAttribute()
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    /**
     * Get the service period for this invoice.
     */
    public function getServicePeriodAttribute()
    {
        if ($this->pppSecret && $this->pppSecret->pppProfile) {
            $billingDay = $this->pppSecret->pppProfile->billing_cycle_day;
            if ($billingDay) {
                $invoiceDate = $this->invoice_date;
                $periodStart = $invoiceDate->copy()->subMonth()->day($billingDay);
                $periodEnd = $invoiceDate->copy()->subDay();
                
                return $periodStart->format('d M') . ' - ' . $periodEnd->format('d M Y');
            }
        }
        
        // Fallback for monthly period
        $invoiceDate = $this->invoice_date;
        $periodStart = $invoiceDate->copy()->subMonth()->startOfMonth();
        $periodEnd = $invoiceDate->copy()->subMonth()->endOfMonth();
        
        return $periodStart->format('M Y');
    }

    /**
     * Check if the invoice is fully paid.
     */
    public function isFullyPaid()
    {
        return $this->remaining_amount <= 0;
    }

    /**
     * Check if the invoice is overdue.
     */
    public function isOverdue()
    {
        return $this->status !== 'paid' && $this->due_date->isPast();
    }

    /**
     * Update the status of the invoice based on payments and due date.
     */
    public function updateStatus()
    {
        if ($this->isFullyPaid()) {
            $this->status = 'paid';
            $this->paid_date = now();
        } elseif ($this->isOverdue()) {
            $this->status = 'overdue';
        } else {
            $this->status = 'unpaid';
        }

        return $this->save();
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber()
    {
        $prefix = 'INV-' . date('Ym');
        $lastInvoice = self::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}