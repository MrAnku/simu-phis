<?php

use App\Models\Users;
use App\Models\UsersGroup;
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

        $groups = UsersGroup::all();
        foreach ($groups as $group) {
            if($group->users !== null){
                $users = json_decode($group->users, true);
                // check if users id exists in array but not in users table then pop them from array
                if (is_array($users)) {
                    $newArray = [];
                    foreach ($users as $userId) {
                        if (Users::where('id', $userId)->exists()) {
                            $newArray[] = $userId;
                        }
                    }
                    // update the users column with the new array
                    $group->users = empty($newArray) ? null : json_encode($newArray);
                    $group->save();
                } 
            }
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
