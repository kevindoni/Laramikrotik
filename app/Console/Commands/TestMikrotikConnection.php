<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikService;
use App\Models\MikrotikSetting;

class TestMikrotikConnection extends Command
{
    protected $signature = 'mikrotik:test-connection 
                            {--timeout=10 : Connection timeout in seconds}
                            {--detailed : Show detailed connection info}';
    
    protected $description = 'Test MikroTik connection and diagnose network issues';

    public function handle()
    {
        $timeout = (int) $this->option('timeout');
        $verbose = $this->option('detailed');
        
        $this->info('🔍 Testing MikroTik connection...');

        try {
            // Get active setting
            $setting = MikrotikSetting::where('is_active', true)->first();
            
            if (!$setting) {
                $this->error('❌ No active MikroTik setting found');
                return 1;
            }

            if ($verbose) {
                $this->info("📡 Configuration:");
                $this->info("   Name: {$setting->name}");
                $this->info("   Host: {$setting->host}");
                $this->info("   Port: {$setting->port}");
                $this->info("   Username: {$setting->username}");
                $this->info("   SSL: " . ($setting->use_ssl ? 'Yes' : 'No'));
                $this->info("   Timeout: {$timeout}s");
                $this->newLine();
            }

            $this->info("🔌 Attempting connection...");
            
            $mikrotikService = new MikrotikService();
            $mikrotikService->setSetting($setting);
            
            $startTime = microtime(true);
            
            try {
                $mikrotikService->connect($timeout);
                $connectTime = round((microtime(true) - $startTime) * 1000, 2);
                $this->info("✅ Connection successful ({$connectTime}ms)");
                
                // Test basic query
                $this->info("📊 Testing basic system query...");
                
                try {
                    $testResult = $mikrotikService->testConnection();
                    $this->info("✅ System test successful ({$testResult['execution_time']}ms)");
                    
                    // Test PPP connection count (lightweight)
                    $this->info("🔢 Testing PPP connection count...");
                    $countResult = $mikrotikService->getPppConnectionCount();
                    $this->info("✅ PPP count successful ({$countResult['execution_time']}ms)");
                    $this->info("📈 Found {$countResult['count']} active PPP connections");
                    
                    // Only try full PPP query if count is reasonable
                    if ($countResult['count'] <= 20) {
                        $this->info("📊 Testing full PPP query...");
                        $queryStart = microtime(true);
                        
                        try {
                            $activeConnections = $mikrotikService->getActivePppConnections();
                            $queryTime = round((microtime(true) - $queryStart) * 1000, 2);
                            $connectionCount = count($activeConnections);
                            
                            $this->info("✅ Full query successful ({$queryTime}ms)");
                            $this->info("📈 Retrieved {$connectionCount} connection details");
                            
                            if ($verbose && $connectionCount > 0) {
                                $this->info("\n📋 Active connections:");
                                $headers = ['Username', 'IP Address', 'Uptime', 'Bytes In', 'Bytes Out'];
                                $rows = [];
                                
                                foreach (array_slice($activeConnections, 0, 5) as $conn) {
                                    $rows[] = [
                                        $conn['name'] ?? 'N/A',
                                        $conn['address'] ?? 'N/A',
                                        $conn['uptime'] ?? 'N/A',
                                        isset($conn['bytes-in']) ? number_format($conn['bytes-in']) : 'N/A',
                                        isset($conn['bytes-out']) ? number_format($conn['bytes-out']) : 'N/A',
                                    ];
                                }
                                
                                $this->table($headers, $rows);
                                
                                if ($connectionCount > 5) {
                                    $this->info("... and " . ($connectionCount - 5) . " more connections");
                                }
                            }
                            
                        } catch (Exception $e) {
                            $this->warn("⚠️ Full PPP query failed: " . $e->getMessage());
                            $this->info("💡 But basic connectivity is working - using fallback sync mode");
                        }
                        
                    } else {
                        $this->warn("⚠️ Large dataset ({$countResult['count']} connections) - skipping full query to prevent timeout");
                        $this->info("💡 System will use fallback sync mode for this router");
                    }
                    
                } catch (Exception $e) {
                    $this->error("❌ Query failed: " . $e->getMessage());
                    $this->warn("💡 Connection works but data retrieval failed");
                    return 2;
                }
                
                // Performance assessment
                if ($connectTime > 3000) {
                    $this->warn("⚠️ Slow connection ({$connectTime}ms) - consider checking network");
                }
                
                $this->info("\n🎉 Connection test completed successfully!");
                return 0;
                
            } catch (Exception $e) {
                $connectTime = round((microtime(true) - $startTime) * 1000, 2);
                $this->error("❌ Connection failed after {$connectTime}ms");
                $this->error("Error: " . $e->getMessage());
                
                // Provide troubleshooting tips
                $this->warn("\n🔧 Troubleshooting tips:");
                $this->warn("1. Check if MikroTik router is online");
                $this->warn("2. Verify host and port settings");
                $this->warn("3. Check firewall rules on MikroTik");
                $this->warn("4. Ensure API service is enabled");
                $this->warn("5. Try increasing timeout with --timeout=30");
                
                return 3;
            }
            
        } catch (Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            return 1;
        }
    }
}
