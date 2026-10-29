import { describe, expect, it } from 'vitest';
import { createApp, h, nextTick } from 'vue';

const { default: ResultsTable } = await import('@/Components/Reports/ResultsTable.vue');
const { setLocale } = await import('@/lang/i18n');

const render = async (props) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    createApp({ render: () => h(ResultsTable, props) }).mount(el);
    await nextTick();
    return el;
};

const years = [{ index: 0, year: 2026, months: 10 }, { index: 1, year: 2027, months: 12 }];
const props = {
    title: 'Revenue', years, totalLabel: 'Total',
    sections: [{ key: 's', rows: [{ key: 'a', label: 'Rooms', values: { 0: 1000, 1: 2500 }, attrs: { 'data-line': 'a' } }] }],
    foot: [{ key: 't', label: 'All', values: { 0: 1000, 1: 2500 }, attrs: { 'data-total': '' } }],
};

describe('results table', () => {
    it('shows a column for each year, the sum of each line and the total line', async () => {
        setLocale('en');
        const el = await render(props);
        expect(el.querySelectorAll('thead th').length).toBe(2 + years.length);
        expect(el.querySelector('[data-line="a"]').textContent).toContain('3,500');
        expect(el.querySelector('[data-total]').textContent).toContain('2,500');
        expect(el.querySelector('thead').textContent).toContain('10');
    });

    it('can leave the sum column out and speaks Arabic', async () => {
        setLocale('ar');
        const el = await render({ ...props, showTotal: false });
        expect(el.querySelectorAll('thead th').length).toBe(1 + years.length);
        expect(el.querySelector('thead th').textContent.trim().length).toBeGreaterThan(0);
    });
});
