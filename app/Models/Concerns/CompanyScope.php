<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

// Limits a query to one company: the given one, or the company of the signed-in person.
// With nobody signed in (and no company given) nothing is returned.
trait CompanyScope
{
    public function scopeOnlyCurrentCompany(Builder $builder, ?int $companyId = null): void
    {
        $builder->where($this->getTable().'.company_id', $companyId ?? auth()->user()?->company_id ?? 0);
    }
}
