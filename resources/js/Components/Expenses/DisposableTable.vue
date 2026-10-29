<!--
  CFO Hotels — DisposableTable (disposable expenses: stock, payment and a percentage for each year)
  Location: resources/js/Components/Expenses/DisposableTable.vue
  A line: the name, the stock at the start, the days of stock kept, the cash share of the payment (the rest is deferred), the days until the deferred
  part is due, and the expense as a percentage of the purchases for each operating year.
-->
<script setup>
import CopyInput from '@/Components/CopyInput.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    rows: { type: Array, required: true },
    years: { type: Array, required: true },
    days: { type: Array, required: true },
    disabled: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    prefix: { type: String, required: true },
});

const { t } = useI18n();
const dayOptions = props.days.map((d) => ({ value: d, text: `${d} ${t('expenses.days')}` }));
const bad = (i, field) => !!props.errors[`${props.prefix}.${i}.${field}`];
const deferred = (row) => Math.round((100 - (Number(row.cash) || 0)) * 100) / 100;

function copyRight(row, from) {
    const start = props.years.findIndex((y) => y.index === from.index);
    for (let i = start + 1; i < props.years.length; i += 1) row.values[props.years[i].index] = row.values[from.index];
}
</script>

<template>
    <div class="rv-scroll" role="region" :aria-label="t('expenses.sections.disposable_expenses_rate')" tabindex="0">
        <table class="ex-grid ex-disposable" data-disposable-table>
            <thead>
                <tr>
                    <th scope="col" class="rv-rowhead">{{ t('expenses.col.name') }}</th>
                    <th scope="col">{{ t('expenses.disposable.beginning') }}</th>
                    <th scope="col">{{ t('expenses.disposable.coverage') }}</th>
                    <th scope="col">{{ t('expenses.disposable.cash') }}</th>
                    <th scope="col">{{ t('expenses.disposable.deferred') }}</th>
                    <th scope="col">{{ t('expenses.disposable.due') }}</th>
                    <th v-for="y in years" :key="y.index" scope="col">
                        <span class="rv-year" dir="ltr">{{ y.year }}</span>
                        <small>{{ t('expenses.disposable.percent') }}</small>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, i) in rows" :key="i" data-expense-row>
                    <th scope="row" class="rv-rowhead ex-name">
                        <input v-model="row.name" class="inp" type="text" maxlength="120" :placeholder="t('expenses.expenseName')" :aria-label="t('expenses.col.name')" :disabled="disabled">
                    </th>
                    <td :class="{ bad: bad(i, 'beginning') }"><NumberField v-model="row.beginning" :min="0" :max="1000000000" :invalid="bad(i, 'beginning')" :disabled="disabled" /></td>
                    <td><SelectField v-model="row.coverage" :options="dayOptions" :disabled="disabled" /></td>
                    <td :class="{ bad: bad(i, 'cash') }"><NumberField v-model="row.cash" unit="%" :min="0" :max="100" :invalid="bad(i, 'cash')" :disabled="disabled" /></td>
                    <td class="rv-num" dir="ltr" data-deferred>{{ deferred(row) }}%</td>
                    <td><SelectField v-model="row.dueDays" :options="dayOptions" :disabled="disabled" /></td>
                    <td v-for="(y, k) in years" :key="y.index" :class="{ bad: bad(i, `values.${y.index}`) }">
                        <CopyInput v-model="row.values[y.index]" suffix="%" :max="100" :copy="!disabled && k < years.length - 1" :invalid="bad(i, `values.${y.index}`)" :aria-label="`${row.name || t('expenses.col.name')} ${y.year}`" :disabled="disabled" @copy="copyRight(row, y)" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
