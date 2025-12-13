<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('user_tours', function (Blueprint $table) {
            $table->id();

            // VARCHAR(255)
            $table->string('company_id', 255);

            // VARCHAR(255)
            $table->string('user_email', 255);

            // BOOLEAN: 0 = not completed, 1 = completed
            $table->boolean('tour_completed')->default(0);

            $table->timestamps();

            $table->index('company_id');
            $table->index('user_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tours');
    }
};
