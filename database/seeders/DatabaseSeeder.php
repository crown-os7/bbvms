<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@ptbima.co.id',
            'departement' => 'Admin',
            'role' => 'admin',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'Reza Raspati',
            'email' => 'reza.raspati@ptbima.co.id',
            'departement' => 'GM Business',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'Rudy',
            'email' => 'rudy@ptbima.co.id',
            'departement' => 'CFO',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'Albert Marthing',
            'email' => 'albert.harris@ptbima.co.id',
            'departement' => 'GM Sales',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'Thessa',
            'email' => 'thessa@ptbima.co.id',
            'departement' => 'HR',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'david',
            'email' => 'david@ptbima.co.id',
            'departement' => 'IT',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'boy',
            'email' => 'boy@ptbima.co.id',
            'departement' => 'IT',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'security',
            'email' => 'security@ptbima.co.id',
            'departement' => 'Security',
            'role' => 'security',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'Fikri',
            'email' => 'Fikri@ptbima.co.id',
            'departement' => 'Finance',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'Ari',
            'email' => 'ari@ptbima.co.id',
            'departement' => 'Fabrikasi',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'Fitri',
            'email' => 'fitri@ptbima.co.id',
            'departement' => 'Sales',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'Eva',
            'email' => 'eva@ptbima.co.id',
            'departement' => 'Procurement',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        User::factory()->create([
            'name' => 'Aldi',
            'email' => 'aldi@ptbima.co.id',
            'departement' => 'Operation',
            'role' => 'employee',
            'password' => '123123123',

        ]);

        $this->call(RoomSeeder::class);
        // $this->call(BookingmeetingSeeder::class);
    }
}
