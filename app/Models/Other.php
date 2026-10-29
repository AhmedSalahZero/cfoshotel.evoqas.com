<?php

namespace App\Models;

use App\Models\Concerns\OtherAccessor;
use App\Models\Concerns\OtherRelation;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;

class Other extends Model implements AllowsLazyLoading
{
    use OtherAccessor, OtherRelation;

    protected $guarded = [];

    protected $casts = [
        'guest_capture_cover_percentage' => 'array',
        'percentage_from_rooms_revenues' => 'array',
    ];
}
