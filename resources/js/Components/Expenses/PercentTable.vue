<!--
  CFO Hotels — PercentTable (expenses as a percentage of revenues, one box for each operating year)
  Location: resources/js/Components/Expenses/PercentTable.vue
  <PercentTable :rows="form.sections[name]" :years="years" :revenue="revenue" :revenue-label="..." :errors="form.errors" :prefix="`sections.${name}`" />
  Under the years: the revenue the percentages are taken from, a line for each expense, and the total of each year (it may not pass 100%).
-->
<script setup>
import { computed } from 'vue';
import CopyInput from '@/Components/CopyInput.vue';
import { money } from '@/Utils/adr';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    rows: { type: Array, required: true },
    years: { type: Array, required: true },
    // null = the revenue has not arrived yet (it comes a moment after the page): show "…" instead of zeros
    revenue: { type: Object, default: null },
    revenueLabel: { type: String, required: true },
    disabled: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    prefix: { type: String, required: true },
    // the management fees screen: two fixed lines, no revenue row for the incentive fee, no total (each fee has one line)
    lockedNames: { type: Boolean, default: false },
    hideRevenue: { type: Boolean, default: false },
    hideTotal: { type: Boolean, default: false },
    nameLabel: { type: String, default: '' },
});

const { t } = useI18n();

const named = (row) => String(row.name ?? '').trim() !== '';
const totals = computed(() => Object.fromEntries(props.years.map((y) => [
    y.index,
    Math.round(props.rows.filter(named).reduce((sum, r) => sum + (Number(r.values[y.index]) || 0), 0) * 100) / 100,
])));
const over = (y) => totals.value[y.index] > 100 || !!props.errors[`${props.prefix}.total.${y.index}`];
const bad = (i, y) => !!props.errors[`${props.prefix}.${i}.values.${y.index}`];

function copyRight(row, from) {
    const start = props.years.findIndex((y) => y.index === from.index);
    for (let i = start + 1; i < props.years.length; i += 1) row.values[props.years[i].index] = row.values[from.index];
}
</script>

<template>
    <div class="rv-scroll" role="region" :aria-label="revenueLabel" tabindex="0">
        <table class="ex-grid" data-percent-table>
            <thead>
                <tr>
                    <th scope="col" class="rv-rowhead">{{ nameLabel || t('expenses.col.name') }}</th>
                    <th v-for="y in years" :key="y.index" scope="col">
                        <span class="rv-year" dir="ltr">{{ y.year }}</span>
                        <small v-if="y.months < 12">{{ t('revenue.partYear', { count: y.months }) }}</small>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="!hideRevenue" class="ex-revenue" data-revenue-row>
                    <th scope="row" class="rv-rowhead">{{ revenueLabel }}</th>
                    <td v-for="y in years" :key="y.index" class="rv-num" dir="ltr">{{ revenue === null ? '…' : money(revenue[y.index] ?? 0, 0) }}</td>
                </tr>
                <tr v-for="(row, i) in rows" :key="i" data-expense-row>
                    <th scope="row" class="rv-rowhead ex-name">
                        <input v-model="row.name" class="inp" type="text" maxlength="120" :placeholder="t('expenses.expenseName')" :aria-label="t('expenses.col.name')" :disabled="disabled" :readonly="lockedNames">
                    </th>
                    <td v-for="(y, k) in years" :key="y.index" :class="{ bad: bad(i, y) }">
                        <CopyInput v-model="row.values[y.index]" suffix="%" :max="100" :copy="!disabled && k < years.length - 1" :invalid="bad(i, y)" :aria-label="`${row.name || t('expenses.col.name')} ${y.year}`" :disabled="disabled" @copy="copyRight(row, y)" />
                    </td>
                </tr>
            </tbody>
            <tfoot v-if="!hideTotal">
                <tr class="ex-total" data-percent-total>
                    <th scope="row" class="rv-rowhead">{{ t('expenses.col.total') }}</th>
                    <td v-for="y in years" :key="y.index" class="rv-num" :class="{ bad: over(y) }" dir="ltr">{{ totals[y.index] }}%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>
