<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create only essential admin user - no dummy data
        User::updateOrCreate(
            ['email' => 'admin@laramikrotik.com'],
            [
                'name' => 'Administrator',
                'last_name' => 'Admin',
                'password' => 'password',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('📧 Email: admin@laramikrotik.com');
        $this->command->info('🔑 Password: password');
        $this->command->info('');
        $this->command->info('ℹ️  No dummy data will be created.');
        $this->command->info('📡 All operational data should be synced from your MikroTik router.');
        $this->command->info('🔧 Please configure your MikroTik connection settings in the admin panel.');
        $this->command->info('');
        $this->command->info('🚀 Your Laravel MikroTik application is ready to use!');
    }
}
