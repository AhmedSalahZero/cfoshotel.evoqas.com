<!--
  CFO Hotels — CostTable (an amount that grows: per night sold, per guest, or a fixed amount every month)
  Location: resources/js/Components/Expenses/CostTable.vue
  <CostTable :rows="form.sections[name]" :head="..." :currencies="currencies" :estimation-date="..." :days-difference="1.75" :terms="false" ... />
  A line: the name, the currency, the amount today, the date of the estimate, the escalation up to the opening, the amount at the opening
  (worked out here as you type; the server works it out again when saving) and the escalation of every year after. A fixed expense also says when it is paid.
-->
<script setup>
import FormField from '@/Components/Form/FormField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import { adrAtOperation, money } from '@/Utils/adr';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    rows: { type: Array, required: true },
    head: { type: String, required: true },
    currencies: { type: Array, required: true },
    estimationDate: { type: String, default: '' },
    daysDifference: { type: Number, default: 0 },
    withTerms: { type: Boolean, default: false },
    termOptions: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    prefix: { type: String, required: true },
});

const { t } = useI18n();
const currencyOptions = props.currencies.map((c) => ({ value: c.id, text: c.name }));
const bad = (i, field) => !!props.errors[`${props.prefix}.${i}.${field}`];

// the three-dot column buttons of the old screens: the first line's figure goes down the column
function copyDown(field) {
    const first = props.rows[0];
    props.rows.forEach((row, i) => { if (i > 0) row[field] = first[field]; });
}
</script>

<template>
    <div class="rv-scroll" role="region" :aria-label="head" tabindex="0">
        <table class="ex-grid ex-cost" data-cost-table>
            <thead>
                <tr>
                    <th scope="col" class="rv-rowhead">{{ t('expenses.col.name') }}</th>
                    <th scope="col">{{ t('expenses.col.currency') }}</th>
                    <th scope="col">{{ head }}</th>
                    <th scope="col">{{ t('expenses.col.estimation') }}</th>
                    <th scope="col">{{ t('expenses.col.escalation') }}<button v-if="!disabled" type="button" class="ex-col" :title="t('expenses.copyColumn')" :aria-label="`${t('expenses.copyColumn')}: ${t('expenses.col.escalation')}`" @click="copyDown('escalation')">⋯</button></th>
                    <th scope="col">{{ t('expenses.col.atOperation') }}</th>
                    <th scope="col">{{ t('expenses.col.annual') }}<button v-if="!disabled" type="button" class="ex-col" :title="t('expenses.copyColumn')" :aria-label="`${t('expenses.copyColumn')}: ${t('expenses.col.annual')}`" @click="copyDown('annual')">⋯</button></th>
                    <th v-if="withTerms" scope="col">{{ t('expenses.col.terms') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, i) in rows" :key="i" data-expense-row>
                    <th scope="row" class="rv-rowhead ex-name">
                        <input v-model="row.name" class="inp" type="text" maxlength="120" :placeholder="t('expenses.expenseName')" :aria-label="t('expenses.col.name')" :disabled="disabled">
                    </th>
                    <td><SelectField v-model="row.currency" :options="currencyOptions" :invalid="bad(i, 'currency')" :disabled="disabled" /></td>
                    <td :class="{ bad: bad(i, 'amount') }"><NumberField v-model="row.amount" :min="0" :max="1000000000" :invalid="bad(i, 'amount')" :disabled="disabled" /></td>
                    <td class="rv-num" dir="ltr">{{ estimationDate }}</td>
                    <td :class="{ bad: bad(i, 'escalation') }"><NumberField v-model="row.escalation" unit="%" :min="0" :max="100" :invalid="bad(i, 'escalation')" :disabled="disabled" /></td>
                    <td class="rv-num" dir="ltr" data-at-operation>{{ money(adrAtOperation(row.amount, row.escalation, daysDifference)) }}</td>
                    <td :class="{ bad: bad(i, 'annual') }"><NumberField v-model="row.annual" unit="%" :min="0" :max="100" :invalid="bad(i, 'annual')" :disabled="disabled" /></td>
                    <td v-if="withTerms"><SelectField v-model="row.terms" :options="termOptions" :invalid="bad(i, 'terms')" :disabled="disabled" /></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
