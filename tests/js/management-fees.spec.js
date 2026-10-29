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

const { default: Fees } = await import('@/Pages/Studies/ManagementFees.vue');
const { setLocale } = await import('@/lang/i18n');

const render = async (props) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp({ render: () => h(Fees, props) });
    app.config.globalProperties.route = globalThis.route;
    app.mount(el);
    await nextTick();
    return el;
};

const years = [{ index: 1, year: 2027, months: 10 }, { index: 2, year: 2028, months: 12 }];
const by = (v) => ({ 1: v, 2: v });
const base = (o = {}) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu: [{ key: 'study', href: '/s', done: true }, { key: 'expenses.rooms', href: '/e', done: false }, { key: 'expenses.management', href: '/m', done: false }],
    access: 'owner', readOnly: false, years, revenue: { 1: 1000, 2: 2000 },
    layout: [{ name: 'base_management_fees_as_percentage_from_revenues', label: 'Base Management Fees', hidesRevenue: false }, { name: 'incentive_management_fees_as_percentage_from_revenues', label: 'Incentive Management Fees', hidesRevenue: true }],
    form: { sections: {
        base_management_fees_as_percentage_from_revenues: [{ id: 1, name: 'Base Management Fees', values: by(3) }],
        incentive_management_fees_as_percentage_from_revenues: [{ id: 2, name: 'Incentive Management Fees', values: by(8) }],
    } },
    ...o,
});

describe('management fees page', () => {
    it('shows the two fees with their fixed names, and the revenue only above the base fee', async () => {
        setLocale('en');
        const el = await render(base());
        expect(el.querySelectorAll('[data-percent-table]').length).toBe(2);
        expect(el.textContent).toContain('Base Management Fees As % From Revenues');
        expect(el.textContent).toContain('Incentive Management Fees As % From EBITDA');
        expect(el.querySelectorAll('[data-revenue-row]').length).toBe(1);
        expect(el.querySelector('[data-revenue-row]').textContent).toContain('1,000');
        expect(el.querySelectorAll('[data-percent-total]').length).toBe(0);
        const names = [...el.querySelectorAll('[data-expense-row] input[type="text"]')].filter((i) => i.value.includes('Management Fees'));
        expect(names.length).toBe(2);
        expect(names.every((i) => i.readOnly)).toBe(true);
    });

    it('has one box for each operating year in each fee', async () => {
        const el = await render(base());
        const rows = el.querySelectorAll('[data-expense-row]');
        expect(rows.length).toBe(2);
        expect(rows[0].querySelectorAll('td input').length).toBe(2);
        expect(rows[0].querySelector('td input').value).toBe('3');
    });

    it('locks every box for a person who may only view', async () => {
        const el = await render(base({ access: 'view' }));
        expect(el.querySelector('[data-savebar]')).toBeNull();
        expect([...el.querySelectorAll('[data-expense-row] td input')].every((i) => i.disabled)).toBe(true);
    });

    it('speaks Arabic', async () => {
        setLocale('ar');
        const el = await render(base());
        expect(el.textContent).toContain('رسوم الإدارة الأساسية');
        setLocale('en');
    });
});
