<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\PppSecret;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\UsageLog;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class HomeController extends Controller
{
    protected $mikrotikService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(MikrotikService $mikrotikService)
    {
        $this->middleware('auth');
        $this->mikrotikService = $mikrotikService;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Statistics for PPPoE/PPP Billing Dashboard
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('is_active', true)->count();
        
        // Calculate revenue
        $totalRevenue = Payment::where('status', 'verified')->sum('amount');
        $monthlyRevenue = Payment::where('status', 'verified')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');
        
        // Last month revenue for comparison
        $lastMonthRevenue = Payment::where('status', 'verified')
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)
            ->sum('amount');
            
        // Projected revenue (based on unpaid invoices for current month)
        $projectedRevenue = Invoice::where('status', 'unpaid')
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('total_amount');
            
        // Invoice statistics
        $pendingInvoices = Invoice::where('status', 'unpaid')->count();
        $overdueInvoices = Invoice::where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->count();
            
        // Get real online users from MikroTik
        $onlineUsers = 0;
        $activeConnections = [];
        try {
            $this->mikrotikService->connect();
            $activeConnections = $this->mikrotikService->getActivePppConnections();
            $onlineUsers = count($activeConnections);
        } catch (Exception $e) {
            // Fallback to database count if MikroTik is not available
            $onlineUsers = PppSecret::where('is_active', true)->count();
            logger()->warning('Could not get active connections from MikroTik for dashboard', [
                'error' => $e->getMessage()
            ]);
        }
        
        // Recent activities
        $recentPayments = Payment::with('customer')
            ->where('status', 'verified')
            ->orderBy('payment_date', 'desc')
            ->limit(5)
            ->get();
            
        $recentCustomers = Customer::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // PPP Statistics
        $totalProfiles = \App\Models\PppProfile::count();
        $totalSecrets = PppSecret::count();
        $activeSecrets = PppSecret::where('is_active', true)->count();

        $widget = [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'monthly_revenue' => $monthlyRevenue,
            'last_month_revenue' => $lastMonthRevenue,
            'projected_revenue' => $projectedRevenue,
            'total_revenue' => $totalRevenue,
            'pending_invoices' => $pendingInvoices,
            'overdue_invoices' => $overdueInvoices,
            'online_users' => $onlineUsers,
            'total_profiles' => $totalProfiles,
            'total_secrets' => $totalSecrets,
            'active_secrets' => $activeSecrets,
            'recent_payments' => $recentPayments,
            'recent_customers' => $recentCustomers,
            'active_connections' => $activeConnections,
        ];

        return view('home', compact('widget'));
    }
}
