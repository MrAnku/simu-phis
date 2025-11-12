<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class MonthlyPpp extends Model
{
    use HasFactory;

    protected $table = 'monthly_ppp';

    protected $fillable = [
        'company_id',
        'month_year',
        'ppp_percentage'
    ];

    protected $casts = [
        'ppp_percentage' => 'decimal:2'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}