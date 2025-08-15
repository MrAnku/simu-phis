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
        Schema::create('ai_call_likelife_agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name');
            $table->string('agent_id')->unique()->comment('Unique identifier for the agent');
            $table->unsignedBigInteger('user_id')
                ->comment('Extracted integer from company_id');
            $table->string('llm')->default('gpt-4o');
            $table->string('tts_provider')->default('elevenlabs');
            $table->string('tts_voice');
            $table->string('language', 2)->default('en');
            $table->text('welcome_message');
            $table->text('system_prompt');
            $table->boolean('use_memory')->default(false);
            $table->boolean('auto_generate_welcome_message')->default(false);
            $table->boolean('auto_end_call')->default(false);
            $table->integer('auto_end_call_duration')->default(30);
            $table->decimal('tts_speed', 3, 2)->default(0.92);
            $table->decimal('tts_stability', 3, 2)->default(0.8);
            $table->decimal('tts_similarity_boost', 3, 2)->default(0.7);
            $table->string('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_call_likelife_agents');
    }
};
