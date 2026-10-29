<?php

namespace App\Models;

use App\Models\Concerns\MeetingAccessor;
use App\Models\Concerns\MeetingRelation;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model implements AllowsLazyLoading
{
    use MeetingAccessor, MeetingRelation;

    protected $guarded = [];

    protected $casts = [
        'rent_seasonality' => 'array',
        'guest_seasonality' => 'array',
        'guest_capture_cover_percentage' => 'array',
        'percentage_from_f_and_b_revenues' => 'array',
    ];
}
