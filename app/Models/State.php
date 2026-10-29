<?php

namespace App\Models;

use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class State extends Model implements AllowsLazyLoading
{
    public function getName(): string
    {
        return $this['name_'.app()->getLocale()];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
}
