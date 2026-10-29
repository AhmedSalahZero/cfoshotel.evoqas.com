<!--
  CFO Hotels — ResultsTable (the calculated results of a study, one column for each calendar year)
  Location: resources/js/Components/Reports/ResultsTable.vue
  <ResultsTable :title :years="[{ index, year, months }]" :total-label :sections="[{ key, head, rows }]" :foot="[row]" :heat :dense />
  A row is { key, label, values: { yearIndex: number }, color?, attrs? }. `sections` are the blocks of lines (each may have a heading line); `foot` are the total lines.
  Same look as the financial statements (years on a band, sticky name column, colour bar, trend line, heat map, lit column, copy and CSV),
  with the data-* markers the pages and their tests rely on given through `attrs` (data-line, data-stream, data-group-total ...).
  The last column is the sum of the years.
-->
<script setup>
import { computed, ref } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useI18n } from '@/lang/i18n';
import { toDelimited } from '@/Utils/reports';
import { figure, heat as heatOf, sparkPoints } from '@/Utils/statements';

const props = defineProps({
    title: { type: String, default: '' },
    years: { type: Array, default: () => [] },
    totalLabel: { type: String, default: '' },
    sections: { type: Array, default: () => [] },
    foot: { type: Array, default: () => [] },
    heat: { type: Boolean, default: true },
    dense: { type: Boolean, default: false },
    showTotal: { type: Boolean, default: true },
});

const { t } = useI18n();
const number = (value) => Number(value) || 0;
const valuesOf = (row) => props.years.map((y) => number(row.values?.[y.index]));
const sumOf = (values) => values.reduce((a, b) => a + b, 0);

const prepare = (row) => {
    const values = valuesOf(row);
    const total = sumOf(values);

    return {
        ...row, numbers: values, text: values.map(figure), total, totalText: figure(total), strength: heatOf(values),
        spark: new Set(values).size > 1 ? sparkPoints(values) : '', idle: values.every((v) => v === 0),
    };
};
const blocks = computed(() => props.sections.map((s) => ({ ...s, rows: s.rows.map(prepare) })));
const footRows = computed(() => props.foot.map(prepare));
const columns = computed(() => props.years.length + 1 + (props.showTotal ? 1 : 0));
const negative = (text) => typeof text === 'string' && text.startsWith('-');
const parity = (i) => i % 2 === 1;
const heated = (row) => props.heat && !row.idle;

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

const delimited = (separator) => {
    const rows = [];
    for (const s of blocks.value) {
        if (s.head) rows.push({ label: s.head, level: 0, text: props.years.map(() => '') });
        for (const r of s.rows) rows.push({ label: r.label, level: s.head ? 1 : 0, text: [...r.text, ...(props.showTotal ? [r.totalText] : [])] });
    }
    for (const r of footRows.value) rows.push({ label: r.label, level: 0, text: [...r.text, ...(props.showTotal ? [r.totalText] : [])] });
    const cols = [...props.years.map((y) => ({ label: String(y.year) })), ...(props.showTotal ? [{ label: props.totalLabel }] : [])];

    return toDelimited({ columns: cols, rows, head: '' }, separator);
};
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
    link.download = `${(props.title || 'results').replace(/[^\p{L}\p{N}]+/gu, '-')}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
};
</script>

<template>
    <section class="st-card rp-card rs-card" :class="{ 'st-dense': dense }" :aria-label="title || undefined">
        <header class="st-head">
            <h3 v-if="title"><span class="st-dot" aria-hidden="true" />{{ title }}</h3>
            <span v-else class="st-grow" />
            <div class="st-tools">
                <button type="button" class="st-tool" :title="t('stmt.table.copy')" @click="copy">
                    <AppIcon :name="copied ? 'check' : 'copy'" :size="14" /><span>{{ copied ? t('stmt.table.copied') : t('stmt.table.copy') }}</span>
                </button>
                <button type="button" class="st-tool" :title="t('stmt.table.csv')" @click="download">
                    <AppIcon name="download" :size="14" /><span>{{ t('stmt.table.csv') }}</span>
                </button>
            </div>
        </header>

        <div class="st-scroll" role="region" tabindex="0" :aria-label="title || undefined" @mouseover="onOver" @mouseleave="onLeave">
            <span v-show="cross.show" class="st-cross" aria-hidden="true" :style="{ insetInlineStart: `${cross.start}px`, inlineSize: `${cross.width}px`, blockSize: `${cross.height}px` }" />
            <table ref="table" class="st-table rp-table rs-table">
                <thead>
                    <tr class="st-years">
                        <th class="st-name" scope="col">{{ t('fin.itemName') }}</th>
                        <th v-for="(y, i) in years" :key="y.index" scope="col" :class="{ 'st-odd': parity(i) }">
                            <span class="rs-year" dir="ltr">{{ y.year }}</span>
                            <small v-if="y.months < 12">{{ t('revenue.partYear', { count: y.months }) }}</small>
                        </th>
                        <th v-if="showTotal" class="st-sumh" scope="col">{{ totalLabel }}</th>
                    </tr>
                </thead>
                <tbody v-for="s in blocks" :key="s.key" :data-section="s.key">
                    <tr v-if="s.head" class="rp-l0 rp-block rs-head">
                        <th :colspan="columns" scope="colgroup" class="st-name"><span class="st-bar" aria-hidden="true" /><span class="st-label">{{ s.head }}</span></th>
                    </tr>
                    <tr v-for="r in s.rows" :key="r.key" class="rp-l2" :class="{ 'st-idle': r.idle }" v-bind="r.attrs">
                        <th scope="row" class="st-name">
                            <span class="st-bar" aria-hidden="true" :style="r.color ? { background: `var(--${r.color})` } : undefined" />
                            <span class="st-label">{{ r.label }}</span>
                            <svg v-if="r.spark && !r.idle" class="st-spark" viewBox="0 0 64 18" aria-hidden="true"><polyline :points="r.spark" /></svg>
                        </th>
                        <td
                            v-for="(text, i) in r.text" :key="i" :data-c="i" dir="ltr"
                            :class="{ 'st-odd': parity(i), 'st-neg': negative(text), 'st-zero': text === '0' }"
                            :style="heated(r) ? { '--h': r.strength[i].toFixed(2) } : undefined"
                        >{{ text }}</td>
                        <td v-if="showTotal" class="st-sum" dir="ltr" :class="{ 'st-neg': r.total < 0 }">{{ r.totalText }}<small :title="t('stmt.table.sumHint')">{{ t('stmt.table.sum') }}</small></td>
                    </tr>
                </tbody>
                <tfoot v-if="footRows.length">
                    <tr v-for="r in footRows" :key="r.key" class="rp-l0 rp-result" v-bind="r.attrs">
                        <th scope="row" class="st-name"><span class="st-bar" aria-hidden="true" /><span class="st-label">{{ r.label }}</span></th>
                        <td v-for="(text, i) in r.text" :key="i" :data-c="i" dir="ltr" :class="{ 'st-odd': parity(i), 'st-neg': negative(text) }">{{ text }}</td>
                        <td v-if="showTotal" class="st-sum" dir="ltr">{{ r.totalText }}<small :title="t('stmt.table.sumHint')">{{ t('stmt.table.sum') }}</small></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</template>
