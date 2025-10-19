<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->string('full_name')->after('id');
            $table->string('role')->default('co-admin')->after('password');
            $table->longText('permissions')->nullable()->after('role');
            $table->boolean('service_status')->default(true)->after('permissions');
            $table->text('additional_info')->nullable()->after('service_status');
            $table->timestamps();
        });
        $firstAdmin = DB::table('admin')->orderBy('id')->first();
        if ($firstAdmin) {
            DB::table('admin')->where('id', $firstAdmin->id)->update([
                'role' => 'admin',
                'full_name' => 'Super Admin'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->dropColumn('full_name');
            $table->dropColumn('role');
            $table->dropColumn('permissions');
            $table->dropColumn('service_status');
            $table->dropColumn('additional_info');
        });
    }
};
