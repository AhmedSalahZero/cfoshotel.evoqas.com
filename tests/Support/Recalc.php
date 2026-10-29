<?php

namespace Tests\Support;

use App\Excel\Ctx;

// Has LibreOffice recalculate a generated workbook and reads the numbers back, so a test can compare them with the application's engine.
// The workbook is saved as flat ODS (.fods): plain XML that keeps every value at full precision.
final class Recalc
{
    public static function available(): bool
    {
        return trim((string) shell_exec('command -v soffice')) !== '';
    }

    /** @return array<string,array<int,array<int,mixed>>> sheet name => [row (1-based)][col (1-based)] => value */
    public static function values(string $xlsxBytes): array
    {
        $dir = sys_get_temp_dir().'/model-recalc-'.bin2hex(random_bytes(6));
        mkdir($dir);
        file_put_contents("$dir/model.xlsx", $xlsxBytes);
        $profile = "$dir/profile";
        exec('soffice -env:UserInstallation=file://'.escapeshellarg($profile).' --headless --calc --convert-to fods --outdir '.escapeshellarg($dir).' '.escapeshellarg("$dir/model.xlsx").' 2>&1', $out, $code);
        $file = "$dir/model.fods";
        if (! is_file($file)) {
            throw new \RuntimeException('LibreOffice did not write the recalculated file: '.implode("\n", $out));
        }
        $sheets = self::parse((string) file_get_contents($file));
        exec('rm -rf '.escapeshellarg($dir));

        return $sheets;
    }

    /** @return array<string,array<int,array<int,mixed>>> */
    private static function parse(string $xml): array
    {
        $doc = new \XMLReader;
        $doc->XML($xml);
        $sheets = [];
        $name = null;
        $row = 0;
        while ($doc->read()) {
            if ($doc->nodeType !== \XMLReader::ELEMENT) {
                continue;
            }
            if ($doc->name === 'table:table') {
                $name = $doc->getAttribute('table:name');
                $sheets[$name] = [];
                $row = 0;
            } elseif ($doc->name === 'table:table-row') {
                $repeat = (int) ($doc->getAttribute('table:number-rows-repeated') ?: 1);
                $node = simplexml_load_string($doc->readOuterXml());
                $cells = [];
                $col = 0;
                foreach ($node->children('table', true) as $cell) {
                    $times = (int) ($cell->attributes('table', true)['number-columns-repeated'] ?? 1);
                    $office = $cell->attributes('office', true);
                    $type = (string) ($office['value-type'] ?? '');
                    $value = null;
                    if ($type === 'float' || $type === 'percentage' || $type === 'currency') {
                        $value = (float) $office['value'];
                    } elseif ($type === 'date') {
                        $value = (string) $office['date-value'];
                    } elseif ($type === 'boolean') {
                        $value = ((string) $office['boolean-value']) === 'true';
                    } elseif ($type === 'string') {
                        $value = isset($office['string-value']) ? (string) $office['string-value'] : trim((string) $cell->children('text', true)->p);
                    }
                    if ($value !== null && $times < 400) {
                        for ($t = 0; $t < $times; $t++) {
                            $cells[$col + $t + 1] = $value;
                        }
                    }
                    $col += $times;
                }
                if ($cells !== [] && $repeat < 50) {
                    for ($r = 0; $r < $repeat; $r++) {
                        $sheets[$name][$row + $r + 1] = $cells;
                    }
                }
                $row += $repeat;
            }
        }

        return $sheets;
    }

    /** The months of a row of the model (key "sheet.row") as month number => value. @return array<int,float> */
    public static function row(array $sheets, Ctx $x, string $key): array
    {
        $r = $x->rows[$key] ?? throw new \RuntimeException("Unknown row '$key'");
        $cells = $sheets[$x->sheets[$r['sheet']]->name][$r['row']] ?? [];
        $out = [];
        for ($i = 0; $i < $x->m->D; $i++) {
            $out[$i] = (float) ($cells[Ctx::FIRST + $i] ?? 0);
        }

        return $out;
    }
}
