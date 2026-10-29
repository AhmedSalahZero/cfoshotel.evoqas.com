<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\Sheet;

// The four loans (property, land, construction, FF&E), as CalculateLoanWithdrawal and CalculateFixedLoanAtEndService work them out.
//
//   drawing     every month the loan pays for part of the cost is a withdrawal month. Interest on what has been drawn runs at (base + margin) / 360 a day for the
//               days since the PREVIOUS withdrawal month, charged on the balance including the new withdrawal (the first withdrawal has no days). The loan amount is
//               the balance after the last withdrawal, and the schedule starts in that month.
//   schedule    one line every installment interval (monthly, quarterly, half yearly), from the start month for tenor / interval periods; the interest of a line is
//               its opening balance x (rate / 360 x days since the line before). The first installment is one interval after the start plus the grace period rounded
//               up to whole intervals. The installment is the loan growth factor at the end divided by the installment factor at the end, and grows by the step rate
//               at every step (step-up / step-down loans). With capitalisation the balance grows from the start; without it, it stays the loan amount until the
//               first installment, and the grace months pay only the interest.
// The schedule is a vertical table on the "Loan schedules" sheet (the tenor can run past the last month of the study); the monthly rows here read it by month number.
final class LoansSheet
{
    private const STEP_UP = ['step-up', 'grace_step-up_with_capitalization', 'grace_step-up_without_capitalization'];
    private const STEP_DOWN = ['step-down', 'grace_step-down_with_capitalization', 'grace_step-down_without_capitalization'];
    private const GRACE = ['grace_step-up_without_capitalization', 'grace_step-down_without_capitalization', 'grace_period_without_capitalization'];
    private const COLUMNS = ['no', 'idx', 'date', 'days', 'f', 'lf', 'sf', 'if', 'amt', 'beg', 'int', 'pay', 'prin', 'end'];

    /** The loans there are: key => [loan row, the row of the monthly draws, the label]. */
    public static function blocks(Ctx $x): array
    {
        $m = $x->m;
        $all = [
            'pp' => [$m->loanFor['property'] ?? null, 'pur.ppln', 'pur.property'],
            'ld' => [$m->loanFor['land'] ?? null, 'pur.ldln', 'pur.land'],
            'hc' => [$m->loanFor['hard'] ?? null, 'inv.hcln', 'inv.hard'],
            'fe' => [$m->loanFor['ffe'] ?? null, 'inv.feln', 'inv.ffe'],
        ];

        return array_filter($all, fn ($b) => $b[0] !== null && isset($x->rows[$b[1]]));
    }

    public static function build(Ctx $x): void
    {
        $blocks = self::blocks($x);
        if ($blocks === []) {
            return;
        }

        $sh = $x->sheet('ln', $x->t('n.ln'));
        $ls = $x->sheet('ls', $x->t('n.ls'));
        $g = new Grid($x, $sh, 'ln');
        $g->title($x->t('ln.title'), $x->t('ln.sub'));

        // the terms of each loan, and where its schedule table sits on the other sheet
        $info = [];
        $row = 4;
        foreach ($blocks as $key => [$loan, $drawRow, $labelKey]) {
            $type = (string) ($loan['loan_type'] ?? '');
            $interval = ['monthly' => 1, 'quartly' => 3, 'semi annually' => 6][(string) ($loan['installment_interval'] ?? '')] ?? 12;
            $tenor = (float) ($loan['period'] ?? 0);
            $count = (int) floor($tenor / $interval + 1e-9);
            $up = in_array($type, self::STEP_UP, true);
            $stepName = $up ? ($loan['step_up_interval'] ?? null) : ($loan['step_down_interval'] ?? null);
            $applied = ['quartly' => 3, 'semi annually' => 6, 'annually' => 12][(string) $stepName] ?? 12;
            $stepable = $up || in_array($type, self::STEP_DOWN, true);
            $info[$key] = [
                'loan' => $loan, 'draw' => $drawRow, 'label' => $x->t($labelKey), 'type' => $type, 'interval' => $interval, 'tenor' => $tenor, 'count' => $count,
                'stepEvery' => $applied / $interval, 'stepable' => $stepable,
                'stepRate' => $stepable ? (float) ($up ? ($loan['step_up_rate'] ?? 0) : ($loan['step_down_rate'] ?? 0)) : 0.0,
                'withCap' => str_contains($type, 'with_capitalization') ? 1 : 0, 'withoutCap' => in_array($type, self::GRACE, true) ? 1 : 0,
                'head' => $row, 'first' => $row + 2, 'last' => $row + 2 + $count,
            ];
            $row += $count + 4;
        }
        $lsPre = $x->pre('ls');
        $letter = fn (string $name) => Sheet::col(array_search($name, self::COLUMNS, true) + 1);
        $range = fn (string $key, string $name) => $lsPre.'$'.$letter($name).'$'.$info[$key]['first'].':$'.$letter($name).'$'.$info[$key]['last'];
        $cellAt = fn (string $key, string $name, int $r) => $lsPre.'$'.$letter($name).'$'.$r;

        // ── the loans: terms, then month by month ──
        foreach ($info as $key => $i) {
            $loan = $i['loan'];
            $cell = fn (string $c) => $g->lcell("{$key}$c");
            $g->section($i['label']);
            $g->scalar("{$key}type", $x->t('ln.type'), $i['type'], 'lock_txt', $x->t('c.locked'));
            $g->scalar("{$key}base", $x->t('ln.base'), (float) ($loan['base_rate'] ?? 0), 'in_pct');
            $g->scalar("{$key}margin", $x->t('ln.margin'), (float) ($loan['margin_rate'] ?? 0), 'in_pct');
            $g->scalar("{$key}price", $x->t('ln.price'), "({$cell('base')}+{$cell('margin')})/100", 'num2', null, true);
            $g->scalar("{$key}tenor", $x->t('ln.tenor'), $i['tenor'], 'lock_int', $x->t('c.locked'));
            $g->scalar("{$key}iv", $x->t('ln.interval'), $i['interval'], 'lock_int', $x->t('c.locked'));
            $g->scalar("{$key}n", $x->t('ln.count'), $i['count'], 'lock_int', $x->t('c.locked'));
            $g->scalar("{$key}grace", $x->t('ln.grace'), (float) ($loan['grace_period'] ?? 0), 'in_int');
            if ($i['stepable']) {
                $g->scalar("{$key}step", $x->t('ln.step'), $i['stepRate'], 'in_pct');
            } else {
                $g->scalar("{$key}step", $x->t('ln.step'), 0, 'lock_int', $x->t('ln.noStep'));
            }
            $g->scalar("{$key}sv", $x->t('ln.stepEvery'), $i['stepEvery'], 'lock_int', $x->t('c.locked'));
            $g->scalar("{$key}wcap", $x->t('ln.withCap'), $i['withCap'], 'lock_int', $x->t('c.locked'));
            $g->scalar("{$key}ncap", $x->t('ln.withoutCap'), $i['withoutCap'], 'lock_int', $x->t('c.locked'));
            $g->scalar("{$key}jis", $x->t('ln.firstInst'), "MIN(ROUNDUP(MAX(0,{$cell('grace')})/{$cell('iv')},0)+1,{$cell('n')})", 'lock_int', $x->t('ln.firstInstNote'), true);
            $g->later("{$key}L", $x->t('ln.amount'), fn () => "{$x->lastCol()}{$g->rowOf("{$key}wc")}", 'num2', $x->t('ln.amountNote'));
            $g->later("{$key}S", $x->t('ln.start'), fn () => "MAX(0,{$x->lastCol()}{$g->rowOf("{$key}lk")})", 'lock_int', $x->t('ln.startNote'));
            $g->later("{$key}A", $x->t('ln.installment'), fn () => "IF({$cellAt($key, 'if', $i['last'])}=0,0,{$cellAt($key, 'lf', $i['last'])}/-{$cellAt($key, 'if', $i['last'])})", 'num2', $x->t('ln.installmentNote'));
            $g->blank();

            $g->monthHeader();
            $g->plan(array_map(fn ($r) => "{$key}$r", array_merge(['w', 'wf', 'lk', 'wd', 'wi', 'wc', 'si', 'sp', 'sr', 'se', 'bal', 'int'], in_array($key, ['ld', 'pp'], true) ? ['cap'] : [])));
            $price = $g->lcell("{$key}price");
            $draw = $x->rng($i['draw']);
            $dates = $x->calRng('date');
            $idx = fn ($k) => $k->cal('idx');
            // a withdrawal month: a month with something drawn (a total drawn under 1 is no loan at all)
            $g->line("{$key}w", $x->t('ln.draw'), fn ($k) => "IF(SUM($draw)<1,0,IF({$k->x($i['draw'])}>0.000001,{$k->x($i['draw'])},0))", ['style' => 'num', 'total' => 'sum']);
            $g->line("{$key}wf", $x->t('ln.isDraw'), fn ($k) => "IF({$k->r("{$key}w")}>0,1,0)", ['style' => 'flag']);
            $g->line("{$key}lk", $x->t('ln.lastDraw'), fn ($k) => "IF({$k->r("{$key}wf")}=1,{$idx($k)},{$k->pr("{$key}lk")})", ['style' => 'flag', 'before' => '-1']);
            $g->line("{$key}wd", $x->t('ln.days'), fn ($k) => "IF({$k->r("{$key}wf")}=1,IF({$k->pr("{$key}lk")}<0,0,{$k->cal('date')}-INDEX($dates,1,{$k->pr("{$key}lk")}+1)),0)", ['style' => 'flag']);
            $g->line("{$key}wi", $x->t('ln.drawInt'), fn ($k) => "IF({$k->r("{$key}wf")}=1,({$k->pr("{$key}wc")}+{$k->r("{$key}w")})*$price/360*{$k->r("{$key}wd")},0)", ['style' => 'num', 'total' => 'sum']);
            $g->line("{$key}wc", $x->t('ln.drawBal'), fn ($k) => "IF({$k->r("{$key}wf")}=1,IF(ABS({$k->pr("{$key}wc")}+{$k->r("{$key}w")}+{$k->r("{$key}wi")})<1,0,{$k->pr("{$key}wc")}+{$k->r("{$key}w")}+{$k->r("{$key}wi")}),{$k->pr("{$key}wc")})", ['style' => 'num', 'before' => '0']);

            $S = $g->lcell("{$key}S");
            $at = fn ($k, string $name) => "SUMIF({$range($key, 'idx')},{$idx($k)},{$range($key, $name)})";
            $g->line("{$key}si", $x->t('ln.schedInt'), fn ($k) => $at($k, 'int'), ['style' => 'num', 'total' => 'sum']);
            $g->line("{$key}sp", $x->t('ln.schedPay'), fn ($k) => $at($k, 'pay'), ['style' => 'num', 'total' => 'sum']);
            $g->line("{$key}sr", $x->t('ln.schedPrin'), fn ($k) => $at($k, 'prin'), ['style' => 'num', 'total' => 'sum']);
            $g->line("{$key}se", $x->t('ln.schedEnd'), fn ($k) => "IF({$idx($k)}>=$S,INDEX({$range($key, 'end')},MATCH({$idx($k)},{$range($key, 'idx')},1)),0)", ['style' => 'num']);
            $g->line("{$key}bal", $x->t('ln.balance'), fn ($k) => "IF({$idx($k)}<$S,{$k->r("{$key}wc")},{$k->r("{$key}se")})", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
            // the interest the application keeps for the loan: the schedule's; for the construction loan the schedule's first line takes the interest of the last drawing period
            $interest = $key === 'hc'
                ? fn ($k) => "IF({$idx($k)}=$S,{$k->r("{$key}wi")},{$k->r("{$key}si")})"
                : fn ($k) => $k->r("{$key}si");
            $g->line("{$key}int", $x->t('ln.interest'), $interest, ['style' => 'num', 'total' => 'sum', 'bold' => true]);
            if (in_array($key, ['ld', 'pp'], true)) {
                $g->line("{$key}cap", $x->t('ln.capital'), fn ($k) => "IF({$idx($k)}<Z_OpIdx,{$k->r("{$key}si")}+{$k->r("{$key}wi")},0)", ['style' => 'num', 'total' => 'sum']);
            }
            $g->blank();
        }
        $g->flush();
        $sh->freeze(4, Ctx::FIRST);

        // ── the schedule tables ──
        $ls->width(1, 8)->width(2, 11)->width(3, 11)->width(4, 8)->width(5, 11);
        for ($c = 6; $c <= count(self::COLUMNS); $c++) {
            $ls->width($c, 16);
        }
        $ls->set(1, 1, $x->t('ls.title'), $x->s('title'));
        for ($c = 2; $c <= count(self::COLUMNS); $c++) {
            $ls->set(1, $c, '', $x->s('title'));
        }
        $ls->height(1, 30)->set(2, 1, $x->t('ls.sub'), $x->s('subtitle'));
        foreach ($info as $key => $i) {
            $cell = fn (string $c) => $g->cell("{$key}$c");
            $L = $cell('L');
            $S = $cell('S');
            $jis = $cell('jis');
            $sv = $cell('sv');
            $stepRate = $cell('step').'/100';
            $price = $cell('price');
            $head = $i['head'];
            for ($c = 1; $c <= count(self::COLUMNS); $c++) {
                $ls->set($head, $c, $c === 1 ? $i['label'] : '', $x->s('section'));
            }
            $heads = ['no' => 'ls.no', 'idx' => 'ls.month', 'date' => 'ls.date', 'days' => 'ls.days', 'f' => 'ls.f', 'lf' => 'ls.lf', 'sf' => 'ls.sf', 'if' => 'ls.if', 'amt' => 'ls.amt',
                'beg' => 'ls.beg', 'int' => 'ls.int', 'pay' => 'ls.pay', 'prin' => 'ls.prin', 'end' => 'ls.end'];
            foreach (self::COLUMNS as $c => $name) {
                $ls->set($head + 1, $c + 1, $x->t($heads[$name]), $x->s('hdr'));
            }
            $ls->height($head + 1, 32);
            for ($j = 0; $j <= $i['count']; $j++) {
                $r = $i['first'] + $j;
                $ref = fn (string $name, ?int $row = null) => $letter($name).($row ?? $r);
                $prev = fn (string $name) => $letter($name).($r - 1);
                $first = $j === 0;
                $ls->set($r, 1, $j, $x->s('flag'));
                $ls->formula($r, 2, "$S+{$ref('no')}*{$cell('iv')}", $x->s('flag'));
                $ls->formula($r, 3, "DATE(YEAR(Z_Start),MONTH(Z_Start)+{$ref('idx')},1)", $x->s('date'));
                $ls->formula($r, 4, $first ? '0' : "{$ref('date')}-{$prev('date')}", $x->s('flag'));
                $ls->formula($r, 5, "$price/360*{$ref('days')}", $x->s('num2'));
                $lfPrev = $first ? $L : $prev('lf');
                $ls->formula($r, 6, "IF(AND({$cell('wcap')}=0,{$ref('no')}<$jis),$L,$lfPrev*(1+{$ref('f')}))", $x->s('num'));
                $ls->formula($r, 7, "IF({$ref('no')}<$jis,0,IF($sv>=1,INT(({$ref('no')}-$jis)/$sv),0))", $x->s('flag'));
                $ifPrev = $first ? '0' : $prev('if');
                $ls->formula($r, 8, "IF({$ref('no')}<$jis,0,IF({$ref('no')}=$jis,-1,$ifPrev+$ifPrev*{$ref('f')}-(1+$stepRate)^{$ref('sf')}))", $x->s('num2'));
                $ls->formula($r, 9, "IF({$ref('no')}<$jis,0,{$cell('A')}*(1+$stepRate)^IF($sv>=2,{$ref('sf')},0))", $x->s('num'));
                $ls->formula($r, 10, $first ? $L : $prev('end'), $x->s('num'));
                $ls->formula($r, 11, "{$ref('beg')}*{$ref('f')}", $x->s('num'));
                $ls->formula($r, 12, "IF(AND({$cell('ncap')}=1,{$ref('amt')}=0),{$ref('int')},{$ref('amt')})", $x->s('num'));
                $ls->formula($r, 13, "{$ref('pay')}-{$ref('int')}", $x->s('num'));
                $ls->formula($r, 14, "IF(ABS({$ref('beg')}+{$ref('int')}-{$ref('pay')})<1,0,{$ref('beg')}+{$ref('int')}-{$ref('pay')})", $x->s('num'));
            }
        }
        $ls->freeze(4, 1);
    }
}
