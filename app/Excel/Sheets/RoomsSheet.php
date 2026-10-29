<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\ModelData;

// Rooms revenue, as RoomsRevenue of the application works it out:
//   nights sold   = rooms x days in the month x operating month x seasonality of the month x occupancy of the year
//   rate          = rate at opening x (1 + escalation) ^ years since opening (x the exchange rate when priced in the additional currency)
//   revenue       = rate x nights sold x the part of the price kept after the sales channels' shares and discounts
//   guests        = guests per room x nights sold
final class RoomsSheet
{
    public static function build(Ctx $x): void
    {
        $m = $x->m;
        $p = $m->p;
        $sh = $x->sheet('rm', $x->t('n.rooms'));
        $g = new Grid($x, $sh, 'rm');
        $g->title($x->t('rooms.title'), $x->t('rooms.sub'));

        $rooms = $m->rooms;
        $channels = $m->channels;
        $generalSeason = ($p['seasonality_type'] ?? '') === 'general-seasonality';

        // ── room types ──
        $g->section($x->t('rooms.types'));
        foreach ($rooms as $i => $room) {
            $n = $m->name('room', $room['room_type_id'] ?? null);
            $g->scalar("count$i", $x->t('rooms.count').' - '.$n, (float) ($room['room_count'] ?? 0), 'in_int');
            $g->scalar("guests$i", $x->t('rooms.guests').' - '.$n, (float) ($room['guest_per_room'] ?? 0), 'in_num');
            $g->scalar("adr$i", $x->t('rooms.adr').' - '.$n, (float) ($room['average_daily_rate'] ?? 0), 'in_num');
            $g->scalar("esc$i", $x->t('rooms.esc').' - '.$n, (float) ($room['average_daily_rate_escalation_rate'] ?? 0), 'in_pct');
            $g->scalar("annual$i", $x->t('rooms.annual').' - '.$n, (float) ($room['average_daily_rate_annual_escalation_rate'] ?? 0), 'in_pct');
            $inAdditional = ($p['additional_currency'] ?? '') !== '' && (string) ($room['chosen_room_currency'] ?? '') === (string) $p['additional_currency'];
            $g->scalar("cur$i", $x->t('rooms.addCur').' - '.$n, $inAdditional ? 1 : 0, 'in_int');
            $g->scalar("adrop$i", $x->t('rooms.adrOp').' - '.$n, "{$g->lcell("adr$i")}*(1+{$g->lcell("esc$i")}/100)^((Z_OpDate-Z_Start)/365)", 'num2', null, true);
        }
        $g->blank();

        // ── yearly assumptions ──
        $g->section($x->t('rooms.yearly'));
        $g->yearHeader('yh');
        $generalOcc = $m->yearly($p['general_occupancy_rate'] ?? []);
        $perRoomOcc = [];
        foreach ($rooms as $i => $room) {
            if (($room['occupancy_rate_type'] ?? '') === 'occupancy_rate_per_room') {
                $perRoomOcc[$i] = true;
                $g->yearInput("occ$i", $x->t('rooms.occ').' - '.$m->name('room', $room['room_type_id'] ?? null), $m->yearly($room['occupancy_rate_per_room'] ?? []), 'in_pct');
            }
        }
        if (count($perRoomOcc) < count($rooms)) {
            $g->yearInput('occ', $x->t('rooms.occGeneral'), $generalOcc, 'in_pct');
        }
        foreach ($channels as $c => $channel) {
            $g->yearInput("share$c", $x->t('rooms.share').' - '.(string) ($channel['name'] ?? ''), $m->yearly($channel['revenue_share_percentage'] ?? []), 'in_pct');
            $g->yearInput("disc$c", $x->t('rooms.disc').' - '.(string) ($channel['name'] ?? ''), $m->yearly($channel['discount_or_commission'] ?? []), 'in_pct');
        }
        $g->yearCalc('eff', $x->t('rooms.eff'), function ($y, $col) use ($g, $channels) {
            if ($channels === []) {
                return '1';
            }
            $parts = [];
            foreach (array_keys($channels) as $c) {
                $parts[] = $col.$g->rowOf("share$c").'*(1-'.$col.$g->rowOf("disc$c").'/100)';
            }

            return '('.implode('+', $parts).')/100';
        }, 'num2');
        $g->blank();

        // ── seasonality ──
        $g->monthsHeader('mh');
        $seasonOf = fn (array $room) => ModelData::arr($room['seasonality'] ?? []);
        if ($generalSeason) {
            $g->monthsInput('season', $x->t('rooms.seasonGeneral'), ModelData::arr($p['general_seasonality'] ?? []));
        } else {
            foreach ($rooms as $i => $room) {
                $g->monthsInput("season$i", $x->t('rooms.season').' - '.$m->name('room', $room['room_type_id'] ?? null), $seasonOf($room));
            }
        }
        $g->blank();

        // ── month by month ──
        $g->section($x->t('rooms.monthly'));
        $g->monthHeader();
        $nightKeys = [];
        $revKeys = [];
        $guestKeys = [];
        foreach ($rooms as $i => $room) {
            $n = $m->name('room', $room['room_type_id'] ?? null);
            $occKey = isset($perRoomOcc[$i]) ? "occ$i" : 'occ';
            $seasonKey = $generalSeason ? 'season' : "season$i";
            $g->line("nt$i", $x->t('rooms.nights').' - '.$n, fn ($k) => "{$g->cell("count$i")}*{$k->cal('days')}*{$k->cal('op')}*INDEX({$g->monthsRng($seasonKey)},1,{$k->cal('mnum')})/100*{$g->pick($occKey, $k)}/100", ['style' => 'num', 'total' => 'sum']);
            $g->line("px$i", $x->t('rooms.price').' - '.$n, fn ($k) => "{$k->cal('op')}*{$g->cell("adrop$i")}*(1+{$g->cell("annual$i")}/100)^{$k->cal('opyr')}*IF({$g->cell("cur$i")}=1,{$x->pickRow('study.exch', $k->c)},1)", ['style' => 'num2']);
            $g->line("rv$i", $x->t('rooms.rev').' - '.$n, fn ($k) => "{$k->r("px$i")}*{$g->pick('eff', $k)}*{$k->r("nt$i")}", ['style' => 'num', 'total' => 'sum']);
            $g->line("gs$i", $x->t('rooms.guestsRow').' - '.$n, fn ($k) => "{$g->cell("guests$i")}*{$k->r("nt$i")}", ['style' => 'num', 'total' => 'sum']);
            $nightKeys[] = "nt$i";
            $revKeys[] = "rv$i";
            $guestKeys[] = "gs$i";
        }
        $g->line('raw', $x->t('rooms.raw'), fn ($k) => $rooms === [] ? '0' : implode('+', array_map(fn ($i) => "{$k->r("px$i")}*{$k->r("nt$i")}", array_keys($rooms))), ['style' => 'num']);
        foreach ($channels as $c => $channel) {
            $g->line("rc$c", $x->t('rooms.channelRev').' - '.(string) ($channel['name'] ?? ''), fn ($k) => "{$k->r('raw')}*{$g->pick("share$c", $k)}/100*(1-{$g->pick("disc$c", $k)}/100)", ['style' => 'num', 'total' => 'sum']);
        }
        $sum = fn (array $keys) => fn ($k) => $keys === [] ? '0' : implode('+', array_map(fn ($key) => $k->r($key), $keys));
        $g->line('nights', $x->t('rooms.totNights'), $sum($nightKeys), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('rev', $x->t('rooms.totRev'), $sum($revKeys), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('guests', $x->t('rooms.totGuests'), $sum($guestKeys), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
