<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\ModelData;

// The purchase of the land and of the property (a hotel bought as it stands), as LandAcquisitionCostAndPayment works it out:
//   cash          the whole cost in the purchase month
//   installment   the first down payment in the purchase month, the second `after` months later (in the first when `after` is 0), then `count` equal installments
//                 one interval apart (monthly, quarterly, half yearly, yearly) after the second
//   customize     up to four shares of the cost, each paid `days` after the purchase (no months when under 30 days, else round(days / 30))
//   equity        the equity share of the cost pays first, payment by payment in date order; the rest is drawn from the loan
final class PurchasesSheet
{
    public static function build(Ctx $x): void
    {
        $m = $x->m;
        $blocks = [];
        if ($m->acquisition !== null) {
            $a = $m->acquisition;
            $blocks['ld'] = ['label' => $x->t('pur.land'), 'row' => $a, 'cost' => 'land_purchase_cost', 'cont' => 'land_contingency_rate', 'method' => 'land_payment_method',
                'first' => 'first_land_down_payment_percentage', 'second' => 'second_land_down_payment_percentage', 'after' => 'land_after_month', 'count' => 'land_installment_count',
                'equity' => 'land_equity_funding_rate', 'date' => 'purchase_date'];
        }
        if ($m->property !== null) {
            $a = $m->property;
            $blocks['pp'] = ['label' => $x->t('pur.property'), 'row' => $a, 'cost' => 'property_purchase_cost', 'cont' => 'property_contingency_rate', 'method' => 'property_payment_method',
                'first' => 'first_property_down_payment_percentage', 'second' => 'second_property_down_payment_percentage', 'after' => 'property_after_month', 'count' => 'property_installment_count',
                'equity' => 'property_equity_funding_rate', 'date' => 'purchase_date'];
        }
        if ($blocks === []) {
            return;
        }

        $sh = $x->sheet('pur', $x->t('n.pur'));
        $g = new Grid($x, $sh, 'pur');
        $g->title($x->t('pur.title'), $x->t('pur.sub'));

        foreach ($blocks as $key => $b) {
            $r = $b['row'];
            $method = (string) ($r[$b['method']] ?? '');
            $g->section($b['label']);
            $g->scalar("{$key}cost", $x->t('inv.cost'), (float) ($r[$b['cost']] ?? 0), 'in_num');
            $g->scalar("{$key}cont", $x->t('inv.cont'), (float) ($r[$b['cont']] ?? 0), 'in_pct');
            $g->scalar("{$key}tc", $x->t('inv.total'), "{$g->lcell("{$key}cost")}*(1+{$g->lcell("{$key}cont")}/100)", 'num2', null, true);
            $g->scalar("{$key}at", $x->t('pur.month'), (int) ($r[$b['date']] ?? 0), 'lock_int', $x->t('c.locked'));
            $g->scalar("{$key}eq", $x->t('inv.equity'), (float) (($r[$b['equity']] ?? null) === null || $r[$b['equity']] === '' ? 100 : $r[$b['equity']]), 'in_pct');
            if ($method === 'installment') {
                $g->scalar("{$key}p1", $x->t('pur.first'), (float) ($r[$b['first']] ?? 0), 'in_pct');
                $g->scalar("{$key}p2", $x->t('pur.second'), (float) ($r[$b['second']] ?? 0), 'in_pct');
                $g->scalar("{$key}af", $x->t('pur.after'), (float) ($r[$b['after']] ?? 0), 'lock_int', $x->t('c.locked'));
                $g->scalar("{$key}cn", $x->t('pur.count'), (float) (($r[$b['count']] ?? 0) ?: 1), 'lock_int', $x->t('c.locked'));
            } elseif ($method === 'customize') {
                $value = ModelData::arr($r['collection_policy_value'] ?? []);
                $rates = (array) ($value['rate'] ?? []);
                $days = (array) ($value['due_in_days'] ?? []);
                for ($e = 0; $e < 4; $e++) {
                    $g->scalar("{$key}r$e", $x->t('col.rate').' '.($e + 1), (float) ($rates[$e] ?? 0), 'in_pct');
                    $g->scalar("{$key}d$e", $x->t('col.days').' '.($e + 1), (float) ($days[$e] ?? 0), 'in_int');
                    $g->scalar("{$key}l$e", $x->t('col.lag').' '.($e + 1), "IF({$g->lcell("{$key}d$e")}<30,0,ROUND({$g->lcell("{$key}d$e")}/30,0))", 'lock_int', null, true);
                }
            }
            $g->blank();
        }

        $g->section($x->t('fac.monthly'));
        $g->monthHeader();
        foreach ($blocks as $key => $b) {
            $r = $b['row'];
            $method = (string) ($r[$b['method']] ?? '');
            $tc = $g->cell("{$key}tc");
            $at = $g->cell("{$key}at");
            $interval = ['monthly' => 1, 'quarterly' => 3, 'semi-annually' => 6, 'annually' => 12][(string) ($r['installment_interval'] ?? '')] ?? 0;
            $g->line($key.'pay', $x->t('inv.paid').' - '.$b['label'], function ($k) use ($method, $tc, $at, $g, $key, $interval) {
                $idx = $k->cal('idx');

                return match ($method) {
                    'cash' => "IF($idx=$at,$tc,0)",
                    'installment' => "IF($idx=$at,({$g->cell("{$key}p1")}+IF({$g->cell("{$key}af")}=0,{$g->cell("{$key}p2")},0))/100*$tc,0)"
                        ."+IF(AND({$g->cell("{$key}af")}>0,$idx=$at+{$g->cell("{$key}af")}),{$g->cell("{$key}p2")}/100*$tc,0)"
                        .($interval === 0 ? '' : "+IF(AND($idx>$at+{$g->cell("{$key}af")},MOD($idx-$at-{$g->cell("{$key}af")},$interval)=0,($idx-$at-{$g->cell("{$key}af")})/$interval<={$g->cell("{$key}cn")}),(100-{$g->cell("{$key}p1")}-{$g->cell("{$key}p2")})/100*$tc/{$g->cell("{$key}cn")},0)"),
                    'customize' => implode('+', array_map(fn ($e) => "IF($idx=$at+{$g->cell("{$key}l$e")},{$g->cell("{$key}r$e")}/100*$tc,0)", range(0, 3))),
                    default => '0',
                };
            }, ['style' => 'num', 'total' => 'sum']);
            $g->line($key.'cum', $x->t('inv.cumPaid').' - '.$b['label'], fn ($k) => ($k->m === 0 ? '0' : $k->pr($key.'cum')).'+'.$k->r($key.'pay'), ['style' => 'num']);
            $g->line($key.'eq', $x->t('inv.equityPaid').' - '.$b['label'], fn ($k) => "MAX(0,MIN({$k->r($key.'pay')},$tc*{$g->cell("{$key}eq")}/100-".($k->m === 0 ? '0' : $k->pr($key.'cum')).'))', ['style' => 'num', 'total' => 'sum']);
            $g->line($key.'ln', $x->t('inv.loanDrawn').' - '.$b['label'], fn ($k) => $k->r($key.'pay').'-'.$k->r($key.'eq'), ['style' => 'num', 'total' => 'sum']);
        }
        $sh->freeze(4, Ctx::FIRST);
    }
}
