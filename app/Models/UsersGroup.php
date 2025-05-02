<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsersGroup extends Model
{
    use HasFactory;

    protected $table = 'users_group';
    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'group_name',
        'users',
        'company_id',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(Users::class, 'group_id', 'group_id');
    }
    public function getUsersCountAttribute(): int
    {
        $users = json_decode($this->users, true);
        return is_array($users) ? count($users) : 0;
    }
}
