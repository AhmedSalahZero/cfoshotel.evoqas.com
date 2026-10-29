<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\K;
use App\Excel\ModelData;

// The base management fee, the property taxes and the property insurance, as calculateBaseManagementFeesAmounts, calculatePropertyTaxes and
// calculatePropertyInsurance (and their payable statements) work them out.
//   base fee    the percentage of the year x the revenue of the month.
//   taxes       once a year, in the month the hotel opens and every twelve months after it, the building's gross value then x the rate (the first year as it is,
//               the later ones x (1 + the escalation)) is spread over the twelve months. It is paid once, in the chosen month of the following year.
//   insurance   the same, on the gross value of the building and of the FF&E, and paid in advance in the month it is charged from.
//   Neither is paid in the last year of the study (the payment would fall after it). What is charged and not yet paid is owed (or, for the insurance, paid in advance).
final class TaxesSheet
{
    public static function build(Ctx $x): void
    {
        $m = $x->m;
        $fee = fn (string $section) => collect($m->managementFees)->first(fn ($f) => ($f['model_name'] ?? '') === 'management' && ($f['section_name'] ?? '') === $section);
        $base = $fee('base_management_fees_as_percentage_from_revenues');
        $property = fn (string $name) => collect($m->expenses)->first(fn ($e) => ($e['model_name'] ?? '') === 'property' && ($e['section_name'] ?? '') === 'other_property_fixed_expenses' && ($e['name'] ?? '') === $name);
        $tax = $property('Property Taxes');
        $insurance = $property('Property Insurance');
        $hasTax = $tax !== null && isset($x->rows['fa.blini']);
        $hasInsurance = $insurance !== null && isset($x->sheets['fa']);

        $sh = $x->sheet('tx', $x->t('n.tx'));
        $g = new Grid($x, $sh, 'tx');
        $g->title($x->t('tx.title'), $x->t('tx.sub'));
        $op = 'Z_OpIdx';
        $stu = fn (K $k) => $k->cal('study');

        $g->section($x->t('tx.assump'));
        $g->yearHeader('yh');
        $g->yearInput('bfp', $x->t('tx.baseFee'), $base ? $m->yearly($base['payload'] ?? []) : array_fill(0, $m->Y, 0.0), 'in_pct');
        $g->blank();
        if ($hasTax) {
            $g->scalar('trate', $x->t('tx.taxRate'), (float) ($tax['percentage_from_fixed_assets'] ?? 0), 'in_pct');
            $g->scalar('tesc', $x->t('tx.esc'), (float) ($tax['guest_annual_escalation_rate'] ?? 0), 'in_pct');
            $g->scalar('tpm', $x->t('tx.payMonth'), (int) ($tax['payment_month'] ?? 0), 'in_int');
        }
        if ($hasInsurance) {
            $g->scalar('irate', $x->t('tx.insRate'), (float) ($insurance['percentage_from_fixed_assets'] ?? 0), 'in_pct');
            $g->scalar('iesc', $x->t('tx.esc'), (float) ($insurance['guest_annual_escalation_rate'] ?? 0), 'in_pct');
        }
        $g->scalar('opm', $x->t('tx.opMonth'), "INDEX({$x->calRng('mnum')},1,$op+1)", 'lock_int', null, true);
        $g->scalar('opy', $x->t('tx.opYear'), "INDEX({$x->calRng('yidx')},1,$op+1)", 'lock_int', null, true);
        $g->scalar('lasty', $x->t('tx.lastYear'), "INDEX({$x->calRng('yidx')},1,Z_StudyEnd+1)", 'lock_int', null, true);
        $g->blank();

        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        $g->line('bf', $x->t('tx.baseFeeAmount'), fn (K $k) => "{$stu($k)}*{$k->x('rev.total')}*{$g->pick('bfp', $k)}/100", ['style' => 'num', 'total' => 'sum']);
        $g->blank();

        // the months a yearly charge starts: the month the hotel opens and every twelve months after it
        $g->plan(['anm', 'anc']);
        $g->line('anm', $x->t('tx.anniversary'), fn (K $k) => "IF(AND({$stu($k)}=1,{$k->cal('mnum')}={$g->lcell('opm')},{$k->cal('yidx')}>={$g->lcell('opy')}),1,0)", ['style' => 'flag']);
        $g->line('anc', $x->t('tx.anniversaries'), fn (K $k) => ($k->m === 0 ? '0' : $k->pr('anc')).'+'.$k->r('anm'), ['style' => 'flag']);
        $first = fn (K $k) => "IF(AND({$k->r('anm')}=1,".($k->m === 0 ? '0' : $k->pr('anc')).'=0),1,0)';
        $ok = fn (K $k) => "IF({$k->cal('yidx')}+1<={$g->lcell('lasty')},1,0)";
        $window = fn (string $row, K $k) => "{$stu($k)}*SUMPRODUCT(({$x->calRng('idx')}>={$k->cal('idx')}-11)*({$x->calRng('idx')}<={$k->cal('idx')})*{$g->rng($row)})/12";
        $g->blank();

        if ($hasTax) {
            $g->section($x->t('tx.taxes'));
            $g->plan(['tbase', 'tann', 'tok', 'texp', 'tpay', 'tbal']);
            $g->line('tbase', $x->t('tx.base'), fn (K $k) => "{$k->r('anm')}*{$k->x('fa.blini')}", ['style' => 'num']);
            $g->line('tann', $x->t('tx.annual'), fn (K $k) => "{$k->r('tbase')}*{$g->lcell('trate')}/100*IF({$first($k)}=1,1,1+{$g->lcell('tesc')}/100)", ['style' => 'num']);
            $g->line('tok', $x->t('tx.payable'), fn (K $k) => "{$k->r('tann')}*{$ok($k)}", ['style' => 'num']);
            $g->line('texp', $x->t('tx.expense'), fn (K $k) => $window('tann', $k), ['style' => 'num', 'total' => 'sum']);
            $g->line('tpay', $x->t('tx.paid'), fn (K $k) => "{$stu($k)}*IF({$g->lcell('tpm')}=0,0,{$x->shift($g->rng('tok'), "(12+{$g->lcell('tpm')}-{$g->lcell('opm')})", $k->c)})", ['style' => 'num', 'total' => 'sum']);
            $g->line('tbal', $x->t('tx.owed'), fn (K $k) => "IF(AND({$stu($k)}=1,{$k->cal('idx')}>=$op),".($k->m === 0 ? '0' : $k->pr('tbal'))."+{$k->r('texp')}-{$k->r('tpay')},0)", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
            $g->blank();
        }
        if ($hasInsurance) {
            $parts = array_values(array_filter(['fa.blini', 'fa.pfini', 'fa.itI'], fn ($key) => isset($x->rows[$key])));
            $g->section($x->t('tx.insurance'));
            $g->plan(['ibase', 'iann', 'iok', 'iexp', 'ibal']);
            $g->line('ibase', $x->t('tx.base'), fn (K $k) => $k->r('anm').'*('.($parts === [] ? '0' : implode('+', array_map(fn ($key) => $k->x($key), $parts))).')', ['style' => 'num']);
            $g->line('iann', $x->t('tx.annual'), fn (K $k) => "{$k->r('ibase')}*{$g->lcell('irate')}/100*IF({$first($k)}=1,1,1+{$g->lcell('iesc')}/100)", ['style' => 'num']);
            $g->line('iok', $x->t('tx.paidAdvance'), fn (K $k) => "{$k->r('iann')}*{$ok($k)}", ['style' => 'num', 'total' => 'sum']);
            $g->line('iexp', $x->t('tx.expense'), fn (K $k) => $window('iann', $k), ['style' => 'num', 'total' => 'sum']);
            $g->line('ibal', $x->t('tx.owedIns'), fn (K $k) => "IF(AND({$stu($k)}=1,{$k->cal('idx')}>=$op),".($k->m === 0 ? '0' : $k->pr('ibal'))."+{$k->r('iexp')}-{$k->r('iok')},0)", ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
            $g->blank();
        }
        $g->line('tin', $x->t('tx.taxIns'), fn (K $k) => ($hasTax ? $k->r('texp') : '0').'+'.($hasInsurance ? $k->r('iexp') : '0'), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('tinpay', $x->t('tx.taxInsPaid'), fn (K $k) => ($hasTax ? $k->r('tpay') : '0').'+'.($hasInsurance ? $k->r('iok') : '0'), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
