// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Statement helpers (the small maths and shapes behind the statement tables)
//  Location: resources/js/Utils/statements.js
//  roleOf(key)        what a line is in a statement: opening, closing, addition, outflow, subtotal, balance or plain
//  summary(role, v)   the figure shown at the end of a line: the sum of a flow, the first of an opening balance, the last of a balance
//  yearBands(cols)    the columns grouped by year: [{ year, span, odd }]
//  heat(values)       how strong each cell is, 0 to 1, against the biggest figure of its line
//  sparkPoints(...)   the points of the little trend line drawn beside a line's name
//  toDelimited(...)   the table as text for copying or downloading
// ══════════════════════════════════════════════════════════════════
import { money } from '@/Utils/adr';

/** The lines of the statements, by the word the calculation gives them. Anything else is plain. */
export const ROLES = {
    beginning_balance: 'opening',
    begining_balance: 'opening',
    end_balance: 'closing',
    total_due: 'subtotal',
    due_amount: 'subtotal',
    total_available: 'subtotal',
    initial_total_gross: 'subtotal',
    final_total_gross: 'subtotal',
    accumulated_depreciation: 'balance',
    revenues: 'addition',
    purchases: 'addition',
    purchase: 'addition',
    additions: 'addition',
    asset_purchases: 'addition',
    property_taxes: 'addition',
    property_insurance: 'addition',
    'management fees': 'addition',
    'corporate taxes': 'addition',
    replacement_cost: 'addition',
    collection: 'outflow',
    payment: 'outflow',
    payments: 'outflow',
    dispensed_disposable_cost: 'outflow',
    total_monthly_depreciation: 'outflow',
};

export const roleOf = (key) => ROLES[key] ?? 'plain';

const numbers = (values) => (values ?? []).map((v) => Number(v) || 0);

/** → { kind: 'sum' | 'first' | 'last' | null, value } */
export function summary(role, values) {
    const v = numbers(values);
    if (!v.length) return { kind: null, value: 0 };
    if (role === 'addition' || role === 'outflow') return { kind: 'sum', value: v.reduce((a, b) => a + b, 0) };
    if (role === 'opening') return { kind: 'first', value: v[0] };
    if (role === 'closing' || role === 'balance') return { kind: 'last', value: v[v.length - 1] };

    return { kind: null, value: 0 };
}

export function yearBands(columns) {
    const bands = [];
    for (const column of columns ?? []) {
        const last = bands[bands.length - 1];
        if (last && last.year === column.year) {
            last.span += 1;
        } else {
            bands.push({ year: column.year, span: 1, odd: bands.length % 2 === 1 });
        }
    }

    return bands;
}

/** The position of each column in its year band, so the cells can wear the colour of their year. */
export function yearParity(columns) {
    return yearBands(columns).flatMap((band) => Array(band.span).fill(band.odd));
}

export function heat(values) {
    const v = numbers(values);
    const biggest = Math.max(0, ...v.map(Math.abs));

    return v.map((x) => (biggest > 0 ? Math.min(1, Math.abs(x) / biggest) : 0));
}

export function sparkPoints(values, width = 64, height = 18, pad = 2) {
    const v = numbers(values);
    if (v.length < 2) return '';
    const lo = Math.min(...v);
    const hi = Math.max(...v);
    const span = hi - lo || 1;

    return v.map((x, i) => `${(pad + (i / (v.length - 1)) * (width - pad * 2)).toFixed(1)},${(height - pad - ((x - lo) / span) * (height - pad * 2)).toFixed(1)}`).join(' ');
}

export const isZeroRow = (values) => numbers(values).every((x) => x === 0);

/** The table as text: tab separated for copying into a spreadsheet, or comma separated for a file. */
export function toDelimited({ columns, rows, labelOf, head }, separator = '\t') {
    const quote = (cell) => (separator === ',' && /[",\n]/.test(String(cell)) ? `"${String(cell).replace(/"/g, '""')}"` : String(cell));
    const lines = [[head, ...columns.map((c) => c.label)]];
    for (const row of rows) {
        lines.push([labelOf(row), ...row.text]);
    }

    return lines.map((line) => line.map(quote).join(separator)).join('\n');
}

export const figure = (value) => money(value, 0);
