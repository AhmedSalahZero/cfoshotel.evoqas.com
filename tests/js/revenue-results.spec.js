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

const { default: Revenue } = await import('@/Pages/Studies/Revenue.vue');
const { default: Others } = await import('@/Pages/Studies/Others.vue');
const { setLocale } = await import('@/lang/i18n');

const render = async (comp, props) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp({ render: () => h(comp, props) });
    app.config.globalProperties.route = globalThis.route;
    app.mount(el);
    await nextTick();
    return el;
};

const years = [{ index: 1, year: 2027, months: 10 }, { index: 2, year: 2028, months: 12 }];
const menu = [{ key: 'study', href: '/s', done: true }, { key: 'revenue', href: '/r', done: false }];
const results = (o = {}) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu, access: 'owner', years, calculated: true, streams: ['rooms', 'foods'],
    revenue: { rooms: { 1: 20196000, 2: 30000000 }, foods: { 1: 1000000, 2: 2000000 }, total: { 1: 21196000, 2: 32000000 } },
    operating: { nightsAvailable: { 1: 30600, 2: 36500 }, nightsPractical: { 1: 30600, 2: 36500 }, nightsSold: { 1: 18360, 2: 21900 }, guests: { 1: 36720, 2: 43800 }, days: { 1: 306, 2: 365 } },
    ...o,
});

describe('revenue results page', () => {
    it('shows each stream, the hotel total and the figures behind the rooms revenue', async () => {
        setLocale('en');
        const el = await render(Revenue, results());
        const rows = [...el.querySelectorAll('[data-revenue-table] tbody tr')].map((r) => r.dataset.stream);
        expect(rows).toEqual(['rooms', 'foods']);
        expect(el.querySelector('[data-hotel-total]').textContent).toContain('Total Revenues');
        expect(el.querySelector('[data-hotel-total]').textContent).toContain('21,196,000');
        expect(el.querySelector('[data-hotel-total]').textContent).toContain('53,196,000');
        expect(el.querySelector('[data-operating="guests"]').textContent).toContain('36,720');
        expect(el.querySelector('[data-operating="nightsSold"]').textContent).toContain('18,360');
        expect(el.querySelectorAll('[data-bars] .rv-bar').length).toBe(2);
        expect(el.querySelector('[data-not-calculated]')).toBeNull();
    });

    it('says so when the rates are not yet entered and when the revenue is zero', async () => {
        const a = await render(Revenue, results({ calculated: false, revenue: null, operating: null }));
        expect(a.querySelector('[data-not-calculated]')).not.toBeNull();
        expect(a.querySelector('[data-revenue-table]')).toBeNull();
        const zero = { rooms: { 1: 0, 2: 0 }, foods: { 1: 0, 2: 0 }, total: { 1: 0, 2: 0 } };
        const b = await render(Revenue, results({ revenue: zero }));
        expect(b.querySelector('[data-no-revenue]')).not.toBeNull();
    });

    it('reads in Arabic', async () => {
        setLocale('ar');
        const el = await render(Revenue, results());
        expect(el.textContent).not.toContain('revenue.results');
        expect(el.querySelector('[data-hotel-total] th').textContent.trim().length).toBeGreaterThan(0);
        setLocale('en');
    });
});

describe('reading aids on a revenue page', () => {
    const years2 = years;
    const base = {
        study: { id: 5, name: 'Nile Hotel' }, menu, access: 'owner', readOnly: false, years: years2, total: false, daysDifference: 1, estimationDate: 'Mar 2026',
        currencies: [{ id: 1, name: 'EGP' }], methods: ['guest_capture_x_charges_per_guest_method', 'percentage_from_rooms_revenue'],
        form: { facilities: [{ key: 1, name: 'Spa', count: 2, method: 'guest_capture_x_charges_per_guest_method', currency: 1, value: 100, escalation: 10, annual: 3, capture: { 1: 15, 2: 15 }, rooms: { 1: 0, 2: 0 }, terms: { type: 'system_default', interval: 'monthly', rates: [0, 0, 0, 0], due: [0, 0, 0, 0] } }, { key: 2, name: 'Spa', count: 2, method: 'percentage_from_rooms_revenue', currency: 1, value: 100, escalation: 10, annual: 3, capture: { 1: 15, 2: 15 }, rooms: { 1: 0, 2: 0 }, terms: { type: 'system_default', interval: 'monthly', rates: [0, 0, 0, 0], due: [0, 0, 0, 0] } }], collection: { mode: 'general', general: { type: 'system_default', interval: 'monthly', rates: [0, 0, 0, 0], due: [0, 0, 0, 0] } } },
    };

    it('shows the guest count and rooms revenue rows when the server sends them', async () => {
        const el = await render(Others, { ...base, aids: { guests: { 1: 36720, 2: 43800 }, roomsRevenue: { 1: 20196000, 2: 30000000 } } });
        const aids = [...el.querySelectorAll('[data-aid]')].map((r) => r.textContent);
        expect(aids.some((t) => t.includes('Total Guest Count') && t.includes('36,720'))).toBe(true);
        expect(aids.some((t) => t.includes('Total Rooms Revenues') && t.includes('20,196,000'))).toBe(true);
    });

    it('shows no aid rows when there are none', async () => {
        const el = await render(Others, { ...base, aids: null });
        expect(el.querySelectorAll('[data-aid]').length).toBe(0);
    });
});
