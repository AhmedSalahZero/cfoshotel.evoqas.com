<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Collection;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — The five revenue streams of a study, described once
//  Location: app/Support/StudyStreams.php
//
//  Rooms, food and beverage, gaming, meetings and other revenue. For each: the company's list of types
//  (room_names ...), the table that holds its rows, the columns of a row, and the switches kept on the study.
//  Rooms and food and beverage are always part of a study; gaming, meetings and other revenue can be switched off.
//  A stream is either "total" (one row for the whole stream, using the company's "Total ..." type) or "by type"
//  (one row per type, a type used at most once).
// ══════════════════════════════════════════════════════════════════

final class StudyStreams
{
    /**
     * relation     name of the study's relation and of the table's rows
     * names        name of the company relation holding the types
     * totalId      method of Company returning the id of its "Total ..." type
     * type/count/value   columns of a row (value = guests per room / seats; null for other revenue)
     * isTotal      column on the study: 1 = total mode
     * on           column on the study: the stream is switched on (null = always on)
     * extra        columns that have no default in the database, filled with this on creation
     */
    public const ALL = [
        'rooms' => [
            'relation' => 'rooms', 'names' => 'roomNames', 'totalId' => 'getTotalRoomId',
            'type' => 'room_type_id', 'count' => 'room_count', 'value' => 'guest_per_room',
            'isTotal' => 'is_total_rooms', 'on' => null, 'extra' => [],
        ],
        'foods' => [
            'relation' => 'foods', 'names' => 'foodNames', 'totalId' => 'getTotalFoodId',
            'type' => 'food_type_id', 'count' => 'food_count', 'value' => 'food_cover',
            'isTotal' => 'is_total_foods', 'on' => null, 'extra' => ['collection_policy_value' => ''],
        ],
        'casinos' => [
            'relation' => 'casinos', 'names' => 'casinoNames', 'totalId' => 'getTotalCasinoId',
            'type' => 'casino_type_id', 'count' => 'casino_count', 'value' => 'casino_cover',
            'isTotal' => 'is_total_casinos', 'on' => 'has_casino_section', 'extra' => ['collection_policy_value' => ''],
        ],
        'meetings' => [
            'relation' => 'meetings', 'names' => 'meetingNames', 'totalId' => 'getTotalMeetingId',
            'type' => 'meeting_type_id', 'count' => 'meeting_count', 'value' => 'meeting_cover',
            'isTotal' => 'is_total_meetings', 'on' => 'has_meeting_section', 'extra' => ['collection_policy_value' => ''],
        ],
        'others' => [
            'relation' => 'others', 'names' => 'otherNames', 'totalId' => 'getTotalOtherId',
            'type' => 'other_type_id', 'count' => 'other_count', 'value' => null,
            'isTotal' => 'is_total_other', 'on' => 'has_other_section', 'extra' => ['collection_policy_value' => ''],
        ],
    ];

    /** The reservation channels a study can use: stored value => words key. */
    public const CHANNELS = [
        'direct-reservations' => 'studies.channels.direct-reservations',
        'agency' => 'studies.channels.agency',
        'online-booking-engine' => 'studies.channels.online-booking-engine',
        'corporates' => 'studies.channels.corporates',
        'others-channels' => 'studies.channels.others-channels',
    ];

    public const PROPERTY_STATUSES = ['existing', 'existing_need_rufurbishing', 'under_development'];

    public const STAR_RATINGS = [1, 2, 3, 4, 5, 7];

    public const DURATIONS = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 20];

    public const FINANCIAL_YEAR_STARTS = ['january', 'april', 'july'];

    /** The company's types of one stream, in the company's order: id, title, whether it is the "Total ..." row. */
    public static function types(Company $company, string $stream): Collection
    {
        $config = self::ALL[$stream];
        $totalId = $company->{$config['totalId']}();

        return $company->{$config['names']}()->where('is_active', 1)->orderBy('id')->get(['id', 'title'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'title' => (string) $row->title, 'total' => (int) $row->id === (int) $totalId]);
    }

    /** The ids a "by type" row may use: the company's active types without the "Total ..." row. */
    public static function typeIds(Company $company, string $stream): array
    {
        return self::types($company, $stream)->where('total', false)->pluck('id')->all();
    }
}
