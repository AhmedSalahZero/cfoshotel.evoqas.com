<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\ModelData;
use App\Excel\Sheet;

// The expenses of one department (rooms, F&B, gaming, meetings, other revenue, general, marketing, property, energy), as the application works them out:
//   % of revenue       the percentage of the year x the revenue the department follows (its own stream, or the whole hotel)
//   per night sold     nights sold x the cost per night      (rooms)
//   per guest          guests of the stream x the cost per guest
//   fixed monthly      the cost per month
//   manpower           head count of the month x the gross monthly salary (net salary / (1 - tax % - insurance %))
//   disposables        % of the revenue is consumed; the stock kept (days of the coming months' consumption) sets what is bought, paid by the terms
//   fixed, paid        the monthly costs are paid at the start of each period (monthly, quarterly, ...): the part not yet used is a prepaid expense
// A cost per unit (night, guest, month, salary) is the value today grown to the opening by the escalation until opening (days / 365 years) and
// then by the yearly escalation after it.
final class ExpenseSheet
{
    private const PERCENT_ROOMS = 'variable_expenses_as_percentage_from_rooms_revenues';

    private const PERCENT_TOTAL = 'variable_expenses_as_percentage_from_total_revenues';

    private const PER_NIGHT = 'variable_expenses_as_cost_per_night_sold';

    private const PER_GUEST = 'variable_expenses_as_cost_per_guest';

    private const FIXED = 'fixed_monthly_expenses';

    private const DISPOSABLE = 'disposable_expenses_rate';

    private const MANPOWER = 'rooms_manpower_expense';

    /** @var array<string,array<string,mixed>> department => its model name in the saved rows, the revenue and the guests it follows */
    public const DEPARTMENTS = [
        'rooms' => ['model' => 'rooms', 'sheet' => 'xr', 'base' => 'rm.rev', 'guests' => 'rm.guests'],
        'foods' => ['model' => 'foods', 'sheet' => 'xf', 'base' => 'fb.rev', 'guests' => 'fb.gc'],
        'gaming' => ['model' => 'gaming', 'sheet' => 'xg', 'base' => 'cs.rev', 'guests' => 'cs.gc'],
        'meetings' => ['model' => 'meetings', 'sheet' => 'xm', 'base' => 'mt.rev', 'guests' => 'mt.gc'],
        'others' => ['model' => 'other', 'sheet' => 'xo', 'base' => 'ot.rev', 'guests' => 'ot.gc'],
        'general' => ['model' => 'general', 'sheet' => 'xe', 'base' => 'rev.total', 'guests' => null],
        'marketing' => ['model' => 'marketing', 'sheet' => 'xk', 'base' => 'rev.total', 'guests' => null],
        'property' => ['model' => 'property', 'sheet' => 'xp', 'base' => 'rev.total', 'guests' => null],
        'energy' => ['model' => 'energy', 'sheet' => 'xn', 'base' => 'rev.total', 'guests' => null],
    ];

    /** the columns of the saved rows behind each cost style: amount, escalation until opening, yearly escalation after it */
    private const COST_COLUMNS = [
        'night' => ['amount' => 'expense_per_night_sold', 'esc' => 'night_expense_escalation_rate', 'ann' => 'night_annual_escalation_rate'],
        'guest' => ['amount' => 'expense_per_guest_sold', 'esc' => 'guest_expense_escalation_rate', 'ann' => 'guest_annual_escalation_rate'],
        'guestNight' => ['amount' => 'expense_per_night_sold', 'esc' => 'night_expense_escalation_rate', 'ann' => 'guest_annual_escalation_rate'],
        'fixed' => ['amount' => 'expense_per_night_sold', 'esc' => 'night_expense_escalation_rate', 'ann' => 'guest_annual_escalation_rate'],
    ];

    public static function build(Ctx $x, string $dept): void
    {
        $cfg = self::DEPARTMENTS[$dept];
        $m = $x->m;
        $key = $cfg['sheet'];
        $sh = $x->sheet($key, $x->t('n.x.'.$dept));
        $g = new Grid($x, $sh, $key);
        $g->title($x->t('x.title.'.$dept), $x->t('x.sub'));

        $lines = array_values(array_filter($m->expenses, fn ($e) => ($e['model_name'] ?? '') === $cfg['model']));
        $of = fn (string $section) => array_values(array_filter($lines, fn ($e) => ($e['section_name'] ?? '') === $section));
        $percent = $of($dept === 'general' || $dept === 'marketing' ? self::PERCENT_TOTAL : self::PERCENT_ROOMS);
        $perNight = $of(self::PER_NIGHT);
        $perGuest = $of(self::PER_GUEST);
        $fixed = $of(self::FIXED);
        $manpower = $of(self::MANPOWER);
        $disposable = $of(self::DISPOSABLE);
        $guestStyle = in_array($dept, ['rooms', 'foods'], true) ? 'guest' : 'guestNight';

        // ── inputs ──
        $g->section($x->t('x.items'));
        $cost = function (array $rows, string $style, string $prefix, string $what) use ($g, $x) {
            $c = self::COST_COLUMNS[$style];
            foreach ($rows as $e) {
                $id = (int) $e['id'];
                $g->scalar("v$id", $x->t($what).' - '.$e['name'], (float) ($e[$c['amount']] ?? 0), 'in_num');
                $g->scalar("e$id", $x->t('fac.esc').' - '.$e['name'], (float) ($e[$c['esc']] ?? 0), 'in_pct');
                $g->scalar("a$id", $x->t('fac.annual').' - '.$e['name'], (float) ($e[$c['ann']] ?? 0), 'in_pct');
                $g->scalar("o$id", $x->t('fac.atOpening').' - '.$e['name'], "{$g->lcell("v$id")}*(1+{$g->lcell("e$id")}/100)^((Z_OpDate-Z_Start)/365)", 'num2', null, true);
            }
        };
        $cost($perNight, 'night', 'n', 'x.perNight');
        $cost($perGuest, $guestStyle, 'g', 'x.perGuest');
        $cost($fixed, 'fixed', 'f', 'x.fixed');
        foreach ($disposable as $e) {
            $id = (int) $e['id'];
            $g->scalar("ob$id", $x->t('x.opening').' - '.$e['name'], (float) ($e['beginning_inventory_balance_value'] ?? 0), 'in_num');
            $g->scalar("cd$id", $x->t('x.coverage').' - '.$e['name'], (float) ($e['inventory_coverage_days'] ?? 0), 'in_int');
            $g->scalar("cm$id", $x->t('x.coverageMonths').' - '.$e['name'], "IFERROR(INDEX({0.25,0.5,1,1.5,2,2.5,3,4,5,6},MATCH({$g->lcell("cd$id")},{7,15,30,45,60,75,90,120,150,180},0)),0)", 'num2', null, true);
            $g->scalar("cp$id", $x->t('x.cash').' - '.$e['name'], (float) ($e['cash_payment_percentage'] ?? 0), 'in_pct');
            $g->scalar("dp$id", $x->t('x.deferred').' - '.$e['name'], (float) ($e['deferred_payment_percentage'] ?? 0), 'in_pct');
            $g->scalar("dd$id", $x->t('x.dueDays').' - '.$e['name'], (float) ($e['due_days'] ?? 0), 'in_int');
            $g->scalar("dl$id", $x->t('col.lag').' - '.$e['name'], "IF({$g->lcell("dd$id")}<30,0,ROUND({$g->lcell("dd$id")}/30,0))", 'lock_int', null, true);
        }
        foreach ($manpower as $e) {
            $id = (int) $e['id'];
            $g->scalar("v$id", $x->t('x.salary').' - '.$e['name'], (float) ($e['current_net_salary'] ?? 0), 'in_num');
            $g->scalar("e$id", $x->t('fac.esc').' - '.$e['name'], (float) ($e['escalation_rate'] ?? 0), 'in_pct');
            $g->scalar("a$id", $x->t('fac.annual').' - '.$e['name'], (float) ($e['annual_escalation_rate'] ?? 0), 'in_pct');
            $g->scalar("t$id", $x->t('x.taxes').' - '.$e['name'], (float) ($e['salary_taxes'] ?? 0), 'in_pct');
            $g->scalar("s$id", $x->t('x.insurance').' - '.$e['name'], (float) ($e['social_insurance'] ?? 0), 'in_pct');
            $g->scalar("o$id", $x->t('x.gross').' - '.$e['name'], "{$g->lcell("v$id")}*(1+{$g->lcell("e$id")}/100)^((Z_OpDate-Z_Start)/365)/(1-{$g->lcell("t$id")}/100-{$g->lcell("s$id")}/100)", 'num2', null, true);
        }
        $g->blank();

        $g->section($x->t('fac.yearly'));
        $g->yearHeader('yh');
        foreach ($disposable as $e) {
            $g->yearInput('pc'.(int) $e['id'], $x->t('x.dispRate').' - '.$e['name'], $m->yearly($e['payload'] ?? []), 'in_pct');
        }
        foreach ($percent as $e) {
            $g->yearInput('pc'.(int) $e['id'], $x->t('x.percent').' - '.$e['name'], $m->yearly($e['payload'] ?? []), 'in_pct');
        }
        $g->blank();

        // ── month by month ──
        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        foreach ($manpower as $e) {
            $id = (int) $e['id'];
            $g->inputLine("hd$id", $x->t('x.heads').' - '.$e['name'], self::heads($m, $e), 'in_num');
        }

        $price = function (array $e, string $style) use ($g, $x) {
            $id = (int) $e['id'];
            $g->line("px$id", $x->t('fac.price').' - '.$e['name'], fn ($k) => "{$k->cal('op')}*{$g->cell("o$id")}*(1+{$g->cell("a$id")}/100)^{$k->cal('opyr')}", ['style' => 'num2']);
        };
        $keys = ['pct' => [], 'night' => [], 'guest' => [], 'fixed' => [], 'manpower' => [], 'disp' => []];
        foreach ($percent as $e) {
            $id = (int) $e['id'];
            $g->line("ex$id", $e['name'], fn ($k) => "{$g->pick("pc$id", $k)}/100*{$k->x($cfg['base'])}", ['style' => 'num', 'total' => 'sum']);
            $keys['pct'][] = "ex$id";
        }
        foreach ($perNight as $e) {
            $id = (int) $e['id'];
            $price($e, 'night');
            $g->line("ex$id", $e['name'], fn ($k) => "{$k->x('rm.nights')}*{$k->r("px$id")}", ['style' => 'num', 'total' => 'sum']);
            $keys['night'][] = "ex$id";
        }
        foreach ($perGuest as $e) {
            $id = (int) $e['id'];
            $price($e, $guestStyle);
            $g->line("ex$id", $e['name'], fn ($k) => "{$k->x($cfg['guests'])}*{$k->r("px$id")}", ['style' => 'num', 'total' => 'sum']);
            $keys['guest'][] = "ex$id";
        }
        foreach ($fixed as $e) {
            $id = (int) $e['id'];
            $price($e, 'fixed');
            $g->line("ex$id", $e['name'], fn ($k) => $k->r("px$id"), ['style' => 'num', 'total' => 'sum']);
            $keys['fixed'][] = "ex$id";
        }
        foreach ($manpower as $e) {
            $id = (int) $e['id'];
            $g->line("ex$id", $e['name'], fn ($k) => "{$k->cal('op')}*{$g->cell("o$id")}*(1+{$g->cell("a$id")}/100)^{$k->cal('opyr')}*{$k->r("hd$id")}", ['style' => 'num', 'total' => 'sum']);
            $keys['manpower'][] = "ex$id";
        }

        $dispKeys = [];
        foreach ($disposable as $e) {
            $id = (int) $e['id'];
            $g->line("ex$id", $e['name'], fn ($k) => "{$g->pick("pc$id", $k)}/100*{$k->x($cfg['base'])}", ['style' => 'num', 'total' => 'sum']);
            $dispKeys[] = "ex$id";
            $last12 = max(1, min(12, $m->D - $m->opIdx));
            $g->plan(["ib$id", "sf$id", "pu$id", "ie$id", "pay$id", "pb$id", "os$id"]);   // the start of a month points at the end of the month before
            $g->line("ib$id", $x->t('x.stockStart').' - '.$e['name'], fn ($k) => "{$k->cal('op')}*IF({$k->cal('oppos')}=0,{$g->cell("ob$id")},".($k->m === 0 ? '0' : $k->pr("ie$id")).')', ['style' => 'num']);
            $g->line("sf$id", $x->t('x.stockKept').' - '.$e['name'], function ($k) use ($g, $x, $id, $m, $last12) {
                if ($k->m === $m->D - 1) {
                    $from = Sheet::col(Ctx::FIRST + $m->D - $last12);
                    $to = Sheet::col(Ctx::FIRST + $m->D - 1);

                    return "{$k->cal('op')}*SUM({$from}{$g->rowOf("ex$id")}:{$to}{$g->rowOf("ex$id")})/$last12*{$g->cell("cm$id")}";
                }
                $parts = [];
                for ($j = 1; $j <= 6; $j++) {
                    if ($k->m + $j < $m->D) {
                        $parts[] = 'MAX(0,MIN(1,'.$g->cell("cm$id").'-'.($j - 1).'))*'.Sheet::col(Ctx::FIRST + $k->m + $j).$g->rowOf("ex$id");
                    }
                }

                return $k->cal('op').'*('.($parts === [] ? '0' : implode('+', $parts)).')';
            }, ['style' => 'num']);
            $g->line("pu$id", $x->t('x.bought').' - '.$e['name'], fn ($k) => "{$k->cal('op')}*MAX(0,{$k->r("ex$id")}+{$k->r("sf$id")}-{$k->r("ib$id")})", ['style' => 'num', 'total' => 'sum']);
            $g->line("ie$id", $x->t('x.stockEnd').' - '.$e['name'], fn ($k) => "{$k->cal('op')}*({$k->r("ib$id")}+{$k->r("pu$id")}-{$k->r("ex$id")})", ['style' => 'num']);
            $g->line("pay$id", $x->t('x.paid').' - '.$e['name'], fn ($k) => "{$g->cell("cp$id")}/100*{$k->r("pu$id")}+{$g->cell("dp$id")}/100*{$x->shift($g->rng("pu$id"), $g->cell("dl$id"), $k->c)}", ['style' => 'num', 'total' => 'sum']);
            $g->line("pb$id", $x->t('x.owed').' - '.$e['name'], fn ($k) => ($k->m === 0 ? '0' : $k->pr("pb$id")).'+'.$k->r("pu$id").'-'.$k->r("pay$id"), ['style' => 'num']);
            $g->line("os$id", $x->t('x.openingPaid').' - '.$e['name'], fn ($k) => "{$k->cal('op')}*IF({$k->cal('oppos')}=0,{$g->cell("ob$id")},0)", ['style' => 'num', 'total' => 'sum']);
        }
        $fixKeys = [];
        foreach ($fixed as $e) {
            $id = (int) $e['id'];
            $interval = (string) ($e['opex_payment_terms'] ?? 'monthly');
            $period = ['monthly' => 1, 'quarterly' => 3, 'quartly' => 3, 'semi-annually' => 6, 'semi annually' => 6, 'annually' => 12][$interval] ?? 1;
            $g->line("fp$id", $x->t('x.paid').' - '.$e['name'], function ($k) use ($g, $x, $id, $period, $m) {
                $cells = [];
                for ($j = 0; $j < $period; $j++) {
                    if ($k->m + $j < $m->D) {
                        $cells[] = Sheet::col(Ctx::FIRST + $k->m + $j).$g->rowOf("ex$id");
                    }
                }

                return "IF(AND({$k->cal('op')}=1,MOD({$k->cal('oppos')},$period)=0),".implode('+', $cells).',0)';
            }, ['style' => 'num', 'total' => 'sum']);
            $g->line("fb$id", $x->t('x.prepaid').' - '.$e['name'], fn ($k) => ($k->m === 0 ? '0' : $k->pr("fb$id")).'+'.$k->r("ex$id").'-'.$k->r("fp$id"), ['style' => 'num']);
            $fixKeys[] = $id;
        }

        $keys['disp'] = $dispKeys;
        $sum = fn (array $list) => fn ($k) => $list === [] ? '0' : implode('+', array_map(fn ($kk) => $k->r($kk), $list));
        foreach (['pct' => 'x.sumPercent', 'night' => 'x.sumNight', 'guest' => 'x.sumGuest', 'fixed' => 'x.sumFixed', 'manpower' => 'x.sumManpower', 'disp' => 'x.sumDisp'] as $cat => $label) {
            $g->line("t_$cat", $x->t($label), $sum($keys[$cat]), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        }
        $agg = fn (string $prefix, array $ids) => fn ($k) => $ids === [] ? '0' : implode('+', array_map(fn ($i) => $k->r($prefix.$i), $ids));
        $dIds = array_map(fn ($e) => (int) $e['id'], $disposable);
        $g->line('t_inv', $x->t('x.sumStock'), $agg('ie', $dIds), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('t_pay', $x->t('x.sumPaid'), $agg('pay', $dIds), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('t_owed', $x->t('x.sumOwed'), $agg('pb', $dIds), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('t_open', $x->t('x.sumOpening'), $agg('os', $dIds), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('t_fpay', $x->t('x.sumFixedPaid'), $agg('fp', $fixKeys), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('t_fbal', $x->t('x.sumPrepaid'), $agg('fb', $fixKeys), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('total', $x->t('x.total'), fn ($k) => implode('+', array_map(fn ($cat) => $k->r("t_$cat"), array_keys($keys))), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $sh->freeze(4, Ctx::FIRST);
    }

    /**
     * The head count of every month, as the application reads it: the figure saved for the month, or the last saved figure for the months after it.
     *
     * @return array<int,float>
     */
    private static function heads(ModelData $m, array $line): array
    {
        $payload = ModelData::arr($line['manpower_payload'] ?? []);
        if ($payload === []) {
            return [];
        }
        $keys = array_keys($payload);
        $last = (int) end($keys);
        $heads = [];
        for ($i = 0; $i < $m->D; $i++) {
            if (isset($payload[$i])) {
                $heads[$i] = (float) $payload[$i];
            } elseif ($i > $last) {
                $heads[$i] = (float) $payload[$last];
            }
        }

        return $heads;
    }
}
