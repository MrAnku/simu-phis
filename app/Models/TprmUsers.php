<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TprmUsers extends Model
{
    use HasFactory;

    protected $table = 'tprm_users';
    public $timestamps = false;

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(TprmUsersGroup::class, 'group_id', 'group_id');
    }

}
