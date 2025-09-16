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
            'capacity' => 10,
            'facility' => 'Lantai 1',
            'description' => 'Ruang rapat kecil untuk pertemuan internal.'
        ]);

        Room::create([
            'imgroom' => 'SADEWA.JPG',
            'name' => 'SADEWA',
            'capacity' => 20,
            'facility' => 'Lantai 1',
            'description' => 'Ruang rapat sedang dengan proyektor.'
        ]);

        Room::create([
            'imgroom' => 'NAKULA&SADEWA.JPG',
            'name' => 'NAKULA & SADEWA',
            'capacity' => 50,
            'facility' => 'Lantai 1',
            'description' => 'Ruang besar untuk seminar dan konferensi.'
        ]);

        Room::create([
            'imgroom' => 'BIMA.JPG',
            'name' => 'BIMA',
            'capacity' => 50,
            'facility' => 'Lantai 2',
            'description' => 'Ruang besar untuk seminar dan konferensi.'
        ]);
    }
}
