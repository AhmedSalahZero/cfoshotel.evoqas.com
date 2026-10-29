<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\K;

// The cash in / out report, month by month, as FinancialReports::workOutCashInOut and formatCashInOut work it out.
//   cash in    collections of the five revenue streams, equity injections, loan withdrawals and (last) the working capital injection
//   cash out   what is paid for disposables, the expenses paid in the month, the fixed expenses paid in advance, salaries, management fees, taxes, start-up costs,
//              land / building / FF&E payments and replacements, and loan installments
//   working capital injection   once a year, in the first month of the year, the equity the hotel needs for the cash not to run below zero (the lowest cash of the
//              year, when negative, compared with the lowest of the year before)
// Also here: what is owed for the incentive management fee (paid in twelve parts over the year after it falls) and for the corporate tax (paid four months later).
final class CashFlowSheet
{
    private const STREAMS = ['rooms', 'foods', 'gaming', 'meetings', 'others'];

    /** which department's sheet feeds which line of the cash out report (as the application's CashOutReport has it) */
    private const PERCENT = ['xr', 'xf', 'xg', 'xm', 'xo', 'xe', 'xk', 'xp', 'xn'];
    private const NIGHT = ['xr'];
    private const GUEST = ['xr', 'xf', 'xg', 'xm', 'xo'];
    private const FIXED = ['xe', 'xk', 'xp', 'xn'];
    private const MANPOWER = ['xr', 'xf', 'xg', 'xo', 'xe', 'xk'];
    private const DISPOSABLES = ['xr', 'xf', 'xg'];

    public static function build(Ctx $x): void
    {
        $has = fn (string $key) => isset($x->rows[$key]);
        $sh = $x->sheet('cf', $x->t('n.cf'));
        $g = new Grid($x, $sh, 'cf');
        $g->title($x->t('cf.title'), $x->t('cf.sub'));
        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        $stu = fn (K $k) => $k->cal('study');
        $o = fn (K $k) => $k->cal('op').'*'.$k->cal('study');
        $sum = fn (K $k, array $keys) => $keys === [] ? '0' : implode('+', array_map(fn ($key) => $k->x($key), $keys));
        $present = fn (array $keys) => array_values(array_filter($keys, $has));
        $sheetsOf = fn (array $sheets, string $row) => $present(array_map(fn ($s) => "$s.$row", $sheets));

        $equity = $present(['pur.ppeq', 'pur.ldeq', 'inv.hceq', 'inv.sceq', 'inv.feeq']);
        $draws = $present(['pur.ppln', 'pur.ldln', 'inv.hcln', 'inv.feln']);
        $payments = $present(['pur.pppay', 'pur.ldpay', 'inv.hcpay', 'inv.scpay', 'inv.fepay']);
        $installments = $present(['ln.ppsp', 'ln.ldsp', 'ln.hcsp', 'ln.fesp']);

        $g->plan(array_merge(
            array_map(fn ($s) => 'c'.$s, self::STREAMS), ['coll', 'eq', 'ln', 'in0', '_'],
            ['dp', 'pc', 'nt', 'gs', 'fx', 'mp', 'mb', 'mi', 'tp', 'tc', 'su', 'ac', 'rp', 'ls', 'out', '_'],
            ['net0', 'acc0', '_', 'ys', 'ye', 'ym', 'wc', '_', 'inTotal', 'net', 'acc', '_'],
            ['mfamt', 'mfpay', 'mfbal', 'ctamt', 'ctpay', 'ctbal', '_', 'fcfe', 'fcff', 'facc', 'anyf'],
        ));

        // ── cash in ──
        foreach (self::STREAMS as $s) {
            $g->line('c'.$s, $x->t('cf.coll.'.$s), fn (K $k) => $has("col.c$s") ? $k->x("col.c$s") : '0', ['style' => 'num', 'total' => 'sum']);
        }
        $g->line('coll', $x->t('cf.collTotal'), fn (K $k) => implode('+', array_map(fn ($s) => $k->r('c'.$s), self::STREAMS)), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('eq', $x->t('cf.equity'), fn (K $k) => $sum($k, $equity), ['style' => 'num', 'total' => 'sum']);
        $g->line('ln', $x->t('cf.drawn'), fn (K $k) => $draws === [] ? '0' : implode('+', array_map(fn ($d) => "IF(SUM({$x->rng($d)})<1,0,{$k->x($d)})", $draws)), ['style' => 'num', 'total' => 'sum']);
        $g->line('in0', $x->t('cf.inBefore'), fn (K $k) => "{$k->r('coll')}+{$k->r('eq')}+{$k->r('ln')}", ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();

        // ── cash out ──
        $g->line('dp', $x->t('cf.disp'), fn (K $k) => $sum($k, array_merge($sheetsOf(self::DISPOSABLES, 't_pay'), $sheetsOf(self::DISPOSABLES, 't_open'))), ['style' => 'num', 'total' => 'sum']);
        $g->line('pc', $x->t('cf.pct'), fn (K $k) => $sum($k, $sheetsOf(self::PERCENT, 't_pct')), ['style' => 'num', 'total' => 'sum']);
        $g->line('nt', $x->t('cf.night'), fn (K $k) => $sum($k, $sheetsOf(self::NIGHT, 't_night')), ['style' => 'num', 'total' => 'sum']);
        $g->line('gs', $x->t('cf.guest'), fn (K $k) => $sum($k, $sheetsOf(self::GUEST, 't_guest')), ['style' => 'num', 'total' => 'sum']);
        $g->line('fx', $x->t('cf.fixed'), fn (K $k) => $sum($k, $sheetsOf(self::FIXED, 't_fpay')), ['style' => 'num', 'total' => 'sum']);
        $g->line('mp', $x->t('cf.manpower'), fn (K $k) => $sum($k, $sheetsOf(self::MANPOWER, 't_manpower')), ['style' => 'num', 'total' => 'sum']);
        $g->line('mb', $x->t('cf.mgmtBase'), fn (K $k) => $has('tx.bf') ? "{$stu($k)}*{$k->x('tx.bf')}" : '0', ['style' => 'num', 'total' => 'sum']);
        $g->line('mi', $x->t('cf.mgmtInc'), fn (K $k) => $k->r('mfpay'), ['style' => 'num', 'total' => 'sum']);
        $g->line('tp', $x->t('cf.taxProp'), fn (K $k) => $has('tx.tinpay') ? $k->x('tx.tinpay') : '0', ['style' => 'num', 'total' => 'sum']);
        $g->line('tc', $x->t('cf.taxCorp'), fn (K $k) => $k->r('ctpay'), ['style' => 'num', 'total' => 'sum']);
        $g->line('su', $x->t('cf.startUp'), fn (K $k) => $has('su.payments') ? $k->x('su.payments') : '0', ['style' => 'num', 'total' => 'sum']);
        $g->line('ac', $x->t('cf.acq'), fn (K $k) => $sum($k, $payments), ['style' => 'num', 'total' => 'sum']);
        $g->line('rp', $x->t('cf.repl'), fn (K $k) => $has('fa.rep') ? $k->x('fa.rep') : '0', ['style' => 'num', 'total' => 'sum']);
        $g->line('ls', $x->t('cf.install'), fn (K $k) => $sum($k, $installments), ['style' => 'num', 'total' => 'sum']);
        $g->line('out', $x->t('cf.outTotal'), fn (K $k) => implode('+', array_map(fn ($r) => $k->r($r), ['dp', 'pc', 'nt', 'gs', 'fx', 'mp', 'mb', 'mi', 'tp', 'tc', 'su', 'ac', 'rp', 'ls'])), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();

        // ── the working capital injection ──
        $g->line('net0', $x->t('cf.netBefore'), fn (K $k) => "{$k->r('in0')}-{$k->r('out')}", ['style' => 'num', 'total' => 'sum']);
        $g->line('acc0', $x->t('cf.accBefore'), fn (K $k) => ($k->m === 0 ? '0' : $k->pr('acc0')).'+'.$k->r('net0'), ['style' => 'num']);
        $g->blank();
        $g->line('ys', $x->t('cf.yearStart'), fn (K $k) => "IF(AND({$stu($k)}=1,".($k->m === 0 ? 'TRUE' : "{$k->cal('year')}<>{$k->pcal('year')}").'),1,0)', ['style' => 'flag']);
        $g->line('ye', $x->t('cf.yearEnd'), fn (K $k) => $k->m === $x->m->D - 1
            ? "IF({$stu($k)}=1,1,0)"
            : "IF(AND({$stu($k)}=1,OR({$k->ncal('study')}=0,{$k->ncal('year')}<>{$k->cal('year')})),1,0)", ['style' => 'flag']);
        $g->line('ym', $x->t('cf.yearMin'), fn (K $k) => "IF({$stu($k)}=0,0,IF({$k->r('ys')}=1,{$k->r('acc0')},MIN(".($k->m === 0 ? $k->r('acc0') : $k->pr('ym')).",{$k->r('acc0')})))", ['style' => 'num']);
        $min = fn (K $k, string $yearExpr) => "SUMPRODUCT(({$x->calRng('year')}=$yearExpr)*{$g->rng('ye')}*{$g->rng('ym')})";
        $g->line('wc', $x->t('cf.wc'), fn (K $k) => "IF({$k->r('ys')}=1,IF({$min($k, $k->cal('year'))}<0,MAX(0,{$min($k, $k->cal('year').'-1')}-{$min($k, $k->cal('year'))}),0),0)", ['style' => 'num', 'total' => 'sum']);
        $g->blank();
        $g->line('inTotal', $x->t('cf.inTotal'), fn (K $k) => "{$k->r('in0')}+{$k->r('wc')}", ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('net', $x->t('cf.net'), fn (K $k) => "{$k->r('inTotal')}-{$k->r('out')}", ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('acc', $x->t('cf.acc'), fn (K $k) => ($k->m === 0 ? '0' : $k->pr('acc')).'+'.$k->r('net'), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();

        // ── what is owed for the incentive fee and the corporate tax ──
        $g->line('mfamt', $x->t('cf.mfAmt'), fn (K $k) => $has('is.inc') ? "{$o($k)}*{$k->x('is.inc')}" : '0', ['style' => 'num', 'total' => 'sum']);
        $g->line('mfpay', $x->t('cf.mfPay'), fn (K $k) => "{$o($k)}*SUMPRODUCT(({$x->calRng('idx')}>={$k->cal('idx')}-12)*({$x->calRng('idx')}<={$k->cal('idx')}-1)*{$g->rng('mfamt')})/12", ['style' => 'num', 'total' => 'sum']);
        $g->line('mfbal', $x->t('cf.mfBal'), fn (K $k) => "{$o($k)}*(".($k->m === 0 ? '0' : $k->pr('mfbal'))."+{$k->r('mfamt')}-{$k->r('mfpay')})", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('ctamt', $x->t('cf.ctAmt'), fn (K $k) => $has('is.tax') ? "{$o($k)}*{$k->x('is.tax')}" : '0', ['style' => 'num', 'total' => 'sum']);
        $g->line('ctpay', $x->t('cf.ctPay'), fn (K $k) => "{$o($k)}*{$x->shift($g->rng('ctamt'), '4', $k->c)}", ['style' => 'num', 'total' => 'sum']);
        $g->line('ctbal', $x->t('cf.ctBal'), fn (K $k) => "{$o($k)}*(".($k->m === 0 ? '0' : $k->pr('ctbal'))."+{$k->r('ctamt')}-{$k->r('ctpay')})", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $g->blank();
        // free cash flow: for the owners (what the hotel earns, less everything it pays, with the loan drawings paying for the building) and for the firm (before the loan installments)
        $g->line('fcfe', $x->t('cf.fcfe'), fn (K $k) => "{$k->r('coll')}-{$k->r('out')}+{$k->r('ln')}", ['style' => 'num', 'total' => 'sum']);
        $g->line('fcff', $x->t('cf.fcff'), fn (K $k) => "{$k->r('coll')}-{$k->r('out')}+{$k->r('ls')}", ['style' => 'num', 'total' => 'sum']);
        $g->line('facc', $x->t('cf.facc'), fn (K $k) => ($k->m === 0 ? '0' : $k->pr('facc')).'+'.$k->r('fcfe'), ['style' => 'num']);
        // the free cash flow has a figure from the first month something is paid or collected (the valuation numbers its years, and counts its months, from there)
        $g->line('anyf', $x->t('cf.anyFlow'), fn (K $k) => "IF(OR({$k->r('coll')}<>0,{$k->r('out')}<>0),1,0)", ['style' => 'flag']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
