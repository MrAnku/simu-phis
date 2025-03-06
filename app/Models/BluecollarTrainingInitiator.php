<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BluecollarTrainingInitiator extends Model
{
    use HasFactory;

    protected $table = 'bluecollar_training_initiators';
    public $timestamps = true;
    protected $fillable = [
        'name',
        'phone_number',
    ];
}
