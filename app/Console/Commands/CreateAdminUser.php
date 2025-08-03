<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {--email=admin@example.com} {--password=password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->info("User with email {$email} already exists. Updating password...");
            $existingUser->password = Hash::make($password);
            $existingUser->role = 'admin';
            $existingUser->save();
            $this->info("Password updated for {$email}");
        } else {
            // Create new user
            $user = User::create([
                'name' => 'Admin',
                'last_name' => 'User',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
            $this->info("Admin user created: {$email}");
        }

        $this->info("Login credentials:");
        $this->info("Email: {$email}");
        $this->info("Password: {$password}");

        return 0;
    }
}
