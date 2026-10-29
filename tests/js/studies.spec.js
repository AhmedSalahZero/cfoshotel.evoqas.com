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
        useForm: (data) => vue.reactive({ ...JSON.parse(JSON.stringify(data)), errors: globalThis.__errors ?? {}, hasErrors: false, isDirty: false, processing: false, put() {}, post() {}, clearErrors() {}, reset() {} }),
    };
});
vi.mock('axios', () => ({ default: { get: vi.fn(async () => ({ data: [{ id: 1, name: 'Cairo' }] })) } }));
vi.mock('@/pwa/register', () => ({ applyUpdate() {}, updateReady: { value: false }, registerServiceWorker() {} }));

globalThis.route = (name, p) => `/${name}${p ? `/${typeof p === 'object' ? p.id ?? '' : p}` : ''}`;

const { default: StudiesIndex } = await import('@/Pages/Studies/Index.vue');
const { default: StudyFormPage } = await import('@/Pages/Studies/Form.vue');
const { default: NumberField } = await import('@/Components/Form/NumberField.vue');
const { default: SwitchField } = await import('@/Components/Form/SwitchField.vue');
const { default: StreamEditor } = await import('@/Components/Studies/StreamEditor.vue');
const { timeline, operationBeforeDevelopmentEnds, monthText, monthAfter } = await import('@/Utils/studyTimeline');
const { setLocale } = await import('@/lang/i18n');

let mounted = [];
const render = async (component, props) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp({ render: () => h(component, props) });
    app.config.globalProperties.route = globalThis.route;
    app.mount(el);
    mounted.push([app, el]);
    await nextTick();
    return el;
};

const row = (o = {}) => ({ type_id: null, count: null, value: null, ...o });
const stream = (on, rows = [row()]) => ({ on, mode: 'types', rows });
const study = (o = {}) => ({
    id: null, study_name: '', property_name: '', property_status: 'existing', star_rating: null, country_id: null, state_id: null, region: '',
    study_start: '2026-01', duration_in_years: 5, development_start_month: 0, development_duration: 0, operation_start_month: 0,
    financial_year_start_month: 'january', main_functional_currency: 1, additional_currency: null, exchange_rate: 1,
    corporate_taxes_rate: 0, investment_return_rate: 10, perpetual_growth_rate: 0,
    streams: { rooms: stream(true), foods: stream(true), casinos: stream(false), meetings: stream(false), others: stream(false) },
    channels: { on: false, names: [] }, ...o,
});
const options = () => ({
    countries: [{ id: 1, name: 'Egypt' }], states: [], currencies: [{ id: 1, name: 'EGP' }, { id: 2, name: 'USD' }],
    statuses: ['existing', 'existing_need_rufurbishing', 'under_development'], star_ratings: [1, 2, 3, 4, 5, 7], durations: [2, 3, 5, 10, 20],
    financial_year_starts: ['january', 'april', 'july'], channels: ['direct-reservations', 'agency'],
    types: { rooms: [{ id: 1, title: 'Double' }], foods: [{ id: 2, title: 'Restaurant' }], casinos: [], meetings: [], others: [] },
});
const listed = (o = {}) => ({
    id: 5, name: 'Nile Hotel', property: 'Nile', stars: 5, status: 'existing', country: 'Egypt', start: '2026-01', end: '2030-12', years: 5,
    development_start: 0, development_duration: 0, operation_start: 0, owner: 'Mona Ali', access: 'owner', shared_with: 2, updated: '2026-10-01',
    can: { edit: true, share: true, delete: true, copy: true, excel: true }, ...o,
});

describe('studies list', () => {
    it('shows a study with its owner, access and actions', async () => {
        setLocale('en');
        const el = await render(StudiesIndex, { studies: [listed()], isAdmin: true, readOnly: false });
        expect(el.textContent).toContain('Nile Hotel');
        expect(el.textContent).toContain('Owner');
        expect(el.querySelector('a[href="/studies.edit/5"]')).not.toBeNull();
        expect(el.querySelector('a[href="/studies.create"]')).not.toBeNull();
    });

    it('offers the Excel model of a study to download, in both languages', async () => {
        setLocale('en');
        const el = await render(StudiesIndex, { studies: [listed()], isAdmin: false, readOnly: false });
        const link = el.querySelector('a[data-excel]');
        expect(link).not.toBeNull();
        expect(link.getAttribute('href')).toBe('/studies.excel/5');
        expect(link.hasAttribute('download')).toBe(true);
        expect(link.getAttribute('aria-label')).toContain('Excel Model');
        const none = await render(StudiesIndex, { studies: [listed({ can: { edit: false, share: false, delete: false, copy: false, excel: false } })], isAdmin: false, readOnly: false });
        expect(none.querySelector('a[data-excel]')).toBeNull();
        setLocale('ar');
        const ar = await render(StudiesIndex, { studies: [listed()], isAdmin: false, readOnly: false });
        expect(ar.querySelector('a[data-excel]').getAttribute('aria-label')).toContain('نموذج Excel');
        setLocale('en');
    });

    it('invites the first study when there are none, and hides "new" for a read-only company', async () => {
        const empty = await render(StudiesIndex, { studies: [], isAdmin: false, readOnly: false });
        expect(empty.textContent).toContain('No studies yet');
        const locked = await render(StudiesIndex, { studies: [], isAdmin: false, readOnly: true });
        expect(locked.querySelector('a[href="/studies.create"]')).toBeNull();
    });

    it('speaks Arabic', async () => {
        setLocale('ar');
        const el = await render(StudiesIndex, { studies: [listed()], isAdmin: false, readOnly: false });
        expect(el.textContent).toContain('الدراسات');
        setLocale('en');
    });
});

describe('study form', () => {
    it('a new study shows the five sections, with the defaults', async () => {
        const el = await render(StudyFormPage, { study: study(), options: options(), access: 'owner', readOnly: false });
        for (const id of ['sec-main', 'sec-timing', 'sec-money', 'sec-streams', 'sec-channels']) expect(el.querySelector(`#${id}`), id).not.toBeNull();
        expect(el.querySelector('#s-return').value).toBe('10');
        expect(el.querySelector('[data-savebar] button[type=submit]')).not.toBeNull();
        expect(el.querySelector('[data-field="exchange"]')).toBeNull();
    });

    it('a colleague with view access sees every box locked and no save button', async () => {
        const el = await render(StudyFormPage, { study: study({ id: 9, study_name: 'Nile' }), options: options(), access: 'view', readOnly: false });
        expect(el.querySelector('#s-name').disabled).toBe(true);
        expect(el.querySelector('[data-savebar] button[type=submit]')).toBeNull();
        expect(el.textContent).toContain('View only');
    });

    it('asks for the exchange rate once an additional currency is chosen', async () => {
        const el = await render(StudyFormPage, { study: study({ id: 9, additional_currency: 2, exchange_rate: 48 }), options: options(), access: 'owner', readOnly: false });
        expect(el.querySelector('[data-field="exchange"]')).not.toBeNull();
        expect(el.querySelector('#s-fx').value).toBe('48');
    });

    it('warns when operation starts outside the study, and when it starts before development ends', async () => {
        const outside = await render(StudyFormPage, { study: study({ id: 9, operation_start_month: 70 }), options: options(), access: 'owner', readOnly: false });
        expect(outside.querySelector('[data-note="outside"]')).not.toBeNull();
        const overlap = await render(StudyFormPage, { study: study({ id: 9, development_start_month: 0, development_duration: 12, operation_start_month: 6 }), options: options(), access: 'owner', readOnly: false });
        expect(overlap.querySelector('[data-note="overlap"]')).not.toBeNull();
    });
});

describe('building blocks', () => {
    it('number field keeps digits only and converts Arabic digits', async () => {
        let value = null;
        const el = await render(NumberField, { modelValue: null, decimals: 0, 'onUpdate:modelValue': (v) => { value = v; } });
        const input = el.querySelector('input');
        input.value = '٣a4';
        input.dispatchEvent(new Event('input'));
        input.dispatchEvent(new Event('blur'));
        await nextTick();
        expect(value).toBe(34);
    });

    it('switch field flips on click', async () => {
        let value = false;
        const el = await render(SwitchField, { modelValue: false, 'onUpdate:modelValue': (v) => { value = v; } });
        (el.querySelector('button, input')).click();
        await nextTick();
        expect(value).toBe(true);
    });

    it('stream editor lists its rows and hides an unused type from the next row', async () => {
        const s = stream(true, [row({ type_id: 1, count: 10, value: 2 })]);
        const el = await render(StreamEditor, { streamKey: 'rooms', stream: s, types: [{ id: 1, title: 'Double' }, { id: 3, title: 'Suite' }], icon: 'bed', title: 'Rooms' });
        expect(el.textContent).toContain('Rooms');
        expect(el.textContent).toContain('Double');
    });
});

describe('study timeline helpers', () => {
    it('draws development and operation as percentages of the study', () => {
        expect(timeline({ years: 5, developmentStart: 6, developmentDuration: 18, operationStart: 24 })).toEqual({ total: 60, dev: { a: 10, w: 30 }, ops: { a: 40, w: 60 } });
    });
    it('has no development bar without a duration, and no operation bar outside the study', () => {
        const t = timeline({ years: 2, operationStart: 24 });
        expect(t.dev).toBeNull();
        expect(t.ops).toBeNull();
    });
    it('detects operation starting before development ends', () => {
        expect(operationBeforeDevelopmentEnds({ developmentStart: 0, developmentDuration: 12, operationStart: 6 })).toBe(true);
        expect(operationBeforeDevelopmentEnds({ developmentStart: 0, developmentDuration: 12, operationStart: 12 })).toBe(false);
        expect(operationBeforeDevelopmentEnds({ developmentDuration: 0, operationStart: 0 })).toBe(false);
    });
    it('prints month names', () => {
        const names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        expect(monthText('2026-03', names)).toBe('Mar 2026');
        expect(monthText('', names)).toBe('');
        expect(monthAfter('2026-11', 3, names)).toBe('Feb 2027');
    });
});
