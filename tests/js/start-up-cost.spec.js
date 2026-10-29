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

const { default: Page } = await import('@/Pages/Studies/StartUpCost.vue');
const { setLocale } = await import('@/lang/i18n');

const render = async (props) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp({ render: () => h(Page, props) });
    app.config.globalProperties.route = globalThis.route;
    app.mount(el);
    await nextTick();
    return el;
};

const months = [{ index: 1, label: 'Mar 2026' }, { index: 2, label: 'Apr 2026' }, { index: 3, label: 'May 2026' }];
const dates = months.slice(0, 2);
const line = (o = {}) => ({ id: null, name: '', currency: 1, cost: 0, date: 1, cash: 100, dueDays: 0, ...o });
const pre = (o = {}) => ({ id: null, name: '', currency: 1, amounts: { 1: 0, 2: 0 }, ...o });
const base = (o = {}) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu: [{ key: 'study', href: '/s', done: true }, { key: 'expenses.rooms', href: '/e', done: false }, { key: 'expenses.startup', href: '/u', done: false }],
    access: 'owner', readOnly: false, currencies: [{ id: 1, name: 'EGP' }], months, dates, days: [0, 15, 30],
    form: { sections: {
        start_up_cost: [line({ id: 1, name: 'Licences', cost: 12000, cash: 40, dueDays: 30 }), line(), line()],
        pre_operating_expense: [pre({ id: 2, name: 'Recruitment', amounts: { 1: 500, 2: 700 } }), pre(), pre()],
    } },
    ...o,
});

describe('start-up cost page', () => {
    it('shows three lines in each table, with the deferred share worked out live', async () => {
        setLocale('en');
        const el = await render(base());
        expect(el.textContent).toContain('Start-up Cost');
        expect(el.textContent).toContain('Pre-operating Expense');
        expect(el.querySelectorAll('[data-startup-row]').length).toBe(3);
        expect(el.querySelectorAll('[data-preop-row]').length).toBe(3);
        expect(el.querySelector('[data-deferred]').textContent).toContain('60%');
        expect(el.querySelectorAll('[data-startup-row]')[1].querySelector('[data-deferred]').textContent).toContain('0%');
    });

    it('has one amount box for each month before the opening', async () => {
        const el = await render(base());
        const row = el.querySelector('[data-preop-row]');
        expect(row.querySelectorAll('td input[type="number"], td input:not([type="text"])').length).toBeGreaterThanOrEqual(2);
        expect(el.querySelectorAll('[data-preop-table] thead th').length).toBe(2 + dates.length);
    });

    it('says so when the study opens in its first month', async () => {
        const el = await render(base({ dates: [] }));
        expect(el.querySelector('[data-no-months]')).not.toBeNull();
        expect(el.querySelector('[data-preop-table]')).toBeNull();
    });

    it('locks every box for a person who may only view', async () => {
        const el = await render(base({ access: 'view' }));
        expect(el.querySelector('[data-savebar]')).toBeNull();
        expect([...el.querySelectorAll('[data-startup-row] input, [data-preop-row] input')].every((i) => i.disabled)).toBe(true);
    });

    it('speaks Arabic', async () => {
        setLocale('ar');
        const el = await render(base());
        expect(el.textContent).toContain('تكلفة البدء');
        setLocale('en');
    });
});
