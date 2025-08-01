<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'identity_card',
        'identity_card_number',
        'location',
        'coordinates',
        'notes',
        'is_active',
        'registered_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'registered_date' => 'date',
    ];

    /**
     * Get all PPP secrets for the customer.
     */
    public function pppSecrets()
    {
        return $this->hasMany(PppSecret::class);
    }

    /**
     * Get all invoices for the customer.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all payments for the customer.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get active PPP secrets for the customer.
     */
    public function activePppSecrets()
    {
        return $this->pppSecrets()->where('is_active', true);
    }

    /**
     * Get unpaid invoices for the customer.
     */
    public function unpaidInvoices()
    {
        return $this->invoices()->whereIn('status', ['unpaid', 'overdue']);
    }

    /**
     * Check if customer has any active PPP secrets.
     */
    public function hasActivePppSecrets()
    {
        return $this->activePppSecrets()->count() > 0;
    }

    /**
     * Check if customer has any unpaid invoices.
     */
    public function hasUnpaidInvoices()
    {
        return $this->unpaidInvoices()->count() > 0;
    }
}