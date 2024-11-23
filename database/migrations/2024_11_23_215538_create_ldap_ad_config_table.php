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
        Schema::create('ldap_ad_config', function (Blueprint $table) {
            $table->id();
            $table->string('ldap_host',255);
            $table->string('ldap_dn',255);
            $table->string('admin_username',255);
            $table->string('admin_password',255);
            $table->string('company_id',255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ldap_ad_config');
    }
};
