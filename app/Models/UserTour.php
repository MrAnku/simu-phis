<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTour extends Model
{
    protected $table = 'user_tours';

    protected $fillable = [
        'company_id',
        'user_email',
        'tour_completed'
    ];

    protected $casts = [
        'tour_completed' => 'boolean'
    ];
}
