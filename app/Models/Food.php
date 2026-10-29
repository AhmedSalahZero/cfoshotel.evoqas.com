<?php

namespace App\Models;

use App\Models\Concerns\FoodAccessor;
use App\Models\Concerns\FoodRelation;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;

class Food extends Model implements AllowsLazyLoading
{
    use FoodAccessor, FoodRelation;

    protected $guarded = [];

    protected $table = 'foods';

    protected $casts = [
        'guest_capture_cover_percentage' => 'array',
        'meal_per_guest' => 'array',
        'cover_per_day' => 'array',
        'percentage_from_rooms_revenues' => 'array',
    ];
}
