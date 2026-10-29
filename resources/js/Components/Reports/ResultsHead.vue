<!--
  CFO Hotels — ResultsHead (the top of a results page: coloured band, key figures and the table options)
  Location: resources/js/Components/Reports/ResultsHead.vue
  <ResultsHead family="revenue" :title icon="trend" :figures="[{ key, label, text, negative, spark }]" v-model:heat v-model:dense />
  The same band, key-figure cards and options as the financial statements, so every results page looks alike.
-->
<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import { useI18n } from '@/lang/i18n';

defineProps({
    family: { type: String, default: 'results' },
    title: { type: String, required: true },
    icon: { type: String, default: 'table' },
    figures: { type: Array, default: () => [] },
    options: { type: Boolean, default: true },
});
const heat = defineModel('heat', { type: Boolean, default: true });
const dense = defineModel('dense', { type: Boolean, default: false });
const { t } = useI18n();
</script>

<template>
    <header class="st-band" :data-family="family">
        <div class="st-band-title">
            <span class="st-band-icon" aria-hidden="true"><AppIcon :name="icon" :size="20" /></span>
            <div>
                <small>{{ t('expenses.groups.results') }}</small>
                <h3>{{ title }}</h3>
            </div>
        </div>
    </header>

    <ul v-if="figures.length" class="st-kpis rp-kpis" data-key-figures :aria-label="t('fin.keyFigures')">
        <li v-for="f in figures" :key="f.key" :data-kpi="f.key">
            <small>{{ f.label }}</small>
            <strong dir="ltr" :class="{ 'rp-neg': f.negative }">{{ f.text }}</strong>
            <svg v-if="f.spark" class="rp-kspark" viewBox="0 0 64 18" aria-hidden="true"><polyline :points="f.spark" /></svg>
        </li>
    </ul>

    <div v-if="options" class="st-bar-tools">
        <label class="st-toggle"><input v-model="heat" type="checkbox"><AppIcon name="heat" :size="14" />{{ t('fin.heat') }}</label>
        <label class="st-toggle"><input v-model="dense" type="checkbox"><AppIcon name="rows" :size="14" />{{ t('fin.dense') }}</label>
    </div>
</template>
