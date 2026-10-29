<!--
  CFO Hotels — Gaming revenue ( /studies/{id}/casinos )
  Location: resources/js/Pages/Studies/Casinos.vue
  Four numbered sections: 1 Facilities and projection method · 2 Charges per guest · 3 Yearly figures (the ones the chosen method needs) · 4 When the money is collected.
  Each gaming facility chooses one of three ways to work out its sales. The number of facilities and their capacity come from the study.
  Server side: App\Http\Controllers\Hospitality\CasinosController (rules: App\Http\Requests\Revenue\CasinosRevenueRequest).
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
    methods: { type: Array, required: true },
    form: { type: Object, required: true },
    aids: { type: Object, default: null },
});

const { t } = useI18n();
const canEdit = computed(() => !props.readOnly && ['owner', 'admin', 'edit'].includes(props.access));
const locked = computed(() => !canEdit.value);
const form = useForm(JSON.parse(JSON.stringify(props.form)));

const currencyOptions = computed(() => props.currencies.map((c) => ({ value: c.id, text: c.name })));
const currencyName = (id) => props.currencies.find((c) => c.id === id)?.name ?? '';
const methodOptions = computed(() => props.methods.map((m) => ({ value: m, text: t(`revenue.casinos.methods.${m}`) })));
const collectionChoices = computed(() => ['general', 'per_facility'].map((v) => ({ value: v, title: t(`revenue.casinos.collection.${v}`), text: t(`revenue.casinos.collection.${v}Text`) })));
const several = computed(() => form.facilities.length > 1);

// which yearly figures a method needs
const needs = (facility) => ({
    capture: facility.method === 'guest_capture_casino_charges_per_guest_method',
    target: facility.method === 'guest_count_target_per_day_charges_per_guest_method',
    rooms: facility.method === 'percentage_from_rooms_revenue',
});

function sameMethodForAll(method) {
    form.facilities.forEach((f) => { f.method = method; });
}

const sections = computed(() => [
    { id: 'gm-facilities', title: t('revenue.casinos.facilities.title'), state: '' },
    { id: 'gm-value', title: t('revenue.casinos.cover.title'), state: '' },
    { id: 'gm-yearly', title: t('revenue.casinos.yearly.title'), state: '' },
    { id: 'gm-collect', title: t('revenue.collection.title'), state: '' },
]);

const problems = computed(() => Object.keys(form.errors).length);
const errorList = computed(() => [...new Set(Object.values(form.errors))].slice(0, 6));

// after = where the person goes once it is saved: 'stay' (this page) or 'next' (the next page of the study, in the order of the previous application)
function save(after = 'stay') {
    if (!canEdit.value) return;
    form.transform((data) => ({ ...data, after })).put(route('studies.casinos.update', props.study.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('revenue.casinos.pageTitle')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('revenue.casinos.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="casinos" />

        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="problems" class="note bad" role="alert">
            <b>{{ t('studies.fixErrors', { count: problems }) }}</b>
            <ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul>
        </div>

        <form class="sf" novalidate data-casinos-form @submit.prevent="save()">
            <SectionNav :items="sections" :title="t('studies.sections.title')">
                <template #summary>
                    <span>{{ t('revenue.casinos.summary.facilities') }} <b dir="ltr">{{ form.facilities.length }}</b></span>
                    <span>{{ t('revenue.casinos.summary.seats') }} <b dir="ltr">{{ form.facilities.reduce((s, f) => s + f.capacity, 0) }}</b></span>
                </template>
            </SectionNav>

            <div class="sf-main">
                <!-- 1 · facilities and method -->
                <SectionCard id="gm-facilities" :number="1" :title="t('revenue.casinos.facilities.title')" :text="t('revenue.casinos.facilities.text')">
                    <p v-if="total" class="mute">{{ t('revenue.casinos.totalMode') }}</p>
                    <div class="rv-cards">
                        <article v-for="(f, i) in form.facilities" :key="f.key" class="rv-room" data-facility>
                            <header>
                                <h4><AppIcon name="dice" :size="16" />{{ f.name }}</h4>
                                <p class="rv-facts">
                                    <span>{{ t('revenue.casinos.facilities.count') }} <b dir="ltr">{{ f.count }}</b></span>
                                    <span>{{ t('revenue.casinos.facilities.seatsEach') }} <b dir="ltr">{{ f.cover }}</b></span>
                                    <span>{{ t('revenue.casinos.facilities.seatsTotal') }} <b dir="ltr">{{ f.capacity }}</b></span>
                                </p>
                            </header>
                            <FormField :label="t('revenue.casinos.method')" :for="`gm-method-${i}`" required :hint="t('revenue.casinos.methodHint')" :error="form.errors[`facilities.${i}.method`]">
                                <SelectField :id="`gm-method-${i}`" v-model="f.method" :options="methodOptions" :disabled="locked" />
                            </FormField>
                            <button v-if="several && !locked" type="button" class="rv-link" @click="sameMethodForAll(f.method)">{{ t('revenue.casinos.sameForAll') }}</button>
                        </article>
                    </div>
                </SectionCard>

                <!-- 2 · value of a cover -->
                <SectionCard id="gm-value" :number="2" :title="t('revenue.casinos.cover.title')" :text="t('revenue.casinos.cover.text')">
                    <div class="rv-cards">
                        <article v-for="(f, i) in form.facilities" :key="f.key" class="rv-room">
                            <header><h4><AppIcon name="dice" :size="16" />{{ f.name }}</h4></header>
                            <p v-if="f.method === 'percentage_from_rooms_revenue'" class="mute" data-no-value>{{ t('revenue.casinos.cover.notNeeded') }}</p>
                            <template v-else>
                            <div class="sf-grid">
                                <div class="c2">
                                    <FormField :label="t('revenue.casinos.cover.currency')" :for="`gm-cur-${i}`" required :error="form.errors[`facilities.${i}.currency`]">
                                        <SelectField :id="`gm-cur-${i}`" v-model="f.currency" :options="currencyOptions" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c2">
                                    <FormField :label="t('revenue.casinos.cover.perGuest')" :for="`gm-val-${i}`" required :error="form.errors[`facilities.${i}.value`]">
                                        <NumberField :id="`gm-val-${i}`" v-model="f.value" :decimals="2" :min="0" :unit="currencyName(f.currency)" :invalid="!!form.errors[`facilities.${i}.value`]" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c2">
                                    <FormField :label="t('revenue.casinos.cover.escalation')" :for="`gm-esc-${i}`" required :hint="t('revenue.casinos.cover.escalationHint', { date: estimationDate })" :error="form.errors[`facilities.${i}.escalation`]">
                                        <NumberField :id="`gm-esc-${i}`" v-model="f.escalation" :decimals="2" :min="0" :max="100" unit="%" :invalid="!!form.errors[`facilities.${i}.escalation`]" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c2">
                                    <FormField :label="t('revenue.casinos.cover.annual')" :for="`gm-ann-${i}`" required :hint="t('revenue.casinos.cover.annualHint')" :error="form.errors[`facilities.${i}.annual`]">
                                        <NumberField :id="`gm-ann-${i}`" v-model="f.annual" :decimals="2" :min="0" :max="100" unit="%" :invalid="!!form.errors[`facilities.${i}.annual`]" :disabled="locked" />
                                    </FormField>
                                </div>
                            </div>
                            <p class="rv-result" data-value-op><span>{{ t('revenue.casinos.cover.atOperation') }}</span> <b dir="ltr">{{ money(adrAtOperation(f.value, f.escalation, daysDifference)) }} {{ currencyName(f.currency) }}</b></p>
                            </template>
                        </article>
                    </div>
                </SectionCard>

                <!-- 3 · yearly figures -->
                <SectionCard id="gm-yearly" :number="3" :title="t('revenue.casinos.yearly.title')" :text="t('revenue.casinos.yearly.text')">
                    <article v-for="(f, i) in form.facilities" :key="f.key" class="rv-yearly" data-yearly>
                        <h4 class="sf-sub"><AppIcon name="dice" :size="15" />{{ f.name }} <small>{{ t(`revenue.casinos.methods.${f.method}`) }}</small></h4>
                        <template v-if="needs(f).capture">
                            <YearGrid :rows="[{ key: 'x', label: t('revenue.casinos.yearly.capture') }]" :years="years" :values="f.capture" single suffix="%" :max="100" :disabled="locked" :errors="form.errors" :error-prefix="`facilities.${i}.capture`">
                                <template v-if="aids" #foot><AidRow :label="t('revenue.aids.guests')" :years="years" :values="aids.guests" /></template>
                            </YearGrid>
                        </template>
                        <template v-if="needs(f).target">
                            <YearGrid :rows="[{ key: 'x', label: t('revenue.casinos.yearly.target') }]" :years="years" :values="f.target" single :disabled="locked" :errors="form.errors" :error-prefix="`facilities.${i}.target`" />
                            <p class="mute rv-cap">{{ t('revenue.casinos.yearly.targetHint', { seats: f.capacity }) }}</p>
                        </template>
                        <template v-if="needs(f).rooms">
                            <YearGrid :rows="[{ key: 'x', label: t('revenue.casinos.yearly.rooms') }]" :years="years" :values="f.rooms" single suffix="%" :max="1000" :disabled="locked" :errors="form.errors" :error-prefix="`facilities.${i}.rooms`">
                                <template v-if="aids" #foot><AidRow :label="t('revenue.aids.roomsRevenue')" :years="years" :values="aids.roomsRevenue" /></template>
                            </YearGrid>
                            <p class="mute rv-cap">{{ t('revenue.casinos.yearly.roomsHint') }}</p>
                        </template>
                    </article>
                </SectionCard>

                <!-- 4 · collection -->
                <SectionCard id="gm-collect" :number="4" :title="t('revenue.collection.title')" :text="t('revenue.collection.text')">
                    <ChoiceCards v-if="!total" v-model="form.collection.mode" :options="collectionChoices" :legend="t('revenue.collection.title')" :disabled="locked" />
                    <CollectionTerms v-if="total || form.collection.mode === 'general'" v-model="form.collection.general" :disabled="locked" :errors="form.errors" error-prefix="collection.general" />
                    <div v-else class="rv-per-channel">
                        <section v-for="(f, i) in form.facilities" :key="f.key" class="rv-chan">
                            <h4>{{ f.name }}</h4>
                            <CollectionTerms v-model="f.terms" :disabled="locked" :errors="form.errors" :error-prefix="`facilities.${i}.terms`" />
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
