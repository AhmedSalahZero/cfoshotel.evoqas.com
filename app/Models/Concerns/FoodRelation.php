<?php

namespace App\Models\Concerns;

use App\Models\HospitalitySector;

trait FoodRelation
{
    public function hospitalitySector()
    {
        return $this->belongsTo(HospitalitySector::class, 'hospitality_sector_id');
    }
}
