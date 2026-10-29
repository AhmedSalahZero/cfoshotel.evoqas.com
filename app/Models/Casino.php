<?php

namespace App\Models;

use App\Models\Concerns\CasinoAccessor;
use App\Models\Concerns\CasinoRelation;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;

class Casino extends Model implements AllowsLazyLoading
{
    use CasinoAccessor, CasinoRelation;

    protected $guarded = [];

    protected $casts = [
        'guest_capture_cover_percentage' => 'array',
        'cover_per_day' => 'array',
        'percentage_from_rooms_revenues' => 'array',
    ];
}
