<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UsageLog;
use App\Models\PppSecret;
use Carbon\Carbon;

class UsageLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pppSecrets = PppSecret::all();
        
        if ($pppSecrets->isEmpty()) {
            $this->command->info('No PPP Secrets found. Please create some PPP Secrets first.');
            return;
        }
        
        $this->command->info('Creating sample usage logs...');
        
        // Generate usage logs for the last 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();
        
        $createdCount = 0;
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Generate random number of sessions per day (0-15)
            $sessionsPerDay = rand(0, 15);
            
            for ($session = 0; $session < $sessionsPerDay; $session++) {
                $pppSecret = $pppSecrets->random();
                
                // Random connection time during the day
                $connectedAt = $date->copy()->addHours(rand(6, 22))->addMinutes(rand(0, 59));
                
                // Random session duration (5 minutes to 8 hours)
                $sessionDuration = rand(300, 28800); // 5 minutes to 8 hours in seconds
                $disconnectedAt = $connectedAt->copy()->addSeconds($sessionDuration);
                
                // Don't create future disconnections
                if ($disconnectedAt->gt(Carbon::now())) {
                    $disconnectedAt = null;
                }
                
                // Generate realistic data usage (1MB to 5GB)
                $bytesIn = rand(1048576, 5368709120); // 1MB to 5GB
                $bytesOut = rand(104857, 536870912);  // 100KB to 500MB
                
                // Generate IP address
                $ipAddress = '192.168.' . rand(1, 255) . '.' . rand(2, 254);
                
                // Generate caller ID (MAC address style)
                $callerIds = [
                    'aa:bb:cc:dd:ee:ff',
                    'bb:cc:dd:ee:ff:aa',
                    'cc:dd:ee:ff:aa:bb',
                    'dd:ee:ff:aa:bb:cc',
                    'ee:ff:aa:bb:cc:dd',
                ];
                
                UsageLog::create([
                    'ppp_secret_id' => $pppSecret->id,
                    'caller_id' => $callerIds[array_rand($callerIds)],
                    'uptime' => gmdate('H:i:s', $sessionDuration),
                    'bytes_in' => $bytesIn,
                    'bytes_out' => $bytesOut,
                    'ip_address' => $ipAddress,
                    'connected_at' => $connectedAt,
                    'disconnected_at' => $disconnectedAt,
                    'session_id' => 'sess_' . uniqid(),
                    'created_at' => $connectedAt,
                    'updated_at' => $disconnectedAt ?? $connectedAt,
                ]);
                
                $createdCount++;
            }
        }
        
        $this->command->info("Created {$createdCount} usage log entries.");
    }
}
