<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\ModelData;

// The Study sheet (the settings and assumptions of the study) and the Calendar sheet (one column per month: its date, days and on / off switches).
final class StudySheet
{
    public static function study(Ctx $x): void
    {
        $m = $x->m;
        $p = $m->p;
        $sh = $x->sheet('study', $x->t('n.study'));
        $g = new Grid($x, $sh, 'study');
        $g->title($x->t('study.title'), $x->t('study.sub'));
        $name = fn (string $n, string $cellKey) => $x->wb->defineName($n, $x->name('study.'.$cellKey));

        $g->section($x->t('study.title'));
        $g->scalar('name', $x->t('study.name'), (string) ($p['study_name'] ?? ''), 'lock_txt');
        $g->scalar('property', $x->t('study.property'), (string) ($p['property_name'] ?? ''), 'lock_txt');
        $g->scalar('start', $x->t('study.start'), (float) $m->serial(0), 'lock_date', $x->t('c.locked'));
        $name('Z_Start', 'start');
        $g->scalar('years', $x->t('study.years'), (int) $p['duration_in_years'], 'lock_int', $x->t('c.locked'));
        $name('Z_Years', 'years');
        $g->scalar('opdate', $x->t('study.opStart'), (float) $m->serial($m->opIdx), 'lock_date', $x->t('c.locked'));
        $name('Z_OpDate', 'opdate');
        $g->blank();

        $g->section($x->t('study.derived'));
        $derived = [
            ['months', 'study.months', 'Z_Months', 'Z_Years*12'],
            ['opidx', 'study.opIdx', 'Z_OpIdx', '(YEAR(Z_OpDate)-YEAR(Z_Start))*12+MONTH(Z_OpDate)-MONTH(Z_Start)'],
            ['baseyear', 'study.baseYear', 'Z_BaseYear', 'YEAR(Z_Start)'],
            ['studyend', 'study.studyEnd', 'Z_StudyEnd', 'Z_Months-1'],
        ];
        foreach ($derived as [$key, $label, $defined, $formula]) {
            $g->scalar($key, $x->t($label), $formula, 'lock_int', null, true);
            $name($defined, $key);
        }
        $g->blank();

        $g->section($x->t('study.assump'));
        $g->scalar('tax', $x->t('study.tax'), (float) ($p['corporate_taxes_rate'] ?? 0), 'in_pct');
        $name('Z_Tax', 'tax');
        $g->scalar('return', $x->t('study.return'), (float) ($p['investment_return_rate'] ?? 0), 'in_pct');
        $name('Z_Return', 'return');
        $g->scalar('growth', $x->t('study.growth'), (float) ($p['perpetual_growth_rate'] ?? 0), 'in_pct');
        $name('Z_Growth', 'growth');
        $hasAdditional = ($p['additional_currency'] ?? '') !== '' && $p['additional_currency'] !== null;
        $g->scalar('addcur', $x->t('study.addCur'), $hasAdditional ? (int) $p['additional_currency'] : 0, 'lock_int', $x->t('c.locked'));
        $name('Z_AddCur', 'addcur');
        $g->blank();

        $g->yearHeader('yh', $x->t('study.exchange'));
        $exchange = ModelData::arr($p['exchange_rates'] ?? []);
        $values = [];
        for ($y = 0; $y < $m->Y; $y++) {
            // a year without a rate counts as 1, as the application does
            $values[] = isset($exchange[$y]) && $exchange[$y] !== '' ? (float) $exchange[$y] : 1.0;
        }
        $g->yearInput('exch', $x->t('rooms.exch'), $values, 'in_num');
        $g->blank();
        $sh->set($g->row, 1, $x->t('study.fixedNote'), $x->s('note'));
    }

    public static function calendar(Ctx $x): void
    {
        $m = $x->m;
        $sh = $x->sheet('cal', $x->t('n.cal'));
        $g = new Grid($x, $sh, 'cal');
        $g->title($x->t('cal.title'), $x->t('cal.sub'));
        $g->plan(['idx', 'date', 'year', 'mnum', 'yidx', 'days', 'op', 'study', 'opyr', 'oppos']);

        $r = $g->row;
        $g->register('idx', $r);
        $sh->set($r, 1, $x->t('cal.idx'), $x->s('label_b'));
        for ($i = 0; $i < $m->D; $i++) {
            $sh->set($r, Ctx::FIRST + $i, $i, $x->s('hdr_int'));
        }
        $g->row++;

        $g->line('date', $x->t('cal.date'), fn ($k) => "DATE(YEAR(Z_Start),MONTH(Z_Start)+{$k->r('idx')},1)", ['style' => 'date']);
        $g->line('year', $x->t('cal.year'), fn ($k) => "YEAR({$k->r('date')})", ['style' => 'flag']);
        $g->line('mnum', $x->t('cal.mnum'), fn ($k) => "MONTH({$k->r('date')})", ['style' => 'flag']);
        $g->line('yidx', $x->t('cal.yidx'), fn ($k) => "{$k->r('year')}-Z_BaseYear", ['style' => 'flag']);
        $g->line('days', $x->t('cal.days'), fn ($k) => "DAY(DATE(YEAR({$k->r('date')}),MONTH({$k->r('date')})+1,0))", ['style' => 'flag']);
        $g->line('op', $x->t('cal.op'), fn ($k) => "IF({$k->r('idx')}>=Z_OpIdx,1,0)", ['style' => 'flag']);
        $g->line('study', $x->t('cal.study'), fn ($k) => "IF({$k->r('idx')}<=Z_StudyEnd,1,0)", ['style' => 'flag']);
        $g->line('opyr', $x->t('cal.opyr'), fn ($k) => "IF({$k->r('op')}=1,INT(({$k->r('idx')}-Z_OpIdx)/12),0)", ['style' => 'flag']);
        $g->line('oppos', $x->t('cal.oppos'), fn ($k) => "IF({$k->r('op')}=1,{$k->r('idx')}-Z_OpIdx,0)", ['style' => 'flag']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
