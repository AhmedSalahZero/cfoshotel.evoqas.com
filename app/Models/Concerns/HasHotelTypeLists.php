<?php

namespace App\Models\Concerns;

use App\Models\CasinoName;
use App\Models\FoodName;
use App\Models\MeetingName;
use App\Models\OtherName;
use App\Models\RoomName;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — The type lists every company starts with
//  Location: app/Models/Concerns/HasHotelTypeLists.php  (used by App\Models\Company)
//
//  Each company has its own lists of room types, food and beverage outlets, gaming facilities, meeting
//  spaces and other revenue types (tables room_names, food_names, casino_names, meeting_names, other_names).
//  The first row of each list is the "Total ..." row. New companies get the standard rows; the insert methods
//  do nothing when the list already has rows, so they are safe to run again.
// ══════════════════════════════════════════════════════════════════

trait HasHotelTypeLists
{
    public function roomNames(): HasMany
    {
        return $this->hasMany(RoomName::class, 'company_id', 'id');
    }

    public function getRoomExceptTotal()
    {
        return $this->roomNames()->where('id', '!=', $this->getTotalRoomId())->get();
    }

    public function getTotalRoomId()
    {
        return getIdFromRowsWithTotal($this->roomNames()->orderBy('id')->get());
    }

    public function insertBasicRooms()
    {
        if ($this->roomNames()->exists()) {
            return;
        }


        $rooms = [
            0 => [
                'title' => __('Total Rooms'),
                'value' => 0,
            ],
            1 => [
                'title' => __('Standard Room'),
                'value' => 1,
            ],
            2 => [
                'title' => __('Standard Single Room'),
                'value' => 2,
            ],
            3 => [
                'title' => __('Standard Double Room'),
                'value' => 3,
            ],
            4 => [
                'title' => __('Deluxe Room'),
                'value' => 4,
            ],
            5 => [
                'title' => __('Deluxe Single Room'),
                'value' => 5,
            ],
            6 => [
                'title' => __('Deluxe Double Room'),
                'value' => 6,
            ],
            7 => [
                'title' => __('Premier Room'),
                'value' => 7,
            ],
            8 => [
                'title' => __('Premier Single Room'),
                'value' => 8,
            ],
            9 => [
                'title' => __('Premier Double Room'),
                'value' => 9,
            ],
            10 => [
                'title' => __('Junior Suite'),
                'value' => 10,
            ],
            11 => [
                'title' => __('Regular Suite'),
                'value' => 11,
            ],
            12 => [
                'title' => __('Presidential Suite'),
                'value' => 12,
            ],
            13 => [
                'title' => __('Royal Suite'),
                'value' => 13,
            ],
        ];
        foreach ($rooms as $roomArr) {
            DB::table('room_names')->insert([
                'title' => $roomArr['title'],
                'company_id' => $this->id,
                'is_active' => 1,
            ]);
        }

    }

    public function foodNames(): HasMany
    {
        return $this->hasMany(FoodName::class, 'company_id', 'id');
    }

    public function getFoodExceptTotal()
    {
        return $this->foodNames()->where('id', '!=', $this->getTotalFoodId())->get();
    }

    public function getTotalFoodId()
    {
        return getIdFromRowsWithTotal($this->foodNames()->orderBy('id')->get());
    }

    public function insertBasicFoods()
    {
        if ($this->foodNames()->exists()) {
            return;
        }


        $foods = [
            0 => [
                'title' => __('Total F&B Facility'),
                'value' => 0,
            ],
            1 => [
                'title' => __('Restaurant'),
                'value' => 1,
            ],

            2 => [
                'title' => __('Lobby Lounge & Bar'),
                'value' => 2,
            ],
            3 => [
                'title' => __('Café'),
                'value' => 3,
            ],

        ];
        foreach ($foods as $foodArr) {
            DB::table('food_names')->insert([
                'title' => $foodArr['title'],
                'company_id' => $this->id,
                'is_active' => 1,
            ]);
        }

    }

    public function casinoNames(): HasMany
    {
        return $this->hasMany(CasinoName::class, 'company_id', 'id');
    }

    public function getCasinoExceptTotal()
    {
        return $this->casinoNames()->where('id', '!=', $this->getTotalCasinoId())->get();
    }

    public function getTotalCasinoId()
    {
        return getIdFromRowsWithTotal($this->casinoNames()->orderBy('id')->get());
    }

    public function insertBasicCasinos()
    {
        if ($this->casinoNames()->exists()) {
            return;
        }


        $items = [
            0 => [
                'title' => __('Total Gaming Facilities'),
                'value' => 0,
            ],
            1 => [
                'title' => __('Large Gaming Hall'),
                'value' => 1,
            ],
            2 => [
                'title' => __('Small Gaming Hall'),
                'value' => 2,
            ],
        ];
        foreach ($items as $item) {
            DB::table('casino_names')->insert([
                'title' => $item['title'],
                'company_id' => $this->id,
                'is_active' => 1,
            ]);
        }

    }

    public function meetingNames(): HasMany
    {
        return $this->hasMany(MeetingName::class, 'company_id', 'id');
    }

    public function getMeetingExceptTotal()
    {
        return $this->meetingNames()->where('id', '!=', $this->getTotalMeetingId())->get();
    }

    public function getTotalMeetingId()
    {
        return getIdFromRowsWithTotal($this->meetingNames()->orderBy('id')->get());
    }

    public function insertBasicMeetings()
    {
        if ($this->meetingNames()->exists()) {
            return;
        }


        $items = [
            0 => [
                'title' => __('Total Meeting Spaces Facilities'),
                'value' => 0,
            ],
            1 => [
                'title' => __('Wedding Halls'),
                'value' => 1,
            ],
            2 => [
                'title' => __('Meeting Rooms'),
                'value' => 2,
            ],

        ];
        foreach ($items as $item) {
            DB::table('meeting_names')->insert([
                'title' => $item['title'],
                'company_id' => $this->id,
                'is_active' => 1,
            ]);
        }

    }

    public function otherNames(): HasMany
    {
        return $this->hasMany(OtherName::class, 'company_id', 'id');
    }

    public function getOtherExceptTotal()
    {
        return $this->otherNames()->where('id', '!=', $this->getTotalOtherId())->get();
    }

    public function getTotalOtherId()
    {
        return getIdFromRowsWithTotal($this->otherNames()->orderBy('id')->get());
    }

    public function insertBasicOthers()
    {
        if ($this->otherNames()->exists()) {
            return;
        }


        $items = [
            0 => [
                'title' => __('Total Other Revenues Facilities'),
                'value' => 0,
            ],
            1 => [
                'title' => __('GYM & SPA'),
                'value' => 1,
            ],
            2 => [
                'title' => __('Laundry'),
                'value' => 2,
            ],
            3 => [
                'title' => __('Commercial Shops'),
                'value' => 3,
            ],
        ];
        foreach ($items as $item) {
            DB::table('other_names')->insert([
                'title' => $item['title'],
                'company_id' => $this->id,
                'is_active' => 1,
            ]);
        }

    }

    /** Creates the standard rows of all five lists (only the lists that are still empty). */
    public function insertBasicSelects(): void
    {
        $this->insertBasicRooms();
        $this->insertBasicFoods();
        $this->insertBasicCasinos();
        $this->insertBasicMeetings();
        $this->insertBasicOthers();
    }
}
