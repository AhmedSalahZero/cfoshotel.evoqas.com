<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\K;

// The balance sheet at the end of each month, as FinancialReports::balanceSheet builds it.
//   Total investment (assets less current liabilities) = long term loans + owners equity; "Check Error" is the difference and must be zero.
//   What is owed for the fixed assets is what has been built or bought less what has been paid for it.
// Months after the study ends are not shown.
final class BalanceSheet
{
    private const STREAMS = ['rooms' => 'is.rev.rooms', 'foods' => 'is.rev.foods', 'gaming' => 'is.rev.gaming', 'meetings' => 'is.rev.meetings', 'others' => 'is.rev.others'];

    public static function build(Ctx $x): void
    {
        $has = fn (string $key) => isset($x->rows[$key]);
        $sh = $x->sheet('bs', $x->t('n.bs'));
        $g = new Grid($x, $sh, 'bs');
        $g->title($x->t('bs.title'), $x->t('bs.sub'));
        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        $stu = fn (K $k) => $k->cal('study');
        $at = fn (K $k, string $key, string $default = '0') => $has($key) ? "{$stu($k)}*{$k->x($key)}" : $default;
        $add = fn (array $parts) => $parts === [] ? '0' : implode('+', $parts);

        // what is owed for each fixed asset: (what was built or bought - what was paid) so far
        $owed = [
            'pp' => ['pur.pppay', fn (K $k) => isset($x->cells['pur.ppat']) ? "IF({$k->cal('idx')}={$k->ctx->name('pur.ppat')},{$k->ctx->name('pur.pptc')},0)" : '0'],
            'ld' => ['pur.ldpay', fn (K $k) => isset($x->cells['pur.ldat']) ? "IF({$k->cal('idx')}={$k->ctx->name('pur.ldat')},{$k->ctx->name('pur.ldtc')},0)" : '0'],
            'hc' => ['inv.hcpay', fn (K $k) => $has('inv.hcex') ? $k->x('inv.hcex') : '0'],
            'sc' => ['inv.scpay', fn (K $k) => $has('inv.scex') ? $k->x('inv.scex') : '0'],
            'fe' => ['inv.fepay', fn (K $k) => $has('inv.feex') ? $k->x('inv.feex') : '0'],
        ];
        $owed = array_filter($owed, fn ($o) => $has($o[0]));
        $loans = array_values(array_filter(['pp', 'ld', 'hc', 'fe'], fn ($key) => $has("ln.{$key}bal")));
        $dept = ['xr', 'xf', 'xg'];

        $g->plan(array_merge(
            ['land', 'bld', 'ffe', 'wipc', 'wipf', 'oth', 'lta', '_'],
            ['cash'], array_map(fn ($s) => 'rc'.$s, array_keys(self::STREAMS)), ['rec', 'inv', 'tca', 'ta', '_'],
            ['dsp', 'ptx', 'ins', 'ctx', 'acr', 'mgf', 'opy'], array_map(fn ($k) => 'fp'.$k, array_keys($owed)), ['fpt', 'tcl', 'wcap', 'ti', '_'],
            array_map(fn ($k) => 'lo'.$k, $loans), ['lot', '_', 'paid', 'ret', 'np', 'oe', '_', 'chk'],
        ));

        // ── assets ──
        $g->line('land', $x->t('bs.land'), fn (K $k) => $at($k, 'fa.lacc'), ['style' => 'num']);
        $g->line('bld', $x->t('bs.building'), fn (K $k) => $at($k, 'fa.blend'), ['style' => 'num']);
        $g->line('ffe', $x->t('bs.ffe'), fn (K $k) => $at($k, 'fa.ffE'), ['style' => 'num']);
        $g->line('wipc', $x->t('bs.wipC'), fn (K $k) => $at($k, 'fa.cend'), ['style' => 'num']);
        $g->line('wipf', $x->t('bs.wipF'), fn (K $k) => $at($k, 'fa.fend'), ['style' => 'num']);
        $g->line('oth', $x->t('bs.startUp'), fn (K $k) => $at($k, 'su.net'), ['style' => 'num']);
        $g->line('lta', $x->t('bs.lta'), fn (K $k) => implode('+', array_map(fn ($r) => $k->r($r), ['land', 'bld', 'ffe', 'wipc', 'wipf', 'oth'])), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();
        $g->line('cash', $x->t('bs.cash'), fn (K $k) => $at($k, 'cf.acc'), ['style' => 'num']);
        foreach (self::STREAMS as $stream => $label) {
            $g->line('rc'.$stream, $x->t($label).' - '.$x->t('bs.receivable'), fn (K $k) => $at($k, "col.o$stream"), ['style' => 'num']);
        }
        $g->line('rec', $x->t('bs.receivables'), fn (K $k) => implode('+', array_map(fn ($s) => $k->r('rc'.$s), array_keys(self::STREAMS))), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('inv', $x->t('bs.inventory'), fn (K $k) => $add(array_map(fn ($d) => $at($k, "$d.t_inv"), $dept)), ['style' => 'num']);
        $g->line('tca', $x->t('bs.tca'), fn (K $k) => "{$k->r('cash')}+{$k->r('rec')}+{$k->r('inv')}", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('ta', $x->t('bs.ta'), fn (K $k) => "{$k->r('lta')}+{$k->r('tca')}", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();

        // ── current liabilities ──
        $g->line('dsp', $x->t('bs.dispPay'), fn (K $k) => $add(array_map(fn ($d) => $at($k, "$d.t_owed"), $dept)), ['style' => 'num']);
        $g->line('ptx', $x->t('bs.propTax'), fn (K $k) => $at($k, 'tx.tbal'), ['style' => 'num']);
        $g->line('ins', $x->t('bs.propIns'), fn (K $k) => $at($k, 'tx.ibal'), ['style' => 'num']);
        $g->line('ctx', $x->t('bs.corpTax'), fn (K $k) => $at($k, 'cf.ctbal'), ['style' => 'num']);
        $g->line('acr', $x->t('bs.accrued'), fn (K $k) => $add(array_map(fn ($d) => $at($k, "$d.t_fbal"), ['xe', 'xk', 'xp', 'xn'])), ['style' => 'num']);
        $g->line('mgf', $x->t('bs.mgmt'), fn (K $k) => $at($k, 'cf.mfbal'), ['style' => 'num']);
        $g->line('opy', $x->t('bs.otherPay'), fn (K $k) => $at($k, 'su.payable'), ['style' => 'num']);
        foreach ($owed as $key => [$payRow, $purchase]) {
            $label = ['pp' => 'pur.property', 'ld' => 'pur.land', 'hc' => 'inv.hard', 'sc' => 'inv.soft', 'fe' => 'inv.ffe'][$key];
            $g->line('fp'.$key, $x->t('bs.assetPay').' - '.$x->t($label), fn (K $k) => "{$stu($k)}*(".($k->m === 0 ? '0' : $k->pr('fp'.$key))."+{$purchase($k)}-{$k->x($payRow)})", ['style' => 'num']);
        }
        $g->line('fpt', $x->t('bs.assetPayTotal'), fn (K $k) => $add(array_map(fn ($key) => $k->r('fp'.$key), array_keys($owed))), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('tcl', $x->t('bs.tcl'), fn (K $k) => implode('+', array_map(fn ($r) => $k->r($r), ['dsp', 'ptx', 'ins', 'ctx', 'acr', 'mgf', 'opy', 'fpt'])), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('wcap', $x->t('bs.workingCapital'), fn (K $k) => "{$k->r('tca')}-{$k->r('tcl')}", ['style' => 'num']);
        $g->line('ti', $x->t('bs.totalInvestment'), fn (K $k) => "{$k->r('ta')}-{$k->r('tcl')}", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();

        // ── funding ──
        foreach ($loans as $key) {
            $label = ['pp' => 'pur.property', 'ld' => 'pur.land', 'hc' => 'inv.hard', 'fe' => 'inv.ffe'][$key];
            $g->line('lo'.$key, $x->t('bs.loan').' - '.$x->t($label), fn (K $k) => $at($k, "ln.{$key}bal"), ['style' => 'num']);
        }
        $g->line('lot', $x->t('bs.loans'), fn (K $k) => $add(array_map(fn ($key) => $k->r('lo'.$key), $loans)), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();
        $g->line('paid', $x->t('bs.paidUp'), fn (K $k) => "{$stu($k)}*(".($k->m === 0 ? '0' : $k->pr('paid'))."+{$k->x('cf.eq')}+{$k->x('cf.wc')})", ['style' => 'num']);
        $g->line('ret', $x->t('bs.retained'), fn (K $k) => "IF(AND({$stu($k)}=1,{$k->cal('idx')}>Z_OpIdx),".($k->m === 0 ? '0' : $k->pr('ret').'+'.$k->pr('np')).',0)', ['style' => 'num']);
        $g->line('np', $x->t('bs.netProfit'), fn (K $k) => $at($k, 'is.np'), ['style' => 'num']);
        $g->line('oe', $x->t('bs.equity'), fn (K $k) => "{$k->r('paid')}+{$k->r('ret')}+{$k->r('np')}", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();
        $g->line('chk', $x->t('bs.check'), fn (K $k) => "{$k->r('ti')}-{$k->r('lot')}-{$k->r('oe')}", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
