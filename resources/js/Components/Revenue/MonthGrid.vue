<!--
  CFO Hotels — MonthGrid (rows of figures, one cell for each of the twelve months)
  Location: resources/js/Components/Revenue/MonthGrid.vue
  <MonthGrid :rows="rows" :values="form.general" :locked="flat" />
  values = { rowKey: { "01": number, ... "12": number } } — or, with single, just { "01": number, ... }.
  locked = the figures are fixed (flat 100 %): shown, not editable.
-->
<script setup>
import CopyInput from '@/Components/CopyInput.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    rows: { type: Array, required: true },
    values: { type: Object, required: true },
    single: { type: Boolean, default: false },
    locked: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    errorPrefix: { type: String, default: '' },
});

const { t, tr } = useI18n();
const months = Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0'));
const names = () => tr('studies.monthsShort');
const bag = (row) => (props.single ? props.values : props.values[row.key]);
const bad = (row, m) => !!props.errors[props.single ? `${props.errorPrefix}.${m}` : `${props.errorPrefix}.${row.key}.${m}`];

function copyRight(row, from) {
    const cells = bag(row);
    for (let i = months.indexOf(from) + 1; i < 12; i += 1) cells[months[i]] = cells[from];
}
</script>

<template>
    <div class="rv-scroll" role="region" tabindex="0" :aria-label="t('revenue.seasonality.title')">
        <table class="rv-grid rv-months">
            <thead>
                <tr>
                    <th scope="col" class="rv-rowhead" />
                    <th v-for="(m, i) in months" :key="m" scope="col"><span class="rv-year">{{ names()[i] }}</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.key">
                    <th scope="row" class="rv-rowhead">{{ row.label }}</th>
                    <td v-for="(m, i) in months" :key="m" :class="{ bad: bad(row, m) }">
                        <span v-if="locked" class="rv-fixed" dir="ltr">100%</span>
                        <CopyInput
                            v-else-if="bag(row)" v-model="bag(row)[m]" suffix="%" :max="100" :copy="!disabled && i < 11"
                            :invalid="bad(row, m)" :aria-label="`${row.label} ${names()[i]}`" :disabled="disabled" @copy="copyRight(row, m)"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
