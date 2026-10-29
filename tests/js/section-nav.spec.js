// CFO Hotels — the side list of sections of a long form can be folded to a narrow rail (run: npm test)
// Location: tests/js/section-nav.spec.js
import { beforeEach, describe, expect, it } from 'vitest';
import { createApp, h, nextTick } from 'vue';

const { default: SectionNav } = await import('@/Components/Form/SectionNav.vue');
const { setLocale } = await import('@/lang/i18n');

const items = [
    { id: 'sec-main', title: 'Main information', state: 'done' },
    { id: 'sec-timing', title: 'Timing', state: 'bad', badge: '2' },
];

const mount = async () => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    createApp({ render: () => h(SectionNav, { items, title: 'Sections' }, { summary: () => h('span', 'Opens Oct 2029') }) }).mount(el);
    await nextTick();
    return el;
};

describe('section list', () => {
    beforeEach(() => {
        window.localStorage.clear();
        setLocale('en');
    });

    it('opens with the names, and folds to the numbers when the button is pressed', async () => {
        const el = await mount();
        const nav = el.querySelector('.sf-nav');
        const toggle = el.querySelector('[data-nav-toggle]');
        expect(nav.classList.contains('is-collapsed')).toBe(false);
        expect(toggle.getAttribute('aria-expanded')).toBe('true');
        expect(toggle.getAttribute('aria-label')).toBe('Hide the sections list');

        toggle.click();
        await nextTick();
        expect(nav.classList.contains('is-collapsed')).toBe(true);
        expect(toggle.getAttribute('aria-expanded')).toBe('false');
        expect(toggle.getAttribute('aria-label')).toBe('Show the sections list');
        // the names stay reachable when only the numbers show
        expect([...nav.querySelectorAll('a')].map((a) => a.getAttribute('title'))).toEqual(['Main information', 'Timing']);
        expect(nav.querySelector('a').getAttribute('aria-label')).toBe('Main information');
    });

    it('remembers the choice for the next form', async () => {
        (await mount()).querySelector('[data-nav-toggle]').click();
        await nextTick();
        expect(window.localStorage.getItem('zavero.sectionNav')).toBe('collapsed');

        const again = await mount();
        expect(again.querySelector('.sf-nav').classList.contains('is-collapsed')).toBe(true);

        again.querySelector('[data-nav-toggle]').click();
        await nextTick();
        expect(window.localStorage.getItem('zavero.sectionNav')).toBe('open');
    });

    it('speaks Arabic', async () => {
        setLocale('ar');
        const el = await mount();
        expect(el.querySelector('[data-nav-toggle]').getAttribute('aria-label')).toBe('إخفاء قائمة الأقسام');
    });
});
