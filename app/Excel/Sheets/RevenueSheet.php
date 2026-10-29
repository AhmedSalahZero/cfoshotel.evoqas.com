<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Grid;

// The revenue of the five streams side by side and added up (the base of the expenses that follow the total revenue, of the fees and of the statements).
final class RevenueSheet
{
    public static function build(Ctx $x): void
    {
        $sh = $x->sheet('rev', $x->t('n.rev'));
        $g = new Grid($x, $sh, 'rev');
        $g->title($x->t('rev.title'), $x->t('rev.sub'));
        $g->monthHeader();
        $keys = [];
        foreach (['rm' => 'rooms', 'fb' => 'foods', 'cs' => 'gaming', 'mt' => 'meetings', 'ot' => 'others'] as $sheetKey => $stream) {
            $g->line($stream, $x->t('col.stream.'.$stream), fn ($k) => $k->x($sheetKey.'.rev'), ['style' => 'num', 'total' => 'sum']);
            $keys[] = $stream;
        }
        $g->line('total', $x->t('rev.total'), fn ($k) => implode('+', array_map(fn ($key) => $k->r($key), $keys)), ['style' => 'tot', 'total' => 'sum', 'bold' => true, 'label_style' => 'label_t']);
        $sh->freeze(4, Ctx::FIRST);
    }
}
