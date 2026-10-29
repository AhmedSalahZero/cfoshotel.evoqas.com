<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Who may see and change a study
//  Location: app/Models/Concerns/HospitalitySectorAccess.php  (used by App\Models\HospitalitySector)
//
//    owner  the person who created the study           sees, changes, shares, copies, deletes
//    admin  the administrator of the same company      sees every study of the company, same rights as the owner
//    edit   a colleague the study is shared with (edit) sees, changes, copies
//    view   a colleague the study is shared with (view) sees, copies (a copy is the colleague's own study)
//    null   everybody else, other companies included: the study does not exist for them
// ══════════════════════════════════════════════════════════════════

trait HospitalitySectorAccess
{
    public const ACCESS_OWNER = 'owner';
    public const ACCESS_ADMIN = 'admin';
    public const ACCESS_EDIT = 'edit';
    public const ACCESS_VIEW = 'view';

    /** The colleagues this study is shared with (pivot: can_edit). */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hospitality_sector_shares', 'hospitality_sector_id', 'user_id')
            ->withPivot('can_edit')
            ->withTimestamps();
    }

    /** The studies this person may see: their company's, and (unless administrator) only their own or shared with them. */
    public function scopeVisibleTo(Builder $builder, User $user): void
    {
        $builder->where('hospitality_sectors.company_id', $user->company_id ?? 0);

        if ($user->isCompanyAdmin()) {
            return;
        }

        $builder->where(function (Builder $q) use ($user) {
            $q->where('hospitality_sectors.creator_id', $user->id)
                ->orWhereExists(fn ($s) => $s->select(DB::raw(1))
                    ->from('hospitality_sector_shares')
                    ->whereColumn('hospitality_sector_shares.hospitality_sector_id', 'hospitality_sectors.id')
                    ->where('hospitality_sector_shares.user_id', $user->id));
        });
    }

    /**
     * What this person is to this study: 'owner' | 'admin' | 'edit' | 'view' | null.
     * $sharedCanEdit = what the share says for this person when the caller already knows it (true = edit, false = view,
     * null = not shared); leave it out and the database is asked.
     */
    public function accessFor(?User $user, ?bool $sharedCanEdit = null, bool $sharedKnown = false): ?string
    {
        if (! $user || $user->company_id === null || (int) $this->company_id !== (int) $user->company_id) {
            return null;
        }
        if ((int) $this->creator_id === (int) $user->id) {
            return self::ACCESS_OWNER;
        }
        if ($user->isCompanyAdmin()) {
            return self::ACCESS_ADMIN;
        }
        if (! $sharedKnown) {
            $row = DB::table('hospitality_sector_shares')
                ->where('hospitality_sector_id', $this->id)->where('user_id', $user->id)->first(['can_edit']);
            $sharedCanEdit = $row ? (bool) $row->can_edit : null;
        }

        return $sharedCanEdit === null ? null : ($sharedCanEdit ? self::ACCESS_EDIT : self::ACCESS_VIEW);
    }

    public static function mayChange(?string $access): bool
    {
        return in_array($access, [self::ACCESS_OWNER, self::ACCESS_ADMIN, self::ACCESS_EDIT], true);
    }

    public static function mayManage(?string $access): bool
    {
        return in_array($access, [self::ACCESS_OWNER, self::ACCESS_ADMIN], true);
    }
}
