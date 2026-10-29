import { describe, expect, it, vi } from 'vitest';
import { createApp, h, nextTick } from 'vue';

vi.mock('@inertiajs/vue3', async () => {
    const vue = await import('vue');
    const link = vue.defineComponent({ props: ['href', 'as'], setup: (p, { slots, attrs }) => () => vue.h('a', { href: p.href, ...attrs }, slots.default?.()) });
    return {
        Head: vue.defineComponent({ setup: () => () => null }),
        Link: link,
        router: { post() {}, put() {}, on() {}, get(url, params) { globalThis.__visit = { url, params }; } },
        usePage: () => ({ props: { auth: { user: { role: 'company_admin', name: 'Mona Ali', email: 'm@x.test' } }, flash: {}, locale: 'en' } }),
        useForm: (data) => vue.reactive({ ...JSON.parse(JSON.stringify(data)), errors: {}, hasErrors: false, isDirty: false, processing: false, put() {}, post() {}, clearErrors() {}, reset() {}, transform() { return this; } }),
    };
});
vi.mock('@/pwa/register', () => ({ applyUpdate() {}, updateReady: { value: false }, registerServiceWorker() {} }));

globalThis.route = (name, p) => `/${name}${p ? `/${typeof p === 'object' ? p.id ?? '' : p}` : ''}`;

const Report = (await import('@/Pages/Studies/Report.vue')).default;
const Ratios = (await import('@/Pages/Studies/Ratios.vue')).default;
const Dashboard = (await import('@/Pages/Studies/Dashboard.vue')).default;
const Compare = (await import('@/Pages/Studies/Compare.vue')).default;
const { setLocale } = await import('@/lang/i18n');
const { slug, visibleRows, expandableKeys, summaryOf, toneOf, yearOf, compact, niceMax, axisOf, toDelimited } = await import('@/Utils/reports');
const { chartItems, stackItems } = await import('@/Utils/dashboard');

const mount = async (Page, props) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp({ render: () => h(Page, props) });
    app.config.globalProperties.route = globalThis.route;
    app.mount(el);
    await nextTick();
    return el;
};
const row = (key, label, level, parent, values, extra = {}) => ({ key, label, level, parent, values, text: values.map((v) => String(v)), ...extra });
const columns = [{ label: "Dec'26", year: 2026 }, { label: "Dec'27", year: 2027 }];
const rows = [
    row('hotelRevenue/Total Revenues', 'Total Revenues', 0, null, [100, 200]),
    row('hotelRevenue/Total Revenues/Total Rooms Revenue', 'Total Rooms Revenue', 1, 'hotelRevenue/Total Revenues', [60, 120], { expandable: true }),
    row('hotelRevenue/Total Revenues/Total Rooms Revenue/Standard Room', 'Standard Room', 2, 'hotelRevenue/Total Revenues/Total Rooms Revenue', [60, 120]),
    row('net_profit/Net Profit', 'Net Profit', 0, null, [-10, 30]),
];
const menu = [{ key: 'study', href: '/s', done: true }, { key: 'financial.income', href: '/i', done: false }, { key: 'financial.cash', href: '/c', done: false }, { key: 'financial.balance', href: '/b', done: false }, { key: 'financial.ratios', href: '/r', done: false }, { key: 'dashboard', href: '/d', done: false }];
const links = { income: '/i', cash: '/c', balance: '/b', ratios: '/r' };
const base = (o = {}) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu, access: 'owner', report: 'income', links, currency: 'EGP', calculated: true, kind: 'tree',
    tabs: [{ name: 'monthly' }, { name: 'annually' }], intervals: [{ name: 'monthly', columns, rows }, { name: 'annually', columns, rows }], ...o,
});

describe('report helpers', () => {
    it('makes a word to look a line up by', () => {
        expect(slug('Total Cash & Banks')).toBe('total_cash_and_banks');
        expect(slug('Return on Sales "ROS"')).toBe('return_on_sales_ros');
        expect(slug("Customers' Receivables")).toBe('customers_receivables');
    });

    it('shows the totals and the main lines, and the lines below only when their line is open', () => {
        expect(visibleRows(rows, new Set()).map((r) => r.level)).toEqual([0, 1, 0]);
        expect(visibleRows(rows, new Set(['hotelRevenue/Total Revenues/Total Rooms Revenue'])).map((r) => r.level)).toEqual([0, 1, 2, 0]);
        // a line below a line that is shut stays hidden, even when its own parent is marked open
        expect(visibleRows([rows[2]], new Set(['hotelRevenue/Total Revenues/Total Rooms Revenue'])).length).toBe(0);
        expect(expandableKeys(rows)).toEqual(['hotelRevenue/Total Revenues/Total Rooms Revenue']);
    });

    it('sums a flow, takes the last figure of a balance, and colours the totals by what they are', () => {
        expect(summaryOf('income', rows[0])).toEqual({ kind: 'sum', value: 300 });
        expect(summaryOf('balance', rows[0])).toEqual({ kind: 'last', value: 200 });
        expect(summaryOf('cash', { label: 'Accumulated Net Cash', values: [1, 5] })).toEqual({ kind: 'last', value: 5 });
        expect(toneOf('income', rows[3])).toBe('result');
        expect(toneOf('income', rows[0])).toBe('block');
        expect(toneOf('cash', { level: 0, key: 'in/Total Cash In' })).toBe('in');
        expect(toneOf('cash', { level: 0, key: 'out/Total Cash Out Report' })).toBe('out');
        expect(toneOf('balance', { level: 0, key: 'Check Error/Check Error' })).toBe('check');
        expect(toneOf('income', rows[1])).toBe('');
    });

    it('writes the table as text, indented by level', () => {
        const text = toDelimited({ columns, rows: rows.slice(0, 2), head: 'Item Name' }, ',');
        expect(text.split('\n')).toEqual(["Item Name,Dec'26,Dec'27", 'Total Revenues,100,200', '  Total Rooms Revenue,60,120']);
    });

    it('shortens numbers and picks round tops for the axis', () => {
        expect(compact(1250000)).toBe('1.25M');
        expect(compact(-3400)).toBe('-3.4K');
        expect(compact(0.5)).toBe('0.5');
        expect(yearOf('01-Dec-2028')).toBe('2028');
        expect(yearOf('2028-12-01')).toBe('2028');
        expect(niceMax(0)).toBe(1);
        expect(niceMax(73)).toBe(100);
        expect(niceMax(480)).toBe(500);
        expect(axisOf([5, -30])).toEqual({ min: -50, max: 5 });
    });

    it('turns the dashboard data into chart items', () => {
        expect(chartItems([{ Value: 10, date: '01-Dec-2028', 'Growth %': 0 }, { Value: 20, date: '01-Dec-2029', 'Growth %': '100.00' }])).toEqual([{ label: '2028', value: 10, note: '' }, { label: '2029', value: 20, note: '+100.0 %' }]);
        expect(chartItems([{ Value: 5, date: '01-Dec-2028', '% From Revenue': '57.56' }])[0].note).toBe('57.6 %');
        expect(stackItems([{ year: '2028', value2: 2, value1: 1, value3: 3 }])).toEqual([{ label: '2028', values: [1, 2, 3] }]);
    });
});

describe('financial statement page', () => {
    it('shows the key figures, the four periods and the lines — the lines below open on a click', async () => {
        setLocale('en');
        const el = await mount(Report, base());
        expect(el.querySelectorAll('[data-key-figures] li').length).toBeGreaterThan(0);
        expect(el.querySelectorAll('[data-interval]').length).toBe(2);
        expect(el.querySelector('[data-line="hotelRevenue/Total Revenues/Total Rooms Revenue/Standard Room"]')).toBeNull();
        el.querySelector('[data-line="hotelRevenue/Total Revenues/Total Rooms Revenue"] .rp-arrow').click();
        await nextTick();
        expect(el.querySelector('[data-line="hotelRevenue/Total Revenues/Total Rooms Revenue/Standard Room"]')).not.toBeNull();
        el.querySelector('[data-collapse-all]').click();
        await nextTick();
        expect(el.querySelector('[data-line="hotelRevenue/Total Revenues/Total Rooms Revenue/Standard Room"]')).toBeNull();
        el.querySelector('[data-expand-all]').click();
        await nextTick();
        expect(el.querySelector('[data-line="hotelRevenue/Total Revenues/Total Rooms Revenue/Standard Room"]')).not.toBeNull();
    });

    it('colours the results, shows a loss in red and sums the line at its end', async () => {
        const el = await mount(Report, base());
        const profit = el.querySelector('[data-line="net_profit/Net Profit"]');
        expect(profit.classList.contains('rp-result')).toBe(true);
        expect(profit.querySelector('td.st-neg')).not.toBeNull();
        expect(profit.querySelector('.st-sum').textContent).toContain('20');
    });

    it('says what is missing when the study cannot be calculated, and judges the balance sheet', async () => {
        const el = await mount(Report, base({ calculated: false, intervals: [], tabs: [] }));
        expect(el.querySelector('[data-not-calculated]')).not.toBeNull();
        const balanced = await mount(Report, base({ report: 'balance', intervals: [{ name: 'monthly', columns, rows: [row('Check Error/Check Error', 'Check Error', 0, null, [0, 0])] }], tabs: [{ name: 'monthly' }] }));
        expect(balanced.querySelector('[data-check].ok')).not.toBeNull();
        const unbalanced = await mount(Report, base({ report: 'balance', intervals: [{ name: 'monthly', columns, rows: [row('Check Error/Check Error', 'Check Error', 0, null, [0, 500])] }], tabs: [{ name: 'monthly' }] }));
        expect(unbalanced.querySelector('[data-check].bad')).not.toBeNull();
    });

    it('tells the person when the property page was never saved, with a link to it, and says nothing otherwise', async () => {
        const warned = await mount(Report, base({ report: 'balance', notices: { propertyMissing: true, propertyUrl: '/studies/5/property-acquisition-costs' } }));
        expect(warned.querySelector('[data-property-missing]')).not.toBeNull();
        expect(warned.querySelector('[data-property-missing] a').getAttribute('href')).toBe('/studies/5/property-acquisition-costs');

        const quiet = await mount(Report, base({ report: 'balance', notices: { propertyMissing: false, propertyUrl: '/x' } }));
        expect(quiet.querySelector('[data-property-missing]')).toBeNull();

        const notCalculated = await mount(Report, base({ report: 'balance', calculated: false, notices: { propertyMissing: true, propertyUrl: '/x' } }));
        expect(notCalculated.querySelector('[data-property-missing]')).toBeNull();
    });

    it('translates the names the system gives and leaves the names the person typed', async () => {
        setLocale('ar');
        const el = await mount(Report, base());
        expect(el.querySelector('[data-line="hotelRevenue/Total Revenues"] .st-label').textContent).toBe('إجمالي الإيرادات');
        el.querySelector('[data-expand-all]').click();
        await nextTick();
        expect(el.querySelector('[data-line$="Standard Room"] .st-label').textContent).toBe('Standard Room');
        setLocale('en');
    });
});

describe('ratios page', () => {
    it('shows a group and its ratios, with the mark after the figure, and closes a group', async () => {
        const groups = [{ key: 'Profitability Ratios', label: 'Profitability Ratios', rows: [{ key: 'ROA', label: 'Return on Assets "ROA"', description: 'ROA = Net Profit ÷ Total Assets', mark: '%', values: [1, 2], text: ['1.00', '2.00'] }] }];
        const el = await mount(Ratios, { study: { id: 5, name: 'Nile Hotel' }, menu, links, calculated: true, columns, groups });
        expect(el.querySelector('[data-group="Profitability Ratios"]')).not.toBeNull();
        expect(el.querySelector('.rp-mark').textContent).toBe('%');
        expect(el.querySelectorAll('[data-line="ROA"]').length).toBe(1);
        el.querySelector('.rp-grouphead .rp-arrow').click();
        await nextTick();
        expect(el.querySelectorAll('[data-line="ROA"]').length).toBe(0);
    });
});

describe('dashboard page', () => {
    const point = (y, v) => ({ Value: v, date: `01-Dec-${y}`, 'Growth %': 0 });
    const dashboard = {
        cards: [{ title: 'Total Loans', value: '0' }, { title: 'Payback Period', value: '6.8 Years ( Oct 2032 )' }],
        revenue: [point(2028, 10), point(2029, 20)], revenueAccumulated: [point(2028, 10), point(2029, 30)],
        breakdown: [{ year: '2028', value1: 1, value2: 2, value3: 0, value4: 0, value5: 0 }], adr: [point(2028, 100)], revpar: [point(2028, 60)], occupancy: [{ Value: 0.6, date: '01-Jan-2028' }],
        grossProfit: [point(2028, 5)], grossProfitAccumulated: [point(2028, 5)], ebitda: [point(2028, 4)], ebit: [point(2028, 3)], ebt: [point(2028, 2)], netProfit: [point(2028, -1)],
    };
    const props = (o = {}) => ({
        study: { id: 5, name: 'Nile Hotel' }, menu, calculated: true, currency: 'EGP', compareUrl: '/studies/compare?first=5', dashboard,
        streams: [{ value: 'Total Revenues', key: 'total' }, { value: 'Total Revenues.subItems.Total Rooms Revenue', key: 'rooms' }],
        profits: [{ value: 'Departments Gross Profit', key: 'total' }], selected: { revenue_stream_type: 'Total Revenues', gross_profit_type: 'Departments Gross Profit' }, ...o,
    });

    it('draws the cards and the charts, and asks for another stream', async () => {
        const el = await mount(Dashboard, props());
        expect(el.querySelectorAll('[data-feasibility] .db-card').length).toBe(2);
        expect(el.querySelector('[data-card="payback_period"] strong').textContent).toBe('6.8 Years ( Oct 2032 )');
        expect(el.querySelectorAll('svg.sc').length).toBe(12);
        expect(el.querySelector('[data-breakdown] [data-legend]').children.length).toBe(5);
        const select = el.querySelector('[data-stream]');
        select.value = 'Total Revenues.subItems.Total Rooms Revenue';
        select.dispatchEvent(new Event('change'));
        await nextTick();
        expect(globalThis.__visit.params.revenue_stream_type).toBe('Total Revenues.subItems.Total Rooms Revenue');
        expect(globalThis.__visit.params.gross_profit_type).toBe('Departments Gross Profit');
        expect(el.querySelector('[data-compare]').getAttribute('href')).toBe('/studies/compare?first=5');
    });

    it('says what is missing when the study cannot be calculated', async () => {
        const el = await mount(Dashboard, props({ calculated: false, dashboard: null }));
        expect(el.querySelector('[data-not-calculated]')).not.toBeNull();
        expect(el.querySelector('svg.sc')).toBeNull();
    });
});

describe('compare page', () => {
    const studies = [{ id: 1, name: 'A', property: 'Nile' }, { id: 2, name: 'B', property: '' }];
    const card = (key) => ({
        key, columns: [{ label: 'Yr-2028', year: 2028 }, { label: 'Yr-2029', year: 2029 }],
        rows: [{ key: 'first', label: 'A', values: [10, 20], text: ['10', '20'] }, { key: 'second', label: 'B', values: [5, 40], text: ['5', '40'] }, { key: 'variance', label: '', values: [5, -20], text: ['5', '-20'] }, { key: 'percent', label: '', values: [100, -50], text: ['100.00 %', '-50.00 %'] }],
    });
    const result = { names: ['A', 'B'], currency: 'EGP', investment: [{ key: 'Total Loans', title: 'Total Loans', text: ['10', '5', '5', '100.00 %'], values: [10, 5, 5, 100] }], cards: ['revenues', 'grossProfit', 'ebitda', 'ebit', 'ebt', 'netProfit'].map(card) };

    it('asks for two different studies before it compares', async () => {
        globalThis.__visit = null;
        const el = await mount(Compare, { action: '/studies/compare', studiesUrl: '/studies', studies, first: null, second: null, error: null, result: null });
        expect(el.querySelector('[data-pick]')).not.toBeNull();
        expect(el.querySelector('[data-run]').disabled).toBe(true);
        const a = el.querySelector('[data-first]');
        const b = el.querySelector('[data-second]');
        a.value = '1'; a.dispatchEvent(new Event('change'));
        b.value = '2'; b.dispatchEvent(new Event('change'));
        await nextTick();
        expect(el.querySelector('[data-run]').disabled).toBe(false);
        el.querySelector('[data-picker]').dispatchEvent(new Event('submit'));
        await nextTick();
        expect(globalThis.__visit.params).toEqual({ first: 1, second: 2 });
    });

    it('shows the investment results and a table for each card, with the sum at the end of each line', async () => {
        const el = await mount(Compare, { action: '/studies/compare', studiesUrl: '/studies', studies, first: 1, second: 2, error: null, result });
        expect(el.querySelectorAll('[data-investment] tbody tr').length).toBe(1);
        expect(el.querySelectorAll('[data-card]').length).toBe(6);
        const revenues = el.querySelector('[data-card="revenues"]');
        expect(revenues.querySelector('[data-line="first"] .st-sum').textContent).toBe('30');
        expect(revenues.querySelector('[data-line="variance"] .st-sum').textContent).toBe('-15');
        expect(revenues.querySelector('[data-line="percent"] .st-sum').textContent).toBe('-33.33 %');
    });

    it('says why it cannot compare', async () => {
        const same = await mount(Compare, { action: '/studies/compare', studies, first: 1, second: 1, error: 'same', result: null });
        expect(same.querySelector('[data-error]').textContent).toContain('same study');
        const bad = await mount(Compare, { action: '/studies/compare', studies, first: 1, second: 2, error: 'not-calculated', result: null });
        expect(bad.querySelector('[data-error]')).not.toBeNull();
    });
});
