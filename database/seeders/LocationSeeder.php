<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Maadi Corniche',
                'name_ar' => 'كورنيش المعادي',
                'description' => 'Beautiful Nile view from Maadi',
                'description_ar' => 'إطلالة رائعة على النيل من المعادي',
                'address' => 'Corniche El Maadi, Cairo',
                'sort_order' => 1,
            ],
            [
                'name' => 'Zamalek Marina',
                'name_ar' => 'مارينا الزمالك',
                'description' => 'Premium dock in Zamalek',
                'description_ar' => 'رصيف مميز في الزمالك',
                'address' => 'Zamalek, Cairo',
                'sort_order' => 2,
            ],
            [
                'name' => 'Garden City Port',
                'name_ar' => 'ميناء جاردن سيتي',
                'description' => 'Central location in Garden City',
                'description_ar' => 'موقع مركزي في جاردن سيتي',
                'address' => 'Garden City, Cairo',
                'sort_order' => 3,
            ],
        ];

        foreach ($locations as $loc) {
            Location::create($loc);
        }
    }
}
