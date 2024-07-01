<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Users extends Model
{
    use HasFactory;

    protected $table = 'users';
    public $timestamps = false;

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UsersGroup::class, 'group_id', 'group_id');
    }

}
