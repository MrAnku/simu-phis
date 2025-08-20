<?php

use App\Models\Company;
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
        $subadmins = Company::where('role', 'sub-admin')->get();

        if ($subadmins->isEmpty()) {
            return;
        }

        foreach ($subadmins as $subadmin) {
            // compare permissions from admin
            $admin = Company::where('company_id', $subadmin->company_id)->where('role', null)->first();
            if ($admin) {
                $adminPermission = json_decode($admin->enabled_feature, true);
                $subadminPermission = json_decode($subadmin->enabled_feature, true);

                if (count($subadminPermission) === count($adminPermission)) {
                    // change subadmin role to admin
                    $subadmin->role = 'admin';
                    $subadmin->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
