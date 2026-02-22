<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Trip;
use App\Models\Vessel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        $vessels = Vessel::all();
        $locations = Location::all();

        for ($i = 0; $i < 15; $i++) {
            $vessel = $vessels->random();
            $location = $locations->isNotEmpty() ? $locations->random() : null;
            $date = Carbon::today()->addDays(rand(1, 14));
            $startHour = rand(9, 18);
            $duration = [60, 90, 120][array_rand([60, 90, 120])];

            Trip::create([
                'vessel_id' => $vessel->id,
                'location_id' => $location?->id,
                'date' => $date,
                'start_time' => sprintf('%02d:00', $startHour),
                'end_time' => sprintf('%02d:00', $startHour + ($duration / 60)),
                'duration_minutes' => $duration,
                'price' => rand(100, 500),
                'available_seats' => $vessel->capacity,
                'notes' => 'رحلة نيلية من القاهرة',
                'status' => 'scheduled',
            ]);
        }
    }
}
