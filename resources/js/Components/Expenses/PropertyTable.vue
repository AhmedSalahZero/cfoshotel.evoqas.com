<!--
  CFO Hotels — PropertyTable (property taxes and property insurance)
  Location: resources/js/Components/Expenses/PropertyTable.vue
  Two fixed lines, as a percentage of the fixed assets. The month the taxes are paid is chosen once, on the first line.
-->
<script setup>
import NumberField from '@/Components/Form/NumberField.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    rows: { type: Array, required: true },
    currencies: { type: Array, required: true },
    disabled: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    prefix: { type: String, required: true },
});

const { t } = useI18n();
const currencyOptions = props.currencies.map((c) => ({ value: c.id, text: c.name }));
const monthOptions = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'].map((m) => ({ value: m, text: t(`expenses.months.${m}`) }));
const bad = (i, field) => !!props.errors[`${props.prefix}.${i}.${field}`];
</script>

<template>
    <div class="rv-scroll" role="region" :aria-label="t('expenses.sections.other_property_fixed_expenses')" tabindex="0">
        <table class="ex-grid ex-property" data-property-table>
            <thead>
                <tr>
                    <th scope="col" class="rv-rowhead">{{ t('expenses.col.name') }}</th>
                    <th scope="col">{{ t('expenses.col.currency') }}</th>
                    <th scope="col">{{ t('expenses.property.percentage') }}</th>
                    <th scope="col">{{ t('expenses.property.annual') }}</th>
                    <th scope="col">{{ t('expenses.property.month') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, i) in rows" :key="i" data-property-row>
                    <th scope="row" class="rv-rowhead">{{ t(`expenses.property.names.${i}`) }}</th>
                    <td><SelectField v-model="row.currency" :options="currencyOptions" :disabled="disabled" /></td>
                    <td :class="{ bad: bad(i, 'percentage') }"><NumberField v-model="row.percentage" unit="%" :min="0" :max="100" :invalid="bad(i, 'percentage')" :disabled="disabled" /></td>
                    <td :class="{ bad: bad(i, 'annual') }"><NumberField v-model="row.annual" unit="%" :min="0" :max="100" :invalid="bad(i, 'annual')" :disabled="disabled" /></td>
                    <td><SelectField v-if="i === 0" v-model="row.month" :options="monthOptions" :disabled="disabled" /></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
