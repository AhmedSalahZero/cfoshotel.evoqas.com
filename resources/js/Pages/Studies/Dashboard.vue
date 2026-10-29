<!--
  CFO Hotels — Study dashboard ( /studies/{id}/dashboard )
  Location: resources/js/Pages/Studies/Dashboard.vue
  Read only. The charts of the previous application's study dashboard (revenue streams, department breakdown, ADR / RevPAR / occupancy, gross profit,
  profitability) and its investment feasibility results, drawn without a chart library (Components/Charts/SeriesChart.vue).
  The revenue stream and the gross profit shown are chosen at the top of their charts (?revenue_stream_type= and ?gross_profit_type=).
  Server side: App\Http\Controllers\Hospitality\DashboardController (figures: App\Services\Reports\StudyAnalysis).
-->
<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import SeriesChart from '@/Components/Charts/SeriesChart.vue';
import StudyNotices from '@/Components/Studies/StudyNotices.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';
import { CARD_ICONS, chartItems, stackItems } from '@/Utils/dashboard';
import { slug } from '@/Utils/reports';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    notices: { type: Object, default: () => ({}) },
    calculated: { type: Boolean, default: false },
    currency: { type: String, default: '' },
    streams: { type: Array, default: () => [] },
    profits: { type: Array, default: () => [] },
    selected: { type: Object, required: true },
    compareUrl: { type: String, default: '' },
    dashboard: { type: Object, default: null },
});

const { t } = useI18n();
const tr = (key, fallback) => {
    const value = t(key);

    return value === key ? fallback : value;
};

const stream = ref(props.selected.revenue_stream_type);
const profit = ref(props.selected.gross_profit_type);
const here = computed(() => props.menu.find((item) => item.key === 'dashboard')?.href ?? '');
const show = () => router.get(here.value, { revenue_stream_type: stream.value, gross_profit_type: profit.value }, { preserveScroll: true, preserveState: true });

const d = computed(() => props.dashboard ?? {});
const charts = computed(() => ({
    revenue: chartItems(d.value.revenue), revenueAccumulated: chartItems(d.value.revenueAccumulated), breakdown: stackItems(d.value.breakdown),
    adr: chartItems(d.value.adr), revpar: chartItems(d.value.revpar), occupancy: chartItems(d.value.occupancy),
    grossProfit: chartItems(d.value.grossProfit), grossProfitAccumulated: chartItems(d.value.grossProfitAccumulated),
    ebitda: chartItems(d.value.ebitda), ebit: chartItems(d.value.ebit), ebt: chartItems(d.value.ebt), netProfit: chartItems(d.value.netProfit),
}));
const series = computed(() => ['rooms', 'foods', 'gaming', 'meetings', 'others'].map((key) => ({ key, label: t(`board.series.${key}`) })));
const cardTitle = (title) => tr(`fin.names.${slug(title)}`, title);
const empty = computed(() => t('board.noData'));
</script>

<template>
    <AppLayout :title="t('board.title')" active="studies" accent="orange">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('board.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="dashboard" />

        <header class="st-band" data-family="dashboard">
            <div class="st-band-title">
                <span class="st-band-icon" aria-hidden="true"><AppIcon name="trend" :size="20" /></span>
                <div>
                    <small>{{ study.name }}</small>
                    <h3>{{ t('board.title') }}</h3>
                </div>
            </div>
            <nav v-if="compareUrl" class="st-types">
                <Link :href="compareUrl" class="st-type" data-compare>{{ t('board.compare') }}</Link>
            </nav>
        </header>

        <StudyNotices v-if="calculated" :notices="notices" />
        <div v-if="!calculated" class="note bad" role="alert" data-not-calculated>
            <AppIcon name="alert" :size="14" /> {{ t('board.notCalculated') }}
        </div>

        <template v-else>
            <section class="db-section" data-feasibility>
                <h3 class="db-title"><AppIcon name="bank" :size="18" />{{ t('board.feasibility') }}</h3>
                <ul class="db-cards">
                    <li v-for="(card, i) in dashboard.cards" :key="card.title" class="db-card" :class="`db-c${(i % 6) + 1}`" :data-card="slug(card.title)">
                        <span class="db-icon" aria-hidden="true"><AppIcon :name="CARD_ICONS[i % CARD_ICONS.length]" :size="18" /></span>
                        <small>{{ cardTitle(card.title) }}</small>
                        <strong dir="ltr">{{ card.value }}</strong>
                    </li>
                </ul>
            </section>

            <section class="db-section" data-revenue>
                <div class="db-head">
                    <h3 class="db-title"><AppIcon name="coins" :size="18" />{{ t('board.chooseStream') }}</h3>
                    <div class="db-pick">
                        <select v-model="stream" class="db-select" :aria-label="t('board.chooseStream')" data-stream @change="show">
                            <option v-for="o in streams" :key="o.value" :value="o.value">{{ t(`board.streams.${o.key}`) }}</option>
                        </select>
                        <button type="button" class="btn" data-go @click="show">{{ t('board.go') }}</button>
                    </div>
                </div>
                <div class="db-grid">
                    <article class="db-chart"><h4>{{ t('board.annualSales') }}</h4><SeriesChart mode="bar" :items="charts.revenue" :tone="1" :label="t('board.annualSales')" :empty="empty" /></article>
                    <article class="db-chart"><h4>{{ t('board.accumulatedSales') }}</h4><SeriesChart mode="area" :items="charts.revenueAccumulated" :tone="2" :label="t('board.accumulatedSales')" :empty="empty" /></article>
                </div>
            </section>

            <section class="db-section" data-breakdown>
                <h3 class="db-title"><AppIcon name="layers" :size="18" />{{ t('board.breakdown') }}</h3>
                <article class="db-chart wide"><SeriesChart mode="stack" :items="charts.breakdown" :series="series" :label="t('board.breakdown')" :empty="empty" /></article>
            </section>

            <section class="db-section" data-rooms>
                <h3 class="db-title"><AppIcon name="bed" :size="18" />{{ t('board.adrRevpar') }}</h3>
                <div class="db-grid three">
                    <article class="db-chart"><h4>{{ t('board.adr') }}</h4><SeriesChart mode="bar" :items="charts.adr" :tone="3" :label="t('board.adr')" :empty="empty" /></article>
                    <article class="db-chart"><h4>{{ t('board.revpar') }}</h4><SeriesChart mode="bar" :items="charts.revpar" :tone="4" :label="t('board.revpar')" :empty="empty" /></article>
                    <article class="db-chart"><h4>{{ t('board.occupancy') }}</h4><SeriesChart mode="bar" format="percent" :items="charts.occupancy" :tone="5" :label="t('board.occupancy')" :empty="empty" /></article>
                </div>
            </section>

            <section class="db-section" data-gross>
                <div class="db-head">
                    <h3 class="db-title"><AppIcon name="trend" :size="18" />{{ t('board.chooseProfit') }}</h3>
                    <div class="db-pick">
                        <select v-model="profit" class="db-select" :aria-label="t('board.chooseProfit')" data-profit @change="show">
                            <option v-for="o in profits" :key="o.value" :value="o.value">{{ t(`board.profits.${o.key}`) }}</option>
                        </select>
                        <button type="button" class="btn" data-go-profit @click="show">{{ t('board.go') }}</button>
                    </div>
                </div>
                <div class="db-grid">
                    <article class="db-chart"><h4>{{ t('board.annualGrossProfit') }}</h4><SeriesChart mode="bar" :items="charts.grossProfit" :tone="2" :label="t('board.annualGrossProfit')" :empty="empty" /></article>
                    <article class="db-chart"><h4>{{ t('board.accumulatedGrossProfit') }}</h4><SeriesChart mode="area" :items="charts.grossProfitAccumulated" :tone="3" :label="t('board.accumulatedGrossProfit')" :empty="empty" /></article>
                </div>
            </section>

            <section class="db-section" data-profitability>
                <h3 class="db-title"><AppIcon name="star" :size="18" />{{ t('board.profitability') }}</h3>
                <p class="db-hint">{{ t('board.profitHint') }}</p>
                <div class="db-grid">
                    <article class="db-chart"><h4>{{ t('board.ebitda') }}</h4><SeriesChart mode="bar" :items="charts.ebitda" :tone="1" :label="t('board.ebitda')" :empty="empty" /></article>
                    <article class="db-chart"><h4>{{ t('board.ebit') }}</h4><SeriesChart mode="bar" :items="charts.ebit" :tone="6" :label="t('board.ebit')" :empty="empty" /></article>
                    <article class="db-chart"><h4>{{ t('board.ebt') }}</h4><SeriesChart mode="bar" :items="charts.ebt" :tone="4" :label="t('board.ebt')" :empty="empty" /></article>
                    <article class="db-chart"><h4>{{ t('board.netProfit') }}</h4><SeriesChart mode="bar" :items="charts.netProfit" :tone="2" :label="t('board.netProfit')" :empty="empty" /></article>
                </div>
            </section>
        </template>
    </AppLayout>
</template>
