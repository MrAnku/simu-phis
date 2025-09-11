<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomisedDashboards extends Model
{
    protected $fillable = [
        'company_id',
        'layout_json', // Store layout as JSON
    ];

    protected $casts = [
        'layout_json' => 'array', // Automatically cast to/from array
    ];
}
