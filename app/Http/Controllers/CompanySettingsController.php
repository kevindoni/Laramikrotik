<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingsController extends Controller
{
    public function index()
    {
        $settings = $this->getCompanySettings();
        return view('company-settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'address' => 'required|string|max:500', 
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'bank_bca' => 'nullable|string|max:50',
            'bank_mandiri' => 'nullable|string|max:50',
            'bank_bni' => 'nullable|string|max:50',
            'bank_bri' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
            'ewallet_dana' => 'nullable|string|max:50',
            'ewallet_ovo' => 'nullable|string|max:50',
            'ewallet_gopay' => 'nullable|string|max:50',
            'ewallet_shopeepay' => 'nullable|string|max:50',
            'ewallet_linkaja' => 'nullable|string|max:50',
            'manual_payment_info' => 'nullable|string|max:1000',
            'payment_note' => 'nullable|string|max:1000',
            'footer_note' => 'nullable|string|max:500',
            'developer_by' => 'nullable|string|max:255',
            'github_url' => 'nullable|url|max:255',
            // Checkbox untuk memilih metode pembayaran yang aktif
            'show_bank_bca' => 'nullable|boolean',
            'show_bank_mandiri' => 'nullable|boolean',
            'show_bank_bni' => 'nullable|boolean',
            'show_bank_bri' => 'nullable|boolean',
            'show_ewallet_dana' => 'nullable|boolean',
            'show_ewallet_ovo' => 'nullable|boolean',
            'show_ewallet_gopay' => 'nullable|boolean',
            'show_ewallet_shopeepay' => 'nullable|boolean',
            'show_ewallet_linkaja' => 'nullable|boolean',
            'show_manual_payment' => 'nullable|boolean',
        ]);

        // Debug: Log checkbox values
        \Log::info('Checkbox values:', [
            'show_bank_bca' => $request->input('show_bank_bca', false),
            'show_bank_mandiri' => $request->input('show_bank_mandiri', false),
            'show_bank_bni' => $request->input('show_bank_bni', false),
            'show_bank_bri' => $request->input('show_bank_bri', false),
            'show_ewallet_dana' => $request->input('show_ewallet_dana', false),
            'show_ewallet_ovo' => $request->input('show_ewallet_ovo', false),
            'show_ewallet_gopay' => $request->input('show_ewallet_gopay', false),
            'show_ewallet_shopeepay' => $request->input('show_ewallet_shopeepay', false),
            'show_ewallet_linkaja' => $request->input('show_ewallet_linkaja', false),
            'show_manual_payment' => $request->input('show_manual_payment', false),
        ]);

        $settings = [
            'company_name' => $request->company_name,
            'address' => $request->address,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'email' => $request->email,
            'bank_bca' => $request->bank_bca,
            'bank_mandiri' => $request->bank_mandiri,
            'bank_bni' => $request->bank_bni,
            'bank_bri' => $request->bank_bri,
            'bank_account_name' => $request->bank_account_name,
            'ewallet_dana' => $request->ewallet_dana,
            'ewallet_ovo' => $request->ewallet_ovo,
            'ewallet_gopay' => $request->ewallet_gopay,
            'ewallet_shopeepay' => $request->ewallet_shopeepay,
            'ewallet_linkaja' => $request->ewallet_linkaja,
            'manual_payment_info' => $request->manual_payment_info,
            'payment_note' => $request->payment_note,
            'footer_note' => $request->footer_note,
            'developer_by' => $request->developer_by,
            'github_url' => $request->github_url,
            // Checkbox settings - use input() method to get boolean values
            'show_bank_bca' => $request->input('show_bank_bca', false),
            'show_bank_mandiri' => $request->input('show_bank_mandiri', false),
            'show_bank_bni' => $request->input('show_bank_bni', false),
            'show_bank_bri' => $request->input('show_bank_bri', false),
            'show_ewallet_dana' => $request->input('show_ewallet_dana', false),
            'show_ewallet_ovo' => $request->input('show_ewallet_ovo', false),
            'show_ewallet_gopay' => $request->input('show_ewallet_gopay', false),
            'show_ewallet_shopeepay' => $request->input('show_ewallet_shopeepay', false),
            'show_ewallet_linkaja' => $request->input('show_ewallet_linkaja', false),
            'show_manual_payment' => $request->input('show_manual_payment', false),
        ];

        // Debug: Log saved settings
        \Log::info('Saved settings:', $settings);
        
        Storage::put('company_settings.json', json_encode($settings, JSON_PRETTY_PRINT));

        return redirect()->route('company-settings.index')->with('success', 'Company settings updated successfully!');
    }

    private function getCompanySettings()
    {
        $defaultSettings = [
            'company_name' => 'LaraNetworks',
            'address' => 'Jl. Raya Teknologi No. 123',
            'city' => 'Kota Digital, ID 12345',
            'postal_code' => '12345',
            'phone' => '(021) 1234-5678',
            'email' => 'billing@laranetworks.com',
            'bank_bca' => '1234567890',
            'bank_mandiri' => '0987654321',
            'bank_bni' => '1122334455',
            'bank_bri' => '5544332211',
            'bank_account_name' => 'LaraNetworks',
            'ewallet_dana' => '0812-3456-7890',
            'ewallet_ovo' => '0812-3456-7890',
            'ewallet_gopay' => '0812-3456-7890',
            'ewallet_shopeepay' => '0812-3456-7890',
            'ewallet_linkaja' => '0812-3456-7890',
            'manual_payment_info' => 'Please include invoice number in payment description',
            'payment_note' => 'Please include invoice number in payment description',
            'footer_note' => 'Thank you for your business! For any questions regarding this invoice, please contact us at billing@laranetworks.com',
            'developer_by' => 'Kevindoni',
            'github_url' => 'https://github.com/kevindoni',
            // Default checkbox settings - semua aktif
            'show_bank_bca' => true,
            'show_bank_mandiri' => true,
            'show_bank_bni' => false,
            'show_bank_bri' => false,
            'show_ewallet_dana' => true,
            'show_ewallet_ovo' => true,
            'show_ewallet_gopay' => false,
            'show_ewallet_shopeepay' => false,
            'show_ewallet_linkaja' => false,
            'show_manual_payment' => false,
        ];

        if (Storage::exists('company_settings.json')) {
            $savedSettings = json_decode(Storage::get('company_settings.json'), true);
            return array_merge($defaultSettings, $savedSettings);
        }

        return $defaultSettings;
    }

    public static function getSettings()
    {
        $defaultSettings = [
            'company_name' => 'LaraNetworks',
            'address' => 'Jl. Raya Teknologi No. 123',
            'city' => 'Kota Digital, ID 12345',
            'postal_code' => '12345',
            'phone' => '(021) 1234-5678',
            'email' => 'billing@laranetworks.com',
            'bank_bca' => '1234567890',
            'bank_mandiri' => '0987654321',
            'bank_bni' => '1122334455',
            'bank_bri' => '5544332211',
            'bank_account_name' => 'LaraNetworks',
            'ewallet_dana' => '0812-3456-7890',
            'ewallet_ovo' => '0812-3456-7890',
            'ewallet_gopay' => '0812-3456-7890',
            'ewallet_shopeepay' => '0812-3456-7890',
            'ewallet_linkaja' => '0812-3456-7890',
            'manual_payment_info' => 'Please include invoice number in payment description',
            'payment_note' => 'Please include invoice number in payment description',
            'footer_note' => 'Thank you for your business! For any questions regarding this invoice, please contact us at billing@laranetworks.com',
            'developer_by' => 'Kevindoni',
            'github_url' => 'https://github.com/kevindoni',
            // Default checkbox settings
            'show_bank_bca' => true,
            'show_bank_mandiri' => true,
            'show_bank_bni' => false,
            'show_bank_bri' => false,
            'show_ewallet_dana' => true,
            'show_ewallet_ovo' => true,
            'show_ewallet_gopay' => false,
            'show_ewallet_shopeepay' => false,
            'show_ewallet_linkaja' => false,
            'show_manual_payment' => false,
        ];

        if (Storage::exists('company_settings.json')) {
            $savedSettings = json_decode(Storage::get('company_settings.json'), true);
            return array_merge($defaultSettings, $savedSettings);
        }

        return $defaultSettings;
    }
}
