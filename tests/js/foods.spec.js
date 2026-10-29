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

const { default: Foods } = await import('@/Pages/Studies/Foods.vue');
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
const METHODS = ['guest_capture_x_cover_value_per_guest_method', 'guest_capture_x_meals_per_guest_x_cover_value_per_meal_method', 'cover_count_target_per_day_x_cover_value_method', 'percentage_from_rooms_revenue'];
const facility = (o = {}) => ({ key: 1, name: 'Restaurant', count: 2, cover: 50, capacity: 100, method: METHODS[0], currency: 1, value: 50, escalation: 10, annual: 3, capture: byYear(20), meals: byYear(0), target: byYear(0), rooms: byYear(0), terms: terms(), ...o });
const page = (o = {}, facilities = [facility()], collection = { mode: 'general', general: terms() }) => ({
    study: { id: 5, name: 'Nile Hotel' }, menu: [{ key: 'study', href: '/s', done: true }, { key: 'foods', href: '/f', done: false }],
    access: 'owner', readOnly: false, years, total: false, daysDifference: 1, estimationDate: 'Mar 2026',
    currencies: [{ id: 1, name: 'EGP' }], methods: METHODS, form: { facilities, collection }, ...o,
});

describe('food and beverage page', () => {
    it('shows the facility, its seats and the live value at opening', async () => {
        setLocale('en');
        const el = await render(Foods, page());
        expect(el.textContent).toContain('Restaurant');
        expect(el.querySelector('[data-value-op]').textContent).toContain('55.00');
        expect(el.querySelectorAll('[data-facility]').length).toBe(1);
    });

    it('shows only the yearly figures the chosen method needs', async () => {
        const per = async (method) => (await render(Foods, page({}, [facility({ method })]))).querySelectorAll('[data-yearly] tbody tr').length;
        expect(await per(METHODS[0])).toBe(1);
        expect(await per(METHODS[1])).toBe(2);
        expect(await per(METHODS[2])).toBe(1);
        expect(await per(METHODS[3])).toBe(1);
    });

    it('offers to use one method for all facilities only when there are several', async () => {
        const one = await render(Foods, page());
        expect(one.querySelector('.rv-link')).toBeNull();
        const two = await render(Foods, page({}, [facility(), facility({ key: 2, name: 'Cafe' })]));
        expect(two.querySelector('.rv-link')).not.toBeNull();
    });

    it('lists each facility under the policy per facility', async () => {
        const el = await render(Foods, page({}, [facility(), facility({ key: 2, name: 'Cafe' })], { mode: 'per_facility', general: terms() }));
        expect(el.querySelectorAll('.rv-chan').length).toBe(2);
    });

    it('has the general policy only for a study that enters food as one total', async () => {
        const el = await render(Foods, page({ total: true }, [facility()], { mode: 'general', general: terms() }));
        expect(el.querySelectorAll('.fx-choice').length).toBe(1); // only the policy type, no "for each facility" choice
        const normal = await render(Foods, page());
        expect(normal.querySelectorAll('.fx-choice').length).toBe(2);
    });

    it('locks every box for a person who may only view, and speaks Arabic', async () => {
        const el = await render(Foods, page({ access: 'view' }));
        expect(el.querySelector('[data-savebar] button[type=submit]')).toBeNull();
        expect([...el.querySelectorAll('[data-yearly] input')].every((i) => i.disabled)).toBe(true);
        setLocale('ar');
        try { expect((await render(Foods, page())).textContent).toContain('الأغذية والمشروبات'); } finally { setLocale('en'); }
    });
});
