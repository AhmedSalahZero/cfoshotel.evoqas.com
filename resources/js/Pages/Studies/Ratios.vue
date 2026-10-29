<!--
  CFO Hotels — Ratio analysis ( /studies/{id}/reports/ratios )
  Location: resources/js/Pages/Studies/Ratios.vue
  Read only. The profitability, liquidity, efficiency and leverage ratios of the previous application, year by year. A group can be closed to its heading.
  Server side: App\Http\Controllers\Hospitality\ReportsController (figures: App\Services\Reports\ReportService::ratioTable).
-->
<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import StudyNotices from '@/Components/Studies/StudyNotices.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';
import { slug } from '@/Utils/reports';
import { heat as heatOf, sparkPoints, yearBands, yearParity } from '@/Utils/statements';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    notices: { type: Object, default: () => ({}) },
    report: { type: String, default: 'ratios' },
    links: { type: Object, default: () => ({}) },
    calculated: { type: Boolean, default: false },
    columns: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
});

const { t } = useI18n();
const tr = (key, fallback) => {
    const value = t(key);

    return value === key ? fallback : value;
};
const name = (label) => tr(`fin.names.${slug(label)}`, label);
const description = (text) => tr(`fin.desc.${slug(text)}`, text);

const closed = ref(new Set());
const toggle = (key) => {
    const next = new Set(closed.value);
    next.has(key) ? next.delete(key) : next.add(key);
    closed.value = next;
};
const heat = ref(true);
const bands = computed(() => yearBands(props.columns));
const parity = computed(() => yearParity(props.columns));
const GROUP_TONES = ['k1', 'k2', 'k3', 'k4'];
const rows = (group) => group.rows.map((row) => ({ ...row, strength: heatOf(row.values), spark: sparkPoints(row.values) }));
const negative = (text) => typeof text === 'string' && text.startsWith('-');
const title = computed(() => t('fin.titles.ratios'));
</script>

<template>
    <AppLayout :title="title" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('fin.lead.ratios') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="financial.ratios" />

        <header class="st-band" data-family="ratios">
            <div class="st-band-title">
                <span class="st-band-icon" aria-hidden="true"><AppIcon name="star" :size="20" /></span>
                <div>
                    <small>{{ t('expenses.groups.financial') }}</small>
                    <h3>{{ title }}</h3>
                </div>
            </div>
        </header>

        <StudyNotices v-if="calculated" :notices="notices" />
        <div v-if="!calculated" class="note bad" role="alert" data-not-calculated>
            <AppIcon name="alert" :size="14" /> {{ t('fin.notCalculated') }}
        </div>

        <template v-else>
            <div class="st-bar-tools">
                <label class="st-toggle"><input v-model="heat" type="checkbox"><AppIcon name="heat" :size="14" />{{ t('fin.heat') }}</label>
            </div>
            <section class="st-card rp-card" :aria-label="title" data-ratios>
                <div class="st-scroll st-tall" role="region" tabindex="0" :aria-label="title">
                    <table class="st-table rp-table rp-ratios">
                        <thead>
                            <tr class="st-years">
                                <th class="st-name" rowspan="2" scope="col">{{ t('fin.itemName') }}</th>
                                <th class="rp-descr" rowspan="2" scope="col">{{ t('fin.description') }}</th>
                                <th v-for="(band, i) in bands" :key="i" :colspan="band.span" scope="colgroup" :class="{ 'st-odd': band.odd }" dir="ltr">{{ band.year }}</th>
                            </tr>
                            <tr class="st-months">
                                <th v-for="(column, i) in columns" :key="i" scope="col" :class="{ 'st-odd': parity[i] }" dir="ltr">{{ column.label }}</th>
                            </tr>
                        </thead>
                        <tbody v-for="(group, g) in groups" :key="group.key" :data-group="group.key" :class="`rp-g${g % GROUP_TONES.length}`">
                            <tr class="rp-l0 rp-block rp-grouphead">
                                <th scope="row" class="st-name" :colspan="columns.length + 2">
                                    <button type="button" class="rp-arrow" :aria-expanded="!closed.has(group.key)" :aria-label="closed.has(group.key) ? t('fin.showLines') : t('fin.hideLines')" @click="toggle(group.key)">
                                        <AppIcon name="chevron" :size="14" class="rp-chev" :class="{ 'rp-shut': closed.has(group.key) }" />
                                    </button>
                                    <span class="st-bar" aria-hidden="true" />
                                    <span class="st-label rp-link" @click="toggle(group.key)">{{ name(group.label) }}</span>
                                </th>
                            </tr>
                            <template v-if="!closed.has(group.key)">
                                <tr v-for="row in rows(group)" :key="row.key" class="rp-l2" :data-line="row.key">
                                    <th scope="row" class="st-name">
                                        <span class="st-bar" aria-hidden="true" />
                                        <span class="st-label">{{ name(row.label) }}</span>
                                        <svg v-if="row.spark" class="st-spark" viewBox="0 0 64 18" aria-hidden="true"><polyline :points="row.spark" /></svg>
                                    </th>
                                    <td class="rp-descr">{{ description(row.description) }}</td>
                                    <td
                                        v-for="(text, i) in row.text" :key="i" dir="ltr"
                                        :class="{ 'st-odd': parity[i], 'st-neg': negative(text) }"
                                        :style="heat ? { '--h': row.strength[i].toFixed(2) } : undefined"
                                    >{{ text }}<small v-if="row.mark" class="rp-mark">{{ row.mark }}</small></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </AppLayout>
</template>
