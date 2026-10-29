<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\Sheet;

// Hard construction, soft construction and furniture, fixtures & equipment (FF&E): what is spent each month, what is paid to the contractor and how the
// payments are funded, as ConstructionExecutionAndPayment, SoftConstructionExecutionAndPayment, FfeExecutionAndPayment and ContractPaymentService do.
//   execution     the cost spread over the months of the work: straight line, S-curve, steady growth or steady decline (the method is fixed in the file)
//   payments      a down payment in the start month + two shares of every month's work paid `days` later (no months when under 30 days, else round(days / 30))
//   equity        the equity share of the total cost pays first (the down payment first, then the months in order); the rest is drawn from the loan
// The application starts the S-curve, growth and decline methods at the start of the development (not at the start month of the screen); the straight
// line starts at the start month of the screen, and the down payment is always paid then.
final class InvestmentSheet
{
    public static function build(Ctx $x): void
    {
        $m = $x->m;
        $p = $m->p;
        $a = $m->acquisition;
        $ffe = $m->ffe;
        $sh = $x->sheet('inv', $x->t('n.inv'));
        $g = new Grid($x, $sh, 'inv');
        $g->title($x->t('inv.title'), $x->t('inv.sub'));

        $dev = (int) ($p['development_start_date'] ?? 0);
        $g->section($x->t('inv.timing'));
        $g->scalar('dev', $x->t('inv.devStart'), $dev, 'lock_int', $x->t('c.locked'));

        $blocks = [];
        if ($a !== null) {
            $blocks['hc'] = [
                'label' => $x->t('inv.hard'), 'cost' => (float) ($a['hard_construction_cost'] ?? 0), 'cont' => (float) ($a['hard_construction_contingency_rate'] ?? 0),
                'start' => is_null($a['hard_construction_start_date'] ?? null) ? $dev : (int) $a['hard_construction_start_date'], 'dur' => (int) ($a['hard_construction_duration'] ?? 0),
                'method' => (string) ($a['hard_execution_method'] ?? ''), 'dp' => (float) ($a['hard_down_payment'] ?? 0),
                'r1' => (float) ($a['hard_balance_rate_one'] ?? 0), 'd1' => (float) ($a['hard_due_one'] ?? 0), 'r2' => (float) ($a['hard_balance_rate_two'] ?? 0), 'd2' => (float) ($a['hard_due_two'] ?? 0),
                'equity' => (float) ($a['hard_equity_funding'] ?? 0), 'straightStart' => 'start',
            ];
            $blocks['sc'] = [
                'label' => $x->t('inv.soft'), 'cost' => (float) ($a['soft_construction_cost'] ?? 0), 'cont' => (float) ($a['soft_construction_contingency_rate'] ?? 0),
                'start' => ((int) ($a['soft_construction_start_date'] ?? 0)) ?: $dev, 'dur' => (int) ($a['soft_construction_duration'] ?? 0),
                'method' => (string) ($a['soft_execution_method'] ?? ''), 'dp' => (float) ($a['soft_down_payment'] ?? 0),
                'r1' => (float) ($a['soft_balance_rate_one'] ?? 0), 'd1' => (float) ($a['soft_due_one'] ?? 0), 'r2' => (float) ($a['soft_balance_rate_two'] ?? 0), 'd2' => (float) ($a['soft_due_two'] ?? 0),
                'equity' => 100.0, 'straightStart' => 'start',
            ];
        }
        if ($ffe !== null) {
            $total = 0.0;
            foreach ($m->ffeItems as $item) {
                $total += (float) ($item['item_cost'] ?? 0) * (1 + (float) ($item['contingency_rate'] ?? 0) / 100);
            }
            $blocks['fe'] = [
                'label' => $x->t('inv.ffe'), 'cost' => $total, 'cont' => 0.0, 'start' => ((int) ($ffe['start_date'] ?? 0)) ?: $dev, 'dur' => (int) ($ffe['duration'] ?? 0),
                'method' => (string) ($ffe['execution_method'] ?? ''), 'dp' => (float) ($ffe['down_payment'] ?? 0),
                'r1' => (float) ($ffe['balance_rate_one'] ?? 0), 'd1' => (float) ($ffe['due_one'] ?? 0), 'r2' => (float) ($ffe['balance_rate_two'] ?? 0), 'd2' => (float) ($ffe['due_two'] ?? 0),
                'equity' => (float) ($ffe['ffe_equity_funding'] ?? 0), 'straightStart' => 'start', 'items' => true,
            ];
        }

        // ── inputs ──
        $g->blank();
        foreach ($blocks as $key => $b) {
            $g->section($b['label']);
            if (! empty($b['items'])) {
                foreach ($m->ffeItems as $i => $item) {
                    $g->scalar("ic$i", $x->t('inv.itemCost').' - '.($item['name'] ?? ''), (float) ($item['item_cost'] ?? 0), 'in_num');
                    $g->scalar("ik$i", $x->t('inv.itemCont').' - '.($item['name'] ?? ''), (float) ($item['contingency_rate'] ?? 0), 'in_pct');
                }
                $costFormula = $m->ffeItems === [] ? '0' : implode('+', array_map(fn ($i) => "{$g->lcell("ic$i")}*(1+{$g->lcell("ik$i")}/100)", array_keys($m->ffeItems)));
                $g->scalar("{$key}tc", $x->t('inv.total'), $costFormula, 'num2', null, true);
            } else {
                $g->scalar("{$key}cost", $x->t('inv.cost'), $b['cost'], 'in_num');
                $g->scalar("{$key}cont", $x->t('inv.cont'), $b['cont'], 'in_pct');
                $g->scalar("{$key}tc", $x->t('inv.total'), "{$g->lcell("{$key}cost")}*(1+{$g->lcell("{$key}cont")}/100)", 'num2', null, true);
            }
            $g->scalar("{$key}start", $x->t('inv.start'), $b['start'], 'lock_int', $x->t('c.locked'));
            $g->scalar("{$key}dur", $x->t('inv.dur'), $b['dur'], 'lock_int', $x->t('c.locked'));
            // the first and the last month of the work (the straight line starts at the start month of the screen, the other methods at the start of the development)
            $g->scalar("{$key}es", $x->t('inv.execStart'), $b['method'] === 'straight-line' ? $g->lcell("{$key}start") : $g->lcell('dev'), 'lock_int', null, true);
            $g->scalar("{$key}ee", $x->t('inv.execEnd'), "{$g->lcell("{$key}es")}+{$g->lcell("{$key}dur")}-1", 'lock_int', null, true);
            $g->scalar("{$key}dp", $x->t('inv.dp'), $b['dp'], 'in_pct');
            $g->scalar("{$key}r1", $x->t('inv.r1'), $b['r1'], 'in_pct');
            $g->scalar("{$key}d1", $x->t('inv.d1'), $b['d1'], 'in_int');
            $g->scalar("{$key}r2", $x->t('inv.r2'), $b['r2'], 'in_pct');
            $g->scalar("{$key}d2", $x->t('inv.d2'), $b['d2'], 'in_int');
            $g->scalar("{$key}l1", $x->t('col.lag').' 1', "IF({$g->lcell("{$key}d1")}<30,0,ROUND({$g->lcell("{$key}d1")}/30,0))", 'lock_int', null, true);
            $g->scalar("{$key}l2", $x->t('col.lag').' 2', "IF({$g->lcell("{$key}d2")}<30,0,ROUND({$g->lcell("{$key}d2")}/30,0))", 'lock_int', null, true);
            if ($key === 'sc') {
                $g->scalar("{$key}eq", $x->t('inv.equity'), 100, 'lock_int', $x->t('inv.softEquity'));
            } else {
                $g->scalar("{$key}eq", $x->t('inv.equity'), $b['equity'], 'in_pct');
            }
            $g->blank();
        }

        // ── month by month ──
        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        foreach ($blocks as $key => $b) {
            $method = $b['method'];
            $tc = $g->cell("{$key}tc");
            $dur = $g->cell("{$key}dur");
            // the straight line starts at the start month of the screen; the other methods at the start of the development
            $execStart = $method === 'straight-line' ? $g->cell("{$key}start") : $g->cell('dev');
            $k = fn ($kk) => "({$kk->cal('idx')}-$execStart+1)";
            if ($method === 's-curve') {
                $third = "ROUND(($dur-$dur*3/4)/4,0)";
                $g->line($key.'f', $x->t('inv.factor').' - '.$b['label'], fn ($kk) => "IF(OR({$k($kk)}<1,{$k($kk)}>$dur),0,IF({$k($kk)}<=$dur/4,IF({$k($kk)}=1,8,".($kk->m === 0 ? '0' : $kk->pr($key.'f'))."+1),IF({$k($kk)}<=$dur/2,".($kk->m === 0 ? '0' : $kk->pr($key.'f'))."+1,IF({$k($kk)}<=$dur*3/4+$third,".($kk->m === 0 ? '0' : $kk->pr($key.'f'))."-0.75,".($kk->m === 0 ? '0' : $kk->pr($key.'f'))."+0.5))))", ['style' => 'num2']);
            }
            $g->line($key.'ex', $x->t('inv.exec').' - '.$b['label'], function ($kk) use ($method, $k, $dur, $tc, $g, $key) {
                $in = "AND({$k($kk)}>=1,{$k($kk)}<=$dur)";

                return match ($method) {
                    'straight-line' => "IF($in,$tc/$dur,0)",
                    'steady-growth' => "IF($in,$tc/($dur*($dur+1)/2)*{$k($kk)},0)",
                    'steady-decline' => "IF($in,$tc/($dur*($dur+1)/2)*($dur-{$k($kk)}+1),0)",
                    's-curve' => "IF(SUM({$g->rng($key.'f')})=0,0,$tc*{$kk->r($key.'f')}/SUM({$g->rng($key.'f')}))",
                    default => '0',
                };
            }, ['style' => 'num', 'total' => 'sum']);
            $g->line($key.'pay', $x->t('inv.paid').' - '.$b['label'], fn ($kk) => "IF({$kk->cal('idx')}={$g->cell("{$key}start")},{$g->cell("{$key}dp")}/100*$tc,0)+{$g->cell("{$key}r1")}/100*{$x->shift($g->rng($key.'ex'), $g->cell("{$key}l1"), $kk->c)}+{$g->cell("{$key}r2")}/100*{$x->shift($g->rng($key.'ex'), $g->cell("{$key}l2"), $kk->c)}", ['style' => 'num', 'total' => 'sum']);
            $g->line($key.'cum', $x->t('inv.cumPaid').' - '.$b['label'], fn ($kk) => ($kk->m === 0 ? '0' : $kk->pr($key.'cum')).'+'.$kk->r($key.'pay'), ['style' => 'num']);
            // the equity pays first, in the order the application keeps its payments: the down payment month first, then the work months from the first, each
            // with its first share (due `lag 1` months later) before its second (`lag 2` months later) - so a month whose first share comes later than the next
            // month's second share is paid for before it. A payment month is ranked by the first work month and share that created it.
            $in = fn ($e) => "AND($e>=$execStart,$e<=$execStart+$dur-1)";
            $g->line($key.'sq', $x->t('inv.order').' - '.$b['label'], function ($kk) use ($g, $key, $in, $execStart) {
                $idx = $kk->cal('idx');
                $one = "{$idx}-{$g->cell("{$key}l1")}";
                $two = "{$idx}-{$g->cell("{$key}l2")}";

                return "IF($idx={$g->cell("{$key}start")},-1,MIN(IF(AND({$g->cell("{$key}r1")}<>0,{$in($one)}),($one-$execStart)*2,1000000),IF(AND({$g->cell("{$key}r2")}<>0,{$in($two)}),($two-$execStart)*2+1,1000000)))";
            }, ['style' => 'flag']);
            $g->line($key.'eq', $x->t('inv.equityPaid').' - '.$b['label'], fn ($kk) => "MAX(0,MIN({$kk->r($key.'pay')},$tc*{$g->cell("{$key}eq")}/100-SUMPRODUCT(({$g->rng($key.'sq')}<{$kk->r($key.'sq')})*{$g->rng($key.'pay')})))", ['style' => 'num', 'total' => 'sum']);
            $g->line($key.'ln', $x->t('inv.loanDrawn').' - '.$b['label'], fn ($kk) => $kk->r($key.'pay').'-'.$kk->r($key.'eq'), ['style' => 'num', 'total' => 'sum']);
        }
        $sh->freeze(4, Ctx::FIRST);
    }
}
