<?php

namespace App\Models;

use App\Models\Concerns\RoomAccessor;
use App\Models\Concerns\RoomRelation;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;

class Room extends Model implements AllowsLazyLoading
{
    use RoomAccessor, RoomRelation;

    protected $guarded = [];
}
