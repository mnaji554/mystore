<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courier_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('national_id')->nullable();
            $table->text('vehicle_plate')->nullable();
            $table->text('driving_license_number')->nullable();
            $table->string('national_id_image_path')->nullable();
            $table->string('vehicle_registration_image_path')->nullable();
            $table->string('driving_license_image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_profiles');
    }
};
