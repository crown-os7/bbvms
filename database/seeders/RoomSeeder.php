<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        Room::create([
            'imgroom' => 'NAKULA.JPG',
            'name' => 'NAKULA',
            'capacity' => 7,
            'facility' => 'Lantai 1',
            'description' => 'Ruang Rapat Umum'
        ]);

        Room::create([
            'imgroom' => 'SADEWA.JPG',
            'name' => 'SADEWA',
            'capacity' => 7,
            'facility' => 'Lantai 1',
            'description' => 'Ruang Rapat Umum'
        ]);

        Room::create([
            'imgroom' => 'NAKULA&SADEWA.JPG',
            'name' => 'NAKULA & SADEWA',
            'capacity' => 14,
            'facility' => 'Lantai 1',
            'description' => 'Ruang Rapat Gabungan Umum'
        ]);

        Room::create([
            'imgroom' => 'BIMA.JPG',
            'name' => 'BIMA',
            'capacity' => 8,
            'facility' => 'Lantai 2',
            'description' => 'Ruang Rapat Internal'
        ]);
    }
}
