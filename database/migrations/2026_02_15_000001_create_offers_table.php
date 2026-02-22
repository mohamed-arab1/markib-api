<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->json('applicable_days')->nullable()->comment('أيام الأسبوع: ["saturday","sunday"] أو null = كل الأيام');
            $table->enum('applies_to', ['all_trips', 'selected_trips'])->default('all_trips');
            $table->decimal('min_booking_amount', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable()->comment('حد أقصى للخصم عند النسبة المئوية');
            $table->string('promo_code', 50)->nullable()->unique();
            $table->unsignedTinyInteger('priority')->default(0)->comment('الأعلى يُطبّق أولاً عند تداخل العروض');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'start_date', 'end_date']);
        });

        Schema::create('offer_trip', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['offer_id', 'trip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_trip');
        Schema::dropIfExists('offers');
    }
};
