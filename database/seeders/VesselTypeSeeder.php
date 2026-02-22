<?php

namespace Database\Seeders;

use App\Models\VesselType;
use Illuminate\Database\Seeder;

class VesselTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Feluca', 'name_ar' => 'فلوكة', 'capacity' => 8, 'description' => 'مركب تقليدي صغير'],
            ['name' => 'Dahabiya', 'name_ar' => 'دهبية', 'capacity' => 12, 'description' => 'مركب فاخر للنزهات'],
            ['name' => 'Cruise Boat', 'name_ar' => 'مركب سياحي', 'capacity' => 50, 'description' => 'مركب كبير للرحلات الجماعية'],
        ];

        foreach ($types as $type) {
            VesselType::create($type);
        }
    }
}
