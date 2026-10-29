// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Report helpers (the small maths and shapes behind the financial statements, the dashboard and the comparison)
//  Location: resources/js/Utils/reports.js
//  slug(label)               the word a line is looked up by in the translations ("Total Cash & Banks" → total_cash_banks)
//  visibleRows(rows, open)   the lines to show: totals and main lines always, the others when the line above them has been opened
//  expandableKeys(rows)      the lines that open into more lines
//  summaryOf(report, row)    the figure at the end of a line: the sum of a flow, or the last figure of a balance
//  toneOf(report, row)       what a total is, so it can wear its colour (result, in, out, check, block)
//  yearOf(date)              the year of a chart date ("01-Dec-2028" or "2028-12-01")
//  compact(value)            1,250,000 → "1.25M"
//  niceMax(max)              a round top for a chart axis
// ══════════════════════════════════════════════════════════════════

export const slug = (label) => String(label ?? '').toLowerCase().replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');

/** the lines to show: the totals and the main lines always; the lines below them when the line above has been opened */
export function visibleRows(rows, open) {
    const shown = new Set();
    const out = [];
    for (const row of rows) {
        if (row.gap) {
            out.push(row);
            continue;
        }
        if ((row.level ?? 0) <= 1 || (shown.has(row.parent) && open.has(row.parent))) {
            shown.add(row.key);
            out.push(row);
        }
    }

    return out;
}

/** the keys of the lines that have lines below them */
export const expandableKeys = (rows) => rows.filter((row) => (row.level ?? 0) >= 1 && (row.expandable || rows.some((other) => other.parent === row.key))).map((row) => row.key);

const BALANCES_IN_CASH = ['Accumulated Net Cash'];

/** → { kind: 'sum' | 'last', value } */
export function summaryOf(report, row) {
    const values = (row.values ?? []).map((v) => Number(v) || 0);
    if (!values.length) return { kind: 'sum', value: 0 };
    const balance = report === 'balance' || (report === 'cash' && BALANCES_IN_CASH.includes(row.label));

    return balance ? { kind: 'last', value: values[values.length - 1] } : { kind: 'sum', value: values.reduce((a, b) => a + b, 0) };
}

const RESULT_INCOME = ['DepartmentsGrossProfit', 'EBITDA', 'EBIT', 'EBT', 'net_profit'];
const RESULT_BALANCE = ['Total Long Term Assets', 'Total Current Assets', 'Total Assets', 'Total Current Liabilities', 'Working Capital', 'Total Investment', 'Owners Equity', 'Total Cash & Banks'];

export function toneOf(report, row) {
    if (row.level !== 0) return '';
    const section = String(row.key).split('/')[0];
    if (report === 'income') return RESULT_INCOME.includes(section) ? 'result' : 'block';
    if (report === 'cash') return section === 'in' ? 'in' : section === 'out' ? 'out' : 'result';
    if (report === 'balance') return section === 'Check Error' ? 'check' : RESULT_BALANCE.includes(section) ? 'result' : 'block';

    return '';
}

export const yearOf = (date) => {
    const text = String(date ?? '');
    const match = text.match(/(\d{4})/);

    return match ? match[1] : text;
};

export function compact(value) {
    const n = Number(value) || 0;
    const abs = Math.abs(n);
    const fix = (x) => String(Number(x.toFixed(abs >= 1e9 ? 2 : abs >= 1e6 ? 2 : 1)));
    if (abs >= 1e9) return `${fix(n / 1e9)}B`;
    if (abs >= 1e6) return `${fix(n / 1e6)}M`;
    if (abs >= 1e3) return `${fix(n / 1e3)}K`;

    return String(Number(n.toFixed(2)));
}

export function niceMax(max) {
    const m = Math.max(0, Number(max) || 0);
    if (m === 0) return 1;
    const power = 10 ** Math.floor(Math.log10(m));
    const unit = m / power;
    const step = unit <= 1 ? 1 : unit <= 2 ? 2 : unit <= 2.5 ? 2.5 : unit <= 5 ? 5 : 10;

    return step * power;
}

/** the ends of a chart axis for values that may go below zero: { min, max } with round numbers */
export function axisOf(values) {
    const list = (values ?? []).map((v) => Number(v) || 0);
    const max = niceMax(Math.max(0, ...list));
    const lowest = Math.min(0, ...list);
    const min = lowest < 0 ? -niceMax(-lowest) : 0;

    return { min, max };
}

/** the table as text (tab separated for a spreadsheet, comma separated for a file) */
export function toDelimited({ columns, rows, head }, separator = '\t') {
    const quote = (cell) => (separator === ',' && /[",\n]/.test(String(cell)) ? `"${String(cell).replace(/"/g, '""')}"` : String(cell));
    const lines = [[head, ...columns.map((c) => c.label)]];
    for (const row of rows) {
        if (row.gap) continue;
        lines.push([`${'  '.repeat(row.level ?? 0)}${row.label}`, ...row.text]);
    }

    return lines.map((line) => line.map(quote).join(separator)).join('\n');
}
