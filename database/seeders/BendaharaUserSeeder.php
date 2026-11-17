<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BendaharaUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => config('registration.bendahara_email')],
            [
                'name' => 'Bendahara',
                'password' => Hash::make(config('registration.bendahara_password')),
                'role' => UserRole::Bendahara,
            ]
        );

        if ($user->role !== UserRole::Bendahara) {
            $user->update(['role' => UserRole::Bendahara]);
        }
    }
}
