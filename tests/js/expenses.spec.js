import { describe, expect, it, vi } from 'vitest';
import { createApp, h, nextTick } from 'vue';

vi.mock('@inertiajs/vue3', async () => {
    const vue = await import('vue');
    const link = vue.defineComponent({ props: ['href', 'as'], setup: (p, { slots, attrs }) => () => vue.h('a', { href: p.href, ...attrs }, slots.default?.()) });
    return {
        Head: vue.defineComponent({ setup: () => () => null }),
        Link: link,
        router: { post() {}, put() {}, on() {} },
        usePage: () => ({ props: { auth: { user: { role: 'company_admin', name: 'Mona Ali', email: 'm@x.test' } }, flash: {}, locale: 'en' } }),
        useForm: (data) => vue.reactive({ ...JSON.parse(JSON.stringify(data)), errors: globalThis.__errors ?? {}, hasErrors: false, isDirty: false, processing: false, put() {}, post() {}, clearErrors() {}, reset() {}, transform() { return this; } }),
    };
});
vi.mock('@/pwa/register', () => ({ applyUpdate() {}, updateReady: { value: false }, registerServiceWorker() {} }));

globalThis.route = (name, p) => `/${name}${p ? `/${typeof p === 'object' ? p.id ?? '' : p}` : ''}`;

const { default: Expenses } = await import('@/Pages/Studies/Expenses.vue');
const { setLocale } = await import('@/lang/i18n');

const render = async (props) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp({ render: () => h(Expenses, props) });
    app.config.globalProperties.route = globalThis.route;
    app.mount(el);
    await nextTick();
    return el;
};

const years = [{ index: 1, year: 2027, months: 10 }, { index: 2, year: 2028, months: 12 }];
const byYear = (v) => ({ 1: v, 2: v });
const dates = [{ index: 10, label: 'Mar 2027' }, { index: 11, label: 'Apr 2027' }, { index: 12, label: 'May 2027' }];
const cost = { id: 1, name: 'Laundry', currency: 1, amount: 100, escalation: 10, atOperation: 0, annual: 3 };
const base = (o = {}) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu: [{ key: 'study', href: '/s', done: true }, { key: 'expenses.rooms', href: '/e', done: false }],
    access: 'owner', readOnly: false, page: 'rooms', years, dates, daysDifference: 2, estimationDate: 'Mar 2026',
    currencies: [{ id: 1, name: 'EGP' }], revenue: { 1: 1000, 2: 2000 }, days: [0, 15, 30], terms: ['monthly', 'quarterly'], flag: false,
    layout: [
        { kind: 'percent', name: 'variable_expenses_as_percentage_from_rooms_revenues', style: null },
        { kind: 'cost', name: 'variable_expenses_as_cost_per_night_sold', style: 'night' },
        { kind: 'disposable', name: 'disposable_expenses_rate', style: null },
        { kind: 'manpower', name: 'rooms_manpower_expense', style: null },
    ],
    form: {
        flag: false,
        sections: {
            variable_expenses_as_percentage_from_rooms_revenues: [{ id: 1, name: 'Commission', values: byYear(60) }, { id: 2, name: 'Cleaning', values: byYear(30) }],
            variable_expenses_as_cost_per_night_sold: [cost],
            disposable_expenses_rate: [{ id: 3, name: 'Soap', beginning: 0, coverage: 15, cash: 40, dueDays: 30, values: byYear(5) }],
            rooms_manpower_expense: [{ id: 4, name: 'Cleaner', salary: 1000, currency: 1, escalation: 0, atOperation: 0, annual: 0, taxes: 10, insurance: 5, heads: { 10: 2, 11: 2, 12: 3 } }],
        },
    },
    ...o,
});

describe('expense pages', () => {
    it('shows one numbered section for each table the server lists, and the live cost at opening', async () => {
        setLocale('en');
        const el = await render(base());
        expect(el.querySelectorAll('[data-percent-table],[data-cost-table],[data-disposable-table]').length).toBe(3);
        expect(el.textContent).toContain('What it costs to sell rooms');
        expect(el.textContent).toContain('Variable Expenses As % From Rooms Revenues');
        // 100 grown 10% for 2 years
        expect(el.querySelector('[data-cost-table] [data-at-operation]').textContent).toContain('121.00');
        expect(el.querySelector('[data-deferred]').textContent).toContain('60%');
    });

    it('totals the percentages of each year and marks a year above 100', async () => {
        setLocale('en');
        const ok = await render(base());
        expect(ok.querySelector('[data-percent-total]').textContent).toContain('90');
        expect(ok.querySelector('[data-percent-total] .bad')).toBeNull();
        const form = base().form;
        form.sections.variable_expenses_as_percentage_from_rooms_revenues[0].values = byYear(80);
        const over = await render(base({ form }));
        expect(over.querySelector('[data-percent-total] .bad')).not.toBeNull();
    });

    it('shows the revenue row, and says so when no revenue is ready', async () => {
        const el = await render(base());
        expect(el.querySelector('[data-revenue-row]').textContent).toContain('Total Rooms Revenues');
        expect(el.querySelector('[data-no-revenue]')).toBeNull();
        const none = await render(base({ revenue: { 1: 0, 2: 0 } }));
        expect(none.querySelector('[data-no-revenue]')).not.toBeNull();
    });

    it('shows the heads table only when manpower is switched on, and the removal note when it was on', async () => {
        const off = await render(base());
        expect(off.querySelector('[data-manpower-table]')).toBeNull();
        const on = base();
        on.form.flag = true;
        const el = await render(base({ flag: true, form: on.form }));
        expect(el.querySelector('[data-manpower-table]')).not.toBeNull();
        expect(el.querySelectorAll('[data-heads-row]').length).toBe(1);
        expect(el.querySelectorAll('[data-heads-row] input').length).toBe(3);
        const gone = await render(base({ flag: true }));
        expect(gone.querySelector('[data-remove-note]')).not.toBeNull();
    });

    it('draws the two property rows and the payment month on the first', async () => {
        const row = (name, month) => ({ id: null, name, currency: 1, percentage: 1, annual: 0, month });
        const el = await render(base({
            page: 'property',
            layout: [{ kind: 'property', name: 'other_property_fixed_expenses', style: null }],
            form: { flag: null, sections: { other_property_fixed_expenses: [row('Property Taxes', '01'), row('Property Insurance', null)] } },
        }));
        expect(el.querySelectorAll('[data-property-row]').length).toBe(2);
        expect(el.querySelectorAll('[data-property-row] select').length).toBeGreaterThanOrEqual(3);
    });

    it('locks every box for a person who may only view, and hides the save bar', async () => {
        const el = await render(base({ access: 'view' }));
        expect(el.querySelector('[data-savebar]')).toBeNull();
        expect([...el.querySelectorAll('[data-expense-row] input')].every((i) => i.disabled)).toBe(true);
    });

    it('speaks Arabic', async () => {
        setLocale('ar');
        const el = await render(base());
        expect(el.textContent).toContain('تكلفة بيع الغرف');
        setLocale('en');
    });
});
