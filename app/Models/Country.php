<?php

namespace App\Models;

use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model implements AllowsLazyLoading
{


    public function getName(): string
    {
        return $this['name_'.app()->getLocale()];
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class, 'country_id', 'id');
    } //
}
