// CFO Hotels — the ADR at the operation date, shown live while the person types.
// Location: resources/js/Utils/adr.js
// The same formula as App\Services\Revenue\RoomsRevenueService::adrAtOperation (the server works out the saved figure):
//   ADR x (1 + escalation %) ^ years from the study start to the operation start.
export function adrAtOperation(adr, escalationPercent, years) {
    const rate = Number(adr);
    if (!Number.isFinite(rate)) return 0;
    const esc = Number(escalationPercent) || 0;

    return rate * Math.pow(1 + esc / 100, Number(years) || 0);
}

/** 1234.5 → "1,234.50" (numbers always read left to right, also on an Arabic page). */
export function money(value, decimals = 2) {
    const n = Number(value);

    return Number.isFinite(n) ? n.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) : '—';
}
