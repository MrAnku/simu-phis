<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfoGraphicCampaign extends Model
{
    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'users_group',
        'inforgraphics',
        'status',
        'scheduled_at',
        'company_id',
    ];
    //appends
    protected $appends = ['groupDetail', 'infoGraphicsData', 'formatted_created_at'];
    public function getGroupDetailAttribute()
    {
        return UsersGroup::where('group_id', $this->users_group)->first();
    }

    public function getScheduledAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d M Y h:i A') : null;
    }

    public function getInfoGraphicsDataAttribute()
    {
        $infographicsIds = json_decode($this->inforgraphics);
        return Inforgraphic::whereIn('id', $infographicsIds)->get();
    }
   
    

    public function campLive()
    {
        return $this->hasMany(InfoGraphicLiveCampaign::class, 'campaign_id', 'campaign_id');
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y h:i A') : null;
    }
}
