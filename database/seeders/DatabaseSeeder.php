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
        // Create admin user only - no dummy data
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrator',
                'last_name' => 'System',
                'password' => 'admin123',
                'role' => 'admin',
            ]
        );

        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('📧 Email: admin@admin.com');
        $this->command->info('🔑 Password: admin123');
        $this->command->info('');
        $this->command->info('ℹ️  All other data (customers, profiles, secrets) will be synced from MikroTik router.');
        $this->command->info('🔧 Please configure your MikroTik connection settings first.');
    }
}
