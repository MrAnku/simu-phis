<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhiteLabel extends Model
{
    use HasFactory;

    protected $table = 'white_labelled_partner';
    public $timestamps = false;
}
