<?php

namespace App\Models;

use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;

class MeetingName extends Model implements AllowsLazyLoading
{
    protected $table = 'meeting_names';

    protected $guarded = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getName(): string
    {
        return $this->getTitle();
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
