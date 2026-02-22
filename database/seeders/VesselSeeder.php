<?php

namespace Database\Seeders;

use App\Models\Vessel;
use App\Models\VesselType;
use Illuminate\Database\Seeder;

class VesselSeeder extends Seeder
{
    public function run(): void
    {
        $types = VesselType::all();

        $vessels = [
            ['name' => 'Nile Star 1', 'name_ar' => 'نجم النيل 1', 'capacity' => 8],
            ['name' => 'Cleopatra', 'name_ar' => 'كليوباترا', 'capacity' => 12],
            ['name' => 'Pharaoh Cruise', 'name_ar' => 'كروز الفرعون', 'capacity' => 50],
        ];

        foreach ($vessels as $i => $v) {
            Vessel::create([
                'vessel_type_id' => $types[$i % $types->count()]->id,
                'name' => $v['name'],
                'name_ar' => $v['name_ar'],
                'capacity' => $v['capacity'],
            ]);
        }
    }
}
