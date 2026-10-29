<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\ModelData;

// The revenue of food & beverage, gaming and other revenue facilities, as the application works it out. Each facility has one method
// (fixed in the file); its formula is the one of that method:
//   guest capture x unit value (x meals per guest)    capture % x guests of the month x unit value (x meals)
//   daily target x unit value                         days in the month x target of the year x unit value
//   percentage of the rooms revenue                   % x rooms revenue of the month
// The unit value is the value at opening grown by the yearly escalation and, when the facilities of the stream are priced in the additional
// currency, by the exchange rate (the application takes the currency of the FIRST facility of the stream for all of them).
final class FacilitySheet
{
    /** @var array<string,array<string,mixed>> */
    private const KINDS = [
        'food' => ['key' => 'fb', 'sheet' => 'n.fb', 'title' => 'fb.title', 'rows' => 'foods', 'id' => 'food_type_id', 'cur' => 'chosen_food_currency', 'value' => 'cover_value', 'esc' => 'cover_value_escalation_rate', 'ann' => 'cover_value_annual_escalation_rate', 'target' => 'cover_per_day'],
        'casino' => ['key' => 'cs', 'sheet' => 'n.cs', 'title' => 'cs.title', 'rows' => 'casinos', 'id' => 'casino_type_id', 'cur' => 'chosen_casino_currency', 'value' => 'cover_value', 'esc' => 'charges_value_escalation_rate', 'ann' => 'charges_value_annual_escalation_rate', 'target' => 'guest_capture_cover_percentage'],
        // the application grows the charges of other revenue by the escalation until opening, every year (not by the annual one)
        'other' => ['key' => 'ot', 'sheet' => 'n.ot', 'title' => 'ot.title', 'rows' => 'others', 'id' => 'other_type_id', 'cur' => 'chosen_other_currency', 'value' => 'charges_per_guest', 'esc' => 'charges_per_guest_escalation_rate', 'ann' => 'charges_per_guest_escalation_rate', 'target' => null],
    ];

    public static function build(Ctx $x, string $kind): void
    {
        $cfg = self::KINDS[$kind];
        $m = $x->m;
        $p = $m->p;
        $rows = $m->{$cfg['rows']};
        $key = $cfg['key'];
        $sh = $x->sheet($key, $x->t($cfg['sheet']));
        $g = new Grid($x, $sh, $key);
        $g->title($x->t($cfg['title']), $x->t('fac.sub'));

        // the currency of the whole stream is the one of its first facility
        $firstCur = (string) ($rows[0][$cfg['cur']] ?? '');
        $inAdditional = ($p['additional_currency'] ?? '') !== '' && $firstCur === (string) $p['additional_currency'];

        $g->section($x->t('fac.items'));
        $g->scalar('cur', $x->t('fac.addCur'), $inAdditional ? 1 : 0, 'in_int');
        foreach ($rows as $i => $row) {
            $n = $m->name($kind, $row[$cfg['id']] ?? null);
            $method = (string) ($row['f&b_facilities'] ?? '');
            $usesValue = $method !== 'percentage_from_rooms_revenue';
            if ($usesValue) {
                $g->scalar("val$i", $x->t('fac.value').' - '.$n, (float) ($row[$cfg['value']] ?? 0), 'in_num');
                $g->scalar("esc$i", $x->t('fac.esc').' - '.$n, (float) ($row[$cfg['esc']] ?? 0), 'in_pct');
                if ($cfg['ann'] !== $cfg['esc']) {
                    $g->scalar("ann$i", $x->t('fac.annual').' - '.$n, (float) ($row[$cfg['ann']] ?? 0), 'in_pct');
                }
                $g->scalar("atop$i", $x->t('fac.atOpening').' - '.$n, "{$g->lcell("val$i")}*(1+{$g->lcell("esc$i")}/100)^((Z_OpDate-Z_Start)/365)", 'num2', null, true);
            }
        }
        $g->blank();

        $g->section($x->t('fac.yearly'));
        $g->yearHeader('yh');
        foreach ($rows as $i => $row) {
            $n = $m->name($kind, $row[$cfg['id']] ?? null);
            $method = (string) ($row['f&b_facilities'] ?? '');
            if ($method === 'percentage_from_rooms_revenue') {
                $g->yearInput("pct$i", $x->t('fac.pct').' - '.$n, $m->yearly($row['percentage_from_rooms_revenues'] ?? []), 'in_pct');

                continue;
            }
            if ($method === 'cover_count_target_per_day_x_cover_value_method' || $method === 'guest_count_target_per_day_charges_per_guest_method') {
                $g->yearInput("tgt$i", $x->t($kind === 'casino' ? 'fac.captureAsTarget' : 'fac.target').' - '.$n, $m->yearly($row[$cfg['target']] ?? []), $kind === 'casino' ? 'in_pct' : 'in_num');

                continue;
            }
            $g->yearInput("cap$i", $x->t('fac.capture').' - '.$n, $m->yearly($row['guest_capture_cover_percentage'] ?? []), 'in_pct');
            if ($method === 'guest_capture_x_meals_per_guest_x_cover_value_per_meal_method') {
                $g->yearInput("meal$i", $x->t('fac.meals').' - '.$n, $m->yearly($row['meal_per_guest'] ?? []), 'in_num');
            }
        }
        $g->blank();

        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        $keys = [];
        foreach ($rows as $i => $row) {
            $n = $m->name($kind, $row[$cfg['id']] ?? null);
            $method = (string) ($row['f&b_facilities'] ?? '');
            $annRef = $cfg['ann'] !== $cfg['esc'] ? "ann$i" : "esc$i";
            if ($method !== 'percentage_from_rooms_revenue' && $method !== '') {
                $g->line("px$i", $x->t('fac.price').' - '.$n, fn ($k) => "{$k->cal('op')}*{$g->cell("atop$i")}*(1+{$g->cell($annRef)}/100)^{$k->cal('opyr')}*IF({$g->cell('cur')}=1,{$x->pickRow('study.exch', $k->c)},1)", ['style' => 'num2']);
            }
            $formula = match ($method) {
                'guest_capture_x_cover_value_per_guest_method', 'guest_capture_casino_charges_per_guest_method', 'guest_capture_x_charges_per_guest_method'
                    => fn ($k) => "{$g->pick("cap$i", $k)}/100*{$k->x('rm.guests')}*{$k->r("px$i")}*{$k->cal('op')}",
                'guest_capture_x_meals_per_guest_x_cover_value_per_meal_method'
                    => fn ($k) => "{$g->pick("cap$i", $k)}/100*{$k->x('rm.guests')}*{$k->r("px$i")}*{$g->pick("meal$i", $k)}*{$k->cal('op')}",
                'cover_count_target_per_day_x_cover_value_method', 'guest_count_target_per_day_charges_per_guest_method'
                    => fn ($k) => "{$k->cal('days')}*{$g->pick("tgt$i", $k)}*{$k->r("px$i")}*{$k->cal('op')}",
                'percentage_from_rooms_revenue'
                    => fn ($k) => "{$g->pick("pct$i", $k)}/100*{$k->x('rm.rev')}*{$k->cal('op')}",
                // no method chosen: the application works out nothing for the facility
                default => fn ($k) => '0',
            };
            $g->line("rv$i", $x->t('fac.rev').' - '.$n, $formula, ['style' => 'num', 'total' => 'sum']);
            $keys[] = "rv$i";
        }
        // guests of the facility (the expenses per guest use them), as calculateFoodsOrCasinoOrOtherGuestCount works them out
        $gKeys = [];
        foreach ($rows as $i => $row) {
            $n = $m->name($kind, $row[$cfg['id']] ?? null);
            $method = (string) ($row['f&b_facilities'] ?? '');
            $guests = match ($method) {
                'guest_capture_x_cover_value_per_guest_method', 'guest_capture_casino_charges_per_guest_method', 'guest_capture_x_charges_per_guest_method', 'guest_capture_x_meals_per_guest_x_cover_value_per_meal_method'
                    => fn ($k) => "{$g->pick("cap$i", $k)}/100*{$k->x('rm.guests')}*{$k->cal('op')}",
                'cover_count_target_per_day_x_cover_value_method', 'guest_count_target_per_day_charges_per_guest_method'
                    => fn ($k) => "{$k->cal('days')}*{$g->pick("tgt$i", $k)}*{$k->cal('op')}",
                'percentage_from_rooms_revenue'
                    => fn ($k) => "{$g->pick("pct$i", $k)}/100*{$k->x('rm.guests')}*{$k->cal('op')}",
                default => fn ($k) => '0',
            };
            $g->line("gc$i", $x->t('fac.guests').' - '.$n, $guests, ['style' => 'num', 'total' => 'sum']);
            $gKeys[] = "gc$i";
        }
        $g->line('gc', $x->t('fac.totGuests'), fn ($k) => $gKeys === [] ? '0' : implode('+', array_map(fn ($kk) => $k->r($kk), $gKeys)), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('rev', $x->t($key.'.total'), fn ($k) => $keys === [] ? '0' : implode('+', array_map(fn ($kk) => $k->r($kk), $keys)), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
