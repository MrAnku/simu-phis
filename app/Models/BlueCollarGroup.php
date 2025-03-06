<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlueCollarGroup extends Model
{
 use HasFactory;
    protected $table = 'blue_collar_groups';
    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'group_name',
        'users',
        'company_id',
    ];

    public function bluecollarusers(): HasMany
    {
        return $this->hasMany(BlueCollarEmployee::class, 'group_id', 'group_id');
    }
}
