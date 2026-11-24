<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable()->unique();
            $table->unsignedTinyInteger('level')->default(1)->comment('1 (newest) to 5 (most experienced)');
            $table->string('vehicle_type', 20);
            $table->string('vehicle_plate_number', 15)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps(); // created_at doubles as the registration date

            $table->index('name');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
