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

    //append the users_count attribute
    protected $appends = ['users_count'];

    protected $fillable = [
        'group_id',
        'group_name',
        'users',
        'company_id',
    ];

    // public function users(): HasMany
    // {
    //     return $this->hasMany(Users::class, 'group_id', 'group_id');
    // }
    public function emailCampaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'users_group', 'group_id');
    }
    public function whatsappCampaigns(): HasMany
    {
        return $this->hasMany(WaCampaign::class, 'users_group', 'group_id');
    }
    public function aiCampaigns(): HasMany
    {
        return $this->hasMany(AiCallCampaign::class, 'emp_group', 'group_id');
    }
    public function getUsersCountAttribute(): int
    {
        $users = json_decode($this->users, true);
        return is_array($users) ? Users::whereIn('id', $users)->count() : 0;
    }
}
