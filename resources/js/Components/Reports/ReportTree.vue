<!--
  CFO Hotels — ReportTree (a financial statement whose lines open into more lines: income statement, cash in / out, balance sheet)
  Location: resources/js/Components/Reports/ReportTree.vue
  <ReportTree report="income" :columns="[{ label, year }]" :rows="[{ key, label, level, parent, values, text, expandable, item, gap }]" :heat="true" :dense="false" />
  The totals (level 0) and the main lines (level 1) are always shown; the lines below a main line open when its arrow is clicked, or all together with "Expand all".
  Same look as the statement tables (years on a band, sticky name column, lit column, trend line, copy and CSV), plus:
    · a colour for each kind of total: the results (profit, assets ...), money in, money out, the check of the balance sheet;
    · the last column sums a flow, or shows the closing figure of a balance;
    · the figures are shown exactly as the server wrote them (text); the numbers (values) are only used for colour, trend and the last column.
-->
<script setup>
import { computed, ref } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useI18n } from '@/lang/i18n';
import { figure, heat as heatOf, sparkPoints, yearBands, yearParity } from '@/Utils/statements';
import { expandableKeys, slug, summaryOf, toDelimited, toneOf, visibleRows } from '@/Utils/reports';

const props = defineProps({
    report: { type: String, required: true },
    title: { type: String, default: '' },
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    heat: { type: Boolean, default: true },
    dense: { type: Boolean, default: false },
});
const open = defineModel('open', { type: Object, default: () => new Set() });

const { t } = useI18n();
const tr = (key, fallback) => {
    const value = t(key);

    return value === key ? fallback : value;
};
// a name the system knows is translated; a name the person typed (a room type, a salary line) is shown as typed
const labelOf = (row) => tr(`fin.names.${slug(row.label)}`, row.label);

const bands = computed(() => yearBands(props.columns));
const parity = computed(() => yearParity(props.columns));
const expandable = computed(() => new Set(expandableKeys(props.rows)));
const shown = computed(() => visibleRows(props.rows, open.value));
const lines = computed(() => shown.value.map((row) => {
    if (row.gap) return { key: row.key, gap: true };
    const sum = summaryOf(props.report, row);

    return {
        ...row, label: labelOf(row), tone: toneOf(props.report, row), canOpen: expandable.value.has(row.key), isOpen: open.value.has(row.key),
        strength: heatOf(row.values), spark: row.level <= 1 ? sparkPoints(row.values) : '', sumKind: sum.kind, sumText: figure(sum.value), sumNegative: sum.value < 0,
        idle: (row.values ?? []).every((v) => !Number(v)),
    };
}));
const heated = (line) => props.heat && line.level >= 1 && !line.idle;
const negative = (text) => typeof text === 'string' && text.startsWith('-');

const toggle = (line) => {
    if (!line.canOpen) return;
    const next = new Set(open.value);
    next.has(line.key) ? next.delete(line.key) : next.add(line.key);
    open.value = next;
};

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

const all = computed(() => props.rows.map((row) => ({ ...row, label: row.gap ? '' : labelOf(row) })));
const delimited = (separator) => toDelimited({ columns: props.columns, rows: all.value, head: t('fin.itemName') }, separator);
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
    link.download = `${(props.title || 'report').replace(/[^\p{L}\p{N}]+/gu, '-')}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
};
</script>

<template>
    <section class="st-card rp-card" :class="{ 'st-dense': dense }" :aria-label="title || undefined" data-report-table>
        <header class="st-head">
            <h3 v-if="title"><span class="st-dot" aria-hidden="true" />{{ title }}</h3>
            <span v-else class="st-grow" />
            <div class="st-tools">
                <button type="button" class="st-tool" data-expand-all :title="t('fin.expandAll')" @click="open = new Set(expandable)">
                    <AppIcon name="rows" :size="14" /><span>{{ t('fin.expandAll') }}</span>
                </button>
                <button type="button" class="st-tool" data-collapse-all :title="t('fin.collapseAll')" @click="open = new Set()">
                    <AppIcon name="chevron" :size="14" /><span>{{ t('fin.collapseAll') }}</span>
                </button>
                <button type="button" class="st-tool" :title="t('stmt.table.copy')" @click="copy">
                    <AppIcon :name="copied ? 'check' : 'copy'" :size="14" /><span>{{ copied ? t('stmt.table.copied') : t('stmt.table.copy') }}</span>
                </button>
                <button type="button" class="st-tool" :title="t('stmt.table.csv')" @click="download">
                    <AppIcon name="download" :size="14" /><span>{{ t('stmt.table.csv') }}</span>
                </button>
            </div>
        </header>

        <div class="st-scroll st-tall" role="region" tabindex="0" :aria-label="title || t('fin.itemName')" @mouseover="onOver" @mouseleave="onLeave">
            <span v-show="cross.show" class="st-cross" aria-hidden="true" :style="{ insetInlineStart: `${cross.start}px`, inlineSize: `${cross.width}px`, blockSize: `${cross.height}px` }" />
            <table ref="table" class="st-table rp-table">
                <thead>
                    <tr class="st-years">
                        <th class="st-name" rowspan="2" scope="col">{{ t('fin.itemName') }}</th>
                        <th v-for="(band, i) in bands" :key="i" :colspan="band.span" scope="colgroup" :class="{ 'st-odd': band.odd }" dir="ltr">{{ band.year }}</th>
                        <th class="st-sumh" rowspan="2" scope="col">{{ t('stmt.table.summary') }}</th>
                    </tr>
                    <tr class="st-months">
                        <th v-for="(column, i) in columns" :key="i" scope="col" :class="{ 'st-odd': parity[i] }" dir="ltr">{{ column.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="line in lines" :key="line.key">
                        <tr v-if="line.gap" class="rp-gap" aria-hidden="true"><td :colspan="columns.length + 2" /></tr>
                        <tr
                            v-else :class="[`rp-l${line.level}`, line.tone ? `rp-${line.tone}` : '', { 'rp-item': line.item, 'st-idle': line.idle, 'rp-open': line.isOpen }]"
                            :data-line="line.key" :data-level="line.level"
                        >
                            <th scope="row" class="st-name" :style="{ '--lv': line.level }">
                                <button v-if="line.canOpen" type="button" class="rp-arrow" :aria-expanded="line.isOpen" :aria-label="line.isOpen ? t('fin.hideLines') : t('fin.showLines')" @click="toggle(line)">
                                    <AppIcon name="chevron" :size="14" class="rp-chev" />
                                </button>
                                <span v-else class="rp-arrow rp-none" aria-hidden="true" />
                                <span class="st-bar" aria-hidden="true" />
                                <span class="st-label" :class="{ 'rp-link': line.canOpen }" @click="toggle(line)">{{ line.label }}</span>
                                <svg v-if="line.spark && !line.idle" class="st-spark" viewBox="0 0 64 18" aria-hidden="true"><polyline :points="line.spark" /></svg>
                            </th>
                            <td
                                v-for="(text, i) in line.text" :key="i" :data-c="i" dir="ltr"
                                :class="{ 'st-odd': parity[i], 'st-neg': negative(text), 'st-zero': text === '0' }"
                                :style="heated(line) ? { '--h': line.strength[i].toFixed(2) } : undefined"
                            >{{ text }}</td>
                            <td class="st-sum" dir="ltr" :class="{ 'st-neg': line.sumNegative }">
                                {{ line.sumText }}<small :title="t(`stmt.table.${line.sumKind === 'last' ? 'lastHint' : 'sumHint'}`)">{{ line.sumKind === 'last' ? t('stmt.table.last') : t('stmt.table.sum') }}</small>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="!lines.length" class="st-empty"><td :colspan="columns.length + 2">{{ t('stmt.table.empty') }}</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
