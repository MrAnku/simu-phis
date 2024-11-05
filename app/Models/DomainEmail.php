<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainEmail extends Model
{
    use HasFactory;

    protected $fillable = ['domain', 'email', 'status'];
}
