<?php

namespace App\Models\Concerns;

use App\Models\Acquisition;
use App\Models\Casino;
use App\Models\DepartmentExpense;
use App\Models\FFE;
use App\Models\FFEItem;
use App\Models\Food;
use App\Models\Loan;
use App\Models\ManagementFee;
use App\Models\Meeting;
use App\Models\Other;
use App\Models\PropertyAcquisition;
use App\Models\PropertyAcquisitionBreakDown;
use App\Models\Room;
use App\Models\SalesChannel;
use App\Models\Concerns\CommonRelations;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HospitalitySectorRelation
{
    use CommonRelations;

    public function rooms()
    {
        return $this->hasMany(Room::class, 'hospitality_sector_id', 'id');
    }

    public function foods()
    {
        return $this->hasMany(Food::class, 'hospitality_sector_id', 'id');
    }

    public function casinos()
    {
        return $this->hasMany(Casino::class, 'hospitality_sector_id', 'id');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'hospitality_sector_id', 'id');
    }

    public function others()
    {
        return $this->hasMany(Other::class, 'hospitality_sector_id', 'id');
    }

    public function salesChannels()
    {
        return $this->hasMany(SalesChannel::class, 'hospitality_sector_id', 'id');
    }

    public function departmentExpenses()
    {
        return $this->hasMany(DepartmentExpense::class, 'hospitality_sector_id', 'id');
    }

    public function ffe()
    {
        return $this->hasOne(FFE::class, 'hospitality_sector_id', 'id');
    }

    public function ffeItems()
    {
        return $this->hasMany(FFEItem::class, 'hospitality_sector_id', 'id');
    }

    public function departmentExpensesFor(string $sectionName, string $modelName)
    {

        return $this->departmentExpenses()->where('section_name', $sectionName)
            ->where('model_name', $modelName);
    }

    public function ffeItemsFor(string $sectionName, string $modelName)
    {
        return $this->ffeItems()->where('section_name', $sectionName)
            ->where('model_name', $modelName);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'hospitality_sector_id', 'id');
    }

    public function managementFees()
    {
        return $this->hasMany(ManagementFee::class, 'hospitality_sector_id', 'id');
    }

    public function managementFeesFor(string $sectionName, string $modelName)
    {
        return $this->managementFees()->where('section_name', $sectionName)
            ->where('model_name', $modelName);
    }

    public function acquisition(): HasOne
    {
        return $this->hasOne(Acquisition::class, 'hospitality_sector_id', 'id');
    }

    public function propertyAcquisition(): HasOne
    {
        return $this->hasOne(PropertyAcquisition::class, 'hospitality_sector_id', 'id');
    }

    public function propertyAcquisitionBreakDown()
    {
        return $this->hasMany(PropertyAcquisitionBreakDown::class, 'hospitality_sector_id', 'id');
    }

    public function propertyAcquisitionBreakDownFor(string $sectionName, string $modelName)
    {
        return $this->PropertyAcquisitionBreakDown()->where('section_name',$sectionName)
            ->where('model_name',$modelName);
    }
}
