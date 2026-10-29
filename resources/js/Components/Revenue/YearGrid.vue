<!--
  CFO Hotels — YearGrid (rows of figures, one cell for each operating year)
  Location: resources/js/Components/Revenue/YearGrid.vue
  <YearGrid :rows="rows" :years="years" :values="form.share" suffix="%" :errors="form.errors" error-prefix="channels.0.share" />   rows = one line for each row of figures (a key and its text)
  values = { rowKey: { yearIndex: number } }. A year with fewer than 12 months is marked (the first and the last year can be part years).
  The three dots copy a number into the years after it (CopyInput). Every row is a real table row, so it scrolls sideways on a phone.
-->
<script setup>
import CopyInput from '@/Components/CopyInput.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    rows: { type: Array, required: true },
    years: { type: Array, required: true },
    values: { type: Object, required: true },
    suffix: { type: String, default: '' },
    max: { type: [Number, String], default: undefined },
    disabled: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    // the start of the error keys: "channels.0.share" gives "channels.0.share.<row>.<year>" or "channels.0.share.<year>" when single
    errorPrefix: { type: String, default: '' },
    single: { type: Boolean, default: false },
    // optional: builds the error key itself, (row, year) => 'channels.0.share.2'
    errorKey: { type: Function, default: null },
    caption: { type: String, default: '' },
});

const { t } = useI18n();

const path = (row, year) => (props.errorKey ? props.errorKey(row, year) : props.single ? `${props.errorPrefix}.${year.index}` : `${props.errorPrefix}.${row.key}.${year.index}`);
const bag = (row) => (props.single ? props.values : props.values[row.key]);
const bad = (row, year) => !!props.errors[path(row, year)];

function copyRight(row, from) {
    const cells = bag(row);
    const start = props.years.findIndex((y) => y.index === from.index);
    for (let i = start + 1; i < props.years.length; i += 1) cells[props.years[i].index] = cells[from.index];
}
</script>

<template>
    <div class="rv-scroll" role="region" :aria-label="caption || undefined" tabindex="0">
        <table class="rv-grid">
            <caption v-if="caption" class="vh">{{ caption }}</caption>
            <thead>
                <tr>
                    <th scope="col" class="rv-rowhead" />
                    <th v-for="y in years" :key="y.index" scope="col">
                        <span class="rv-year" dir="ltr">{{ y.year }}</span>
                        <small v-if="y.months < 12">{{ t('revenue.partYear', { count: y.months }) }}</small>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.key">
                    <th scope="row" class="rv-rowhead">{{ row.label }}</th>
                    <td v-for="(y, i) in years" :key="y.index" :class="{ bad: bad(row, y) }">
                        <CopyInput
                            v-if="bag(row)" v-model="bag(row)[y.index]" :suffix="suffix" :max="max" :copy="!disabled && i < years.length - 1"
                            :invalid="bad(row, y)" :aria-label="`${row.label} ${y.year}`" :disabled="disabled" @copy="copyRight(row, y)"
                        />
                    </td>
                </tr>
            </tbody>
            <tfoot v-if="$slots.foot">
                <slot name="foot" />
            </tfoot>
        </table>
    </div>
</template>
