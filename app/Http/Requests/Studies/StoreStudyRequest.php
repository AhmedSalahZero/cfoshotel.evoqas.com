<?php

namespace App\Http\Requests\Studies;

use App\Models\HospitalitySector;
use Illuminate\Support\Facades\Gate;

// A new study: any person of a company may create one (it will be theirs).
class StoreStudyRequest extends StudyRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('create', HospitalitySector::class);
    }
}
