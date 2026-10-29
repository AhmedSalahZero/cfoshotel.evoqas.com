<!--
  CFO Hotels — Compare between two studies ( /studies/compare?first=&second= )
  Location: resources/js/Pages/Studies/Compare.vue
  Read only. Two studies side by side: the investment feasibility results with their variance, then revenues, gross profit, EBITDA, EBIT, EBT and
  net profit year by year (figures in thousands), each with the variance amount and the variance %.
  Server side: App\Http\Controllers\Hospitality\ComparisonController (figures: App\Services\Reports\ComparisonService).
-->
<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';
import { slug } from '@/Utils/reports';
import { yearBands, yearParity } from '@/Utils/statements';

const props = defineProps({
    action: { type: String, default: '' },
    studiesUrl: { type: String, default: '' },
    studies: { type: Array, default: () => [] },
    first: { type: Number, default: null },
    second: { type: Number, default: null },
    error: { type: String, default: null },
    result: { type: Object, default: null },
});

const { t } = useI18n();
const tr = (key, fallback) => {
    const value = t(key);

    return value === key ? fallback : value;
};
const one = ref(props.first ?? '');
const two = ref(props.second ?? '');
const ready = computed(() => one.value !== '' && two.value !== '' && one.value !== two.value);
const run = () => { if (ready.value) router.get(props.action, { first: one.value, second: two.value }, { preserveScroll: true }); };
const swap = () => { [one.value, two.value] = [two.value, one.value]; };
const nameOf = (study) => (study.property ? `${study.name} — ${study.property}` : study.name);

const total = (values) => values.reduce((a, b) => a + (Number(b) || 0), 0);
const money = (n) => Math.round(n).toLocaleString('en-US');
// the last column of a card: the sum of the years; for the % row, the variance of the two sums
const sums = (card) => {
    const [a, b, v] = card.rows;
    const sa = total(a.values);
    const sb = total(b.values);

    return { first: money(sa), second: money(sb), variance: money(total(v.values)), percent: `${(sb ? (sa / sb - 1) * 100 : 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} %` };
};
const bandsOf = (card) => yearBands(card.columns);
const parityOf = (card) => yearParity(card.columns);
const negative = (text) => typeof text === 'string' && text.startsWith('-');
const rowLabel = (card, row) => (row.key === 'variance' ? t('cmp.variance') : row.key === 'percent' ? t('cmp.variancePercent') : row.label);
</script>

<template>
    <AppLayout :title="t('cmp.title')" active="studies" accent="blue">
        <div class="pagehead">
            <div>
                <h2>{{ t('cmp.title') }}</h2>
                <p class="lead mb0">{{ t('cmp.lead') }}</p>
            </div>
            <Link v-if="studiesUrl" :href="studiesUrl" class="btn btn-line"><AppIcon name="back" :size="15" />{{ t('cmp.back') }}</Link>
        </div>

        <form class="cp-pick" data-picker @submit.prevent="run">
            <label>
                <span>{{ t('cmp.first') }}</span>
                <select v-model="one" data-first>
                    <option value="" disabled>{{ t('cmp.choose') }}</option>
                    <option v-for="s in studies" :key="s.id" :value="s.id">{{ nameOf(s) }}</option>
                </select>
            </label>
            <button type="button" class="cp-swap" :title="t('cmp.swap')" :aria-label="t('cmp.swap')" @click="swap"><AppIcon name="rows" :size="16" /></button>
            <label>
                <span>{{ t('cmp.second') }}</span>
                <select v-model="two" data-second>
                    <option value="" disabled>{{ t('cmp.choose') }}</option>
                    <option v-for="s in studies" :key="s.id" :value="s.id">{{ nameOf(s) }}</option>
                </select>
            </label>
            <button type="submit" class="btn" :disabled="!ready" data-run>{{ t('cmp.run') }}</button>
        </form>

        <div v-if="error" class="note bad" role="alert" data-error><AppIcon name="alert" :size="14" /> {{ error === 'same' ? t('cmp.same') : t('cmp.notCalculated') }}</div>
        <div v-else-if="!result" class="note" role="status" data-pick><AppIcon name="info" :size="14" /> {{ t('cmp.pick') }}</div>

        <template v-else>
            <section class="st-card cp-card" data-investment>
                <header class="st-head"><h3><span class="st-dot" aria-hidden="true" />{{ t('cmp.investment') }}</h3></header>
                <div class="st-scroll" role="region" tabindex="0" :aria-label="t('cmp.investment')">
                    <table class="st-table cp-table">
                        <thead>
                            <tr class="st-months">
                                <th class="st-name" scope="col">{{ t('cmp.item') }}</th>
                                <th scope="col">{{ result.names[0] }}</th>
                                <th scope="col">{{ result.names[1] }}</th>
                                <th scope="col">{{ t('cmp.variance') }}</th>
                                <th scope="col">{{ t('cmp.variancePercent') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in result.investment" :key="row.key" :data-line="slug(row.key)">
                                <th scope="row" class="st-name"><span class="st-bar" aria-hidden="true" /><span class="st-label">{{ tr(`fin.names.${slug(row.title)}`, row.title) }}</span></th>
                                <td dir="ltr">{{ row.text[0] }}</td>
                                <td dir="ltr">{{ row.text[1] }}</td>
                                <td dir="ltr" class="cp-var" :class="{ 'st-neg': negative(row.text[2]) }">{{ row.text[2] }}</td>
                                <td dir="ltr" class="cp-var" :class="{ 'st-neg': negative(row.text[3]) }">{{ row.text[3] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-for="card in result.cards" :key="card.key" class="st-card cp-card" :data-card="card.key">
                <header class="st-head">
                    <h3><span class="st-dot" aria-hidden="true" />{{ t(`cmp.cards.${card.key}`) }}</h3>
                    <span class="st-chip">{{ t('cmp.figures', { currency: result.currency }) }}</span>
                </header>
                <div class="st-scroll" role="region" tabindex="0" :aria-label="t(`cmp.cards.${card.key}`)">
                    <table class="st-table cp-table">
                        <thead>
                            <tr class="st-years">
                                <th class="st-name" rowspan="2" scope="col">{{ t('cmp.item') }}</th>
                                <th v-for="(band, i) in bandsOf(card)" :key="i" :colspan="band.span" scope="colgroup" :class="{ 'st-odd': band.odd }" dir="ltr">{{ band.year }}</th>
                                <th class="st-sumh" rowspan="2" scope="col">{{ t('stmt.table.summary') }}</th>
                            </tr>
                            <tr class="st-months">
                                <th v-for="(column, i) in card.columns" :key="i" scope="col" :class="{ 'st-odd': parityOf(card)[i] }" dir="ltr">{{ column.label }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in card.rows" :key="row.key" :class="`cp-${row.key}`" :data-line="row.key">
                                <th scope="row" class="st-name"><span class="st-bar" aria-hidden="true" /><span class="st-label">{{ rowLabel(card, row) }}</span></th>
                                <td v-for="(text, i) in row.text" :key="i" dir="ltr" :class="{ 'st-odd': parityOf(card)[i], 'st-neg': negative(text) }">{{ text }}</td>
                                <td class="st-sum" dir="ltr" :class="{ 'st-neg': negative(sums(card)[row.key]) }">{{ sums(card)[row.key] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </AppLayout>
</template>
