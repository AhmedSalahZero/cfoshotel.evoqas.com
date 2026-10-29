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
        useForm: (data) => vue.reactive({ ...JSON.parse(JSON.stringify(data)), errors: globalThis.__errors ?? {}, hasErrors: false, isDirty: false, processing: false, put(url) { globalThis.__put = { url, data: JSON.parse(JSON.stringify(this)) }; }, post() {}, clearErrors() {}, reset() {}, transform() { return this; } }),
    };
});
vi.mock('@/pwa/register', () => ({ applyUpdate() {}, updateReady: { value: false }, registerServiceWorker() {} }));

globalThis.route = (name, p) => `/${name}${p ? `/${typeof p === 'object' ? p.id ?? '' : p}` : ''}`;

const sums = await import('@/Utils/investment');
const { default: Land } = await import('@/Pages/Studies/LandAcquisition.vue');
const { default: Property } = await import('@/Pages/Studies/PropertyAcquisition.vue');
const { default: Ffe } = await import('@/Pages/Studies/FfeCost.vue');
const { setLocale } = await import('@/lang/i18n');

const render = async (Page, props) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp({ render: () => h(Page, props) });
    app.config.globalProperties.route = globalThis.route;
    app.mount(el);
    await nextTick();
    return el;
};

const months = Array.from({ length: 13 }, (_, i) => ({ index: i, label: `M${i}` }));
const endLabels = Object.fromEntries(Array.from({ length: 30 }, (_, i) => [i, `M${i}`]));
const menu = [{ key: 'study', href: '/s', done: true }, { key: 'investment.property', href: '/p', done: false }, { key: 'investment.land', href: '/l', done: false }, { key: 'investment.ffe', href: '/f', done: false }];
const loan = (o = {}) => ({ loan_type: 'normal', base_rate: 0, margin_rate: 0, period: 0, grace_period: 0, installment_interval: 'quartly', step_up_rate: 0, step_up_interval: 'annually', step_down_rate: 0, step_down_interval: 'annually', ...o });
const purchase = (o = {}) => ({ date: 0, cost: 1000, contingency: 10, method: 'cash', first: 0, second: 0, after: 0, count: 1, interval: 'monthly', terms: { rates: [0, 0, 0, 0], due: [0, 0, 0, 0] }, equity: 100, ...o });
const build = (o = {}) => ({ down: 20, one: 30, dueOne: 30, dueTwo: 60, cost: 500, contingency: 10, start: 2, duration: 4, method: 'straight-line', ...o });
const shell = { study: { id: 5, name: 'Nile Hotel' }, menu, access: 'owner', readOnly: false };

const landProps = (o = {}) => ({
    ...shell, months, opening: 12, endLabels,
    lists: { methods: ['cash', 'installment', 'customize'], intervals: ['monthly', 'quarterly', 'semi-annually', 'annually'], collectionDue: [0, 30, 60], dueDays: [0, 15, 30, 60], hardExecution: ['straight-line', 's-curve'], softExecution: ['straight-line', 'steady-growth', 'steady-decline'] },
    form: { land: purchase(), hard: build({ equity: 100 }), soft: build(), loans: { land: loan(), hard_cost_construction: loan() } },
    ...o,
});

describe('investment sums', () => {
    it('works out total cost, balances, equity and debt', () => {
        expect(sums.totalCost(1000, 10)).toBeCloseTo(1100);
        expect(sums.balanceRate(20, 30)).toBe(50);
        expect(sums.balanceRate(70, 50)).toBe(0);
        expect(sums.debtRate(60)).toBe(40);
        expect(sums.equityAmount(2000, 25)).toBe(500);
        expect(sums.debtAmount(2000, 25)).toBe(1500);
    });

    it('ends a construction its duration after its start', () => {
        expect(sums.endIndex(2, 4)).toBe(6);
        expect(sums.endIndex(2, -3)).toBe(2);
    });

    it('prices a loan as base plus margin', () => {
        expect(sums.pricing(3.5, 1.25)).toBe(4.75);
    });

    it('knows what each loan type asks for', () => {
        expect(sums.loanFlags('normal')).toEqual({ stepUp: false, stepDown: false, grace: false, capitalization: null });
        expect(sums.loanFlags('step-up').stepUp).toBe(true);
        expect(sums.loanFlags('step-down').stepDown).toBe(true);
        expect(sums.loanFlags('grace_period_with_capitalization')).toMatchObject({ grace: true, capitalization: 'with_capitalization' });
        expect(sums.loanFlags('grace_step-down_without_capitalization')).toMatchObject({ grace: true, stepDown: true, capitalization: 'without_capitalization' });
    });

    it('cuts a grace period back to leave two months of the tenor', () => {
        expect(sums.graceLimit(12, 12)).toBe(10);
        expect(sums.graceLimit(5, 12)).toBe(5);
        expect(sums.graceLimit(0, 12)).toBe(0);
    });

    it('checks that collection rates add up to 100', () => {
        expect(sums.ratesAddUp([40, 30, 20, 10])).toBe(true);
        expect(sums.ratesAddUp([40, 30, 20, 5])).toBe(false);
    });
});

describe('land and construction page', () => {
    it('shows the land, the hard cost and the soft cost with the old names', async () => {
        setLocale('en');
        const el = await render(Land, landProps());
        for (const text of ['Land Acquisition Cost', 'Construction Cost [Hard Cost]', 'Construction Cost [Soft Cost]', 'Purchase Date', 'Select Land Payment Method', 'Select Execution Method', 'Payment Terms', 'Balance Rate Two %']) {
            expect(el.textContent).toContain(text);
        }
        expect(el.querySelector('[data-total-purchase]').textContent).toBe('1,100');
        expect(el.querySelector('[data-hard-total]').textContent).toBe('550');
    });

    it('works out the construction end, and warns when it is after the opening', async () => {
        const ok = await render(Land, landProps());
        expect(ok.querySelector('[data-hard-end]').textContent).toBe('M6');
        const late = await render(Land, landProps({ form: { ...landProps().form, hard: build({ start: 10, duration: 5 }) } }));
        expect(late.querySelector('[data-hard-end]').classList.contains('bad')).toBe(true);
    });

    it('asks for the loan only when part of the cost is funded by debt', async () => {
        const none = await render(Land, landProps());
        expect(none.querySelectorAll('[data-loan]').length).toBe(0);
        const form = landProps().form;
        form.land.equity = 60;
        form.hard.equity = 50;
        const both = await render(Land, landProps({ form }));
        expect(both.querySelectorAll('[data-loan]').length).toBe(2);
        expect(both.querySelector('[data-debt-rate]').textContent).toBe('40.0%');
    });

    it('shows the installment fields, or the four collection rates, for the payment method chosen', async () => {
        const form = landProps().form;
        form.land = purchase({ method: 'installment', first: 20, second: 30, count: 4, interval: 'quarterly' });
        const inst = await render(Land, landProps({ form }));
        expect(inst.querySelector('[data-balance]').textContent).toBe('50.0%');
        expect(inst.querySelector('[data-collection]')).toBeNull();

        const form2 = landProps().form;
        form2.land = purchase({ method: 'customize', terms: { rates: [40, 30, 20, 5], due: [0, 30, 60, 0] } });
        const custom = await render(Land, landProps({ form: form2 }));
        expect(custom.querySelectorAll('.inv-rate').length).toBe(4);
        expect(custom.querySelector('[data-rates-total]').classList.contains('bad')).toBe(true);
        expect(custom.querySelector('[data-balance]')).toBeNull();
    });

    it('shows step and grace fields only for the loan types that use them', async () => {
        const form = landProps().form;
        form.land.equity = 50;
        form.loans.land = loan({ loan_type: 'grace_step-up_with_capitalization', base_rate: 3, margin_rate: 2, period: 60, grace_period: 6, step_up_rate: 1 });
        const el = await render(Land, landProps({ form }));
        expect(el.querySelector('[data-pricing]').textContent).toBe('5.00%');
        expect(el.querySelector('[data-grace]')).not.toBeNull();
        expect(el.querySelector('[data-capitalization]').textContent).toBe('With Capitalization');
        expect(el.querySelector('[data-step-up]')).not.toBeNull();
        expect(el.querySelector('[data-step-down]')).toBeNull();
    });

    it('shows server errors on the fields and in the summary, and speaks Arabic', async () => {
        globalThis.__errors = { 'land.cost': 'Purchase Cost must be at least 0.' };
        const el = await render(Land, landProps());
        globalThis.__errors = undefined;
        expect(el.textContent).toContain('Purchase Cost must be at least 0.');
        expect(el.querySelector('[role="alert"]')).not.toBeNull();

        setLocale('ar');
        const ar = await render(Land, landProps());
        expect(ar.textContent).toContain('تكلفة شراء الأرض');
        setLocale('en');
    });
});

describe('property acquisition page', () => {
    const propertyProps = (o = {}) => ({
        ...shell, months,
        lists: { methods: ['cash', 'installment', 'customize'], intervals: ['monthly', 'quarterly', 'semi-annually', 'annually'], collectionDue: [0, 30], depreciation: [3, 4, 5], buildingDepreciation: [3, 4, 5, 50], replacement: [1, 2, 3, 4, 5] },
        form: {
            property: purchase({ cost: 1000, contingency: 0 }),
            breakdown: [{ name: 'Land Cost', percentage: 20, depreciation: 0 }, { name: 'Building Cost', percentage: 70, depreciation: 25 }, { name: 'Furniture, Fixture & Equipment Cost', percentage: 10, depreciation: 5 }],
            replacement: { rate: 2, interval: 1, ffeRate: 3, ffeInterval: 2 },
            loans: { property_loan: loan() },
        },
        ...o,
    });

    it('splits the purchase cost over land, building and furniture', async () => {
        const el = await render(Property, propertyProps());
        expect(el.textContent).toContain('Property Acquisition Cost');
        const rows = [...el.querySelectorAll('[data-item-value]')].map((c) => c.textContent);
        expect(rows).toEqual(['200', '700', '100']);
        expect(el.querySelector('[data-share-total]').textContent).toBe('100.0%');
        expect(el.querySelector('[data-shares-note]').classList.contains('ok')).toBe(true);
        expect(el.textContent).toContain('Refurbishment & Renovation');
    });

    it('has no depreciation for the land and marks shares that do not add up to 100', async () => {
        const form = propertyProps().form;
        form.breakdown[2].percentage = 5;
        const el = await render(Property, propertyProps({ form }));
        const rows = el.querySelectorAll('[data-breakdown-row]');
        expect(rows[0].querySelector('select')).toBeNull();
        expect(rows[1].querySelector('select')).not.toBeNull();
        expect(el.querySelector('[data-shares-note]').classList.contains('bad')).toBe(true);
        expect(el.querySelector('[data-share-total]').classList.contains('bad')).toBe(true);
    });

    it('offers 50 years for the building only', async () => {
        const el = await render(Property, propertyProps());
        const options = (row) => [...el.querySelectorAll('[data-breakdown-row]')[row].querySelectorAll('option')].map((o) => o.value);
        expect(options(1)).toContain('50');
        expect(options(2)).not.toContain('50');
    });
});

describe('FF&E page', () => {
    const row = (o = {}) => ({ id: null, name: '', currency: 1, cost: 0, contingency: 0, depreciation: 3, replacementRate: 0, replacementInterval: 1, ...o });
    const ffeProps = (o = {}) => ({
        ...shell, currencies: [{ id: 1, name: 'EGP' }, { id: 2, name: 'USD' }], months, opening: 12, endLabels,
        lists: { dueDays: [0, 15, 30], execution: ['straight-line', 's-curve', 'steady-growth', 'steady-decline'], depreciation: [3, 4, 5], replacement: [1, 2, 3, 4, 5] },
        form: {
            rows: [row({ id: 7, name: 'Beds', cost: 1000, contingency: 20 }), row({ id: 8, name: 'TVs', cost: 500, contingency: 0 }), ...Array.from({ length: 8 }, () => row())],
            dates: { start: 1, duration: 6, method: 's-curve' },
            terms: { down: 10, one: 40, dueOne: 15, dueTwo: 30 },
            equity: 100,
            loans: { ffe_cost: loan() },
        },
        ...o,
    });

    it('lists ten items with the total of each and of all', async () => {
        const el = await render(Ffe, ffeProps());
        expect(el.textContent).toContain('Furniture, Fixtures, and Equipment (FF&E) Cost');
        expect(el.querySelectorAll('[data-ffe-row]').length).toBe(10);
        expect(el.querySelectorAll('[data-row-total]')[0].textContent).toBe('1,200');
        expect(el.querySelector('[data-items-total]').textContent).toBe('1,700');
    });

    it('works out the end date and the second balance', async () => {
        const el = await render(Ffe, ffeProps());
        expect(el.querySelector('[data-ffe-end]').textContent).toBe('M7');
        expect(el.querySelector('[data-balance-two]').textContent).toBe('50.0%');
    });

    it('funds the items by equity and debt and asks for the loan when there is debt', async () => {
        const form = ffeProps().form;
        form.equity = 70;
        const el = await render(Ffe, ffeProps({ form }));
        expect(el.querySelector('[data-equity-amount]').textContent).toBe('1,190');
        expect(el.querySelector('[data-debt-amount]').textContent).toBe('510');
        expect(el.querySelectorAll('[data-loan]').length).toBe(1);
    });

    it('offers the currencies of the study', async () => {
        const el = await render(Ffe, ffeProps());
        const options = [...el.querySelector('[data-ffe-row] select').querySelectorAll('option')].map((o) => o.textContent);
        expect(options).toEqual(['EGP', 'USD']);
    });
});

describe('field names follow the reference project', () => {
    it('keeps the old names for the sections and loan types', async () => {
        const mod = await import('../../resources/js/Utils/investment.js');
        expect(mod.LOAN_INSTALLMENT).toContain('quartly');
        expect(mod.LOAN_INSTALLMENT).toContain('semi annually');
        expect(mod.LOAN_TYPES).toContain('grace_step-up_without_capitalization');
    });
});
