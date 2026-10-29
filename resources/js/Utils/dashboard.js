// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Dashboard helpers (the data of the charts as the charts want it)
//  Location: resources/js/Utils/dashboard.js
//  chartItems(data)     [{ Value, date, 'Growth %' | '% From Revenue' }]  →  [{ label: year, value, note }]
//  stackItems(data)     [{ year, value1 .. value5 }]                       →  [{ label: year, values }]
//  CARD_ICONS           the icon of each feasibility card, in the order the server gives them
// ══════════════════════════════════════════════════════════════════
import { yearOf } from '@/Utils/reports';

const signed = (n) => `${n > 0 ? '+' : ''}${n.toFixed(1)} %`;

export function chartItems(data) {
    return (data ?? []).map((point) => {
        let note = '';
        if ('Growth %' in point) {
            const growth = Number(point['Growth %']);
            note = growth ? signed(growth) : '';
        } else if ('% From Revenue' in point) {
            note = `${Number(point['% From Revenue']).toFixed(1)} %`;
        }

        return { label: yearOf(point.date), value: Number(point.Value) || 0, note };
    });
}

export function stackItems(data) {
    return (data ?? []).map((point) => ({
        label: String(point.year),
        values: Object.keys(point).filter((k) => /^value\d+$/.test(k)).sort((a, b) => Number(a.slice(5)) - Number(b.slice(5))).map((k) => Number(point[k]) || 0),
    }));
}

export const CARD_ICONS = ['building', 'layers', 'coins', 'users', 'bank', 'trend', 'star', 'tag', 'coins', 'trend', 'calendar'];
