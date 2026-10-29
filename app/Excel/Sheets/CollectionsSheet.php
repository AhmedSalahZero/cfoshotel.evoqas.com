<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;
use App\Excel\ModelData;
use App\Excel\Sheet;

// Collections and receivables of the five revenue streams, as CollectionPolicyService and ReceivableEndBalanceService work them out.
// A stream is collected either by one set of terms for the whole stream, or by terms of its own for every item (sales channel / facility).
//   customize       up to four shares, each collected `days` after the sale: 0 months when the days are below 30, else round(days / 30)
//   system default  the revenue of every period (month, quarter, half year, year of operating months counted from the opening) is collected
//                   in the FIRST month of the period
// The receivable at the end of a month is what was sold up to it minus what was collected up to it.
final class CollectionsSheet
{
    /** stream => [sector prefix of the "per channel or general" switch, general-terms prefix, revenue sheet, total revenue row] */
    private const STREAMS = [
        'rooms' => ['room', 'rooms', 'rm', 'rev'],
        'foods' => ['food', 'foods', 'fb', 'rev'],
        'gaming' => ['casino', 'casinos', 'cs', 'rev'],
        'meetings' => ['meeting', 'meetings', 'mt', 'rev'],
        'others' => ['other', 'others', 'ot', 'rev'],
    ];

    public static function build(Ctx $x): void
    {
        $m = $x->m;
        $p = $m->p;
        $sh = $x->sheet('col', $x->t('n.col'));
        $g = new Grid($x, $sh, 'col');
        $g->title($x->t('col.title'), $x->t('col.sub'));

        // ── the items of every stream, with their revenue row and their terms ──
        $streams = [];
        foreach (self::STREAMS as $stream => [$switch, $general, $sheetKey]) {
            $perItem = ($p[$switch.'_collection_policy_type'] ?? '') === 'terms_per_sales_channel';
            $items = [];
            if ($perItem) {
                $rows = match ($stream) {
                    'rooms' => $m->channels,
                    'foods' => $m->foods,
                    'gaming' => $m->casinos,
                    'meetings' => $m->meetings,
                    'others' => $m->others,
                };
                foreach ($rows as $i => $row) {
                    $label = $stream === 'rooms' ? (string) ($row['name'] ?? '') : $m->name($switch, $row[$switch.'_type_id'] ?? null);
                    $items[] = [
                        'label' => $label,
                        'revenue' => $stream === 'rooms' ? "rm.rc$i" : "$sheetKey.rv$i",
                        'type' => (string) ($row['collection_policy_type'] ?? ''),
                        'interval' => (string) ($row['collection_policy_interval'] ?? ''),
                        'value' => ModelData::arr($row['collection_policy_value'] ?? []),
                    ];
                }
            } else {
                $items[] = [
                    'label' => $x->t('col.stream.'.$stream),
                    'revenue' => $sheetKey.'.rev',
                    'type' => (string) ($p[$general.'_general_collection_policy_type'] ?? ''),
                    'interval' => (string) ($p[$general.'_general_collection_policy_interval'] ?? ''),
                    'value' => ModelData::arr($p[$general.'_general_collection_policy_value'] ?? []),
                ];
            }
            $streams[$stream] = $items;
        }

        // ── the terms (inputs) ──
        $g->section($x->t('col.policies'));
        $titles = [1 => $x->t('col.item')];
        for ($k = 0; $k < 4; $k++) {
            $titles[Ctx::FIRST + 2 * $k] = $x->t('col.rate').' '.($k + 1);
            $titles[Ctx::FIRST + 2 * $k + 1] = $x->t('col.days').' '.($k + 1);
            $titles[Ctx::FIRST + 8 + $k] = $x->t('col.lag').' '.($k + 1);
        }
        $r = $g->row;
        foreach ([1, 2, 3, 4] as $c) {
            $sh->set($r, $c, $c === 1 ? $titles[1] : '', $x->s($c === 1 ? 'hdr_l' : 'hdr'));
        }
        for ($c = Ctx::FIRST; $c < Ctx::FIRST + 12; $c++) {
            $sh->set($r, $c, $titles[$c] ?? '', $x->s('hdr'));
        }
        $sh->height($r, 32);
        $g->row++;
        foreach ($streams as $stream => $items) {
            foreach ($items as $n => $item) {
                $r = $g->row;
                $sh->set($r, 1, $x->t('col.stream.'.$stream).' - '.$item['label'], $x->s('label_i'));
                if ($item['type'] === 'customize') {
                    $rates = (array) ($item['value']['rate'] ?? []);
                    $days = (array) ($item['value']['due_in_days'] ?? []);
                    for ($k = 0; $k < 4; $k++) {
                        $sh->set($r, Ctx::FIRST + 2 * $k, (float) ($rates[$k] ?? 0), $x->s('in_pct'));
                        $sh->set($r, Ctx::FIRST + 2 * $k + 1, (float) ($days[$k] ?? 0), $x->s('in_int'));
                        $day = Sheet::col(Ctx::FIRST + 2 * $k + 1).$r;
                        $sh->formula($r, Ctx::FIRST + 8 + $k, "IF($day<30,0,ROUND($day/30,0))", $x->s('flag'));
                        $x->cells["col.$stream.$n.rate$k"] = $x->pre('col').'$'.Sheet::col(Ctx::FIRST + 2 * $k).'$'.$r;
                        $x->cells["col.$stream.$n.lag$k"] = $x->pre('col').'$'.Sheet::col(Ctx::FIRST + 8 + $k).'$'.$r;
                    }
                } elseif ($item['type'] === 'system_default' && in_array($item['interval'], ['monthly', 'quarterly', 'quartly', 'semi-annually', 'semi annually', 'annually'], true)) {
                    $interval = match ($item['interval']) {
                        'quartly' => 'quarterly',
                        'semi annually' => 'semi-annually',
                        default => $item['interval'],
                    };
                    $sh->set($r, 2, str_replace('%s', $x->t('int.'.$interval), $x->t('col.sysDefault')), $x->s('note'));
                    $streams[$stream][$n]['period'] = ['monthly' => 1, 'quarterly' => 3, 'semi-annually' => 6, 'annually' => 12][$interval];
                } else {
                    $sh->set($r, 2, $x->t('col.notSet'), $x->s('note'));
                }
                $g->row++;
            }
        }
        $g->blank();

        // ── month by month ──
        $g->section($x->t('col.monthly'));
        $g->monthHeader();
        $collectedKeys = [];
        $owedKeys = [];
        foreach ($streams as $stream => $items) {
            $sKeysC = [];
            $sKeysO = [];
            foreach ($items as $n => $item) {
                $label = $x->t('col.stream.'.$stream).' - '.$item['label'];
                $revKey = $item['revenue'];
                $revRange = $x->rng($revKey);
                if ($item['type'] === 'customize') {
                    $g->line("c{$stream}$n", $x->t('col.collected').' - '.$label, function ($k) use ($x, $stream, $n, $revRange) {
                        $parts = [];
                        for ($e = 0; $e < 4; $e++) {
                            $parts[] = $x->name("col.$stream.$n.rate$e").'/100*'.$x->shift($revRange, $x->name("col.$stream.$n.lag$e"), $k->c);
                        }

                        return implode('+', $parts);
                    }, ['style' => 'num', 'total' => 'sum']);
                } elseif (isset($item['period'])) {
                    $period = $item['period'];
                    $g->line("c{$stream}$n", $x->t('col.collected').' - '.$label, function ($k) use ($x, $revKey, $period) {
                        $cells = [];
                        for ($j = 0; $j < $period; $j++) {
                            if ($k->m + $j < $x->m->D) {
                                $cells[] = $x->x($revKey, Sheet::col(Ctx::FIRST + $k->m + $j));
                            }
                        }

                        return "IF(AND({$k->cal('op')}=1,MOD({$k->cal('oppos')},$period)=0),".implode('+', $cells).',0)';
                    }, ['style' => 'num', 'total' => 'sum']);
                } else {
                    $g->line("c{$stream}$n", $x->t('col.collected').' - '.$label, fn ($k) => '0', ['style' => 'num', 'total' => 'sum']);
                }
                $g->line("o{$stream}$n", $x->t('col.owed').' - '.$label, fn ($k) => ($k->m === 0 ? '0' : $k->pr("o{$stream}$n")).'+'.$k->x($revKey).'-'.$k->r("c{$stream}$n"), ['style' => 'num']);
                $sKeysC[] = "c{$stream}$n";
                $sKeysO[] = "o{$stream}$n";
            }
            $g->line("c$stream", $x->t('col.collected').' - '.$x->t('col.stream.'.$stream), fn ($k) => implode('+', array_map(fn ($key) => $k->r($key), $sKeysC)), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
            $g->line("o$stream", $x->t('col.owed').' - '.$x->t('col.stream.'.$stream), fn ($k) => implode('+', array_map(fn ($key) => $k->r($key), $sKeysO)), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
            $collectedKeys[] = "c$stream";
            $owedKeys[] = "o$stream";
        }
        $g->line('collected', $x->t('col.totCollected'), fn ($k) => implode('+', array_map(fn ($key) => $k->r($key), $collectedKeys)), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $g->line('owed', $x->t('col.totOwed'), fn ($k) => implode('+', array_map(fn ($key) => $k->r($key), $owedKeys)), ['style' => 'tot', 'bold' => true, 'label_style' => 'label_t']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
