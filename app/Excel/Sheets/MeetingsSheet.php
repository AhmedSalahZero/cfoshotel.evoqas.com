<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\ModelData;

// Meeting spaces revenue, as the application works it out. Each space has one method (fixed in the file):
//   guests x charge per guest x occupancy   operating days of the year x occupancy x the month's share of the year x charge x capacity (cover x count)
//   spaces x daily rent x occupancy         the same with the number of spaces instead of the capacity
//   percentage of the food & beverage       % x the F&B revenue of the month
// The month's share of the year comes from the seasonality chosen for the method (flat, by quarter or by month): the weight of the month
// divided by the weights of the operating months of its calendar year.
final class MeetingsSheet
{
    private const PER_GUEST = 'guest_count_charges_per_guest_occupancy_rate_method';

    private const DAILY_RENT = 'facility_count_daily_rent_occupancy_rate_method';

    private const PERCENT = 'percentage_from_f_b_revenue';

    public static function build(Ctx $x): void
    {
        $m = $x->m;
        $p = $m->p;
        $rows = $m->meetings;
        $sh = $x->sheet('mt', $x->t('n.mt'));
        $g = new Grid($x, $sh, 'mt');
        $g->title($x->t('mt.title'), $x->t('fac.sub'));

        $firstCur = (string) ($rows[0]['chosen_meeting_currency'] ?? '');
        $inAdditional = ($p['additional_currency'] ?? '') !== '' && $firstCur === (string) $p['additional_currency'];

        $g->section($x->t('fac.items'));
        $g->scalar('cur', $x->t('fac.addCur'), $inAdditional ? 1 : 0, 'in_int');
        foreach ($rows as $i => $row) {
            $n = $m->name('meeting', $row['meeting_type_id'] ?? null);
            $method = (string) ($row['f&b_facilities'] ?? '');
            if ($method === self::PERCENT) {
                continue;
            }
            $g->scalar("count$i", $x->t('mt.count').' - '.$n, (float) ($row['meeting_count'] ?? 0), 'in_int');
            $g->scalar("cover$i", $x->t('mt.cover').' - '.$n, (float) ($row['meeting_cover'] ?? 0), 'in_int');
            $g->scalar("val$i", $x->t($method === self::PER_GUEST ? 'mt.perGuest' : 'mt.rent').' - '.$n, (float) (($method === self::PER_GUEST ? $row['charges_value_per_guest'] : $row['cover_value']) ?? 0), 'in_num');
            $g->scalar("esc$i", $x->t('fac.esc').' - '.$n, (float) ($row['charges_value_escalation_rate'] ?? 0), 'in_pct');
            $g->scalar("ann$i", $x->t('fac.annual').' - '.$n, (float) ($row['charges_value_annual_escalation_rate'] ?? 0), 'in_pct');
            $g->scalar("atop$i", $x->t('fac.atOpening').' - '.$n, "{$g->lcell("val$i")}*(1+{$g->lcell("esc$i")}/100)^((Z_OpDate-Z_Start)/365)", 'num2', null, true);
        }
        $g->blank();

        $g->section($x->t('fac.yearly'));
        $g->yearHeader('yh');
        foreach ($rows as $i => $row) {
            $n = $m->name('meeting', $row['meeting_type_id'] ?? null);
            if (($row['f&b_facilities'] ?? '') === self::PERCENT) {
                $g->yearInput("pct$i", $x->t('mt.pctFb').' - '.$n, $m->yearly($row['percentage_from_f_and_b_revenues'] ?? []), 'in_pct');
            } else {
                $g->yearInput("occ$i", $x->t('mt.occupancy').' - '.$n, $m->yearly($row['guest_capture_cover_percentage'] ?? []), 'in_pct');
            }
        }
        $g->blank();

        // seasonality: the weights of the months (or quarters) behind each space's method
        $g->monthsHeader('mh');
        $weights = [];
        foreach ($rows as $i => $row) {
            $method = (string) ($row['f&b_facilities'] ?? '');
            if ($method === self::PERCENT) {
                continue;
            }
            $perGuest = $method === self::PER_GUEST;
            $interval = (string) ($p[$perGuest ? 'guest_meeting_seasonality_interval' : 'rent_meeting_seasonality_interval'] ?? 'flat-seasonality');
            $general = ($p[$perGuest ? 'guest_meeting_seasonality_type' : 'rent_meeting_seasonality_type'] ?? '') === 'general-seasonality';
            $values = ModelData::arr($general ? ($p[$perGuest ? 'guest_meeting_general_seasonality' : 'rent_meeting_general_seasonality'] ?? []) : ($row[$perGuest ? 'guest_seasonality' : 'rent_seasonality'] ?? []));
            $n = $m->name('meeting', $row['meeting_type_id'] ?? null);
            if ($interval === 'monthly-seasonality' || $interval === 'distribute_monthly') {
                $g->monthsInput("sea$i", $x->t('mt.season').' - '.$n, $values);
                $weights[$i] = 'monthly';
            } elseif ($interval === 'quarterly-seasonality' || $interval === 'distribute_quarterly') {
                $r = $g->row;
                $g->register("sea$i", $r);
                $sh->set($r, 1, $x->t('mt.seasonQ').' - '.$n, $x->s('label_i'));
                foreach (['quarter-one', 'quarter-two', 'quarter-three', 'quarter-four'] as $q => $name) {
                    $sh->set($r, Ctx::FIRST + $q, (float) ($values[$name] ?? $values[$q] ?? 0), $x->s('in_pct'));
                }
                $g->row++;
                $weights[$i] = 'quarterly';
            } else {
                $weights[$i] = 'flat';
            }
        }
        $g->blank();

        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        $g->line('dy', $x->t('mt.daysYear'), fn ($k) => "SUMPRODUCT(({$x->calRng('yidx')}={$k->cal('yidx')})*{$x->calRng('days')}*{$x->calRng('op')})", ['style' => 'flag']);
        $keys = [];
        foreach ($rows as $i => $row) {
            $n = $m->name('meeting', $row['meeting_type_id'] ?? null);
            $method = (string) ($row['f&b_facilities'] ?? '');
            if ($method === self::PERCENT) {
                $g->line("rv$i", $x->t('fac.rev').' - '.$n, fn ($k) => "{$g->pick("pct$i", $k)}/100*{$k->x('fb.rev')}", ['style' => 'num', 'total' => 'sum']);
                $keys[] = "rv$i";

                continue;
            }
            $g->line("px$i", $x->t('fac.price').' - '.$n, fn ($k) => "{$k->cal('op')}*{$g->cell("atop$i")}*(1+{$g->cell("ann$i")}/100)^{$k->cal('opyr')}*IF({$g->cell('cur')}=1,{$x->pickRow('study.exch', $k->c)},1)", ['style' => 'num2']);
            $weight = match ($weights[$i]) {
                'monthly' => fn ($k) => "INDEX({$g->monthsRng("sea$i")},1,{$k->cal('mnum')})/100",
                'quarterly' => fn ($k) => "INDEX(\${$x->col(0)}\${$g->rowOf("sea$i")}:\${$x->col(3)}\${$g->rowOf("sea$i")},1,INT(({$k->cal('mnum')}-1)/3)+1)/300",
                default => fn ($k) => '1/12',
            };
            $g->line("w$i", $x->t('mt.weight').' - '.$n, fn ($k) => "{$k->cal('op')}*(".$weight($k).')', ['style' => 'num2']);
            $g->line("adj$i", $x->t('mt.share').' - '.$n, function ($k) use ($g, $x, $i) {
                $total = "SUMPRODUCT(({$x->calRng('yidx')}={$k->cal('yidx')})*{$g->rng("w$i")})";

                return "IF($total=0,0,{$k->r("w$i")}/$total)";
            }, ['style' => 'num2']);
            $capacity = $method === self::PER_GUEST ? "{$g->cell("cover$i")}*{$g->cell("count$i")}" : $g->cell("count$i");
            $g->line("rv$i", $x->t('fac.rev').' - '.$n, fn ($k) => "{$k->r('dy')}*{$g->pick("occ$i", $k)}/100*{$k->r("adj$i")}*{$k->r("px$i")}*$capacity", ['style' => 'num', 'total' => 'sum']);
            $keys[] = "rv$i";
        }
        // guests of the spaces (the expenses per guest use them), as calculateMeetingGuestCount works them out: the capacity (cover x count) in every
        // method that has an occupancy; a share of the guests of the food & beverage facilities in the percentage method
        $gKeys = [];
        foreach ($rows as $i => $row) {
            $n = $m->name('meeting', $row['meeting_type_id'] ?? null);
            if (($row['f&b_facilities'] ?? '') === self::PERCENT) {
                $g->line("gc$i", $x->t('fac.guests').' - '.$n, fn ($k) => "{$g->pick("pct$i", $k)}/100*{$k->x('fb.gc')}", ['style' => 'num', 'total' => 'sum']);
            } else {
                $g->line("gc$i", $x->t('fac.guests').' - '.$n, fn ($k) => "{$k->r('dy')}*{$g->pick("occ$i", $k)}/100*{$k->r("adj$i")}*{$g->cell("cover$i")}*{$g->cell("count$i")}", ['style' => 'num', 'total' => 'sum']);
            }
            $gKeys[] = "gc$i";
        }
        $g->line('gc', $x->t('fac.totGuests'), fn ($k) => $gKeys === [] ? '0' : implode('+', array_map(fn ($kk) => $k->r($kk), $gKeys)), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('rev', $x->t('mt.total'), fn ($k) => $keys === [] ? '0' : implode('+', array_map(fn ($kk) => $k->r($kk), $keys)), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
