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

const { default: Casinos } = await import('@/Pages/Studies/Casinos.vue');
const { default: Meetings } = await import('@/Pages/Studies/Meetings.vue');
const { setLocale } = await import('@/lang/i18n');

const render = async (component, props) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp({ render: () => h(component, props) });
    app.config.globalProperties.route = globalThis.route;
    app.mount(el);
    await nextTick();
    return el;
};

const years = [{ index: 1, year: 2027, months: 10 }, { index: 2, year: 2028, months: 12 }];
const byYear = (v) => Object.fromEntries(years.map((y) => [y.index, v]));
const terms = (o = {}) => ({ type: 'system_default', interval: 'monthly', rates: [0, 0, 0, 0], due: [0, 0, 0, 0], ...o });
const MONTHS = Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0'));
const months = (n) => Object.fromEntries(MONTHS.map((m, i) => [m, i < 8 ? 8 : 9]).map(([m, v]) => [m, n ?? v]));
const quarters = () => ({ 'quarter-one': 25, 'quarter-two': 25, 'quarter-three': 25, 'quarter-four': 25 });
const plan = (o = {}) => ({ type: 'general', interval: 'flat', months: months(), quarters: quarters(), perMonths: { 1: months(), 2: months() }, perQuarters: { 1: quarters(), 2: quarters() }, ...o });
const base = (o = {}) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu: [{ key: 'study', href: '/s', done: true }, { key: 'casinos', href: '/c', done: false }],
    access: 'owner', readOnly: false, years, total: false, daysDifference: 1, estimationDate: 'Mar 2026', currencies: [{ id: 1, name: 'EGP' }], ...o,
});

const CG = 'guest_capture_casino_charges_per_guest_method';
const CT = 'guest_count_target_per_day_charges_per_guest_method';
const CR = 'percentage_from_rooms_revenue';
const casino = (o = {}) => ({ key: 1, name: 'Slots', count: 2, cover: 30, capacity: 60, method: CG, currency: 1, value: 100, escalation: 10, annual: 3, capture: byYear(15), target: byYear(0), rooms: byYear(0), terms: terms(), ...o });
const casinoPage = (facilities = [casino()], o = {}) => base({ methods: [CG, CT, CR], form: { facilities, collection: { mode: 'general', general: terms() } }, ...o });

describe('gaming page', () => {
    it('shows the facility, its capacity and the live charges at opening', async () => {
        setLocale('en');
        const el = await render(Casinos, casinoPage());
        expect(el.textContent).toContain('Slots');
        expect(el.querySelector('[data-value-op]').textContent).toContain('110.00');
    });

    it('shows the figures of the chosen method only and no charges for a percentage of rooms revenue', async () => {
        const rows = async (method) => (await render(Casinos, casinoPage([casino({ method })]))).querySelectorAll('[data-yearly] tbody tr').length;
        expect(await rows(CG)).toBe(1);
        expect(await rows(CT)).toBe(1);
        expect(await rows(CR)).toBe(1);
        const rooms = await render(Casinos, casinoPage([casino({ method: CR })]));
        expect(rooms.querySelector('[data-no-value]')).not.toBeNull();
        expect(rooms.querySelector('[data-value-op]')).toBeNull();
    });

    it('locks every box for a person who may only view', async () => {
        const el = await render(Casinos, casinoPage([casino()], { access: 'view' }));
        expect(el.querySelector('[data-savebar] button[type=submit]')).toBeNull();
        expect([...el.querySelectorAll('[data-yearly] input')].every((i) => i.disabled)).toBe(true);
    });
});

const MG = 'guest_count_charges_per_guest_occupancy_rate_method';
const MR = 'facility_count_daily_rent_occupancy_rate_method';
const MF = 'percentage_from_f_b_revenue';
const meeting = (o = {}) => ({ key: 1, name: 'Ballroom', count: 3, cover: 200, capacity: 600, method: MG, currency: 1, value: 200, escalation: 10, annual: 3, occupancy: byYear(40), fb: byYear(0), terms: terms(), ...o });
const meetingPage = (facilities = [meeting()], plans = {}, o = {}) => base({ methods: [MG, MR, MF], form: { facilities, guestPlan: plan(), rentPlan: plan(), collection: { mode: 'general', general: terms() }, ...plans }, ...o });

describe('meeting spaces page', () => {
    it('shows the live charges at opening and the occupancy of each year', async () => {
        const el = await render(Meetings, meetingPage());
        expect(el.querySelector('[data-value-op]').textContent).toContain('220.00');
        expect(el.querySelectorAll('[data-yearly] tbody input').length).toBe(2);
    });

    it('shows a seasonality plan only for the methods in use', async () => {
        const guest = await render(Meetings, meetingPage());
        expect(guest.querySelector('[data-plan=guest]')).not.toBeNull();
        expect(guest.querySelector('[data-plan=rent]')).toBeNull();
        const both = await render(Meetings, meetingPage([meeting(), meeting({ key: 2, name: 'Boardroom', method: MR })]));
        expect(both.querySelector('[data-plan=rent]')).not.toBeNull();
        const fb = await render(Meetings, meetingPage([meeting({ method: MF })]));
        expect(fb.querySelector('[data-plan]')).toBeNull();
    });

    it('shows the total of a monthly plan and flags one that is not 100', async () => {
        const good = await render(Meetings, meetingPage([meeting()], { guestPlan: plan({ interval: 'monthly' }) }));
        expect(good.querySelector('[data-plan-total]').className).toContain('ok');
        const off = { ...months(), '12': 50 };
        const bad = await render(Meetings, meetingPage([meeting()], { guestPlan: plan({ interval: 'monthly', months: off }) }));
        expect(bad.querySelector('[data-plan-total]').className).toContain('bad');
    });

    it('has four quarters in a quarterly plan, one row for each facility in a plan per facility', async () => {
        const q = await render(Meetings, meetingPage([meeting()], { guestPlan: plan({ interval: 'quarterly' }) }));
        expect(q.querySelectorAll('[data-plan=guest] tbody input').length).toBe(4);
        const per = await render(Meetings, meetingPage([meeting(), meeting({ key: 2, name: 'Hall' })], { guestPlan: plan({ type: 'per_facility', interval: 'monthly' }) }));
        expect(per.querySelectorAll('[data-plan=guest] [data-plan-row]').length).toBe(2);
    });

    it('offers only the general plan for a study that enters meetings as one total, and speaks Arabic', async () => {
        const el = await render(Meetings, meetingPage([meeting()], {}, { total: true }));
        expect(el.querySelectorAll('[data-plan=guest] .fx-choice').length).toBe(1); // only the shape of the year
        setLocale('ar');
        try { expect((await render(Meetings, meetingPage())).textContent).toContain('موسمية'); } finally { setLocale('en'); }
    });
});
