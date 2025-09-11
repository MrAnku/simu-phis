 <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
           $table->string('employee_type')->after('emp_grp_name');
        });

         // Set default value for existing records
        DB::table('ai_call_campaigns')->update(['employee_type' => 'normal']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_call_campaigns', function (Blueprint $table) {
           $table->dropColumn('employee_type');
        });
    }
};
