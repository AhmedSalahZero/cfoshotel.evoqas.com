<?php

namespace App\Policies;

use App\Models\HospitalitySector;
use App\Models\User;
use Illuminate\Auth\Access\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — HospitalitySectorPolicy (who may do what with a study)
//  Location: app/Policies/HospitalitySectorPolicy.php
//  The rules are in App\Models\Concerns\HospitalitySectorAccess. A study the person may not even see is "not found"
//  (404), never "forbidden", so nobody can find out which studies exist.
// ══════════════════════════════════════════════════════════════════

class HospitalitySectorPolicy
{
    public function create(User $user): bool
    {
        return $user->canUseStudies();
    }

    public function view(User $user, HospitalitySector $study): Response
    {
        return $study->accessFor($user) !== null ? Response::allow() : Response::denyAsNotFound();
    }

    public function copy(User $user, HospitalitySector $study): Response
    {
        return $this->view($user, $study);
    }

    public function update(User $user, HospitalitySector $study): Response
    {
        $access = $study->accessFor($user);

        return match (true) {
            $access === null => Response::denyAsNotFound(),
            HospitalitySector::mayChange($access) => Response::allow(),
            default => Response::deny(__('studies.messages.view_only')),
        };
    }

    public function share(User $user, HospitalitySector $study): Response
    {
        $access = $study->accessFor($user);

        return match (true) {
            $access === null => Response::denyAsNotFound(),
            HospitalitySector::mayManage($access) => Response::allow(),
            default => Response::deny(__('studies.messages.owner_only')),
        };
    }

    public function delete(User $user, HospitalitySector $study): Response
    {
        return $this->share($user, $study);
    }
}
