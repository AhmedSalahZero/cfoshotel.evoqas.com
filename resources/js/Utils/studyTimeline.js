// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — The picture of a study's timing
//  Location: resources/js/Utils/studyTimeline.js
//
//  A study has a length in months (years x 12). Inside it, development (building / refurbishing) runs for a
//  number of months starting some months after the study starts, and operation starts some months after the
//  study starts and runs to the end. These helpers turn those numbers into the bars the screens draw, and the
//  month names the screens print.
//
//    timeline({ years: 5, developmentStart: 4, developmentDuration: 18, operationStart: 24 })
//      → { total: 60, dev: { a: 6.67, w: 30 }, ops: { a: 40, w: 60 } }      (a = where it starts, w = how wide, in %)
// ══════════════════════════════════════════════════════════════════
import { addMonths, parseMonth } from '@/Utils/studyDates';

const whole = (value) => {
    const n = Number(value);

    return Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
};

export function timeline({ years, developmentStart = 0, developmentDuration = 0, operationStart = 0 }) {
    const total = Math.max(1, whole(years) * 12);
    const percent = (months) => Math.round((Math.min(total, Math.max(0, months)) / total) * 10000) / 100;
    const devFrom = whole(developmentStart);
    const devLength = Math.min(whole(developmentDuration), Math.max(0, total - devFrom));
    const opFrom = whole(operationStart);

    return {
        total,
        dev: devLength > 0 ? { a: percent(devFrom), w: percent(devLength) } : null,
        ops: opFrom < total ? { a: percent(opFrom), w: percent(total - opFrom) } : null,
    };
}

/** Development is still going on after operation has started (worth a gentle warning, not an error). */
export function operationBeforeDevelopmentEnds({ developmentStart = 0, developmentDuration = 0, operationStart = 0 }) {
    return whole(developmentDuration) > 0 && whole(operationStart) < whole(developmentStart) + whole(developmentDuration);
}

/** "2026-03" → "Mar 2026" (names = the twelve short month names of the person's language); "" when it is not a month. */
export function monthText(value, names) {
    const parts = parseMonth(value);

    return parts ? `${names[parts.month - 1]} ${parts.year}` : '';
}

/** The month n months after a "YYYY-MM", written as text; "" while the start month is missing. */
export function monthAfter(start, months, names) {
    const target = addMonths(start, whole(months));

    return target ? monthText(target, names) : '';
}
