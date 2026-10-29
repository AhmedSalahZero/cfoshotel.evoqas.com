<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\ModelData;

// Start-up costs and pre-operating expenses, as StartUpCostService works them out. Both are capitalised and written off over the 12 months from the opening.
//   start-up cost       one amount at a month, paid in cash (%) and later (% after `days`: no months when under 30 days, else round(days / 30)); what is not
//                       paid yet is owed to the supplier. Written off from the opening (or from the month after it was spent, when that is after the opening).
//   pre-operating       an amount for every month, paid in the month. All of it is written off over the 12 months from the opening.
// Only the months of the study count (the statements stop there).
final class StartUpSheet
{
    public static function build(Ctx $x): void
    {
        $m = $x->m;
        $lines = array_values(array_filter($m->expenses, fn ($e) => ($e['model_name'] ?? '') === 'start'));
        $startUp = array_values(array_filter($lines, fn ($e) => ($e['section_name'] ?? '') === 'start_up_cost'));
        $preOp = array_values(array_filter($lines, fn ($e) => ($e['section_name'] ?? '') === 'pre_operating_expense'));

        $sh = $x->sheet('su', $x->t('n.su'));
        $g = new Grid($x, $sh, 'su');
        $g->title($x->t('su.title'), $x->t('su.sub'));

        $g->section($x->t('su.items'));
        foreach ($startUp as $i => $e) {
            $g->scalar("cost$i", $x->t('su.cost').' - '.$e['name'], (float) ($e['start_up_cost'] ?? 0), 'in_num');
            $g->scalar("at$i", $x->t('su.month').' - '.$e['name'], (float) ($e['date'] ?? 0), 'in_int');
            $g->scalar("cp$i", $x->t('x.cash').' - '.$e['name'], (float) ($e['cash_payment_percentage'] ?? 0), 'in_pct');
            $g->scalar("dp$i", $x->t('x.deferred').' - '.$e['name'], (float) ($e['deferred_payment_percentage'] ?? 0), 'in_pct');
            $g->scalar("dd$i", $x->t('x.dueDays').' - '.$e['name'], (float) ($e['due_days'] ?? 0), 'in_int');
            $g->scalar("dl$i", $x->t('col.lag').' - '.$e['name'], "IF({$g->lcell("dd$i")}<30,0,ROUND({$g->lcell("dd$i")}/30,0))", 'lock_int', null, true);
            $g->scalar("ds$i", $x->t('su.depStart').' - '.$e['name'], "IF({$g->lcell("at$i")}<Z_OpIdx,Z_OpIdx,{$g->lcell("at$i")}+1)", 'lock_int', null, true);
        }
        $g->blank();

        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        foreach ($preOp as $j => $e) {
            $payload = ModelData::arr($e['manpower_payload'] ?? []);
            $values = [];
            foreach ($payload as $month => $amount) {
                $values[(int) $month] = (float) $amount;
            }
            $g->inputLine("po$j", $x->t('su.preOp').' - '.$e['name'], $values, 'in_num');
        }

        $stu = fn ($k) => $k->cal('study');
        $payKeys = $depKeys = $gross = $accum = [];
        foreach ($startUp as $i => $e) {
            $g->plan(["pay$i", "pb$i", "gr$i", "dp_$i", "ac$i", "en$i"]);
            $g->line("pay$i", $x->t('su.paid').' - '.$e['name'], fn ($k) => "IF({$k->cal('idx')}={$g->cell("at$i")},{$g->cell("cp$i")}/100*{$g->cell("cost$i")},0)+IF({$k->cal('idx')}={$g->cell("at$i")}+{$g->cell("dl$i")},{$g->cell("dp$i")}/100*{$g->cell("cost$i")},0)", ['style' => 'num', 'total' => 'sum']);
            $g->line("pb$i", $x->t('su.owed').' - '.$e['name'], fn ($k) => ($k->m === 0 ? '0' : $k->pr("pb$i")).'+IF('.$k->cal('idx').'='.$g->cell("at$i").','.$g->cell("cost$i").",0)-{$k->r("pay$i")}", ['style' => 'num']);
            $g->line("gr$i", $x->t('su.gross').' - '.$e['name'], fn ($k) => "IF({$k->cal('idx')}>={$g->cell("at$i")},{$g->cell("cost$i")},0)", ['style' => 'num']);
            $g->line("dp_$i", $x->t('su.dep').' - '.$e['name'], fn ($k) => "IF(AND({$k->cal('idx')}>={$g->cell("ds$i")},{$k->cal('idx')}<={$g->cell("ds$i")}+11),{$g->cell("cost$i")}/12,0)", ['style' => 'num', 'total' => 'sum']);
            $g->line("ac$i", $x->t('su.accum').' - '.$e['name'], fn ($k) => ($k->m === 0 ? '0' : $k->pr("ac$i")).'+'.$k->r("dp_$i"), ['style' => 'num']);
            $g->line("en$i", $x->t('su.net').' - '.$e['name'], fn ($k) => $k->r("gr$i").'-'.$k->r("ac$i"), ['style' => 'num']);
        }
        foreach ($preOp as $j => $e) {
            $g->plan(["gp$j", "dq$j", "aq$j", "nq$j"]);
            $g->line("gp$j", $x->t('su.gross').' - '.$e['name'], fn ($k) => ($k->m === 0 ? '0' : $k->pr("gp$j")).'+'.$k->r("po$j"), ['style' => 'num']);
            $g->line("dq$j", $x->t('su.dep').' - '.$e['name'], fn ($k) => "IF(AND({$k->cal('idx')}>=Z_OpIdx,{$k->cal('idx')}<=Z_OpIdx+11),SUMPRODUCT({$x->calRng('study')}*{$g->rng("po$j")})/12,0)", ['style' => 'num', 'total' => 'sum']);
            $g->line("aq$j", $x->t('su.accum').' - '.$e['name'], fn ($k) => ($k->m === 0 ? '0' : $k->pr("aq$j")).'+'.$k->r("dq$j"), ['style' => 'num']);
            $g->line("nq$j", $x->t('su.net').' - '.$e['name'], fn ($k) => $k->r("gp$j").'-'.$k->r("aq$j"), ['style' => 'num']);
        }

        $su = array_keys($startUp);
        $po = array_keys($preOp);
        $sumOf = fn (array $parts) => fn ($k) => $stu($k).'*('.($parts === [] ? '0' : implode('+', array_map(fn ($p) => $k->r($p), $parts))).')';
        $g->line('payments', $x->t('su.totPaid'), $sumOf(array_merge(array_map(fn ($i) => "pay$i", $su), array_map(fn ($j) => "po$j", $po))), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('dep', $x->t('su.totDep'), $sumOf(array_merge(array_map(fn ($i) => "dp_$i", $su), array_map(fn ($j) => "dq$j", $po))), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('payable', $x->t('su.totOwed'), $sumOf(array_map(fn ($i) => "pb$i", $su)), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('gross', $x->t('su.totGross'), $sumOf(array_merge(array_map(fn ($i) => "gr$i", $su), array_map(fn ($j) => "gp$j", $po))), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('accum', $x->t('su.totAccum'), $sumOf(array_merge(array_map(fn ($i) => "ac$i", $su), array_map(fn ($j) => "aq$j", $po))), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('net', $x->t('su.totNet'), $sumOf(array_merge(array_map(fn ($i) => "en$i", $su), array_map(fn ($j) => "nq$j", $po))), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
