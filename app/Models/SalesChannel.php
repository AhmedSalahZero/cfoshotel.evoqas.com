<?php

namespace App\Models;

use App\Models\Concerns\SalesChannelAccessor;
use App\Models\Concerns\SalesChannelRelation;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;

class SalesChannel extends Model implements AllowsLazyLoading
{
    protected $table = 'saleschannels';

    use SalesChannelAccessor, SalesChannelRelation;

    protected $guarded = [];
}
