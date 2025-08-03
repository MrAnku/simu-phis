<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Company extends Authenticatable implements JWTSubject
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
        'pass_create_token',
        'created_at',
        'approve_date',
        'usedemployees',
        'role',
        'enabled_feature'
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

    public function company_settings()
    {

        return $this->hasOne(CompanySettings::class, 'email', 'email');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'partner_id');
    }

    public function whatsappConfig()
    {
        return $this->hasOne(CompanyWhatsappConfig::class, 'company_id', 'company_id');
    }

    public function users()
    {
        return $this->hasMany(Users::class, 'company_id', 'company_id');
    }

    public function quishingLiveCamps()
    {
        return $this->hasMany(QuishingLiveCamp::class, 'company_id', 'company_id');
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function siemConfig()
    {
        return $this->hasOne(SiemProvider::class, 'company_id', 'company_id');
    }

    public function smishingLiveCamps()
    {
        return $this->hasMany(SmishingLiveCampaign::class, 'company_id', 'company_id');
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function company_whiteLabel()
    {
        return $this->hasOne(WhiteLabelledCompany::class, 'company_id', 'company_id');
    }

    public function autoSyncProviders()
    {
        return $this->hasMany(AutoSyncEmployee::class, 'company_id', 'company_id');
    }

    public function license()
    {
        return $this->hasOne(CompanyLicense::class, 'company_id', 'company_id');
    }
}
