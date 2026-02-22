<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Trip;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->unsignedInteger('total_seats')->default(0)->after('price');
        });

        Trip::query()->each(function (Trip $trip) {
            $reserved = $trip->bookings()
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('passengers_count');
            $trip->update(['total_seats' => $trip->available_seats + $reserved]);
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('total_seats');
        });
    }
};
