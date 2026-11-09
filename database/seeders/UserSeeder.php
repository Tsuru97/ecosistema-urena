<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if admin exists
        $admin = User::where('email', 'admin@urenamuebles.com')->first();
        
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin',
                'lastname' => 'System',
                'username' => 'admin',
                'email' => 'admin@urenamuebles.com', // Updated email as per request context usually implies system email
                'password' => Hash::make('password'), // Generic password
                'status' => 1,
                'email_verified_at' => now(),
            ]);
            
            // Assign Spatie Role
            $admin->assignRole('Super Admin');
        }
    }
}
