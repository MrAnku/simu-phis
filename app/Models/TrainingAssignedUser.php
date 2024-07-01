<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingAssignedUser extends Model
{
    use HasFactory;

    protected $table = 'training_assigned_users';
    public $timestamps = false;
}
