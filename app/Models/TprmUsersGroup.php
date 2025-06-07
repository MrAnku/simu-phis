<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TprmUsersGroup extends Model
{
    use HasFactory;

    protected $table = 'tprm_users_group';
    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'group_name',
        'users',
        'company_id',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(TprmUsers::class, 'group_id', 'group_id');
    }
    public function tprmCampaigns(): HasMany
    {
        return $this->hasMany(TprmCampaign::class, 'users_group', 'group_id');
    }
}
