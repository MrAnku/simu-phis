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
        Schema::create('partner_live_invites', function (Blueprint $table) {
            $table->id();
            $table->integer('admin')->nullable()->comment('null is for super admin');
            $table->string('invite_id');
            $table->string('program_name');
            $table->string('partner_email');
            $table->boolean('sent')->default(false);
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_live_invites');
    }
};
