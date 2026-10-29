import { describe, expect, it, vi } from 'vitest';
import { createApp, h, nextTick } from 'vue';

vi.mock('@inertiajs/vue3', async () => {
    const vue = await import('vue');
    const passthrough = (tag) => vue.defineComponent({ props: ['href', 'as'], setup: (p, { slots, attrs }) => () => vue.h(tag, { href: p.href, ...attrs }, slots.default?.()) });
    return {
        Head: vue.defineComponent({ setup: () => () => null }),
        Link: passthrough('a'),
        router: { post() {}, on() {} },
        usePage: () => ({ props: { auth: { user: { role: 'company_admin', name: 'Mona Ali', email: 'm@x.test' } }, flash: {}, locale: 'en' } }),
        useForm: (data) => {
            const form = vue.reactive({ ...JSON.parse(JSON.stringify(data)), errors: globalThis.__errors ?? {}, hasErrors: Object.keys(globalThis.__errors ?? {}).length > 0, processing: false,
                put() {}, post() {}, delete() {}, clearErrors() {}, reset() {}, transform() { return this; } });
            return form;
        },
    };
});
vi.mock('@/pwa/register', () => ({ applyUpdate() {}, updateReady: { value: false }, registerServiceWorker() {} }));

globalThis.route = (name, p) => `/${name}${p ? `/${typeof p === 'object' ? p.id ?? '' : p}` : ''}`;

const { default: CompaniesIndex } = await import('@/Pages/Admin/Companies/Index.vue');
const { default: CompanyForm } = await import('@/Pages/Admin/Companies/Form.vue');
const { default: UsersIndex } = await import('@/Pages/Users/Index.vue');
const { default: UserForm } = await import('@/Pages/Users/Form.vue');
const { default: Home } = await import('@/Pages/Home.vue');
const { default: MonthYearInput } = await import('@/Components/MonthYearInput.vue');
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
    return el.innerHTML;
};
const dom = () => mounted.at(-1)[1];

describe('screens render', () => {
    it('home welcomes the person, shows the last studies and how a study is built', async () => {
        const html = await render(Home, { studyCount: 2, canCreate: true, recent: [{ id: 7, name: 'Nile Corniche', property: 'Nile', stars: 5, updated: '2026-10-29' }] });
        expect(html).toContain('Welcome, Mona Ali');
        expect(html).toContain('Continue where you left off');
        expect(html).toContain('Nile Corniche');
        expect(html).toContain('href="/studies.edit/7"');
        expect(html).toContain('Compare two studies');
        expect(html).toContain('How a study is built');
        expect(html).not.toContain('Coming next');
    });

    it('home invites a person without studies to create the first one', async () => {
        const html = await render(Home, { studyCount: 0, canCreate: true, recent: [] });
        expect(html).toContain('No studies yet');
        expect(html).toContain('New study');
        expect(html).not.toContain('Compare two studies');
    });

    it('home offers the people of the company to a company administrator', async () => {
        const html = await render(Home, {});
        expect(html).toContain('People of my company');
        expect(html).toContain('href="/users.index"');
    });

    it('the month picker shows the chosen month and year', async () => {
        await render(MonthYearInput, { modelValue: '2026-03' });
        const [month, year] = dom().querySelectorAll('select');
        expect(month.value).toBe('3');
        expect(year.value).toBe('2026');
    });

    it('companies: list, new form and edit form', async () => {
        const list = await render(CompaniesIndex, { companies: [{ id: 1, name_en: 'Delta', name_ar: 'دلتا', status: 'trial', users_limit: 5, active_users: 2, ends: '2026-12-31', read_only: true }] });
        expect(list).toContain('Delta');
        expect(list).toContain('2 / 5');
        expect(list).toContain('Read-only');
        const fresh = await render(CompanyForm, { company: null, users: [], statuses: ['trial', 'active', 'suspended'], defaults: { users_limit: 5, subscription_starts_at: '2026-10-27', subscription_ends_at: '2027-10-27' } });
        expect(fresh).toContain('First administrator');
        expect(dom().querySelector('#c-limit').value).toBe('5');
        const edit = await render(CompanyForm, { company: { id: 1, name_en: 'Delta', name_ar: 'دلتا', status: 'active', users_limit: 5, subscription_starts_at: '2026-01-01', subscription_ends_at: '2026-12-31', contact_phone: '', contact_email: '', notes: '', read_only: false }, users: [{ id: 9, name: 'Sam', email: 's@x.test', role: 'company_admin', active: true, last_login: null }], statuses: ['trial', 'active', 'suspended'], defaults: null });
        expect(edit).not.toContain('First administrator');
        expect(edit).toContain('Company administrator');
        expect(dom().querySelector('#c-en').value).toBe('Delta');
    });

    it('people: list and forms', async () => {
        const list = await render(UsersIndex, { users: [{ id: 1, name: 'Sam', email: 's@x.test', job_title: null, role: 'company_admin', active: true, last_login: null }], limit: 5, active: 1 });
        expect(list).toContain('1 of 5 accounts are active');
        await render(UserForm, { person: null });
        expect(dom().querySelector('#u-mail').readOnly).toBe(false);
        await render(UserForm, { person: { id: 1, name: 'Sam', email: 's@x.test', job_title: '', role: 'company_admin', active: true, is_me: true } });
        expect(dom().querySelector('#u-mail').readOnly).toBe(true);
        expect(dom().querySelector('#u-role').disabled).toBe(true);
    });

    it('the top bar: tabs follow the role', async () => {
        const html = await render(Home, {});
        expect(html).toContain('People');
        expect(html).not.toContain('>Companies<');
    });
});
