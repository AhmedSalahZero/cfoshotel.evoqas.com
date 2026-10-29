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

const { default: Others } = await import('@/Pages/Studies/Others.vue');
const { setLocale } = await import('@/lang/i18n');

const render = async (props) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp({ render: () => h(Others, props) });
    app.config.globalProperties.route = globalThis.route;
    app.mount(el);
    await nextTick();
    return el;
};

const years = [{ index: 1, year: 2027, months: 10 }, { index: 2, year: 2028, months: 12 }];
const byYear = (v) => Object.fromEntries(years.map((y) => [y.index, v]));
const terms = () => ({ type: 'system_default', interval: 'monthly', rates: [0, 0, 0, 0], due: [0, 0, 0, 0] });
const GUEST = 'guest_capture_x_charges_per_guest_method';
const ROOMS = 'percentage_from_rooms_revenue';
const other = (o = {}) => ({ key: 1, name: 'Spa', count: 2, method: GUEST, currency: 1, value: 100, escalation: 10, annual: 3, capture: byYear(15), rooms: byYear(0), terms: terms(), ...o });
const page = (facilities = [other()], o = {}) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu: [{ key: 'study', href: '/s', done: true }, { key: 'others', href: '/o', done: false }],
    access: 'owner', readOnly: false, years, total: false, daysDifference: 1, estimationDate: 'Mar 2026', currencies: [{ id: 1, name: 'EGP' }],
    methods: [GUEST, ROOMS], form: { facilities, collection: { mode: 'general', general: terms() } }, ...o,
});

describe('other revenue page', () => {
    it('uses the names of the reference project and works out the charges at opening', async () => {
        setLocale('en');
        const el = await render(page());
        const text = el.textContent;
        for (const name of ['Other Revenues Facility Count', 'Choose Other Revenues Sales Projection Method', 'Charges Per Guest Escalation Rate %', 'Charges Per Guest At Operation Date', 'Guest Capture %']) {
            expect(text).toContain(name);
        }
        expect(el.querySelector('[data-value-op]').textContent).toContain('110.00');
    });

    it('shows one yearly row for each method and no charges for a percentage of rooms revenue', async () => {
        const rows = async (method) => (await render(page([other({ method })]))).querySelectorAll('[data-yearly] tbody tr').length;
        expect(await rows(GUEST)).toBe(1);
        expect(await rows(ROOMS)).toBe(1);
        const rooms = await render(page([other({ method: ROOMS })]));
        expect(rooms.querySelector('[data-no-value]')).not.toBeNull();
        expect(rooms.querySelector('[data-value-op]')).toBeNull();
    });

    it('speaks Arabic and locks every box for a person who may only view', async () => {
        setLocale('ar');
        const el = await render(page([other()], { access: 'view' }));
        expect(el.textContent).toContain('الإيرادات الأخرى');
        expect(el.querySelector('[data-savebar] button[type=submit]')).toBeNull();
        expect([...el.querySelectorAll('[data-yearly] input')].every((i) => i.disabled)).toBe(true);
        setLocale('en');
    });
});
