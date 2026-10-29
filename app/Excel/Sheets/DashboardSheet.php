<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\K;
use App\Excel\Sheet;

// The dashboard: the key figures of the investment, the year-by-year summary and ratios, and the value of the hotel, as StudyAnalysis::ratios and ::dashboard work them out.
//   year        a calendar year of the study; flows are the sum of its months, balances are those of its December (a year the study leaves before its December
//               gives no balance, and no free cash flow)
//   ratios      as the application has them (the days ratios use 360 days)
//   value       the free cash flow of each year; the last year also carries the terminal value, last cash flow x (1 + growth) / (rate - growth) (the owners', less the
//               loans still owed; the firm's, at the weighted cost of capital); NPV discounts each year by its number (the first year one year); IRR is Excel's
//   payback     the first month the accumulated free cash flow of the owners is back to what they put in
final class DashboardSheet
{
    public static function build(Ctx $x): void
    {
        $has = fn (string $key) => isset($x->rows[$key]);
        $sh = $x->sheet('db', $x->t('n.db'));
        $g = new Grid($x, $sh, 'db');
        $g->title($x->t('db.title'), $x->t('db.sub'));
        $Y = $x->m->Y;
        $loans = array_values(array_filter(['pp', 'ld', 'hc', 'fe'], fn ($k) => isset($x->cells["ln.{$k}L"])));
        // ── key figures: the formulas are written once the rows below exist ──
        $g->section($x->t('db.key'));
        $cell = fn (string $c) => $g->lcell($c);
        // the cells of a year row (the first Y columns)
        $yr = fn (string $key) => '$'.Sheet::col(Ctx::FIRST).'$'.$g->rowOf($key).':$'.Sheet::col(Ctx::FIRST + $Y - 1).'$'.$g->rowOf($key);
        $sum = fn (string $key) => "SUM({$x->rng($key)})";
        $loanPart = fn (callable $one) => $loans === [] ? '0' : implode('+', array_map($one, $loans));
        $g->later('fixedInv', $x->t('db.fixedInv'), fn () => "{$sum('cf.ln')}+{$sum('cf.eq')}", 'num');
        $g->later('wcInj', $x->t('db.wcInj'), fn () => $sum('cf.wc'), 'num');
        $g->later('eq', $x->t('db.eq'), fn () => "{$sum('cf.eq')}+{$sum('cf.wc')}", 'num');
        $g->later('loanTotal', $x->t('db.loans'), fn () => $loanPart(fn ($k) => $x->name("ln.{$k}L")), 'num');
        $g->later('req', $x->t('db.req'), fn () => "{$cell('eq')}+{$cell('loanTotal')}", 'num');
        $g->later('ke', $x->t('db.ke'), fn () => 'IF(Z_Return=0,1,Z_Return)', 'num2');
        $g->later('cod', $x->t('db.cod'), fn () => $loans === [] ? '0' : "IF({$cell('loanTotal')}>0,({$loanPart(fn ($k) => "{$x->name("ln.{$k}L")}*{$x->name("ln.{$k}price")}")}),0)/MAX({$cell('loanTotal')},1)*(1-Z_Tax/100)", 'pctf');
        $g->later('wacc', $x->t('db.wacc'), fn () => "IF({$cell('req')}<>0,{$cell('eq')}/{$cell('req')}*{$cell('ke')}/100+{$cell('loanTotal')}/{$cell('req')}*{$cell('cod')},0)", 'pctf');
        $g->later('npvE', $x->t('db.npvE'), fn () => "SUMPRODUCT({$yr('fe')}/(1+{$cell('ke')}/100)^{$yr('pos')})", 'num');
        $g->later('irrE', $x->t('db.irrE'), fn () => "IFERROR(IRR({$yr('fe')},0.1),0)", 'pctf');
        $g->later('npvF', $x->t('db.npvF'), fn () => "SUMPRODUCT({$yr('ff')}/(1+{$cell('wacc')})^{$yr('pos')})", 'num');
        $g->later('irrF', $x->t('db.irrF'), fn () => "IFERROR(IRR({$yr('ff')},0.1),0)", 'pctf');
        $g->later('pbMonths', $x->t('db.pbMonths'), fn () => "IFERROR(MATCH(1,{$g->rng('pbf')},0)-{$cell('fk')}+1,0)", 'int');
        $g->later('pbYears', $x->t('db.pbYears'), fn () => "IF({$cell('pbMonths')}=0,0,ROUND({$cell('pbMonths')}/12,1))", 'num2');
        $g->later('fy', $x->t('db.firstYear'), fn () => "IFERROR(INDEX({$x->calRng('yidx')},1,MATCH(1,{$x->rng('cf.anyf')},0)),0)", 'int');
        $g->later('fk', $x->t('db.firstMonth'), fn () => "IFERROR(MATCH(1,{$x->rng('cf.anyf')},0),1)", 'int');
        $g->later('g', $x->t('db.growth'), fn () => 'Z_Growth/100', 'pctf');
        $g->later('loanEnd', $x->t('db.loanEnd'), fn () => $loanPart(fn ($k) => "INDEX({$x->rng("ln.{$k}se")},1,Z_StudyEnd+1)"), 'num');
        $g->later('tvE', $x->t('db.tvE'), fn () => "SUMPRODUCT({$yr('last')}*{$yr('fcfe')})*(1+{$cell('g')})/IF({$cell('ke')}/100-{$cell('g')}<=0,1,{$cell('ke')}/100-{$cell('g')})", 'num');
        $g->later('tvF', $x->t('db.tvF'), fn () => "SUMPRODUCT({$yr('last')}*{$yr('fcff')})*(1+{$cell('g')})/IF({$cell('wacc')}-{$cell('g')}<=0,1,{$cell('wacc')}-{$cell('g')})", 'num');
        $g->blank();

        // ── year by year ──
        $g->section($x->t('db.years'));
        $g->yearHeader('yh');
        $hdr = $g->rowOf('yh');
        $year = fn (string $c) => "{$c}\${$hdr}";
        $sumY = fn (string $key) => fn (int $y, string $c) => "SUMIF({$x->calRng('year')},{$year($c)},{$x->rng($key)})";
        $eoy = fn (string $key) => fn (int $y, string $c) => "SUMPRODUCT(({$x->calRng('year')}={$year($c)})*({$x->calRng('mnum')}=12)*{$x->calRng('study')}*{$x->rng($key)})";
        $row = fn (string $key, string $label, callable $f, string $style = 'num') => $g->yearCalc($key, $label, $f, $style);
        $r = fn (string $key, string $c) => $c.$g->rowOf($key);

        $g->plan(['rev', 'gp', 'ebitda', 'ebit', 'ebt', 'np', '_']);
        $row('rev', $x->t('is.revTotal'), $sumY('is.rtotal'));
        $row('gp', $x->t('is.gpTotal'), $sumY('is.gtotal'));
        $row('ebitda', $x->t('is.ebitda'), $sumY('is.ebitda'));
        $row('ebit', $x->t('is.ebit'), $sumY('is.ebit'));
        $row('ebt', $x->t('is.ebt'), $sumY('is.ebt'));
        $row('np', $x->t('is.np'), $sumY('is.np'));
        $g->blank();

        $g->plan(['ta', 'tca', 'tcl', 'ti', 'lot', 'tl', 'oe', 'cash', 'rec', 'inv', 'sup', 'wcap', 'dis', '_']);
        $row('ta', $x->t('bs.ta'), $eoy('bs.ta'));
        $row('tca', $x->t('bs.tca'), $eoy('bs.tca'));
        $row('tcl', $x->t('bs.tcl'), $eoy('bs.tcl'));
        $row('ti', $x->t('bs.totalInvestment'), $eoy('bs.ti'));
        $row('lot', $x->t('bs.loans'), $eoy('bs.lot'));
        $row('tl', $x->t('db.liabilities'), fn ($y, $c) => $r('tcl', $c).'+'.$r('lot', $c));
        $row('oe', $x->t('bs.equity'), $eoy('bs.oe'));
        $row('cash', $x->t('bs.cash'), $eoy('bs.cash'));
        $row('rec', $x->t('bs.receivables'), $eoy('bs.rec'));
        $row('inv', $x->t('bs.inventory'), $eoy('bs.inv'));
        $row('sup', $x->t('db.suppliers'), fn ($y, $c) => implode('+', array_map(fn ($k) => $eoy("bs.$k")($y, $c), ['dsp', 'ins', 'opy'])));
        $row('wcap', $x->t('bs.workingCapital'), $eoy('bs.wcap'));
        $disposables = array_values(array_filter(['xr.t_disp', 'xf.t_disp', 'xg.t_disp'], $has));
        $row('dis', $x->t('db.dispCost'), fn ($y, $c) => $disposables === [] ? '0' : implode('+', array_map(fn ($key) => "SUMPRODUCT(({$x->calRng('year')}={$year($c)})*{$x->calRng('op')}*{$x->calRng('study')}*{$x->rng($key)})", $disposables)));
        $g->blank();

        // ratios: a ratio is 0 when what it is divided by is 0
        $div = fn (string $a, string $b, string $times = '') => fn ($y, $c) => "IF({$r($b, $c)}<>0,{$r($a, $c)}/{$r($b, $c)}$times,0)";
        $g->section($x->t('db.ratios'));
        $g->plan(['ros', 'roa', 'roce', 'roe', 'gpm', 'ebm', 'npm', '_', 'cr', 'qr', 'car', 'wcr', '_', 'dso', 'dio', 'dpo', 'ccc', '_', 'da', 'de', '_']);
        $row('ros', $x->t('db.ros'), $div('ebit', 'rev'), 'pctf');
        $row('roa', $x->t('db.roa'), $div('np', 'ta'), 'pctf');
        $row('roce', $x->t('db.roce'), $div('ebit', 'ti'), 'pctf');
        $row('roe', $x->t('db.roe'), $div('np', 'oe'), 'pctf');
        $row('gpm', $x->t('db.gpm'), $div('gp', 'rev'), 'pctf');
        $row('ebm', $x->t('db.ebm'), $div('ebitda', 'rev'), 'pctf');
        $row('npm', $x->t('db.npm'), $div('np', 'rev'), 'pctf');
        $g->blank();
        $row('cr', $x->t('db.cr'), $div('tca', 'tcl'), 'num2');
        $row('qr', $x->t('db.qr'), fn ($y, $c) => "IF({$r('tcl', $c)}<>0,({$r('cash', $c)}+{$r('rec', $c)})/{$r('tcl', $c)},0)", 'num2');
        $row('car', $x->t('db.car'), $div('cash', 'tcl'), 'num2');
        $row('wcr', $x->t('bs.workingCapital'), fn ($y, $c) => $r('wcap', $c));
        $g->blank();
        $row('dso', $x->t('db.dso'), $div('rec', 'rev', '*360'), 'num');
        $row('dio', $x->t('db.dio'), $div('inv', 'dis', '*360'), 'num');
        $row('dpo', $x->t('db.dpo'), $div('sup', 'dis', '*360'), 'num');
        $row('ccc', $x->t('db.ccc'), fn ($y, $c) => "{$r('dso', $c)}+{$r('dio', $c)}-{$r('dpo', $c)}", 'num');
        $g->blank();
        $row('da', $x->t('db.da'), $div('tl', 'ta'), 'num2');
        $row('de', $x->t('db.de'), $div('tl', 'oe'), 'num2');
        $g->blank();

        // ── value of the hotel ──
        $g->section($x->t('db.value'));
        $g->plan(['pos', 'inc', 'last', 'fcfe', 'fcff', 'fe', 'ff']);
        $row('pos', $x->t('db.pos'), fn ($y, $c) => "MAX(0,$y-{$cell('fy')}+1)", 'flag');
        $row('inc', $x->t('db.included'), fn ($y, $c) => "IF(SUMPRODUCT(({$x->calRng('year')}={$year($c)})*({$x->calRng('mnum')}=12)*{$x->calRng('study')})=1,1,0)", 'flag');
        $row('last', $x->t('db.lastYear'), fn ($y, $c) => $y === $Y - 1 ? "IF({$r('inc', $c)}=1,1,0)" : "IF(AND({$r('inc', $c)}=1,{$r('inc', Sheet::col(Ctx::FIRST + $y + 1))}=0),1,0)", 'flag');
        $row('fcfe', $x->t('db.fcfe'), fn ($y, $c) => "{$r('inc', $c)}*".$sumY('cf.fcfe')($y, $c));
        $row('fcff', $x->t('db.fcff'), fn ($y, $c) => "{$r('inc', $c)}*".$sumY('cf.fcff')($y, $c));
        $row('fe', $x->t('db.feTerminal'), fn ($y, $c) => "{$r('fcfe', $c)}+{$r('last', $c)}*({$g->lcell('tvE')}-{$g->lcell('loanEnd')})");
        $row('ff', $x->t('db.ffTerminal'), fn ($y, $c) => "{$r('fcff', $c)}+{$r('last', $c)}*{$g->lcell('tvF')}");
        $g->blank();
        // ── payback, month by month ──
        $g->section($x->t('db.payback'));
        $g->monthHeader();
        $g->line('pbf', $x->t('db.pbFlag'), fn (K $k) => "IF(AND({$k->cal('idx')}+1>={$g->lcell('fk')},{$k->x('cf.facc')}>=0,{$k->x('cf.facc')}>={$g->lcell('eq')}),1,0)", ['style' => 'flag']);

        $g->flush();
        $sh->freeze(4, Ctx::FIRST);
    }
}
