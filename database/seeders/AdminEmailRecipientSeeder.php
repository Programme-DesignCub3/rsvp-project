<?php

namespace Database\Seeders;

use App\Models\AdminEmailRecipient;
use Illuminate\Database\Seeder;

class AdminEmailRecipientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AdminEmailRecipient::query()->firstOrCreate(
            ['email' => 'david@designcub3.com'],
            ['name' => 'David'],
        );
    }
}
