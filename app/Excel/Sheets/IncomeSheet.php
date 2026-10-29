<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\K;

// The income statement, month by month, as the application builds the one it uses for the balance sheet (ReportItems::forInterval for a year):
//   revenue and departmental expenses -> departments gross profit -> undistributed expenses and the other deductions (base fee, property taxes and insurance)
//   -> EBITDA -> incentive fee, depreciation -> EBIT -> loan interest -> EBT -> corporate tax -> net profit.
// Only the months from the opening to the end of the study are in it. The incentive fee and the corporate tax are worked out once a year, from the profit of the
// calendar year, and fall in December. The corporate tax is only charged when the study has an incentive fee line (as the application does).
final class IncomeSheet
{
    private const STREAMS = [
        'rooms' => ['rm.rev', 'is.rooms', 'xr'],
        'foods' => ['fb.rev', 'is.foods', 'xf'],
        'gaming' => ['cs.rev', 'is.gaming', 'xg'],
        'meetings' => ['mt.rev', 'is.meetings', 'xm'],
        'others' => ['ot.rev', 'is.others', 'xo'],
    ];

    private const UNDISTRIBUTED = ['general' => ['is.general', 'xe'], 'marketing' => ['is.marketing', 'xk'], 'property' => ['is.property', 'xp'], 'energy' => ['is.energy', 'xn']];

    public static function build(Ctx $x): void
    {
        $m = $x->m;
        $has = fn (string $key) => isset($x->rows[$key]);
        $incentive = collect($m->managementFees)->first(fn ($f) => ($f['model_name'] ?? '') === 'management' && ($f['section_name'] ?? '') === 'incentive_management_fees_as_percentage_from_revenues');

        $sh = $x->sheet('is', $x->t('n.is'));
        $g = new Grid($x, $sh, 'is');
        $g->title($x->t('is.title'), $x->t('is.sub'));
        $g->section($x->t('tx.assump'));
        $g->yearHeader('yh');
        $g->yearInput('ifp', $x->t('is.incentive'), $incentive ? $m->yearly($incentive['payload'] ?? []) : array_fill(0, $m->Y, 0.0), 'in_pct');
        $g->blank();

        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        // only the months from the opening to the end of the study count
        $o = fn (K $k) => $k->cal('op').'*'.$k->cal('study');

        $streams = array_filter(self::STREAMS, fn ($s) => $has($s[0]));
        $g->plan(array_merge(
            array_map(fn ($s) => 'r'.$s, array_keys($streams)),
            ['rtotal'],
            ['_'],
            array_map(fn ($s) => 'd'.$s, array_keys($streams)),
            ['dtotal'],
            ['_'],
            array_map(fn ($s) => 'g'.$s, array_keys($streams)),
            ['gtotal'],
            ['_'],
            array_map(fn ($u) => 'u'.$u, array_keys(self::UNDISTRIBUTED)),
            ['utotal'],
            ['_', 'obase', 'otax', 'ototal', 'ebitda', 'ae', 'inc', 'dep', 'ebit', '_'],
            ['ipp', 'ild', 'ihc', 'ife', 'itotal', 'ebt', 'aebt', 'tax', 'np'],
        ));

        foreach ($streams as $name => [$revRow, , $expSheet]) {
            $g->line('r'.$name, $x->t('is.rev.'.$name), fn (K $k) => "{$o($k)}*{$k->x($revRow)}", ['style' => 'num', 'total' => 'sum']);
        }
        $g->line('rtotal', $x->t('is.revTotal'), fn (K $k) => implode('+', array_map(fn ($n) => $k->r('r'.$n), array_keys($streams))) ?: '0', ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();
        foreach ($streams as $name => [, , $expSheet]) {
            $g->line('d'.$name, $x->t('is.dir.'.$name), fn (K $k) => $has("$expSheet.total") ? "{$o($k)}*{$k->x("$expSheet.total")}" : '0', ['style' => 'num', 'total' => 'sum']);
        }
        $g->line('dtotal', $x->t('is.dirTotal'), fn (K $k) => implode('+', array_map(fn ($n) => $k->r('d'.$n), array_keys($streams))) ?: '0', ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();
        foreach ($streams as $name => $s) {
            $g->line('g'.$name, $x->t('is.gp.'.$name), fn (K $k) => $k->r('r'.$name).'-'.$k->r('d'.$name), ['style' => 'num', 'total' => 'sum']);
        }
        $g->line('gtotal', $x->t('is.gpTotal'), fn (K $k) => $k->r('rtotal').'-'.$k->r('dtotal'), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();
        foreach (self::UNDISTRIBUTED as $name => [, $expSheet]) {
            $g->line('u'.$name, $x->t('is.und.'.$name), fn (K $k) => $has("$expSheet.total") ? "{$o($k)}*{$k->x("$expSheet.total")}" : '0', ['style' => 'num', 'total' => 'sum']);
        }
        $g->line('utotal', $x->t('is.undTotal'), fn (K $k) => implode('+', array_map(fn ($n) => $k->r('u'.$n), array_keys(self::UNDISTRIBUTED))), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();
        $g->line('obase', $x->t('is.baseFee'), fn (K $k) => $has('tx.bf') ? "{$o($k)}*{$k->x('tx.bf')}" : '0', ['style' => 'num', 'total' => 'sum']);
        $g->line('otax', $x->t('tx.taxIns'), fn (K $k) => $has('tx.tin') ? "{$o($k)}*{$k->x('tx.tin')}" : '0', ['style' => 'num', 'total' => 'sum']);
        $g->line('ototal', $x->t('is.otherTotal'), fn (K $k) => $k->r('obase').'+'.$k->r('otax'), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('ebitda', $x->t('is.ebitda'), fn (K $k) => "{$k->r('gtotal')}-{$k->r('utotal')}-{$k->r('ototal')}", ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        // a year's EBITDA, shown in each month of the year, and the incentive fee on it (a loss earns none): charged in December
        $g->line('ae', $x->t('is.yearEbitda'), fn (K $k) => "SUMIF({$x->calRng('year')},{$k->cal('year')},{$g->rng('ebitda')})", ['style' => 'num']);
        $g->line('inc', $x->t('is.incentiveFee'), fn (K $k) => $incentive
            ? "IF({$k->cal('mnum')}=12,IF({$k->r('ae')}<0,0,{$g->pick('ifp', $k)}/100*{$k->r('ae')}),0)"
            : '0', ['style' => 'num', 'total' => 'sum']);
        $g->line('dep', $x->t('fa.depIncome'), fn (K $k) => $has('fa.depPl') ? $k->x('fa.depPl') : ($has('su.dep') ? "IF({$k->cal('idx')}>=Z_OpIdx,{$k->x('su.dep')},0)" : '0'), ['style' => 'num', 'total' => 'sum']);
        $g->line('ebit', $x->t('is.ebit'), fn (K $k) => "{$o($k)}*({$k->r('ebitda')}-{$k->r('dep')}-{$k->r('inc')})", ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();
        $loans = ['ipp' => ['pp', 'pur.property'], 'ild' => ['ld', 'pur.land'], 'ihc' => ['hc', 'inv.hard'], 'ife' => ['fe', 'inv.ffe']];
        foreach ($loans as $row => [$key]) {
            $label = ['pp' => 'is.int.pp', 'ld' => 'is.int.ld', 'hc' => 'is.int.hc', 'fe' => 'is.int.fe'][$key];
            $g->line($row, $x->t($label), fn (K $k) => $has("ln.{$key}int") ? "{$o($k)}*{$k->x("ln.{$key}int")}" : '0', ['style' => 'num', 'total' => 'sum']);
        }
        $g->line('itotal', $x->t('is.intTotal'), fn (K $k) => implode('+', array_map(fn ($r) => $k->r($r), array_keys($loans))), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('ebt', $x->t('is.ebt'), fn (K $k) => $k->r('ebit').'-'.$k->r('itotal'), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('aebt', $x->t('is.yearEbt'), fn (K $k) => "SUMIF({$x->calRng('year')},{$k->cal('year')},{$g->rng('ebt')})", ['style' => 'num']);
        $g->line('tax', $x->t('is.tax'), fn (K $k) => $incentive
            ? "IF({$k->cal('mnum')}=12,IF({$k->r('aebt')}<0,0,Z_Tax/100*{$k->r('aebt')}),0)"
            : '0', ['style' => 'num', 'total' => 'sum']);
        $g->line('np', $x->t('is.np'), fn (K $k) => $k->r('ebt').'-'.$k->r('tax'), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
