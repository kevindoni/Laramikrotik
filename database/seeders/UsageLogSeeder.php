<?php

namespace Database\Seeders;

use App\Models\UsageLog;
use App\Models\PppSecret;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UsageLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing PPP secrets
        $pppSecrets = PppSecret::all();
        
        if ($pppSecrets->isEmpty()) {
            $this->command->info('No PPP secrets found. Please create PPP secrets first.');
            return;
        }
        
        $this->command->info('Creating sample usage logs...');
        
        // Create usage logs for the past 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();
        
        $logsCreated = 0;
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Random number of users active each day (30-80% of total users)
            $activeUserCount = rand(
                (int)($pppSecrets->count() * 0.3), 
                (int)($pppSecrets->count() * 0.8)
            );
            
            // Randomly select users for this day
            $activeUsers = $pppSecrets->random($activeUserCount);
            
            foreach ($activeUsers as $user) {
                // Some users might have multiple sessions per day
                $sessionsPerUser = rand(1, 3);
                
                for ($session = 0; $session < $sessionsPerUser; $session++) {
                    // Random connection time during the day
                    $connectTime = $date->copy()->addHours(rand(6, 22))->addMinutes(rand(0, 59));
                    
                    // Session duration between 30 minutes to 8 hours
                    $durationMinutes = rand(30, 480);
                    $disconnectTime = $connectTime->copy()->addMinutes($durationMinutes);
                    
                    // Don't create logs for future dates
                    if ($connectTime->gt(Carbon::now())) {
                        continue;
                    }
                    
                    // Random data usage (more realistic patterns)
                    $baseUsage = rand(10 * 1024 * 1024, 500 * 1024 * 1024); // 10MB to 500MB base
                    $variableFactor = rand(50, 200) / 100; // 0.5x to 2x multiplier
                    
                    $bytesOut = (int)($baseUsage * $variableFactor); // Download
                    $bytesIn = (int)($bytesOut * rand(5, 20) / 100); // Upload (5-20% of download)
                    
                    // Random IP addresses
                    $ipAddress = '10.0.' . rand(1, 254) . '.' . rand(1, 254);
                    
                    // Create the usage log
                    UsageLog::create([
                        'ppp_secret_id' => $user->id,
                        'caller_id' => '00:' . sprintf('%02x', rand(0, 255)) . ':' . sprintf('%02x', rand(0, 255)) . ':' . sprintf('%02x', rand(0, 255)) . ':' . sprintf('%02x', rand(0, 255)) . ':' . sprintf('%02x', rand(0, 255)),
                        'uptime' => $durationMinutes * 60, // Convert to seconds
                        'bytes_in' => $bytesIn,
                        'bytes_out' => $bytesOut,
                        'ip_address' => $ipAddress,
                        'connected_at' => $connectTime,
                        'disconnected_at' => $disconnectTime > Carbon::now() ? null : $disconnectTime,
                        'session_id' => '*' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    ]);
                    
                    $logsCreated++;
                }
            }
        }
        
        // Create some active sessions (no disconnect time)
        $activeSessionCount = rand(3, 8);
        $activeUsers = $pppSecrets->random($activeSessionCount);
        
        foreach ($activeUsers as $user) {
            $connectTime = Carbon::now()->subHours(rand(1, 6));
            $durationSoFar = Carbon::now()->diffInSeconds($connectTime);
            
            // Data usage so far
            $bytesOut = rand(50 * 1024 * 1024, 200 * 1024 * 1024); // 50MB to 200MB
            $bytesIn = (int)($bytesOut * rand(5, 15) / 100); // Upload (5-15% of download)
            
            $ipAddress = '10.0.' . rand(1, 254) . '.' . rand(1, 254);
            
            UsageLog::create([
                'ppp_secret_id' => $user->id,
                'caller_id' => '00:' . sprintf('%02x', rand(0, 255)) . ':' . sprintf('%02x', rand(0, 255)) . ':' . sprintf('%02x', rand(0, 255)) . ':' . sprintf('%02x', rand(0, 255)) . ':' . sprintf('%02x', rand(0, 255)),
                'uptime' => $durationSoFar,
                'bytes_in' => $bytesIn,
                'bytes_out' => $bytesOut,
                'ip_address' => $ipAddress,
                'connected_at' => $connectTime,
                'disconnected_at' => null, // Active session
                'session_id' => '*' . strtoupper(substr(md5(uniqid()), 0, 8)),
            ]);
            
            $logsCreated++;
        }
        
        $this->command->info("Created {$logsCreated} usage log entries for demonstration.");
        $this->command->info("Usage logs span from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        $this->command->info("Active sessions: {$activeSessionCount}");
    }
}
