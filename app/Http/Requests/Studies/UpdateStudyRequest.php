<?php

namespace App\Http\Requests\Studies;

use Illuminate\Support\Facades\Gate;

// Changing a study: the owner, the company administrator, or a colleague it was shared with for editing.
class UpdateStudyRequest extends StudyRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('update', $this->route('study'));
    }
}
