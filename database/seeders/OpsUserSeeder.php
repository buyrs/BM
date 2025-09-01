<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OpsUserSeeder extends Seeder
{
    public function run(): void
    {
        $opsUser = User::firstOrCreate([
            'email' => 'ops@bm.com',
        ], [
            'name' => 'Ops Manager',
            'password' => Hash::make('ops123'),
            'email_verified_at' => now(),
        ]);
        $opsUser->assignRole('ops');
    }
}
