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

const { default: Rooms } = await import('@/Pages/Studies/Rooms.vue');
const { default: Types } = await import('@/Pages/Company/Types.vue');
const { adrAtOperation, money } = await import('@/Utils/adr');
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

const years = [{ index: 1, year: 2027, months: 10 }, { index: 2, year: 2028, months: 12 }, { index: 3, year: 2029, months: 12 }];
const byYear = (v) => Object.fromEntries(years.map((y) => [y.index, v]));
const months = (v) => Object.fromEntries(Array.from({ length: 12 }, (_, i) => [String(i + 1).padStart(2, '0'), v]));
const terms = () => ({ type: 'system_default', interval: 'monthly', rates: [0, 0, 0, 0], due: [0, 0, 0, 0] });
const page = (o = {}, form = {}) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu: [{ key: 'study', href: '/s', done: true }, { key: 'rooms', href: '/r', done: false }],
    access: 'owner', readOnly: false, years, total: false, daysDifference: 1, estimationDate: 'Mar 2026',
    currencies: [{ id: 1, name: 'EGP' }, { id: 2, name: 'USD' }], additionalCurrency: null, mainCurrency: 'EGP',
    form: {
        currency: 1,
        rooms: [{ key: 1, name: 'Double', count: 10, nights: 3650, guests: 2, adr: 1000, escalation: 10, annual: 3 }],
        exchange: byYear(1),
        occupancy: { mode: 'general', general: byYear(60), perRoom: { 1: byYear(0) } },
        seasonality: { type: 'general', interval: 'flat', general: months(100), perRoom: { 1: months(100) } },
        channels: [],
        collection: { mode: 'general', general: terms() },
        ...form,
    },
    ...o,
});

describe('adr', () => {
    it('grows the rate by the escalation for the years before opening', () => {
        expect(adrAtOperation(1000, 10, 1)).toBeCloseTo(1100, 6);
        expect(adrAtOperation(1000, 10, 2)).toBeCloseTo(1210, 6);
        expect(adrAtOperation(1000, 0, 3)).toBe(1000);
        expect(money(1100)).toBe('1,100.00');
    });
});

describe('rooms page', () => {
    it('shows the room, the live ADR at opening and one cell for each operating year', async () => {
        setLocale('en');
        const el = await render(Rooms, page());
        expect(el.textContent).toContain('Double');
        expect(el.querySelector('[data-adr-op]').textContent).toContain('1,100.00');
        expect(el.querySelectorAll('#rv-occupancy tbody input').length).toBe(3);
        expect(el.textContent).toContain('10 months');
        expect(el.querySelector('#rv-exchange')).toBeNull();
        expect(el.querySelector('#rv-channels')).toBeNull();
    });

    it('shows the exchange rate only with an additional currency, and the channels only when the study has them', async () => {
        const el = await render(Rooms, page({ additionalCurrency: 'USD' }, { channels: [{ name: 'agency', share: byYear(100), discount: byYear(0), terms: terms() }] }));
        expect(el.querySelector('#rv-exchange')).not.toBeNull();
        expect(el.querySelector('#rv-channels')).not.toBeNull();
        expect(el.querySelectorAll('[data-share-total]').length).toBe(3);
        expect(el.querySelector('[data-share-total]').className).toContain('ok');
    });

    it('flags channel shares that do not add up to 100', async () => {
        const el = await render(Rooms, page({}, { channels: [{ name: 'agency', share: byYear(60), discount: byYear(0), terms: terms() }] }));
        expect(el.querySelector('[data-share-total]').className).toContain('bad');
    });

    it('offers Save and Save & next to a person who may edit, and neither to a person who may only view', async () => {
        const editor = await render(Rooms, page());
        expect(editor.querySelector('[data-savebar] button[type=submit][data-save-stay]')).not.toBeNull();
        expect(editor.querySelector('[data-savebar] button[type=button][data-save-next]')?.textContent).toContain('Save & next');
        const viewer = await render(Rooms, page({ access: 'view' }));
        expect(viewer.querySelector('[data-save-next]')).toBeNull();
    });

    it('locks every box for a person who may only view', async () => {
        const el = await render(Rooms, page({ access: 'view' }));
        expect(el.querySelector('[data-savebar] button[type=submit]')).toBeNull();
        expect([...el.querySelectorAll('#rv-occupancy tbody input')].every((i) => i.disabled)).toBe(true);
    });

    it('asks for four rates that total 100 when the policy is customised', async () => {
        const custom = { type: 'customize', interval: 'monthly', rates: [40, 30, 20, 5], due: [0, 30, 60, 90] };
        const el = await render(Rooms, page({}, { collection: { mode: 'general', general: custom } }));
        expect(el.querySelector('.rv-total').className).toContain('bad');
        expect(el.querySelectorAll('.rv-instal-row').length).toBe(4);
    });

    it('renders in Arabic', async () => {
        setLocale('ar');
        const el = await render(Rooms, page());
        try { expect(el.textContent).toContain('الغرف والأسعار'); } finally { setLocale('en'); }
    });
});

describe('types page', () => {
    const lists = { rooms: [{ id: 1, title: 'Double', inUse: true }, { id: 2, title: 'Suite', inUse: false }], foods: [], casinos: [], meetings: [], others: [] };

    it('locks a type that a study uses and lets an unused one be removed', async () => {
        const el = await render(Types, { lists, readOnly: false });
        expect(el.querySelectorAll('.rv-typelist li').length).toBe(2);
        expect(el.querySelectorAll('.rv-lock').length).toBe(1);
        expect(el.querySelectorAll('.rv-x').length).toBe(1);
    });

    it('adds a blank row', async () => {
        const el = await render(Types, { lists, readOnly: false });
        const add = [...el.querySelectorAll('button')].find((b) => b.textContent.includes('Add a type'));
        add.click();
        await nextTick();
        expect(el.querySelectorAll('.rv-typelist input').length).toBe(3);
    });
});
