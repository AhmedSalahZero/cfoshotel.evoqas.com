<!--
  CFO Hotels — SeriesChart (a small chart drawn as it is: bars, stacked bars or a running total, year by year)
  Location: resources/js/Components/Charts/SeriesChart.vue
  <SeriesChart mode="bar" :items="[{ year, value, note }]" format="compact" :tone="1" />            one bar for each year; the note sits under the year
  <SeriesChart mode="stack" :items="[{ year, values: [...] }]" :series="[{ key, name }]" />          bars made of several parts, with a legend
  <SeriesChart mode="area" :items="[{ year, value }]" />                                             a running total
  (the year goes in each item's label)
  No chart library: the chart is an SVG as wide as its box, so it stays sharp and readable on a phone. Years always run left to right.
  Every bar and point has a title (shown on hover and read by screen readers), and the whole chart has a table-like description.
-->
<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { axisOf, compact } from '@/Utils/reports';

const props = defineProps({
    mode: { type: String, default: 'bar' },
    items: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    format: { type: String, default: 'compact' },
    tone: { type: Number, default: 1 },
    height: { type: Number, default: 240 },
    label: { type: String, default: '' },
    empty: { type: String, default: '' },
});

const box = ref(null);
const width = ref(560);
let watcher = null;
onMounted(() => {
    if (box.value) width.value = Math.max(280, box.value.clientWidth || 560);
    if (typeof ResizeObserver !== 'undefined' && box.value) {
        watcher = new ResizeObserver(() => { width.value = Math.max(280, box.value.clientWidth || 560); });
        watcher.observe(box.value);
    }
});
onBeforeUnmount(() => watcher?.disconnect());

const show = (value) => (props.format === 'percent' ? `${(Number(value) * 100).toFixed(0)}%` : compact(value));
const full = (value) => (props.format === 'percent' ? `${(Number(value) * 100).toFixed(1)} %` : Number(value).toLocaleString('en-US', { maximumFractionDigits: 2 }));

const pad = { top: 26, right: 14, bottom: 44, left: 52 };
const inner = computed(() => ({ w: Math.max(40, width.value - pad.left - pad.right), h: props.height - pad.top - pad.bottom }));
const totals = computed(() => props.items.map((item) => (props.mode === 'stack' ? (item.values ?? []).reduce((a, b) => a + (Number(b) || 0), 0) : Number(item.value) || 0)));
const axis = computed(() => axisOf(props.mode === 'area' ? [Math.max(0, ...totals.value)] : totals.value));
const span = computed(() => (axis.value.max - axis.value.min) || 1);
const y = (value) => pad.top + inner.value.h - ((value - axis.value.min) / span.value) * inner.value.h;
const ticks = computed(() => [0, 1, 2, 3, 4].map((i) => axis.value.min + (span.value * i) / 4));
const slot = computed(() => inner.value.w / Math.max(1, props.items.length));
const barW = computed(() => Math.min(54, slot.value * 0.62));
const cx = (i) => pad.left + slot.value * i + slot.value / 2;

const bars = computed(() => props.items.map((item, i) => {
    const value = totals.value[i];
    const top = y(Math.max(value, 0));
    const base = y(Math.max(axis.value.min, 0));
    const parts = [];
    if (props.mode === 'stack') {
        let running = 0;
        (item.values ?? []).forEach((v, s) => {
            const amount = Number(v) || 0;
            if (amount > 0) parts.push({ s, y: y(running + amount), h: y(running) - y(running + amount) });
            running += amount;
        });
    }

    return { ...item, i, value, x: cx(i) - barW.value / 2, y: value >= 0 ? top : base, h: Math.max(value === 0 ? 0 : 1, Math.abs(base - y(value))), negative: value < 0, parts, labelY: value >= 0 ? top - 6 : y(value) + 14 };
}));
const line = computed(() => props.items.map((item, i) => `${cx(i).toFixed(1)},${y(totals.value[i]).toFixed(1)}`).join(' '));
const area = computed(() => (props.items.length ? `${cx(0).toFixed(1)},${y(0).toFixed(1)} ${line.value} ${cx(props.items.length - 1).toFixed(1)},${y(0).toFixed(1)}` : ''));
const description = computed(() => props.items.map((item, i) => `${item.label}: ${full(totals.value[i])}${item.note ? ` (${item.note})` : ''}`).join('; '));
</script>

<template>
    <div ref="box" class="sc-box" :data-mode="mode">
        <p v-if="!items.length" class="sc-empty">{{ empty }}</p>
        <template v-else>
            <svg class="sc" :class="`sc-t${tone}`" dir="ltr" :viewBox="`0 0 ${width} ${height}`" :width="width" :height="height" role="img" :aria-label="label || undefined">
                <title v-if="label">{{ label }}</title>
                <desc>{{ description }}</desc>
                <g class="sc-grid">
                    <g v-for="tick in ticks" :key="tick">
                        <line :x1="pad.left" :x2="width - pad.right" :y1="y(tick)" :y2="y(tick)" :class="{ 'sc-zero': Math.abs(tick) < 1e-9 }" />
                        <text :x="pad.left - 8" :y="y(tick) + 4" class="sc-tick">{{ show(tick) }}</text>
                    </g>
                </g>
                <template v-if="mode === 'area'">
                    <polygon :points="area" class="sc-area" />
                    <polyline :points="line" class="sc-line" />
                    <g v-for="(item, i) in items" :key="i" class="sc-hit">
                        <title>{{ item.label }}: {{ full(totals[i]) }}</title>
                        <circle :cx="cx(i)" :cy="y(totals[i])" r="4.5" class="sc-dot" />
                        <text :x="cx(i)" :y="y(totals[i]) - 10" class="sc-value">{{ show(totals[i]) }}</text>
                    </g>
                </template>
                <template v-else>
                    <g v-for="bar in bars" :key="bar.i" class="sc-hit">
                        <title>{{ bar.label }}: {{ full(bar.value) }}{{ bar.note ? ` (${bar.note})` : '' }}</title>
                        <template v-if="mode === 'stack'">
                            <rect v-for="part in bar.parts" :key="part.s" :x="bar.x" :y="part.y" :width="barW" :height="Math.max(0, part.h)" :class="`sc-s${(part.s % 6) + 1}`" />
                        </template>
                        <rect v-else :x="bar.x" :y="bar.y" :width="barW" :height="bar.h" rx="4" class="sc-bar" :class="{ 'sc-neg': bar.negative }" />
                        <text v-if="bar.value !== 0" :x="bar.x + barW / 2" :y="bar.labelY" class="sc-value">{{ show(bar.value) }}</text>
                    </g>
                </template>
                <g class="sc-axis">
                    <template v-for="(item, i) in items" :key="i">
                        <text :x="cx(i)" :y="height - pad.bottom + 18" class="sc-year">{{ item.label }}</text>
                        <text v-if="item.note" :x="cx(i)" :y="height - pad.bottom + 33" class="sc-note" :class="{ 'sc-down': String(item.note).trim().startsWith('-') }">{{ item.note }}</text>
                    </template>
                </g>
            </svg>
            <ul v-if="mode === 'stack' && series.length" class="sc-legend" data-legend>
                <li v-for="(each, s) in series" :key="each.key"><span :class="`sc-sw sc-s${(s % 6) + 1}`" aria-hidden="true" />{{ each.label }}</li>
            </ul>
        </template>
    </div>
</template>
