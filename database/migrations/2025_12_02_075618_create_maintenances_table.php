<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->boolean('maintenance_mode')->default(0); // 0 = OFF, 1 = ON
            $table->timestamps();
        });

        // Automatically insert one row
        DB::table('maintenances')->insert([
            'maintenance_mode' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
