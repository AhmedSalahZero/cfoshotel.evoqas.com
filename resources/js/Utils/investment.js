// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Investment sums (what the investment pages work out while the user types)
//  Location: resources/js/Utils/investment.js
//
//  The same sums the old application did in acquisitionScripts.ts and loanForm.ts, as plain functions so they can be tested:
//    total cost            cost × (1 + contingency / 100)
//    balance rate          100 − down payments  (the second balance of the payment terms, the balance of an installment)
//    equity / debt         the share of the total that equity funds, and the rest that debt funds
//    end of a construction the start month plus its duration in months
//    loan pricing          base rate + margin rate
//    loan type             which fields it asks for (step-up, step-down, grace period) and its capitalization
// ══════════════════════════════════════════════════════════════════

const n = (value) => {
    const number = Number(value);

    return Number.isFinite(number) ? number : 0;
};

/** Money is shown rounded to a whole number; the sums keep their decimals. */
export const round = (value, decimals = 2) => {
    const f = 10 ** decimals;

    return Math.round((n(value) + Number.EPSILON) * f) / f;
};

export const totalCost = (cost, contingency) => n(cost) * (1 + n(contingency) / 100);

/** What is left of 100 % after the given shares. Never shown below zero. */
export const balanceRate = (...shares) => Math.max(0, round(100 - shares.reduce((sum, share) => sum + n(share), 0), 4));

export const debtRate = (equity) => Math.max(0, round(100 - n(equity), 4));

export const equityAmount = (total, equity) => n(total) * n(equity) / 100;

export const debtAmount = (total, equity) => n(total) * debtRate(equity) / 100;

export const endIndex = (start, duration) => n(start) + Math.max(0, Math.trunc(n(duration)));

export const sum = (list) => list.reduce((total, value) => total + n(value), 0);

/** The customised collection of a purchase: the four rates must add up to 100 %. */
export const ratesAddUp = (rates) => Math.abs(sum(rates) - 100) < 0.005;

export const pricing = (base, margin) => round(n(base) + n(margin), 6);

const STEP_UP = ['step-up', 'grace_step-up_with_capitalization', 'grace_step-up_without_capitalization'];
const STEP_DOWN = ['step-down', 'grace_step-down_with_capitalization', 'grace_step-down_without_capitalization'];
const WITH_CAPITALIZATION = ['grace_step-up_with_capitalization', 'grace_period_with_capitalization', 'grace_step-down_with_capitalization'];

/** What a loan type asks for. `capitalization` is null for the types without a grace period. */
export function loanFlags(type) {
    const grace = !['normal', 'step-up', 'step-down'].includes(type);

    return {
        stepUp: STEP_UP.includes(type),
        stepDown: STEP_DOWN.includes(type),
        grace,
        capitalization: grace ? (WITH_CAPITALIZATION.includes(type) ? 'with_capitalization' : 'without_capitalization') : null,
    };
}

/** A grace period must leave two months of the tenor: it is cut back to the tenor minus two, as the old script did. */
export function graceLimit(grace, period) {
    const g = n(grace);
    const p = n(period);

    return g !== 0 && g >= p - 1 ? Math.max(0, p - 2) : g;
}

export const LOAN_TYPES = [
    'normal', 'step-up', 'step-down', 'grace_period_with_capitalization', 'grace_period_without_capitalization', 'grace_step-up_with_capitalization',
    'grace_step-up_without_capitalization', 'grace_step-down_with_capitalization', 'grace_step-down_without_capitalization',
];
export const LOAN_INSTALLMENT = ['monthly', 'quartly', 'semi annually'];
export const LOAN_STEP = ['quartly', 'semi annually', 'annually'];
