<?php

namespace App\Support;

use App\Models\Acquisition;
use App\Models\HospitalitySector;
use App\Models\PropertyAcquisition;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyNotices (what the financial pages tell the person about the study, besides its figures)
//  Location: app/Support/StudyNotices.php
//
//  propertyMissing: the useful life of the building (and the replacement of it and of the furniture) is read from the Property Acquisition Costs
//  page. A study that builds a hotel but has never saved that page cannot carry the cost of the building to the fixed assets when it opens:
//  the building is missing from the balance sheet and its depreciation from the income statement. The previous application did the same, silently.
// ══════════════════════════════════════════════════════════════════

final class StudyNotices
{
    /** @return array{propertyMissing: bool, propertyUrl: string} */
    public static function for(HospitalitySector $study): array
    {
        return [
            'propertyMissing' => self::propertyMissing($study),
            'propertyUrl' => route('studies.investment.property.edit', $study->id),
        ];
    }

    public static function propertyMissing(HospitalitySector $study): bool
    {
        if (PropertyAcquisition::query()->where('hospitality_sector_id', $study->id)->exists()) {
            return false;
        }

        $construction = Acquisition::query()->where('hospitality_sector_id', $study->id)->first();

        return $construction !== null && ((float) $construction->getHardConstructionCost() > 0 || (float) $construction->getSoftConstructionCost() > 0);
    }
}
