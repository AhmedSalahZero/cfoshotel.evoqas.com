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

const { default: Page } = await import('@/Pages/Studies/Statement.vue');
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

const { roleOf, summary, yearBands, yearParity, heat, sparkPoints, isZeroRow, toDelimited } = await import('@/Utils/statements');

const columns = [{ label: "Mar'26", year: 2026 }, { label: "Apr'26", year: 2026 }, { label: "Jan'27", year: 2027 }];
const rows = [
    { key: 'beginning_balance', values: [0, 100, 50], text: ['0', '100', '50'] },
    { key: 'revenues', values: [100, 50, 200], text: ['100', '50', '200'] },
    { key: 'collection', values: [0, -100, 0], text: ['0', '-100', '0'] },
    { key: 'end_balance', values: [100, 50, 250], text: ['100', '50', '250'] },
    { key: 'custom_line', values: [0, 0, 0], text: ['0', '0', '0'] },
];
const base = (o = {}) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu: [{ key: 'study', href: '/s', done: true }, { key: 'revenue', href: '/r', done: false }, { key: 'statements.collection', href: '/c', done: false }],
    access: 'owner', family: 'collection', type: 'rooms', types: ['rooms', 'foods', 'total'], links: { rooms: '/c/rooms', foods: '/c/foods', total: '/c/total' },
    calculated: true, kind: 'periods', tabs: [{ name: 'monthly' }, { name: 'annually' }],
    intervals: [
        { name: 'monthly', columns, tables: [{ key: 'total', title: 'total', rows }] },
        { name: 'annually', columns: columns.slice(0, 2), tables: [{ key: 'total', title: 'total', rows: rows.map((r) => ({ ...r, values: r.values.slice(0, 2), text: r.text.slice(0, 2) })) }] },
    ],
    ...o,
});

describe('statement helpers', () => {
    it('knows what each line is and what to show at the end of it', () => {
        expect(roleOf('revenues')).toBe('addition');
        expect(roleOf('whatever')).toBe('plain');
        expect(summary('addition', [1, 2, 3])).toEqual({ kind: 'sum', value: 6 });
        expect(summary('opening', [4, 2])).toEqual({ kind: 'first', value: 4 });
        expect(summary('closing', [4, 2])).toEqual({ kind: 'last', value: 2 });
        expect(summary('plain', [4, 2]).kind).toBeNull();
    });

    it('groups the columns by year, tints the cells of every second year and scales the heat to the biggest figure of a line', () => {
        expect(yearBands(columns)).toEqual([{ year: 2026, span: 2, odd: false }, { year: 2027, span: 1, odd: true }]);
        expect(yearParity(columns)).toEqual([false, false, true]);
        expect(heat([0, 50, -100])).toEqual([0, 0.5, 1]);
        expect(sparkPoints([1])).toBe('');
        expect(sparkPoints([1, 2]).split(' ').length).toBe(2);
        expect(isZeroRow([0, '0'])).toBe(true);
    });

    it('writes the table as text for copying and downloading', () => {
        const text = toDelimited({ columns, rows: rows.slice(0, 2), labelOf: (r) => r.key, head: 'Item' }, ',');
        expect(text.split('\n')[0]).toBe("Item,Mar'26,Apr'26,Jan'27");
        expect(text.split('\n')[2]).toBe('revenues,100,50,200');
    });
});

describe('statement page', () => {
    it('shows the types, the intervals and the table with its coloured lines, and switches interval', async () => {
        setLocale('en');
        const el = await render(base());
        expect(el.querySelectorAll('.st-type').length).toBe(3);
        expect(el.querySelector('.st-type.on').textContent).toContain('Rooms');
        expect(el.querySelector('.st-band h3').textContent).toContain('Rooms Receivables Statement Report');
        expect(el.querySelectorAll('[data-statement-table] th[scope="col"].st-odd').length).toBeGreaterThan(0);
        expect(el.querySelector('[data-line="revenues"]').className).toContain('st-addition');
        expect(el.querySelector('[data-line="end_balance"]').className).toContain('st-closing');
        expect(el.querySelector('[data-line="custom_line"]').className).toContain('st-idle');
        expect(el.querySelector('[data-line="revenues"] .st-sum').textContent).toContain('350');
        expect(el.querySelector('[data-line="collection"] td.st-neg')).not.toBeNull();
        expect(el.querySelectorAll('[data-line="revenues"] td[data-c]').length).toBe(3);
        el.querySelector('[data-interval="annually"]').click();
        await nextTick();
        expect(el.querySelectorAll('[data-line="revenues"] td[data-c]').length).toBe(2);
    });

    it('says so when the study cannot be calculated or has nothing to show', async () => {
        setLocale('en');
        expect((await render(base({ calculated: false, intervals: [], tabs: [] }))).querySelector('[data-not-calculated]')).not.toBeNull();
        expect((await render(base({ intervals: [{ name: 'monthly', columns, tables: [] }], tabs: [{ name: 'monthly' }] }))).querySelector('[data-empty]')).not.toBeNull();
    });

    it('shows a loan schedule with the totals above it and the old headings', async () => {
        setLocale('en');
        const el = await render(base({
            family: 'loans', type: 'property', types: ['property', 'land'], links: { property: '/l/p', land: '/l/l' }, kind: 'schedule', tabs: [], intervals: [],
            rows: [{ no: 1, date: '2026-04-30', beginning: '1,000', schedule: '110', interest: '10', principle: '100', end: '900' }],
            totals: { schedule: '110', interest: '10', principle: '100' },
        }));
        expect(el.querySelectorAll('[data-totals] li').length).toBe(3);
        expect(el.querySelector('[data-schedule]').textContent).toContain('Begining Balance');
        expect(el.querySelector('[data-row="1"]').textContent).toContain('900');
    });

    it('is written in Arabic as well', async () => {
        setLocale('ar');
        const el = await render(base());
        expect(el.querySelector('.st-band h3').textContent).toContain('الغرف');
        setLocale('en');
    });
});
