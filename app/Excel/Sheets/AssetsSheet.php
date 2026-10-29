<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\K;

// The fixed assets: projects under progress, building, furniture / fixtures / equipment (FF&E), land, their depreciation and replacement, as
// ProjectsUnderProgress, PropertyAcquisition::calculatePropertyAssets, FFE::calculateFFEAssets and calculateLandFixedAssets work them out.
//
//   projects under progress   what is spent on the construction (and on the FF&E), plus the loan interest of the building period, is held here until the month
//                             after the work and the interest period end, and is then moved to the fixed assets in that month.
//   assets                    each is added in its month (the purchase, or the move from the projects under progress), grows by the replacement cost in every
//                             replacement month, and is written off in equal monthly shares over its life, starting in the opening month (or the month after the move).
//   Every addition (and every replacement) is written off over its own run of months; the lines add up.
// Months after the study ends do not count; the opening and the months before it carry no write-off in the income statement.
final class AssetsSheet
{
    public static function build(Ctx $x): void
    {
        $m = $x->m;
        // there is work to hold under progress when the application has execution months for it (a method it knows and a duration)
        $hasHard = $m->acquisition !== null && in_array((string) ($m->acquisition['hard_execution_method'] ?? ''), ['straight-line', 's-curve'], true)
            && (int) ($m->acquisition['hard_construction_duration'] ?? 0) > 0 && isset($x->rows['inv.hcex']);
        $hasFfe = $m->ffe !== null && isset($x->rows['inv.feex']) && (int) ($m->ffe['duration'] ?? 0) > 0
            && in_array((string) ($m->ffe['execution_method'] ?? ''), ['straight-line', 's-curve', 'steady-growth', 'steady-decline'], true);
        if ($m->property === null && ! $hasHard && ! $hasFfe && $m->acquisition === null) {
            return;
        }

        $sh = $x->sheet('fa', $x->t('n.fa'));
        $g = new Grid($x, $sh, 'fa');
        $g->title($x->t('fa.title'), $x->t('fa.sub'));
        $op = 'Z_OpIdx';

        $breakdown = fn (string $name) => collect($m->breakdown)->first(fn ($b) => ($b['name'] ?? '') === $name);
        $building = $breakdown('Building Cost');
        $landItem = $breakdown('Land Cost');
        $ffeItem = $breakdown('Furniture, Fixture & Equipment Cost');
        $pr = $m->property;
        $has = fn (string $key) => isset($x->rows[$key]);

        // ── what is assumed ──
        $g->section($x->t('fa.assump'));
        if ($pr !== null) {
            $g->scalar('p', $x->t('fa.pMonth'), (int) ($pr['purchase_date'] ?? 0), 'lock_int', $x->t('c.locked'));
            $g->scalar('pc', $x->t('fa.pCost'), ((float) ($pr['property_purchase_cost'] ?? 0)) > 0 ? 1 : 0, 'lock_int', $x->t('c.locked'));
            $g->scalar('amtB', $x->t('fa.amtB'), (float) ($building['item_amount'] ?? 0), 'in_num');
            $g->scalar('amtF', $x->t('fa.amtF'), (float) ($ffeItem['item_amount'] ?? 0), 'in_num');
            $g->scalar('amtL', $x->t('fa.amtL'), (float) ($landItem['item_amount'] ?? 0), 'in_num');
            $g->scalar('pctB', $x->t('fa.pctB'), (float) ($building['property_cost_percentage'] ?? 0), 'in_pct');
            $g->scalar('pctF', $x->t('fa.pctF'), (float) ($ffeItem['property_cost_percentage'] ?? 0), 'in_pct');
            $g->scalar('pctL', $x->t('fa.pctL'), (float) ($landItem['property_cost_percentage'] ?? 0), 'in_pct');
            $g->scalar('bdur', $x->t('fa.bLife'), $building ? (float) ($building['depreciation_duration'] ?? 0) : 1, 'in_num');
            $g->scalar('brate', $x->t('fa.bRate'), (float) ($pr['replacement_cost_rate'] ?? 0), 'in_pct');
            $g->scalar('bint', $x->t('fa.bInt'), (float) ($pr['replacement_interval'] ?? 0), 'in_num');
            $g->scalar('fdur', $x->t('fa.fLife'), $ffeItem ? (float) ($ffeItem['depreciation_duration'] ?? 0) : 1, 'in_num');
            $g->scalar('frate', $x->t('fa.fRate'), (float) ($pr['ffe_replacement_cost_rate'] ?? 0), 'in_pct');
            $g->scalar('fint', $x->t('fa.fInt'), (float) ($pr['ffe_replacement_interval'] ?? 0), 'in_num');
        }
        foreach ($m->ffeItems as $i => $item) {
            $g->scalar("idur$i", $x->t('fa.life').' - '.($item['name'] ?? ''), (float) ($item['depreciation_duration'] ?? 0), 'in_num');
            $g->scalar("irate$i", $x->t('fa.rRate').' - '.($item['name'] ?? ''), (float) ($item['replacement_cost_rate'] ?? 0), 'in_pct');
            $g->scalar("iint$i", $x->t('fa.rInt').' - '.($item['name'] ?? ''), (float) ($item['replacement_interval'] ?? 0), 'in_num');
        }
        $g->blank();

        // where the projects under progress end: the month the capitalised interest stops, and the month after which they are moved to the assets
        if ($hasHard) {
            $g->section($x->t('fa.wipC'));
            $g->scalar('cF', $x->t('fa.capEnd'), "MAX(MAX($op-1,0),{$x->name('inv.hcee')})", 'lock_int', null, true);
            $g->scalar('cT', $x->t('fa.moveAfter'), "MAX({$x->name('inv.scee')},{$g->lcell('cF')})", 'lock_int', null, true);
            $g->later('ctn', $x->t('fa.moveMonth'), fn () => "MIN({$g->lcell('cT')}+1,Z_StudyEnd)", 'lock_int');
            $g->later('ctv', $x->t('fa.moveValue'), fn () => "IF({$g->lcell('cT')}+1<=Z_StudyEnd,INDEX({$g->rng('ctot')},1,{$g->lcell('cT')}+2),0)", 'num2');
            $g->blank();
        }
        if ($hasFfe) {
            $g->section($x->t('fa.wipF'));
            $g->scalar('fF', $x->t('fa.capEnd'), "MAX(MAX($op-1,0),{$x->name('inv.feee')})", 'lock_int', null, true);
            $g->later('ftn', $x->t('fa.moveMonth'), fn () => "MIN({$g->lcell('fF')}+1,Z_StudyEnd)", 'lock_int');
            $g->later('ftv', $x->t('fa.moveValue'), fn () => "IF({$g->lcell('fF')}+1<=Z_StudyEnd,INDEX({$g->rng('ftot')},1,{$g->lcell('fF')}+2),0)", 'num2');
            $g->blank();
        }
        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        $stu = fn (K $k) => $k->cal('study');

        // ── projects under progress ──
        if ($hasHard) {
            $g->plan(['cact', 'cadd', 'cci', 'cbeg', 'ctot', 'ctr', 'cend']);
            $T = $g->lcell('cT');
            $F = $g->lcell('cF');
            $g->line('cact', $x->t('fa.processed'), fn ($k) => "IF(AND({$stu($k)}=1,{$k->cal('idx')}<=$T+1),1,0)", ['style' => 'flag']);
            $g->line('cadd', $x->t('fa.additions').' - '.$x->t('fa.wipC'), fn ($k) => "{$k->r('cact')}*({$k->x('inv.hcex')}+{$k->x('inv.scex')})", ['style' => 'num', 'total' => 'sum']);
            $withdrawal = $has('ln.hcwf');
            $g->line('cci', $x->t('fa.capInt'), fn ($k) => $withdrawal
                ? "{$k->r('cact')}*IF({$k->x('ln.hcwf')}=1,{$k->x('ln.hcwi')},IF({$k->cal('idx')}<=$F,{$k->x('ln.hcint')},0))"
                : '0', ['style' => 'num', 'total' => 'sum']);
            $g->line('cbeg', $x->t('fa.beginning'), fn ($k) => "{$k->r('cact')}*".($k->m === 0 ? '0' : $k->pr('ctot')), ['style' => 'num']);
            $g->line('ctot', $x->t('fa.total'), fn ($k) => "{$k->r('cbeg')}+{$k->r('cadd')}+{$k->r('cci')}", ['style' => 'num']);
            $g->line('ctr', $x->t('fa.moved'), fn ($k) => "IF(AND({$k->r('cact')}=1,{$k->cal('idx')}=$T+1),{$k->r('ctot')},0)", ['style' => 'num', 'total' => 'sum']);
            $g->line('cend', $x->t('fa.endBal').' - '.$x->t('fa.wipC'), fn ($k) => "{$k->r('ctot')}-{$k->r('ctr')}", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
            $g->blank();
        }
        if ($hasFfe) {
            $g->plan(['fact', 'fadd', 'fci', 'fbeg', 'ftot', 'ftr', 'fend']);
            $F = $g->lcell('fF');
            $g->line('fact', $x->t('fa.processed'), fn ($k) => "IF(AND({$stu($k)}=1,{$k->cal('idx')}<=$F+1),1,0)", ['style' => 'flag']);
            $g->line('fadd', $x->t('fa.additions').' - '.$x->t('fa.wipF'), fn ($k) => "{$k->r('fact')}*{$k->x('inv.feex')}", ['style' => 'num', 'total' => 'sum']);
            $g->line('fci', $x->t('fa.capInt'), fn ($k) => "{$k->r('fact')}*IF({$k->cal('idx')}<=$F,".($has('ln.fewi') ? "{$k->x('ln.fewi')}+{$k->x('ln.feint')}" : '0').',0)', ['style' => 'num', 'total' => 'sum']);
            $g->line('fbeg', $x->t('fa.beginning'), fn ($k) => "{$k->r('fact')}*".($k->m === 0 ? '0' : $k->pr('ftot')), ['style' => 'num']);
            $g->line('ftot', $x->t('fa.total'), fn ($k) => "{$k->r('fbeg')}+{$k->r('fadd')}+{$k->r('fci')}", ['style' => 'num']);
            $g->line('ftr', $x->t('fa.moved'), fn ($k) => "IF(AND({$k->r('fact')}=1,{$k->cal('idx')}=$F+1),{$k->r('ftot')},0)", ['style' => 'num', 'total' => 'sum']);
            $g->line('fend', $x->t('fa.endBal').' - '.$x->t('fa.wipF'), fn ($k) => "{$k->r('ftot')}-{$k->r('ftr')}", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
            $g->blank();
        }

        // ── assets ──
        $asset = function (string $key, string $label, string $variant, array $c) use ($g, $x, $stu, $op) {
            $g->plan(array_map(fn ($r) => "$key$r", ['isr', 'dsi', 'dei', 'add', 'ini', 'rep', 'fin', 'amt', 'dep', 'acc', 'aci', 'end']));
            $dur = $c['dur'];
            $g->line("{$key}isr", $x->t('fa.isRep').' - '.$label, fn ($k) => "IF({$stu($k)}=0,0,IF({$c['rm']}=0,IF({$k->cal('idx')}=$op,1,0),IF(AND({$k->cal('idx')}>$op,MOD({$k->cal('idx')}-$op,{$c['rm']})=0),1,0)))", ['style' => 'flag']);
            $g->line("{$key}dsi", $x->t('fa.depStart').' - '.$label, function (K $k) use ($key, $variant, $c, $op) {
                $idx = $k->cal('idx');
                $isr = $k->r("{$key}isr");
                if ($variant === 'P') {
                    $rule = "IF(AND({$c['p']}<$op,{$c['pc']}=1),$op,IF(IF({$c['tnn']}=1,$op>0,{$c['tn']}<$op),$op,IF({$c['tnn']}=1,1,{$c['tn']}+1)))";

                    return "IF(AND($isr=1,OR({$c['p']}<=$op,IF({$c['tnn']}=1,TRUE,{$c['tn']}<=$op))),$idx+1,$rule)";
                }

                return "IF({$c['tn']}<$op,$op,$idx+1)";
            }, ['style' => 'flag']);
            $g->line("{$key}dei", $x->t('fa.depEnd').' - '.$label, fn ($k) => $variant === 'P'
                ? "{$k->r("{$key}dsi")}+$dur-1"
                : "IF(AND({$k->r("{$key}isr")}=1,{$c['tn']}<=$op),{$k->cal('idx')}+$dur,{$k->r("{$key}dsi")}+$dur-1)", ['style' => 'flag']);
            $g->line("{$key}add", $x->t('fa.additions').' - '.$label, function (K $k) use ($variant, $c, $stu) {
                $idx = $k->cal('idx');
                $cap = isset($c['cap']) && $c['cap'] !== null ? '+'.$k->r($c['cap']) : '';
                if ($variant === 'P') {
                    return "{$stu($k)}*(IF($idx={$c['p']},{$c['amount']},IF(AND({$c['tnn']}=0,$idx={$c['tn']}),{$c['tv']},0))$cap)";
                }

                return "{$stu($k)}*IF($idx={$c['tn']},{$c['tv']},0)";
            }, ['style' => 'num', 'total' => 'sum']);
            $g->line("{$key}ini", $x->t('fa.initial').' - '.$label, fn ($k) => "{$stu($k)}*({$k->r("{$key}add")}+".($k->m === 0 ? '0' : $k->pr("{$key}fin")).')', ['style' => 'num']);
            $g->line("{$key}rep", $x->t('fa.replacement').' - '.$label, fn ($k) => "IF({$k->r("{$key}isr")}=1,{$k->r("{$key}ini")}*{$c['rate']}/100,0)", ['style' => 'num', 'total' => 'sum']);
            $g->line("{$key}fin", $x->t('fa.gross').' - '.$label, fn ($k) => "{$k->r("{$key}ini")}+{$k->r("{$key}rep")}", ['style' => 'num']);
            $g->line("{$key}amt", $x->t('fa.share').' - '.$label, fn ($k) => "({$k->r("{$key}add")}+{$k->r("{$key}rep")})/MAX(1,$dur)", ['style' => 'num']);
            $g->line("{$key}dep", $x->t('fa.dep').' - '.$label, fn ($k) => "{$stu($k)}*SUMPRODUCT({$g->rng("{$key}amt")}*({$g->rng("{$key}dsi")}<={$k->cal('idx')})*({$g->rng("{$key}dei")}>={$k->cal('idx')}))", ['style' => 'num', 'total' => 'sum']);
            $g->line("{$key}acc", $x->t('fa.accum').' - '.$label, fn ($k) => "{$stu($k)}*(".($k->m === 0 ? '0' : $k->pr("{$key}acc"))."+{$k->r("{$key}dep")})", ['style' => 'num']);
            // the net book value takes the write-off of the additions and replacements made up to that month only (the application works month by month): an
            // addition written off from a month before it was made (a replacement while the first work is still being written off) counts from its own month
            $g->line("{$key}aci", $x->t('fa.accumThen').' - '.$label, function ($k) use ($key, $g, $stu, $x) {
                $d = $k->cal('idx');
                $dei = $g->rng("{$key}dei");
                $v = "(($d+{$dei}-ABS($d-{$dei}))/2-{$g->rng("{$key}dsi")}+1)";

                return "{$stu($k)}*SUMPRODUCT(({$x->calRng('idx')}<=$d)*{$g->rng("{$key}amt")}*($v+ABS($v))/2)";
            }, ['style' => 'num']);
            $g->line("{$key}end", $x->t('fa.net').' - '.$label, fn ($k) => "{$k->r("{$key}fin")}-{$k->r("{$key}aci")}", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
            $g->blank();
        };

        $blocks = [];
        if ($pr !== null) {
            $tnC = $hasHard ? $g->lcell('ctn') : '0';
            foreach ([['bl', 'fa.building', 'bdur', 'brate', 'bint', 'amtB', 'pctB', true], ['pf', 'fa.ffeProp', 'fdur', 'frate', 'fint', 'amtF', 'pctF', false]] as [$key, $labelKey, $dur, $rate, $int, $amt, $pct, $withWip]) {
                $label = $x->t($labelKey);
                if ($has('ln.ppcap')) {
                    $g->line("{$key}cap", $x->t('fa.capInt').' - '.$label, fn ($k) => "{$stu($k)}*{$k->x('ln.ppcap')}*{$g->lcell($pct)}/100", ['style' => 'num', 'total' => 'sum']);
                }
                $asset($key, $label, 'P', [
                    'dur' => "{$g->lcell($dur)}*12", 'rate' => $g->lcell($rate), 'rm' => "{$g->lcell($int)}*12", 'p' => $g->lcell('p'), 'pc' => $g->lcell('pc'),
                    'amount' => $g->lcell($amt), 'tn' => $withWip && $hasHard ? $g->lcell('ctn') : '0', 'tnn' => $withWip && $hasHard ? '0' : '1',
                    'tv' => $withWip && $hasHard ? $g->lcell('ctv') : '0', 'cap' => $has('ln.ppcap') ? "{$key}cap" : null,
                ]);
                $blocks[] = $key;
            }
        }
        $items = [];
        if ($hasFfe && $m->ffeItems !== []) {
            $totalCost = fn () => implode('+', array_map(fn ($i) => "{$x->name("inv.ic$i")}*(1+{$x->name("inv.ik$i")}/100)", array_keys($m->ffeItems)));
            foreach ($m->ffeItems as $i => $item) {
                $share = "IF(({$totalCost()})=0,0,{$g->lcell('ftv')}*{$x->name("inv.ic$i")}*(1+{$x->name("inv.ik$i")}/100)/({$totalCost()}))";
                $asset("i$i", (string) ($item['name'] ?? ''), 'F', [
                    'dur' => "{$g->lcell("idur$i")}*12", 'rate' => $g->lcell("irate$i"), 'rm' => "{$g->lcell("iint$i")}*12", 'tn' => $g->lcell('ftn'), 'tv' => $share,
                ]);
                $items[] = "i$i";
            }
        }

        // ── totals ──
        $g->section($x->t('fa.totals'));
        $sumOf = fn (array $keys, string $row) => fn (K $k) => $keys === [] ? '0' : implode('+', array_map(fn ($b) => $k->r("$b$row"), $keys));
        $g->plan(['itI', 'itG', 'itA', 'itE', 'itD', 'itR', 'ffG', 'ffA', 'ffE', 'ffD', 'ffR', 'dep', 'depPl', 'rep']);
        $g->line('itI', $x->t('fa.itemsInitial'), $sumOf($items, 'ini'), ['style' => 'num']);
        $g->line('itG', $x->t('fa.itemsGross'), $sumOf($items, 'fin'), ['style' => 'num']);
        $g->line('itA', $x->t('fa.itemsAccum'), $sumOf($items, 'acc'), ['style' => 'num']);
        $g->line('itE', $x->t('fa.itemsNet'), $sumOf($items, 'end'), ['style' => 'num']);
        $g->line('itD', $x->t('fa.itemsDep'), $sumOf($items, 'dep'), ['style' => 'num', 'total' => 'sum']);
        $g->line('itR', $x->t('fa.itemsRep'), $sumOf($items, 'rep'), ['style' => 'num', 'total' => 'sum']);
        $ffeBlocks = array_values(array_filter(array_merge($blocks, $items), fn ($b) => $b !== 'bl'));
        foreach (['ffG' => ['fin', 'fa.ffeGross'], 'ffA' => ['acc', 'fa.ffeAccum'], 'ffE' => ['end', 'fa.ffeNet'], 'ffD' => ['dep', 'fa.ffeDep'], 'ffR' => ['rep', 'fa.ffeRep']] as $row => [$part, $label]) {
            $g->line($row, $x->t($label), $sumOf($ffeBlocks, $part), ['style' => 'num', 'total' => in_array($row, ['ffD', 'ffR'], true) ? 'sum' : null]);
        }
        $all = array_merge($blocks, $items);
        $g->line('dep', $x->t('fa.allDep'), $sumOf($all, 'dep'), ['style' => 'num', 'total' => 'sum']);
        $g->line('depPl', $x->t('fa.depIncome'), fn (K $k) => "IF({$k->cal('idx')}>=$op,{$k->r('dep')}".($has('su.dep') ? "+{$k->x('su.dep')}" : '').',0)', ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t', 'total' => 'sum']);
        $g->line('rep', $x->t('fa.allRep'), $sumOf($pr === null ? [] : array_merge($blocks, $items), 'rep'), ['style' => 'num', 'total' => 'sum']);
        $g->blank();

        // ── land ──
        $hasLand = $m->acquisition !== null && $has('pur.ldpay');
        if ($hasLand || $pr !== null) {
            $g->section($x->t('fa.land'));
            $g->plan(['lbuy', 'lcap', 'lacc']);
            $g->line('lbuy', $x->t('fa.landBuy'), fn (K $k) => "{$stu($k)}*(".($hasLand ? "IF({$k->cal('idx')}={$x->name('pur.ldat')},{$x->name('pur.ldtc')},0)" : '0').($pr !== null ? "+IF({$k->cal('idx')}={$g->lcell('p')},{$g->lcell('amtL')},0)" : '').')', ['style' => 'num', 'total' => 'sum']);
            $g->line('lcap', $x->t('fa.landCap'), fn (K $k) => "{$stu($k)}*(".($pr !== null && $has('ln.ppcap') ? "{$k->x('ln.ppcap')}*{$g->lcell('pctL')}/100" : '0').($has('ln.ldcap') ? "+{$k->x('ln.ldcap')}" : '').')', ['style' => 'num', 'total' => 'sum']);
            $g->line('lacc', $x->t('fa.landAccum'), fn (K $k) => "{$stu($k)}*(".($k->m === 0 ? '0' : $k->pr('lacc'))."+{$k->r('lbuy')}+{$k->r('lcap')})", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        }
        $g->flush();
        $sh->freeze(4, Ctx::FIRST);
    }
}
