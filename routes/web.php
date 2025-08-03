<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MikrotikSettingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PppProfileController;
use App\Http\Controllers\PppSecretController;
use App\Http\Controllers\UsageLogController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes(['register' => false]);

Route::middleware(['auth'])->group(function () {
    // Home Dashboard
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    
    // User Profile
    Route::get('/profile', [UserController::class, 'profile'])->name('profile.edit');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    
    // User Management (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('users', UserController::class);
    });
    
    // Customer Management
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('/customers/overdue', [CustomerController::class, 'overdue'])->name('customers.overdue');
    Route::get('/customers/inactive', [CustomerController::class, 'inactive'])->name('customers.inactive');
    Route::get('/customers/no-service', [CustomerController::class, 'noService'])->name('customers.no-service');
    Route::resource('customers', CustomerController::class);
    
    // PPP Profile Management
    Route::post('/ppp-profiles/sync-from-mikrotik', [PppProfileController::class, 'syncFromMikrotik'])->name('ppp-profiles.sync-from-mikrotik');
    Route::post('/ppp-profiles/{pppProfile}/sync-to-mikrotik', [PppProfileController::class, 'syncToMikrotik'])->name('ppp-profiles.sync-to-mikrotik');
    Route::delete('/ppp-profiles/bulk-delete', [PppProfileController::class, 'bulkDelete'])->name('ppp-profiles.bulk-delete');
    Route::post('/ppp-profiles/bulk-sync', [PppProfileController::class, 'bulkSync'])->name('ppp-profiles.bulk-sync');
    Route::resource('ppp-profiles', PppProfileController::class);
    
    // PPP Secret Management
    Route::post('/ppp-secrets/sync-from-mikrotik', [PppSecretController::class, 'syncFromMikrotik'])->name('ppp-secrets.sync-from-mikrotik');
    Route::post('/ppp-secrets/{pppSecret}/sync-to-mikrotik', [PppSecretController::class, 'syncToMikrotik'])->name('ppp-secrets.sync-to-mikrotik');
    Route::get('/ppp-secrets/generate-username', [PppSecretController::class, 'generateUsername'])->name('ppp-secrets.generate-username');
    Route::get('/ppp-secrets/generate-password', [PppSecretController::class, 'generatePassword'])->name('ppp-secrets.generate-password');
    Route::get('/ppp-secrets/active-connections', [PppSecretController::class, 'activeConnections'])->name('ppp-secrets.active-connections');
    Route::post('/ppp-secrets/{pppSecret}/enable', [PppSecretController::class, 'enable'])->name('ppp-secrets.enable');
    Route::post('/ppp-secrets/{pppSecret}/disable', [PppSecretController::class, 'disable'])->name('ppp-secrets.disable');
    Route::post('/ppp-secrets/{pppSecret}/disconnect', [PppSecretController::class, 'disconnect'])->name('ppp-secrets.disconnect');
    Route::delete('/ppp-secrets/bulk-delete', [PppSecretController::class, 'bulkDelete'])->name('ppp-secrets.bulk-delete');
    Route::post('/ppp-secrets/bulk-enable', [PppSecretController::class, 'bulkEnable'])->name('ppp-secrets.bulk-enable');
    Route::post('/ppp-secrets/bulk-disable', [PppSecretController::class, 'bulkDisable'])->name('ppp-secrets.bulk-disable');
    Route::post('/ppp-secrets/bulk-sync', [PppSecretController::class, 'bulkSync'])->name('ppp-secrets.bulk-sync');
    
    // Debug routes for PPP secrets (remove in production)
    Route::get('/ppp-secrets/test-connection', function() {
        try {
            $service = new \App\Services\MikrotikService();
            $service->connect();
            
            return response()->json([
                'success' => true,
                'message' => 'Connection to MikroTik successful!',
                'host' => $service->loadActiveSetting()->host ?? 'unknown'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    })->name('ppp-secrets.test-connection');
    
    Route::get('/ppp-secrets/test-sync', function() {
        try {
            $service = new \App\Services\MikrotikService();
            $service->connect();
            
            // Try to get first few secrets for testing
            $secrets = $service->getAllPppSecrets(3);
            
            return response()->json([
                'success' => true,
                'message' => 'Retrieved secrets successfully!',
                'count' => count($secrets),
                'sample' => $secrets
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    })->name('ppp-secrets.test-sync');
    
    // Test route for batching
    Route::get('/ppp-secrets/test-batch', function() {
        try {
            $service = new \App\Services\MikrotikService();
            $service->connect();
            
            $start = microtime(true);
            $secrets = $service->getAllPppSecrets(5); // Small batch size
            $end = microtime(true);
            
            return response()->json([
                'success' => true,
                'count' => count($secrets),
                'time_taken' => round($end - $start, 2) . ' seconds',
                'sample' => count($secrets) > 0 ? $secrets[0]['name'] : 'No secrets found'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    })->name('ppp-secrets.test-batch');
    
    // Debug route to see raw MikroTik response
    Route::get('/ppp-secrets/debug-response', function() {
        try {
            $service = new \App\Services\MikrotikService();
            $service->connect();
            
            // Get just 2 secrets for debugging
            $secrets = $service->getPppSecretsBatch(0, 2);
            
            return response()->json([
                'success' => true,
                'count' => count($secrets),
                'raw_data' => $secrets,
                'first_secret_keys' => count($secrets) > 0 ? array_keys($secrets[0]) : [],
                'structure_info' => [
                    'is_array' => is_array($secrets),
                    'first_is_array' => count($secrets) > 0 ? is_array($secrets[0]) : false,
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->name('ppp-secrets.debug-response');
    
    Route::resource('ppp-secrets', PppSecretController::class);
    
    // Invoice Management
    Route::get('/invoices/generate-monthly', [InvoiceController::class, 'generateMonthlyInvoices'])->name('invoices.generate-monthly');
    Route::get('/invoices/overdue', [InvoiceController::class, 'overdue'])->name('invoices.overdue');
    Route::get('/invoices/unpaid', [InvoiceController::class, 'unpaid'])->name('invoices.unpaid');
    Route::get('/invoices/paid', [InvoiceController::class, 'paid'])->name('invoices.paid');
    Route::post('/invoices/process-overdue', [InvoiceController::class, 'processOverdue'])->name('invoices.process-overdue');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::post('/invoices/{invoice}/send-reminder', [InvoiceController::class, 'sendReminder'])->name('invoices.send-reminder');
    Route::post('/invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-as-paid');
    Route::resource('invoices', InvoiceController::class);
    
    // Payment Management
    Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::get('/payments/report', [PaymentController::class, 'report'])->name('payments.report');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('/payments/{payment}/download-receipt', [PaymentController::class, 'downloadReceipt'])->name('payments.download-receipt');
    Route::post('/payments/{payment}/send-receipt', [PaymentController::class, 'sendReceipt'])->name('payments.send-receipt');
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::delete('/payments/bulk-delete', [PaymentController::class, 'bulkDelete'])->name('payments.bulk-delete');
    Route::post('/payments/bulk-export', [PaymentController::class, 'bulkExport'])->name('payments.bulk-export');
    Route::get('/payments/bulk-print', [PaymentController::class, 'bulkPrint'])->name('payments.bulk-print');
    Route::resource('payments', PaymentController::class);
    
    // MikroTik Settings Management
    Route::get('/mikrotik-settings/dashboard', [MikrotikSettingController::class, 'dashboard'])->name('mikrotik-settings.dashboard');
    Route::patch('/mikrotik-settings/{mikrotikSetting}/activate', [MikrotikSettingController::class, 'setActive'])->name('mikrotik-settings.activate');
    Route::post('/mikrotik-settings/{mikrotikSetting}/set-active', [MikrotikSettingController::class, 'setActive'])->name('mikrotik-settings.set-active');
    Route::post('/mikrotik-settings/{mikrotikSetting}/test-connection', [MikrotikSettingController::class, 'testConnection'])->name('mikrotik-settings.test-connection');
    Route::post('/mikrotik-settings/{mikrotikSetting}/test-ajax', [MikrotikSettingController::class, 'testConnectionAjax'])->name('mikrotik-settings.test-ajax');
    Route::post('/mikrotik-settings/{mikrotikSetting}/diagnostics', [MikrotikSettingController::class, 'diagnostics'])->name('mikrotik-settings.diagnostics');
    
    // Sync from MikroTik (pull data)
    Route::get('/mikrotik-settings/sync/status', [MikrotikSettingController::class, 'syncStatus'])->name('mikrotik-settings.sync-status');
    Route::post('/mikrotik-settings/sync/all', [MikrotikSettingController::class, 'syncAll'])->name('mikrotik-settings.sync-all');
    Route::post('/mikrotik-settings/sync/profiles', [MikrotikSettingController::class, 'syncProfiles'])->name('mikrotik-settings.sync-profiles');
    Route::post('/mikrotik-settings/sync/secrets', [MikrotikSettingController::class, 'syncSecrets'])->name('mikrotik-settings.sync-secrets');
    
    // Push to MikroTik (push data)
    Route::post('/mikrotik-settings/push/all', [MikrotikSettingController::class, 'pushAll'])->name('mikrotik-settings.push-all');
    Route::post('/mikrotik-settings/push/profiles', [MikrotikSettingController::class, 'pushProfiles'])->name('mikrotik-settings.push-profiles');
    Route::post('/mikrotik-settings/push/secrets', [MikrotikSettingController::class, 'pushSecrets'])->name('mikrotik-settings.push-secrets');
    
    // Auto-sync toggles
    Route::post('/mikrotik-settings/profiles/{profileId}/toggle-auto-sync', [MikrotikSettingController::class, 'toggleProfileAutoSync'])->name('mikrotik-settings.toggle-profile-auto-sync');
    Route::post('/mikrotik-settings/secrets/{secretId}/toggle-auto-sync', [MikrotikSettingController::class, 'toggleSecretAutoSync'])->name('mikrotik-settings.toggle-secret-auto-sync');
    
    Route::resource('mikrotik-settings', MikrotikSettingController::class);
    
    // Usage Logs Management
    Route::get('/usage-logs/active-connections', [UsageLogController::class, 'activeConnections'])->name('usage-logs.active-connections');
    Route::get('/usage-logs/statistics', [UsageLogController::class, 'statistics'])->name('usage-logs.statistics');
    Route::post('/usage-logs/sync-from-mikrotik', [UsageLogController::class, 'syncFromMikrotik'])->name('usage-logs.sync-from-mikrotik');
    Route::get('/usage-logs/customer/{customer}', [UsageLogController::class, 'forCustomer'])->name('usage-logs.for-customer');
    Route::resource('usage-logs', UsageLogController::class)->only(['index', 'show']);
    
    // MikroTik Monitoring Routes
    Route::prefix('mikrotik')->name('mikrotik.')->group(function () {
        // System Health Monitoring
        Route::get('/system-health', [App\Http\Controllers\MikrotikMonitorController::class, 'systemHealth'])->name('system-health');
        Route::get('/temperature', [App\Http\Controllers\MikrotikMonitorController::class, 'temperature'])->name('temperature');
        Route::get('/cpu-memory', [App\Http\Controllers\MikrotikMonitorController::class, 'cpuMemory'])->name('cpu-memory');
        Route::get('/disk-usage', [App\Http\Controllers\MikrotikMonitorController::class, 'diskUsage'])->name('disk-usage');
        
        // Network Health Monitoring
        Route::get('/interfaces', [App\Http\Controllers\MikrotikMonitorController::class, 'interfaces'])->name('interfaces');
        Route::get('/bandwidth', [App\Http\Controllers\MikrotikMonitorController::class, 'bandwidth'])->name('bandwidth');
        Route::get('/firewall', [App\Http\Controllers\MikrotikMonitorController::class, 'firewall'])->name('firewall');
        Route::get('/routing', [App\Http\Controllers\MikrotikMonitorController::class, 'routing'])->name('routing');
        
        // Network Performance Tools
        Route::match(['GET', 'POST'], '/ping-test', [App\Http\Controllers\MikrotikMonitorController::class, 'pingTest'])->name('ping-test');
        Route::match(['GET', 'POST'], '/speed-test', [App\Http\Controllers\MikrotikMonitorController::class, 'speedTest'])->name('speed-test');
        Route::match(['GET', 'POST'], '/bandwidth-test', [App\Http\Controllers\MikrotikMonitorController::class, 'bandwidthTest'])->name('bandwidth-test');
        Route::get('/latency-monitor', [App\Http\Controllers\MikrotikMonitorController::class, 'latencyMonitor'])->name('latency-monitor');
        
        // Quality Monitoring
        Route::get('/quality-metrics', [App\Http\Controllers\MikrotikMonitorController::class, 'qualityMetrics'])->name('quality-metrics');
        Route::get('/packet-loss', [App\Http\Controllers\MikrotikMonitorController::class, 'packetLoss'])->name('packet-loss');
    });
    
    // About page
    Route::get('/about', [App\Http\Controllers\AboutController::class, 'index'])->name('about');
});