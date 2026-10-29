<!--
  CFO Hotels — Revenue results ( /studies/{id}/revenue )
  Location: resources/js/Pages/Studies/Revenue.vue
  Read only. The revenue of each year by stream and the hotel total, with the nights and guests behind the rooms figure, as worked out from
  the inputs of the other pages. A bar for each year shows the mix of streams; the table under it holds the exact figures.
  Server side: App\Http\Controllers\Hospitality\RevenueResultsController (figures: App\Services\Revenue\RevenueFiguresService).
-->
<script setup>
import { computed, ref } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import ResultsHead from '@/Components/Reports/ResultsHead.vue';
import ResultsTable from '@/Components/Reports/ResultsTable.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { money } from '@/Utils/adr';
import { useI18n } from '@/lang/i18n';
import { figure, sparkPoints } from '@/Utils/statements';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    years: { type: Array, default: () => [] },
    calculated: { type: Boolean, default: false },
    revenue: { type: Object, default: null },
    operating: { type: Object, default: null },
    streams: { type: Array, default: () => [] },
});

const { t } = useI18n();
const heat = ref(true);
const dense = ref(false);
const icons = { rooms: 'bed', foods: 'utensils', casinos: 'dice', meetings: 'present', others: 'spark' };

const sum = (map) => Object.values(map ?? {}).reduce((a, b) => a + (Number(b) || 0), 0);
const rows = computed(() => props.streams.map((key) => ({ key, icon: icons[key], values: props.revenue?.[key] ?? {}, total: sum(props.revenue?.[key]) })));
const grandTotal = computed(() => sum(props.revenue?.total));
const peak = computed(() => Math.max(1, ...props.years.map((y) => Number(props.revenue?.total?.[y.index]) || 0)));
const hasRevenue = computed(() => grandTotal.value > 0);

const bars = computed(() => props.years.map((y) => {
    const total = Number(props.revenue?.total?.[y.index]) || 0;
    return {
        year: y,
        total,
        height: (total / peak.value) * 100,
        parts: rows.value.map((r) => ({ key: r.key, share: total > 0 ? ((Number(r.values[y.index]) || 0) / total) * 100 : 0 })).filter((p) => p.share > 0),
    };
}));

const operatingRows = computed(() => [
    { key: 'nightsAvailable', values: props.operating?.nightsAvailable },
    { key: 'nightsPractical', values: props.operating?.nightsPractical },
    { key: 'nightsSold', values: props.operating?.nightsSold },
    { key: 'guests', values: props.operating?.guests },
    { key: 'days', values: props.operating?.days },
]);

const COLORS = { rooms: 'blue', foods: 'amber', casinos: 'cyan', meetings: 'green', others: 'sec' };
const tableRows = computed(() => rows.value.map((r) => ({ key: r.key, label: t(`revenue.results.streams.${r.key}`), values: r.values, color: COLORS[r.key], attrs: { 'data-stream': r.key } })));
const footRows = computed(() => [{ key: 'total', label: t('revenue.results.hotel'), values: props.revenue?.total ?? {}, attrs: { 'data-hotel-total': '' } }]);
const behindRows = computed(() => operatingRows.value.map((o) => ({ key: o.key, label: t(`revenue.results.operating.${o.key}`), values: o.values ?? {}, attrs: { 'data-operating': o.key } })));
const figures = computed(() => {
    const totals = props.years.map((y) => Number(props.revenue?.total?.[y.index]) || 0);
    if (!totals.length) return [];
    const best = totals.indexOf(Math.max(...totals));
    const first = totals[0];
    const last = totals[totals.length - 1];
    const growth = first > 0 ? ((last - first) / first) * 100 : 0;

    return [
        { key: 'total', label: t('rs.kpi.total'), text: figure(grandTotal.value), spark: sparkPoints(totals) },
        { key: 'peak', label: t('rs.kpi.peak'), text: `${props.years[best].year} · ${figure(totals[best])}` },
        { key: 'average', label: t('rs.kpi.average'), text: figure(grandTotal.value / totals.length) },
        { key: 'growth', label: t('rs.kpi.growth'), text: `${growth.toFixed(1)} %`, negative: growth < 0 },
    ];
});
</script>

<template>
    <AppLayout :title="t('revenue.results.pageTitle')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('revenue.results.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="revenue" />

        <div v-if="!calculated" class="note bad" role="alert" data-not-calculated>
            <AppIcon name="alert" :size="14" /> {{ t('revenue.results.notCalculated') }}
        </div>
        <div v-else-if="!hasRevenue" class="note" role="status" data-no-revenue>
            <AppIcon name="info" :size="14" /> {{ t('revenue.results.noRevenue') }}
        </div>

        <template v-if="calculated">
            <ResultsHead v-model:heat="heat" v-model:dense="dense" family="revenue" icon="trend" :title="t('revenue.results.title')" :figures="hasRevenue ? figures : []" />

            <section class="rv-results" :aria-label="t('revenue.results.title')">
                <p class="mute">{{ t('revenue.results.text') }}</p>

                <div class="rv-bars" role="img" :aria-label="t('revenue.results.chartLabel')" data-bars>
                    <div v-for="b in bars" :key="b.year.index" class="rv-bar">
                        <div class="rv-bar-col" :style="{ height: `${Math.max(b.height, b.total > 0 ? 3 : 0)}%` }">
                            <i v-for="p in b.parts" :key="p.key" :class="`rv-s-${p.key}`" :style="{ height: `${p.share}%` }" />
                        </div>
                        <span class="rv-bar-year" dir="ltr">{{ b.year.year }}</span>
                    </div>
                </div>

                <ResultsTable
                    data-revenue-table :title="t('revenue.results.title')" :years="years" :total-label="t('revenue.results.total')"
                    :sections="[{ key: 'streams', rows: tableRows }]" :foot="footRows" :heat="heat" :dense="dense"
                />
            </section>

            <section class="rv-results" :aria-label="t('revenue.results.behind')">
                <p class="mute">{{ t('revenue.results.behindText') }}</p>
                <ResultsTable
                    data-operating-table :title="t('revenue.results.behind')" :years="years" :show-total="false"
                    :sections="[{ key: 'operating', rows: behindRows }]" :heat="heat" :dense="dense"
                />
            </section>
        </template>
    </AppLayout>
</template>
