<?php

namespace App\Http\Requests\Expenses;

use App\Models\HospitalitySector;
use App\Services\Expenses\ManagementFeesService;
use App\Support\StudyYears;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

// CFO Hotels — the checks of the management fees screen: a percentage between 0 and 100 for every operating year, for both fees.
// Location: app/Http/Requests/Expenses/ManagementFeesRequest.php

class ManagementFeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('study'));
    }

    public function rules(): array
    {
        /** @var HospitalitySector $study */
        $study = $this->route('study');
        $rules = [];
        foreach (array_keys(ManagementFeesService::SECTIONS) as $section) {
            foreach (StudyYears::of($study) as $year) {
                $rules["sections.{$section}.0.values.{$year['index']}"] = ['required', 'numeric', 'min:0', 'max:100'];
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [];
        /** @var HospitalitySector $study */
        $study = $this->route('study');
        foreach (array_keys(ManagementFeesService::SECTIONS) as $section) {
            foreach (StudyYears::of($study) as $year) {
                $attributes["sections.{$section}.0.values.{$year['index']}"] = __('expenses.fields.year', ['year' => $year['year']]);
            }
        }

        return $attributes;
    }
}
