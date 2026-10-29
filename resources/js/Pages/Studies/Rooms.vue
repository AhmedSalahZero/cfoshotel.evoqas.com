<!--
  CFO Hotels — Rooms revenue ( /studies/{id}/rooms )
  Location: resources/js/Pages/Studies/Rooms.vue
  Six numbered sections: 1 Rooms and rates · 2 Exchange rate (only with an additional currency) · 3 Occupancy · 4 Seasonality ·
  5 Reservation channels (only when the study uses them) · 6 When the money is collected.
  Server side: App\Http\Controllers\Hospitality\RoomsController (rules: App\Http\Requests\Revenue\RoomsRevenueRequest).
-->
<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import ChoiceCards from '@/Components/Form/ChoiceCards.vue';
import FormField from '@/Components/Form/FormField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SectionCard from '@/Components/Form/SectionCard.vue';
import SectionNav from '@/Components/Form/SectionNav.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import CollectionTerms from '@/Components/Revenue/CollectionTerms.vue';
import MonthGrid from '@/Components/Revenue/MonthGrid.vue';
import AidRow from '@/Components/Revenue/AidRow.vue';
import YearGrid from '@/Components/Revenue/YearGrid.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { adrAtOperation, money } from '@/Utils/adr';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    readOnly: { type: Boolean, default: false },
    years: { type: Array, required: true },
    total: { type: Boolean, default: false },
    daysDifference: { type: Number, default: 0 },
    estimationDate: { type: String, default: '' },
    currencies: { type: Array, default: () => [] },
    additionalCurrency: { type: String, default: null },
    mainCurrency: { type: String, default: '' },
    form: { type: Object, required: true },
    aids: { type: Object, default: null },
});

const { t } = useI18n();
const canEdit = computed(() => !props.readOnly && ['owner', 'admin', 'edit'].includes(props.access));
const locked = computed(() => !canEdit.value);
const form = useForm(JSON.parse(JSON.stringify(props.form)));
const hasChannels = computed(() => form.channels.length > 0);

const currencyOptions = computed(() => props.currencies.map((c) => ({ value: c.id, text: c.name })));
const currencyName = computed(() => props.currencies.find((c) => c.id === form.currency)?.name ?? props.mainCurrency);
const roomRows = computed(() => form.rooms.map((r) => ({ key: r.key, label: r.name })));

const modeOptions = (group) => ['general', 'per_room'].map((v) => ({ value: v, title: t(`revenue.${group}.${v}`), text: t(`revenue.${group}.${v}Text`) }));
const intervalOptions = computed(() => ['flat', 'monthly'].map((v) => ({ value: v, title: t(`revenue.seasonality.${v}`), text: t(`revenue.seasonality.${v}Text`) })));
const modeChoices = computed(() => (props.total ? modeOptions('occupancy').slice(0, 1) : modeOptions('occupancy')));
const seasonChoices = computed(() => (props.total ? modeOptions('seasonality').slice(0, 1) : modeOptions('seasonality')));
const collectionChoices = computed(() => ['general', 'per_channel'].map((v) => ({ value: v, title: t(`revenue.collection.${v}`), text: t(`revenue.collection.${v}Text`) })));

const shareTotal = (year) => form.channels.reduce((sum, c) => sum + (Number(c.share[year]) || 0), 0);
const shareOk = (year) => Math.abs(shareTotal(year) - 100) < 0.005;

const sections = computed(() => {
    const list = [{ id: 'rv-rooms', title: t('revenue.rooms.title') }];
    if (props.additionalCurrency) list.push({ id: 'rv-exchange', title: t('revenue.exchange.title') });
    list.push({ id: 'rv-occupancy', title: t('revenue.occupancy.title') }, { id: 'rv-season', title: t('revenue.seasonality.title') });
    if (hasChannels.value) list.push({ id: 'rv-channels', title: t('revenue.channels.title') });
    list.push({ id: 'rv-collect', title: t('revenue.collection.title') });

    return list.map((s) => ({ ...s, state: '' }));
});
const numberOf = (id) => sections.value.findIndex((s) => s.id === id) + 1;

const problems = computed(() => Object.keys(form.errors).length);
const errorList = computed(() => [...new Set(Object.values(form.errors))].slice(0, 6));

// after = where the person goes once it is saved: 'stay' (this page) or 'next' (the next page of the study, in the order of the previous application)
function save(after = 'stay') {
    if (!canEdit.value) return;
    form.transform((data) => ({ ...data, after })).put(route('studies.rooms.update', props.study.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('revenue.rooms.pageTitle')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('revenue.rooms.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="rooms" />

        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="problems" class="note bad" role="alert">
            <b>{{ t('studies.fixErrors', { count: problems }) }}</b>
            <ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul>
        </div>

        <form class="sf" novalidate data-rooms-form @submit.prevent="save()">
            <SectionNav :items="sections" :title="t('studies.sections.title')">
                <template #summary>
                    <span>{{ t('revenue.summary.currency') }} <b>{{ currencyName || '—' }}</b></span>
                    <span>{{ t('revenue.summary.rooms') }} <b dir="ltr">{{ form.rooms.reduce((s, r) => s + (Number(r.count) || 0), 0) }}</b></span>
                </template>
            </SectionNav>

            <div class="sf-main">
                <!-- 1 · rooms and rates -->
                <SectionCard id="rv-rooms" :number="numberOf('rv-rooms')" :title="t('revenue.rooms.title')" :text="t('revenue.rooms.text')">
                    <div class="sf-grid">
                        <div class="c2">
                            <FormField :label="t('revenue.rooms.currency')" for="rv-cur" required :error="form.errors.currency">
                                <SelectField id="rv-cur" v-model="form.currency" :options="currencyOptions" :disabled="locked" />
                            </FormField>
                        </div>
                    </div>
                    <div class="rv-cards">
                        <article v-for="(room, i) in form.rooms" :key="room.key" class="rv-room">
                            <header>
                                <h4><AppIcon name="bed" :size="16" />{{ room.name }}</h4>
                                <p class="rv-facts"><span><b dir="ltr">{{ room.count }}</b> {{ t('revenue.rooms.roomsN') }}</span><span><b dir="ltr">{{ money(room.nights, 0) }}</b> {{ t('revenue.rooms.nights') }}</span><span v-if="room.guests"><b dir="ltr">{{ room.guests }}</b> {{ t('revenue.rooms.guests') }}</span></p>
                            </header>
                            <div class="sf-grid">
                                <div class="c3">
                                    <FormField :label="t('revenue.rooms.adr')" :for="`rv-adr-${i}`" required :error="form.errors[`rooms.${i}.adr`]">
                                        <NumberField :id="`rv-adr-${i}`" v-model="room.adr" :decimals="2" :min="0" :unit="currencyName" :invalid="!!form.errors[`rooms.${i}.adr`]" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c3">
                                    <FormField :label="t('revenue.rooms.escalation')" :for="`rv-esc-${i}`" required :hint="t('revenue.rooms.escalationHint', { date: estimationDate })" :error="form.errors[`rooms.${i}.escalation`]">
                                        <NumberField :id="`rv-esc-${i}`" v-model="room.escalation" :decimals="2" :min="0" :max="100" unit="%" :invalid="!!form.errors[`rooms.${i}.escalation`]" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c3">
                                    <FormField :label="t('revenue.rooms.annual')" :for="`rv-ann-${i}`" required :hint="t('revenue.rooms.annualHint')" :error="form.errors[`rooms.${i}.annual`]">
                                        <NumberField :id="`rv-ann-${i}`" v-model="room.annual" :decimals="2" :min="0" :max="100" unit="%" :invalid="!!form.errors[`rooms.${i}.annual`]" :disabled="locked" />
                                    </FormField>
                                </div>
                            </div>
                            <p class="rv-result" data-adr-op><span>{{ t('revenue.rooms.adrAtOperation') }}</span> <b dir="ltr">{{ money(adrAtOperation(room.adr, room.escalation, daysDifference)) }} {{ currencyName }}</b></p>
                        </article>
                    </div>
                </SectionCard>

                <!-- 2 · exchange rate -->
                <SectionCard v-if="additionalCurrency" id="rv-exchange" :number="numberOf('rv-exchange')" :title="t('revenue.exchange.title')" :text="t('revenue.exchange.text', { from: additionalCurrency, to: mainCurrency })">
                    <YearGrid :rows="[{ key: 'x', label: t('revenue.exchange.row', { from: additionalCurrency, to: mainCurrency }) }]" :years="years" :values="form.exchange" single :disabled="locked" :errors="form.errors" error-prefix="exchange" />
                </SectionCard>

                <!-- 3 · occupancy -->
                <SectionCard id="rv-occupancy" :number="numberOf('rv-occupancy')" :title="t('revenue.occupancy.title')" :text="t('revenue.occupancy.text')">
                    <ChoiceCards v-model="form.occupancy.mode" :options="modeChoices" :legend="t('revenue.occupancy.title')" :disabled="locked" />
                    <p v-if="total" class="mute">{{ t('revenue.totalMode') }}</p>
                    <YearGrid v-if="form.occupancy.mode === 'general'" :rows="[{ key: 'g', label: t('revenue.occupancy.allRooms') }]" :years="years" :values="form.occupancy.general" single suffix="%" :max="100" :disabled="locked" :errors="form.errors" error-prefix="occupancy.general">
                        <template v-if="aids" #foot><AidRow :label="t('revenue.aids.nightsAvailable')" :years="years" :values="aids.nightsAvailable" /></template>
                    </YearGrid>
                    <YearGrid v-else :rows="roomRows" :years="years" :values="form.occupancy.perRoom" suffix="%" :max="100" :disabled="locked" :errors="form.errors" error-prefix="occupancy.perRoom">
                        <template v-if="aids" #foot><AidRow :label="t('revenue.aids.nightsAvailable')" :years="years" :values="aids.nightsAvailable" /></template>
                    </YearGrid>
                </SectionCard>

                <!-- 4 · seasonality -->
                <SectionCard id="rv-season" :number="numberOf('rv-season')" :title="t('revenue.seasonality.title')" :text="t('revenue.seasonality.text')">
                    <ChoiceCards v-model="form.seasonality.type" :options="seasonChoices" :legend="t('revenue.seasonality.title')" :disabled="locked" />
                    <h4 class="sf-sub">{{ t('revenue.seasonality.shape') }}</h4>
                    <ChoiceCards v-model="form.seasonality.interval" :options="intervalOptions" :legend="t('revenue.seasonality.shape')" :disabled="locked" />
                    <MonthGrid v-if="form.seasonality.type === 'general'" :rows="[{ key: 'g', label: t('revenue.occupancy.allRooms') }]" :values="form.seasonality.general" single :locked="form.seasonality.interval === 'flat'" :disabled="locked" :errors="form.errors" error-prefix="seasonality.general" />
                    <MonthGrid v-else :rows="roomRows" :values="form.seasonality.perRoom" :locked="form.seasonality.interval === 'flat'" :disabled="locked" :errors="form.errors" error-prefix="seasonality.perRoom" />
                </SectionCard>

                <!-- 5 · channels -->
                <SectionCard v-if="hasChannels" id="rv-channels" :number="numberOf('rv-channels')" :title="t('revenue.channels.title')" :text="t('revenue.channels.text')">
                    <h4 class="sf-sub">{{ t('revenue.channels.share') }}</h4>
                    <YearGrid :rows="form.channels.map((c, i) => ({ key: i, label: t(`studies.channels.${c.name}`) }))" :years="years" :values="Object.fromEntries(form.channels.map((c, i) => [i, c.share]))" suffix="%" :max="100" :disabled="locked" :errors="form.errors" :error-key="(r, y) => `channels.${r.key}.share.${y.index}`">
                        <template #foot>
                            <tr class="rv-sum">
                                <th scope="row" class="rv-rowhead">{{ t('revenue.channels.total') }}</th>
                                <td v-for="y in years" :key="y.index" :class="shareOk(y.index) ? 'ok' : 'bad'" data-share-total><b dir="ltr">{{ shareTotal(y.index) }}%</b></td>
                            </tr>
                        </template>
                    </YearGrid>
                    <h4 class="sf-sub">{{ t('revenue.channels.discount') }}</h4>
                    <YearGrid :rows="form.channels.map((c, i) => ({ key: i, label: t(`studies.channels.${c.name}`) }))" :years="years" :values="Object.fromEntries(form.channels.map((c, i) => [i, c.discount]))" suffix="%" :max="100" :disabled="locked" :errors="form.errors" :error-key="(r, y) => `channels.${r.key}.discount.${y.index}`" />
                </SectionCard>

                <!-- 6 · collection -->
                <SectionCard id="rv-collect" :number="numberOf('rv-collect')" :title="t('revenue.collection.title')" :text="t('revenue.collection.text')">
                    <ChoiceCards v-if="hasChannels" v-model="form.collection.mode" :options="collectionChoices" :legend="t('revenue.collection.title')" :disabled="locked" />
                    <CollectionTerms v-if="!hasChannels || form.collection.mode === 'general'" v-model="form.collection.general" :disabled="locked" :errors="form.errors" error-prefix="collection.general" />
                    <div v-else class="rv-per-channel">
                        <section v-for="(c, i) in form.channels" :key="c.name" class="rv-chan">
                            <h4>{{ t(`studies.channels.${c.name}`) }}</h4>
                            <CollectionTerms v-model="c.terms" :disabled="locked" :errors="form.errors" :error-prefix="`channels.${i}.terms`" />
                        </section>
                    </div>
                </SectionCard>

                <div v-if="canEdit" class="sf-bar" data-savebar>
                    <p class="sf-bar-msg" :class="problems ? 'bad' : (form.isDirty ? 'dirty' : 'ok')" role="status">
                        <span class="sf-bar-dot" />
                        <span v-if="form.processing">{{ t('studies.saving') }}</span>
                        <span v-else-if="problems">{{ t('studies.fixErrors', { count: problems }) }}</span>
                        <span v-else-if="form.isDirty">{{ t('studies.unsaved') }}</span>
                        <span v-else>{{ t('studies.allSaved') }}</span>
                    </p>
                    <Link :href="route('studies.edit', study.id)" class="btn btn-line">{{ t('common.cancel') }}</Link>
                    <button type="submit" class="btn btn-line" :disabled="form.processing" data-save-stay><AppIcon name="check" :size="15" />{{ t('studies.save') }}</button>
                    <button type="button" class="btn" :disabled="form.processing" data-save-next @click="save('next')">
                        <AppIcon name="check" :size="15" />{{ t('studies.saveNext') }}
                    </button>
                </div>
                <div v-else class="sf-bar">
                    <p class="sf-bar-msg"><span class="sf-bar-dot" /><span>{{ t('studies.viewOnlyBar') }}</span></p>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
