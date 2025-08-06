<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PppSecret;
use App\Models\Customer;
use App\Models\PppProfile;

class CreateTestSecrets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-secrets {--count=10 : Number of secrets to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test PPP secrets for development';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $count = $this->option('count');
        
        $this->info("=== Membuat {$count} PPP Secrets Manual untuk Testing ===");

        try {
            // Buat beberapa customer dummy
            $customers = [];
            for ($i = 1; $i <= 5; $i++) {
                $customer = Customer::create([
                    'name' => "Customer Test {$i}",
                    'email' => "customer{$i}@test.com",
                    'phone' => "0812345678{$i}",
                    'address' => "Alamat Test {$i}",
                    'identity_card' => "ktp",
                    'identity_card_number' => "123456789{$i}",
                    'registered_date' => now()->toDateString(),
                ]);
                $customers[] = $customer;
                $this->info("Customer {$i} created: {$customer->name}");
            }

            // Ambil profile yang ada
            $profiles = PppProfile::all();
            if ($profiles->isEmpty()) {
                $this->warn("Tidak ada PPP Profile yang tersedia. Membuat profile default...");
                $profile = PppProfile::create([
                    'name' => 'default',
                    'local_address' => '192.168.1.1',
                    'remote_address' => '192.168.1.0/24',
                    'rate_limit' => '1M/1M',
                    'billing_cycle' => 'monthly',
                    'auto_sync' => false,
                ]);
                $profiles = collect([$profile]);
            }

            // Buat beberapa PPP secrets
            $secrets = [];
            for ($i = 1; $i <= $count; $i++) {
                $profile = $profiles->random();
                $customer = $customers[array_rand($customers)];
                
                $secret = PppSecret::create([
                    'name' => "test_user_{$i}",
                    'password' => "password{$i}",
                    'service' => 'pppoe',
                    'profile' => $profile->name,
                    'local_address' => $profile->local_address,
                    'remote_address' => $profile->remote_address,
                    'rate_limit' => $profile->rate_limit,
                    'comment' => "Secret test {$i}",
                    'last_logged_out' => now()->subDays(rand(1, 30)),
                    'disabled' => false,
                    'customer_id' => $customer->id,
                    'ppp_profile_id' => $profile->id,
                    'original_profile' => $profile->name,
                ]);
                
                $secrets[] = $secret;
                $this->info("Secret {$i} created: {$secret->name} (Customer: {$customer->name}, Profile: {$profile->name})");
            }

            $this->newLine();
            $this->info("=== Summary ===");
            $this->info("Customers created: " . count($customers));
            $this->info("Secrets created: " . count($secrets));
            $this->info("Profiles used: " . $profiles->count());

            $this->newLine();
            $this->info("=== Testing Database Query ===");
            $totalSecrets = PppSecret::count();
            $this->info("Total secrets in database: {$totalSecrets}");
            
            $secretsWithRelations = PppSecret::with(['customer', 'pppProfile'])->get();
            $this->info("Secrets with relations: " . $secretsWithRelations->count());
            
            foreach ($secretsWithRelations->take(3) as $secret) {
                $this->line("- {$secret->name} (Customer: {$secret->customer->name}, Profile: {$secret->pppProfile->name})");
            }

            $this->newLine();
            $this->info("=== Manual Secrets Created Successfully ===");
            $this->info("Sekarang Anda bisa mengakses halaman PPP Secrets untuk melihat data yang sudah dibuat.");

            return 0;

        } catch (Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("File: " . $e->getFile());
            $this->error("Line: " . $e->getLine());
            return 1;
        }
    }
} 