<?php

namespace App\Excel\Sheets;

use App\Excel\Ctx;
use App\Excel\Sheet;

// The Cover: the brand and a place for the client's logo, the study, the checks, the key results, a legend, how to use the file and the contents with links.
final class CoverSheet
{
    /** Sheet keys in the order of the tabs. */
    public static function order(Ctx $x): array
    {
        $keys = ['cover', 'db', 'study', 'rm', 'fb', 'cs', 'ot', 'mt', 'rev', 'col', 'xr', 'xf', 'xg', 'xm', 'xo', 'xe', 'xk', 'xp', 'xn', 'su', 'inv', 'pur', 'ln', 'ls', 'fa', 'tx', 'is', 'cf', 'bs', 'cal'];

        return array_values(array_filter($keys, fn ($k) => isset($x->sheets[$k])));
    }

    public static function build(Ctx $x): void
    {
        $m = $x->m;
        $has = fn (string $key) => isset($x->cells[$key]);
        $sh = $x->sheet('cover', $x->t('n.cover'));
        $sh->protect(false)->gridlines(false);
        $sh->width(1, 3)->width(2, 52)->width(3, 22)->width(4, 22)->width(5, 22)->width(6, 22)->width(7, 22)->width(8, 3);
        // the brand and the place of the client's logo
        $sh->set(2, 2, 'CFO Hotels', $x->s('brand'))->height(2, 40);
        $sh->set(3, 2, $x->t('cv.product'), $x->s('brand_sub'));
        $sh->set(4, 2, $x->t('cv.tagline'), $x->s('subtitle'));
        for ($r = 2; $r <= 5; $r++) {
            for ($c = 5; $c <= 7; $c++) {
                $sh->set($r, $c, $r === 2 && $c === 5 ? $x->t('cv.logo') : '', $x->s('logo'));
            }
        }
        $sh->merge(2, 5, 5, 7);

        // the study
        $r = 7;
        self::band($x, $sh, $r++, $x->t('cv.study'));
        $sh->set($r, 2, $x->t('study.name'), $x->s('label_i'))->set($r, 3, (string) ($m->p['study_name'] ?? ''), $x->s('label_b'));
        $r++;
        $sh->set($r, 2, $x->t('study.property'), $x->s('label_i'))->set($r, 3, (string) ($m->p['property_name'] ?? ''), $x->s('label_b'));
        $r++;
        $sh->set($r, 2, $x->t('cv.period'), $x->s('label_i'))->formula($r, 3, 'TEXT(Z_Start,"yyyy-mm")&" - "&TEXT(DATE(YEAR(Z_Start),MONTH(Z_Start)+Z_Months-1,1),"yyyy-mm")', $x->s('label_b'));
        $r++;
        $sh->set($r, 2, $x->t('study.opStart'), $x->s('label_i'))->formula($r, 3, 'TEXT(Z_OpDate,"yyyy-mm")', $x->s('label_b'));
        $r++;
        $sh->set($r, 2, $x->t('cv.generated'), $x->s('label_i'))->set($r, 3, gmdate('Y-m-d'), $x->s('label_b'));
        $r += 2;

        // key results
        self::band($x, $sh, $r++, $x->t('cv.results'));
        $tiles = [[$x->t('db.npvE'), 'db.npvE', 'kpi'], [$x->t('db.irrE'), 'db.irrE', 'kpi_pct'], [$x->t('db.pbYears'), 'db.pbYears', 'kpi'], [$x->t('db.req'), 'db.req', 'kpi']];
        foreach ($tiles as $i => [$label, $key, $style]) {
            $col = [2, 3, 4, 5][$i];
            $sh->set($r, $col, $label, $x->s('kpi_lbl'));
            $value = $has($key) ? $x->name($key) : '0';
            $sh->formula($r + 1, $col, $style === 'kpi_pct' ? "$value*100" : $value, $x->s($style));
        }
        $sh->height($r, 24)->height($r + 1, 30);
        $r += 3;

        // checks
        self::band($x, $sh, $r++, $x->t('cv.checks'));
        $ok = $x->t('cv.ok');
        $bad = $x->t('cv.check');
        $okRows = [];
        if (isset($x->rows['bs.chk'])) {
            $sh->set($r, 2, $x->t('bs.check'), $x->s('label_i'))->formula($r, 3, 'IF(SUMPRODUCT(ABS('.$x->rng('bs.chk').'))<1,"'.$ok.'","'.$bad.'")', $x->s('ok'));
            $okRows[] = $r;
            $r++;
        }
        foreach ($okRows as $or) {
            $sh->conditional('C'.$or, 'C'.$or.'="'.$bad.'"', 0)->conditional('C'.$or, 'C'.$or.'="'.$ok.'"', 1);
        }
        $r++;

        // legend
        self::band($x, $sh, $r++, $x->t('cv.legend'));
        $sh->set($r, 2, $x->t('cv.legIn'), $x->s('label_i'))->set($r, 3, '123', $x->s('legend_in'));
        $r++;
        $sh->set($r, 2, $x->t('cv.legLock'), $x->s('label_i'))->set($r, 3, '123', $x->s('lock_int'));
        $r++;
        $sh->set($r, 2, $x->t('cv.legCalc'), $x->s('label_i'))->set($r, 3, '123', $x->s('num'));
        $r += 2;

        // how to use
        self::band($x, $sh, $r++, $x->t('cv.how'));
        foreach (['cv.h1', 'cv.h2', 'cv.h3', 'cv.h4', 'cv.h5'] as $k) {
            $sh->set($r, 2, '• '.$x->t($k), $x->s('label'));
            $r++;
        }
        $r++;

        // contents
        self::band($x, $sh, $r++, $x->t('cv.contents'));
        foreach (self::order($x) as $key) {
            if ($key === 'cover') {
                continue;
            }
            $s = $x->sheets[$key];
            $sh->set($r, 2, $s->name, $x->s('link'))->link($r, 2, Ctx::q($s->name).'!A1');
            $sh->set($r, 3, $x->t('cv.d.'.$key), $x->s('label'));
            $r++;
        }
        $sh->tab('FF0E2350');
    }

    private static function band(Ctx $x, Sheet $sh, int $r, string $text): void
    {
        for ($c = 2; $c <= 7; $c++) {
            $sh->set($r, $c, $c === 2 ? $text : '', $x->s('section'));
        }
        $sh->height($r, 20);
    }
}
