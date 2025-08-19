<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoSyncEmployee extends Model
{
    protected $fillable = [
        'provider',
        'local_group_id',
        'provider_group_id',
        'sync_freq_days',
        'sync_employee_limit',
        'last_synced_at',
        'company_id',
    ];

    public function localGroupDetail()
    {
        return $this->belongsTo(UsersGroup::class, 'local_group_id', 'group_id');
    }
}
