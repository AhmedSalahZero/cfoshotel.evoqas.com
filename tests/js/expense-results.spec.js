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

const { default: Page } = await import('@/Pages/Studies/ExpenseResults.vue');
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

const years = [{ index: 0, year: 2026, months: 10 }, { index: 1, year: 2027, months: 12 }];
const z = { 0: 0, 1: 0 };
const base = (o = {}) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu: [{ key: 'study', href: '/s', done: true }, { key: 'revenue', href: '/r', done: false }, { key: 'expenses.rooms', href: '/e', done: false }, { key: 'expenses.results', href: '/x', done: false }],
    access: 'owner', years, calculated: true,
    groups: [
        { key: 'direct', total: { 0: 0, 1: 3000 }, sections: [
            { key: 'rooms', total: { 0: 0, 1: 3000 }, lines: [{ key: 'percent', values: { 0: 0, 1: 2000 } }, { key: 'night', values: { 0: 0, 1: 1000 } }, { key: 'guest', values: z }] },
            { key: 'foods', total: z, lines: [{ key: 'percent', values: z }] },
        ] },
        { key: 'undistributed', total: { 0: 0, 1: 500 }, sections: [{ key: 'general', total: { 0: 0, 1: 500 }, lines: [{ key: 'fixed', values: { 0: 0, 1: 500 } }] }] },
        { key: 'management', total: { 0: 0, 1: 90 }, sections: [{ key: 'management', total: { 0: 0, 1: 90 }, lines: [{ key: 'base', values: { 0: 0, 1: 90 } }] }] },
        { key: 'startup', total: { 0: 12000, 1: 0 }, sections: [{ key: 'startup', total: { 0: 12000, 1: 0 }, lines: [{ key: 'payments', values: { 0: 12000, 1: 0 } }] }] },
    ],
    totals: { operating: { 0: 0, 1: 3590 } },
    ...o,
});

describe('expense results page', () => {
    it('shows each group with its lines and totals, and leaves out a department with nothing in it', async () => {
        setLocale('en');
        const el = await render(base());
        expect(el.querySelectorAll('[data-group]').length).toBe(4);
        expect(el.querySelector('[data-section="rooms"]')).not.toBeNull();
        expect(el.querySelector('[data-section="foods"]')).toBeNull();
        // a line with no money in it is left out of a department that has some
        expect(el.querySelectorAll('[data-section="rooms"] [data-line]').length).toBe(2);
        expect(el.querySelector('[data-group="direct"] [data-group-total]').textContent).toContain('3,000');
        expect(el.querySelector('[data-group="startup"]').textContent).toContain('12,000');
        expect(el.querySelector('[data-operating-total]').textContent).toContain('3,590');
    });

    it('has a column for every calendar year, the part year marked', async () => {
        const el = await render(base());
        expect(el.querySelectorAll('[data-group="direct"] thead th').length).toBe(2 + years.length);
        expect(el.querySelector('[data-group="direct"] thead').textContent).toContain('2026');
    });

    it('says so when the study cannot be calculated yet, and when nothing is entered', async () => {
        const off = await render(base({ calculated: false, groups: [], totals: {} }));
        expect(off.querySelector('[data-not-calculated]')).not.toBeNull();
        expect(off.querySelector('[data-group]')).toBeNull();
        const empty = await render(base({ groups: base().groups.map((g) => ({ ...g, total: z, sections: g.sections.map((s) => ({ ...s, total: z })) })) }));
        expect(empty.querySelector('[data-no-expenses]')).not.toBeNull();
    });

    it('speaks Arabic', async () => {
        setLocale('ar');
        const el = await render(base());
        expect(el.textContent).toContain('المصروفات المباشرة');
        setLocale('en');
    });
});
