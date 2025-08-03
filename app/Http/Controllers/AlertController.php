<?php

namespace App\Http\Controllers;

use App\Models\PppSecret;
use App\Models\PppProfile;
use App\Models\Notification;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class AlertController extends Controller
{
    protected $mikrotikService;

    public function __construct(MikrotikService $mikrotikService)
    {
        $this->mikrotikService = $mikrotikService;
    }

    /**
     * Display alerts for users with upcoming due dates
     */
    public function index(Request $request)
    {
        \Log::info('AlertController::index method called');
        
        try {
            // Get users with due dates within the next 1-7 days
            $today = Carbon::today();
            $nextWeek = Carbon::today()->addDays(7);
            
            \Log::info('Date range: ' . $today->toDateString() . ' to ' . $nextWeek->toDateString());
            
            $upcomingDueUsers = PppSecret::with(['customer', 'pppProfile'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $nextWeek])
                ->where('is_active', true)
                ->orderBy('due_date', 'asc')
                ->get();

            \Log::info('Upcoming due users found: ' . $upcomingDueUsers->count());

            // Get users with overdue payments
            $overdueUsers = PppSecret::with(['customer', 'pppProfile'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->where('is_active', true)
                ->orderBy('due_date', 'asc')
                ->get();

            \Log::info('Overdue users found: ' . $overdueUsers->count());

            // Get users that should be blocked (due tomorrow or overdue)
            $tomorrow = Carbon::tomorrow();
            $usersToBlock = PppSecret::with(['customer', 'pppProfile'])
                ->whereNotNull('due_date')
                ->where('due_date', '<=', $tomorrow)
                ->where('is_active', true)
                ->get();

            \Log::info('Users to block found: ' . $usersToBlock->count());

            // Check if there's a "Blokir" profile
            $blokirProfile = PppProfile::where('name', 'Blokir')
                ->orWhere('name', 'LIKE', '%blokir%')
                ->orWhere('name', 'LIKE', '%block%')
                ->first();

            \Log::info('Blokir profile found: ' . ($blokirProfile ? 'Yes' : 'No'));

        $alertCounts = [
            'upcoming' => $upcomingDueUsers->count(),
            'overdue' => $overdueUsers->count(),
            'to_block' => $usersToBlock->count(),
            'total' => $upcomingDueUsers->count() + $overdueUsers->count(),
            'paid_today' => $this->getPaidTodayCount()
        ];
        
        return view('alerts.index', [
            'upcomingDueUsers' => $upcomingDueUsers,
            'overdueUsers' => $overdueUsers,
            'usersToBlock' => $usersToBlock,
            'blokirProfile' => $blokirProfile,
            'alertCounts' => $alertCounts,
            'today' => $today
        ]);

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to load alerts: ' . $e->getMessage());
        }
    }

    /**
     * Block user by changing profile to "Blokir"
     */
    public function blockUser(Request $request, $secretId)
    {
        try {
            $secret = PppSecret::findOrFail($secretId);
            
            // Find Blokir profile
            $blokirProfile = PppProfile::where('name', 'Blokir')
                ->orWhere('name', 'LIKE', '%blokir%')
                ->orWhere('name', 'LIKE', '%block%')
                ->first();

            if (!$blokirProfile) {
                return redirect()->back()
                    ->with('error', 'Profile "Blokir" tidak ditemukan. Silakan buat profile tersebut terlebih dahulu.');
            }

            // Update profile to Blokir
            $oldProfile = $secret->pppProfile;
            
            // Save original profile before blocking (if not already saved)
            if (!$secret->original_ppp_profile_id) {
                $secret->original_ppp_profile_id = $secret->ppp_profile_id;
            }
            
            $secret->ppp_profile_id = $blokirProfile->id;
            $secret->save();

            // Update in MikroTik router
            $this->mikrotikService->connect();
            $this->updateProfileInMikrotik($secret, $blokirProfile);

            return redirect()->back()
                ->with('success', "User {$secret->username} berhasil diblokir. Profile diubah dari {$oldProfile->name} ke {$blokirProfile->name}.");

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to block user: ' . $e->getMessage());
        }
    }

    /**
     * Mark user payment as paid and update due date
     */
    public function markAsPaid(Request $request, $secretId)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_notes' => 'nullable|string|max:500'
        ]);

        try {
            $secret = PppSecret::findOrFail($secretId);
            $customer = $secret->customer;

            if (!$customer) {
                return redirect()->back()
                    ->with('error', 'User tidak memiliki customer data.');
            }

            // Calculate new due date (30 days from today or current due date, whichever is later)
            $today = now();
            $currentDueDate = $secret->due_date;
            
            // If current due date is in the future, extend from that date
            // If current due date is past, start from today
            $newDueDate = $currentDueDate->isFuture() 
                ? $currentDueDate->addDays(30) 
                : $today->addDays(30);

            // Update due date
            $secret->due_date = $newDueDate;
            $secret->save();

            // Create payment record if Payment model exists
            try {
                $paymentData = [
                    'customer_id' => $customer->id,
                    'amount' => $request->payment_amount,
                    'payment_method' => $request->payment_method,
                    'payment_date' => now(),
                    'status' => 'completed',
                    'notes' => $request->payment_notes ?: "Payment confirmed via alert system for user {$secret->username}",
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                // Use DB insert to avoid model dependency issues
                $paymentId = \DB::table('payments')->insertGetId($paymentData);
                
                // Create notification for payment received
                Notification::create([
                    'type' => 'payment_received',
                    'title' => 'Pembayaran Diterima',
                    'message' => "Pembayaran sebesar Rp " . number_format($request->payment_amount, 0, ',', '.') . " dari {$customer->name} via " . ucwords(str_replace('_', ' ', $request->payment_method)) . " telah diterima dan dikonfirmasi",
                    'data' => [
                        'payment_id' => $paymentId,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'username' => $secret->username,
                        'amount' => $request->payment_amount,
                        'payment_method' => $request->payment_method,
                        'new_due_date' => $newDueDate->format('Y-m-d'),
                        'was_blocked' => $wasBlocked ?? false
                    ],
                    'icon' => 'fas fa-money-check-alt',
                    'color' => 'success',
                ]);
                
            } catch (Exception $e) {
                // Log but don't fail the main operation
                \Log::warning('Failed to create payment record or notification: ' . $e->getMessage());
            }

            // If user is currently blocked, unblock them
            $wasBlocked = false;
            $blokirProfile = PppProfile::where('name', 'Blokir')->first();
            if ($secret->ppp_profile_id === $blokirProfile?->id) {
                $defaultProfile = PppProfile::where('name', 'default')->first();
                if ($defaultProfile) {
                    $secret->ppp_profile_id = $defaultProfile->id;
                    $secret->save();
                    $wasBlocked = true;

                    // Update in MikroTik router
                    $this->mikrotikService->connect();
                    $this->updateProfileInMikrotik($secret, $defaultProfile);
                }
            }

            $message = "✅ Pembayaran user {$secret->username} berhasil dikonfirmasi!<br>";
            $message .= "💰 Amount: Rp " . number_format($request->payment_amount, 0, ',', '.') . " ({$request->payment_method})<br>";
            $message .= "📅 Due date diperpanjang hingga {$newDueDate->format('d M Y')}";
            
            if ($wasBlocked) {
                $message .= "<br>🔓 User telah dibuka blokirnya dan dapat mengakses internet kembali";
            }

            return redirect()->back()->with('success', $message);

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to mark payment as paid: ' . $e->getMessage());
        }
    }

    /**
     * Unblock user by restoring original profile
     */
    public function unblockUser(Request $request, $secretId)
    {
        try {
            $secret = PppSecret::findOrFail($secretId);
            $customer = $secret->customer;

            if (!$customer) {
                return redirect()->back()
                    ->with('error', 'User tidak memiliki customer data untuk menentukan profile asli.');
            }

            // Find appropriate profile based on customer or default
            $defaultProfile = PppProfile::where('name', 'default')->first();
            
            if (!$defaultProfile) {
                return redirect()->back()
                    ->with('error', 'Profile "default" tidak ditemukan.');
            }

            $oldProfile = $secret->pppProfile;
            $secret->ppp_profile_id = $defaultProfile->id;
            $secret->save();

            // Update in MikroTik router
            $this->mikrotikService->connect();
            $this->updateProfileInMikrotik($secret, $defaultProfile);

            return redirect()->back()
                ->with('success', "User {$secret->username} berhasil dibuka blokirnya. Profile diubah dari {$oldProfile->name} ke {$defaultProfile->name}.");

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to unblock user: ' . $e->getMessage());
        }
    }

    /**
     * Get count of payments made today
     */
    private function getPaidTodayCount()
    {
        try {
            return \DB::table('payments')
                ->whereDate('payment_date', today())
                ->where('status', 'completed')
                ->count();
        } catch (Exception $e) {
            return 0; // Return 0 if payments table doesn't exist
        }
    }

    /**
     * Update profile in MikroTik router
     */
    private function updateProfileInMikrotik($secret, $newProfile)
    {
        try {
            $client = $this->mikrotikService->getClient();
            
            if ($client && $secret->mikrotik_id) {
                $query = new \RouterOS\Query('/ppp/secret/set');
                $query->equal('.id', $secret->mikrotik_id);
                $query->equal('profile', $newProfile->name);
                
                $client->query($query)->read();
                
                // Disconnect active session to force profile change
                $this->disconnectActiveSession($secret->username);
            }
        } catch (Exception $e) {
            logger()->warning('Failed to update profile in MikroTik', [
                'username' => $secret->username,
                'new_profile' => $newProfile->name,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Disconnect active PPP session
     */
    private function disconnectActiveSession($username)
    {
        try {
            $client = $this->mikrotikService->getClient();
            
            if ($client) {
                $query = new \RouterOS\Query('/ppp/active/remove');
                $query->where('name', $username);
                
                $client->query($query)->read();
            }
        } catch (Exception $e) {
            // Silent fail - user might not be connected
        }
    }

    /**
     * Auto-block users with due date today or overdue
     */
    public function autoBlock(Request $request)
    {
        try {
            $today = Carbon::today();
            $usersToBlock = PppSecret::with(['customer', 'pppProfile'])
                ->whereNotNull('due_date')
                ->where('due_date', '<=', $today)
                ->where('is_active', true)
                ->get();

            $blokirProfile = PppProfile::where('name', 'Blokir')->first();
            
            if (!$blokirProfile) {
                return redirect()->back()
                    ->with('error', 'Profile "Blokir" tidak ditemukan.');
            }

            $blockedCount = 0;
            $this->mikrotikService->connect();

            foreach ($usersToBlock as $secret) {
                if ($secret->pppProfile->name !== 'Blokir') {
                    // Save original profile before blocking (if not already saved)
                    if (!$secret->original_ppp_profile_id) {
                        $secret->original_ppp_profile_id = $secret->ppp_profile_id;
                    }
                    
                    $secret->ppp_profile_id = $blokirProfile->id;
                    $secret->save();
                    
                    $this->updateProfileInMikrotik($secret, $blokirProfile);
                    $blockedCount++;
                }
            }

            return redirect()->back()
                ->with('success', "Auto-block completed. {$blockedCount} users have been blocked due to overdue payments.");

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Auto-block failed: ' . $e->getMessage());
        }
    }

    /**
     * Get alert count for navigation badge
     */
    public function getAlertCount()
    {
        try {
            $today = Carbon::today();
            $nextWeek = Carbon::today()->addDays(7);
            
            $count = PppSecret::whereNotNull('due_date')
                ->where(function($query) use ($today, $nextWeek) {
                    $query->where('due_date', '<', $today) // Overdue
                          ->orWhereBetween('due_date', [$today, $nextWeek]); // Upcoming
                })
                ->where('is_active', true)
                ->count();

            return response()->json(['count' => $count]);

        } catch (Exception $e) {
            return response()->json(['count' => 0, 'error' => $e->getMessage()]);
        }
    }
}
