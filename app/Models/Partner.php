<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $table = 'partners';
    public $timestamps = false;

    public function notices()
    {
        return $this->hasMany(AdminNoticeToPartner::class, 'partner_id', 'partner_id');
    }
}
