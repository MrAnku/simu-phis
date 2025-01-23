<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Company extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $table = 'company';
    public $timestamps = false;

    protected $fillable = [
        'email',
        'full_name',
        'company_name',
        'company_id',
        'partner_id',
        'employees',
        'storage_region',
        'approved',
        'service_status',
        'password',
        'created_at',
        'approve_date',
        'usedemployess'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company_settings(){

        return $this->hasOne(Settings::class, 'company_id', 'company_id');
    }

    public function partner(){
        return $this->belongsTo(Partner::class, 'partner_id', 'partner_id');
    }

}
