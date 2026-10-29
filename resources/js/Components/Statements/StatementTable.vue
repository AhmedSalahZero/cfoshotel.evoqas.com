<!--
  CFO Hotels — StatementTable (one table of a statement: a line for each kind of figure, a column for each month / quarter / year)
  Location: resources/js/Components/Statements/StatementTable.vue
  <StatementTable :title="..." :columns="[{ label, year }]" :rows="[{ key, values, text }]" :heat="true" :dense="false" />
  What it adds to a plain table:
    · the years sit on a band above the months, and the cells wear the colour of their year;
    · every kind of line has its own colour (opening, additions, outflows, subtotals, closing) and a little trend line;
    · a heat tint shows where the figures are biggest (switch it off with :heat="false");
    · the name column and the headings stay in view while scrolling, and the hovered column is lit from top to bottom;
    · the last column sums a flow, or shows the first / last figure of a balance;
    · copy to a spreadsheet and download as CSV.
  The figures are shown exactly as the server wrote them (text); the numbers (values) are only used for colour, trend and the summary column.
-->
<script setup>
import { computed, ref } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useI18n } from '@/lang/i18n';
import { figure, heat as heatOf, isZeroRow, roleOf, sparkPoints, summary, toDelimited, yearBands, yearParity } from '@/Utils/statements';

const props = defineProps({
    title: { type: String, default: '' },
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    heat: { type: Boolean, default: true },
    dense: { type: Boolean, default: false },
});

const { t } = useI18n();
const tr = (key, fallback) => {
    const value = t(key);

    return value === key ? fallback : value;
};
const words = (key) => String(key).replace(/[_-]/g, ' ');
const labelOf = (row) => tr(`stmt.rows.${row.key}`, words(row.key));

const bands = computed(() => yearBands(props.columns));
const parity = computed(() => yearParity(props.columns));
const lines = computed(() => props.rows.map((row) => {
    const role = roleOf(row.key);
    const sum = summary(role, row.values);

    return {
        ...row, role, label: labelOf(row), strength: heatOf(row.values), spark: sparkPoints(row.values), idle: isZeroRow(row.values),
        sumKind: sum.kind, sumText: sum.kind ? figure(sum.value) : '', sumNegative: sum.value < 0,
    };
}));
const allIdle = computed(() => lines.value.length > 0 && lines.value.every((l) => l.idle));
const heated = (line) => props.heat && ['addition', 'outflow', 'closing', 'balance'].includes(line.role) && !line.idle;
const negative = (text) => typeof text === 'string' && text.startsWith('-');

// the hovered column is lit from top to bottom by one shape that follows the pointer
const cross = ref({ show: false, start: 0, width: 0, height: 0 });
const table = ref(null);
const onOver = (event) => {
    const cell = event.target.closest?.('td[data-c]');
    if (!cell || !table.value) {
        cross.value.show = false;

        return;
    }
    const rtl = document.documentElement.dir === 'rtl';
    const start = rtl ? table.value.offsetWidth - cell.offsetLeft - cell.offsetWidth : cell.offsetLeft;
    cross.value = { show: true, start, width: cell.offsetWidth, height: table.value.offsetHeight };
};
const onLeave = () => { cross.value.show = false; };

const delimited = (separator) => toDelimited({ columns: props.columns, rows: props.rows, labelOf, head: tr('stmt.table.item', 'Item') }, separator);
const copied = ref(false);
const copy = async () => {
    try {
        await navigator.clipboard.writeText(delimited('\t'));
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 1600);
    } catch {
        copied.value = false;
    }
};
const download = () => {
    const blob = new Blob([`﻿${delimited(',')}`], { type: 'text/csv;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${(props.title || 'statement').replace(/[^\p{L}\p{N}]+/gu, '-')}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
};
</script>

<template>
    <section class="st-card" :class="{ 'st-dense': dense }" :aria-label="title || undefined" data-statement-table>
        <header class="st-head">
            <h3 v-if="title"><span class="st-dot" aria-hidden="true" />{{ title }}</h3>
            <span v-else class="st-grow" />
            <span v-if="allIdle" class="st-chip" data-idle>{{ t('stmt.table.idle') }}</span>
            <div class="st-tools">
                <button type="button" class="st-tool" :title="t('stmt.table.copy')" @click="copy">
                    <AppIcon :name="copied ? 'check' : 'copy'" :size="14" /><span>{{ copied ? t('stmt.table.copied') : t('stmt.table.copy') }}</span>
                </button>
                <button type="button" class="st-tool" :title="t('stmt.table.csv')" @click="download">
                    <AppIcon name="download" :size="14" /><span>{{ t('stmt.table.csv') }}</span>
                </button>
            </div>
        </header>

        <div class="st-scroll" role="region" tabindex="0" :aria-label="title || t('stmt.table.item')" @mouseover="onOver" @mouseleave="onLeave">
            <span v-show="cross.show" class="st-cross" aria-hidden="true" :style="{ insetInlineStart: `${cross.start}px`, inlineSize: `${cross.width}px`, blockSize: `${cross.height}px` }" />
            <table ref="table" class="st-table">
                <thead>
                    <tr class="st-years">
                        <th class="st-name" rowspan="2" scope="col">{{ t('stmt.table.item') }}</th>
                        <th v-for="(band, i) in bands" :key="i" :colspan="band.span" scope="colgroup" :class="{ 'st-odd': band.odd }" dir="ltr">{{ band.year }}</th>
                        <th class="st-sumh" rowspan="2" scope="col">{{ t('stmt.table.summary') }}</th>
                    </tr>
                    <tr class="st-months">
                        <th v-for="(column, i) in columns" :key="i" scope="col" :class="{ 'st-odd': parity[i] }" dir="ltr">{{ column.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in lines" :key="line.key" :class="[`st-${line.role}`, { 'st-idle': line.idle }]" :data-line="line.key">
                        <th scope="row" class="st-name">
                            <span class="st-bar" aria-hidden="true" />
                            <span class="st-label">{{ line.label }}</span>
                            <svg v-if="line.spark && !line.idle" class="st-spark" viewBox="0 0 64 18" aria-hidden="true"><polyline :points="line.spark" /></svg>
                        </th>
                        <td
                            v-for="(text, i) in line.text" :key="i" :data-c="i" dir="ltr"
                            :class="{ 'st-odd': parity[i], 'st-neg': negative(text), 'st-zero': text === '0' }"
                            :style="heated(line) ? { '--h': line.strength[i].toFixed(2) } : undefined"
                        >{{ text }}</td>
                        <td class="st-sum" dir="ltr" :class="{ 'st-neg': line.sumNegative }">
                            <template v-if="line.sumKind">
                                {{ line.sumText }}<small :title="t(`stmt.table.${line.sumKind}Hint`)">{{ t(`stmt.table.${line.sumKind}`) }}</small>
                            </template>
                        </td>
                    </tr>
                    <tr v-if="!lines.length" class="st-empty"><td :colspan="columns.length + 2">{{ t('stmt.table.empty') }}</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
