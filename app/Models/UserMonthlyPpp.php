<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserMonthlyPpp extends Model
{
    use HasFactory;

    protected $table = 'user_monthly_ppp';

    protected $fillable = [
        'company_id',
        'user_email',
        'month_year',
        'ppp_percentage'
    ];

    protected $casts = [
        'ppp_percentage' => 'decimal:2'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_email', 'user_email');
    }
}