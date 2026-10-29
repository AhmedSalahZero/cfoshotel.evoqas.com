<?php

namespace App\Models\Concerns;

use Illuminate\Support\Collection;

trait SalesChannelAccessor
{
    public function getId(): int
    {
        return $this->id;
    }

    public function getIdentifier()
    {
        return $this->getSalesChannelIdentifier();
    }

    public function getSalesChannelIdentifier()
    {
        return $this->getName();
    }

    public static function getSalesChannelIdentifierColumnName()
    {
        return 'name';
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRevenueSharePercentageAtYear($year)
    {
        $revenueSharePercentage = (array) json_decode($this->revenue_share_percentage);
        if (! count($revenueSharePercentage)) {
            return 0;
        }
        $revenueSharePercentage = arrayToValueIndexes($revenueSharePercentage);

        return isset($revenueSharePercentage[$year]) ? $revenueSharePercentage[$year] : 0;
    }

    public function getPercentages()
    {
        $percentages = $this->percentages;
        if (! $percentages) {
            return [];
        }
        $percentages = (array) json_decode($percentages);
        $percentages = arrayToValueIndexes($percentages);

        return $percentages;

    }

    public function getDiscountOrCommissionAtYear($year)
    {
        $discountOrCommission = (array) json_decode($this->discount_or_commission);
        if (! count($discountOrCommission)) {
            return 0;
        }
        $discountOrCommission = arrayToValueIndexes($discountOrCommission);

        return isset($discountOrCommission[$year]) ? $discountOrCommission[$year] : 0;
    }


    public function getCompanyId(): int
    {
        return (int) ($this->hospitalitySector->company_id ?? 0);
    }


    public function getCompanyName(): string
    {
        return $this->hospitalitySector?->company?->getName() ?? '';
    }


    public function getCreatorName(): string
    {
        return $this->hospitalitySector?->creator?->name ?? __('N/A');
    }

    public function getCollectionPolicyType()
    {
        return $this->collection_policy_type;
    }

    public function collectionPolicyInterval()
    {
        return $this->collection_policy_interval;
    }

    public function isSystemDefaultCollectionPolicy()
    {
        return $this->getCollectionPolicyType() == 'system_default';
    }

    public function isCustomizeCollectionPolicy()
    {
        return $this->getCollectionPolicyType() == 'customize';
    }

    public function getSalesChannelRateAndDueInDays(int $index, $type)
    {
        if (! $this->isCustomizeCollectionPolicy()) {
            return [
                'rate' => 0,
                'due_in_days' => 0,
            ][$type];
        }

        return [
            'rate' => ((array) json_decode($this->collection_policy_value))['rate'][$index] ?? 0,
            'due_in_days' => ((array) json_decode($this->collection_policy_value))['due_in_days'][$index] ?? 0,
        ][$type];
    }

    public function getCollectionPolicyValue()
    {
        $collectionPolicyValue = convertJsonToArray($this->collection_policy_value);

        return $collectionPolicyValue;
    }

    public function setRevenueSharePercentageAttribute($jsonValue)
    {
        $this->attributes['revenue_share_percentage'] = repeatJson($jsonValue);
    }

    public function setDiscountOrCommissionAttribute($jsonValue)
    {
        $this->attributes['discount_or_commission'] = repeatJson($jsonValue);
    }

    public static function isNone(Collection $salesChannels)
    {
        return count($salesChannels) == 0;
    }
}
