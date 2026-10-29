<!--
  CFO Hotels — Meeting spaces revenue ( /studies/{id}/meetings )
  Location: resources/js/Pages/Studies/Meetings.vue
  Five numbered sections: 1 Facilities and projection method · 2 Charges (per guest or daily rent) · 3 Yearly figures · 4 Seasonality (how the demand is spread over the months) · 5 When the money is collected.
  Each meeting facility chooses one of three ways to work out its sales. The number of facilities and their capacity come from the study.
  Server side: App\Http\Controllers\Hospitality\MeetingsController (rules: App\Http\Requests\Revenue\MeetingsRevenueRequest).
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
import Distribution from '@/Components/Revenue/Distribution.vue';
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
const methodOptions = computed(() => props.methods.map((m) => ({ value: m, text: t(`revenue.meetings.methods.${m}`) })));
const collectionChoices = computed(() => ['general', 'per_facility'].map((v) => ({ value: v, title: t(`revenue.meetings.collection.${v}`), text: t(`revenue.meetings.collection.${v}Text`) })));
const several = computed(() => form.facilities.length > 1);

// which yearly figures a method needs
const PER_GUEST = 'guest_count_charges_per_guest_occupancy_rate_method';
const DAILY_RENT = 'facility_count_daily_rent_occupancy_rate_method';
const FB = 'percentage_from_f_b_revenue';
const needs = (facility) => ({ occupancy: facility.method !== FB, fb: facility.method === FB });
const usersOf = (method) => form.facilities.filter((f) => f.method === method).map((f) => ({ key: f.key, name: f.name }));
const guestUsers = computed(() => usersOf(PER_GUEST));
const rentUsers = computed(() => usersOf(DAILY_RENT));

function sameMethodForAll(method) {
    form.facilities.forEach((f) => { f.method = method; });
}

const sections = computed(() => [
    { id: 'gm-facilities', title: t('revenue.meetings.facilities.title'), state: '' },
    { id: 'gm-value', title: t('revenue.meetings.cover.title'), state: '' },
    { id: 'gm-yearly', title: t('revenue.meetings.yearly.title'), state: '' },
    { id: 'gm-season', title: t('revenue.meetings.season.title'), state: '' },
    { id: 'gm-collect', title: t('revenue.collection.title'), state: '' },
]);

const problems = computed(() => Object.keys(form.errors).length);
const errorList = computed(() => [...new Set(Object.values(form.errors))].slice(0, 6));

// after = where the person goes once it is saved: 'stay' (this page) or 'next' (the next page of the study, in the order of the previous application)
function save(after = 'stay') {
    if (!canEdit.value) return;
    form.transform((data) => ({ ...data, after })).put(route('studies.meetings.update', props.study.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('revenue.meetings.pageTitle')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('revenue.meetings.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="meetings" />

        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="problems" class="note bad" role="alert">
            <b>{{ t('studies.fixErrors', { count: problems }) }}</b>
            <ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul>
        </div>

        <form class="sf" novalidate data-meetings-form @submit.prevent="save()">
            <SectionNav :items="sections" :title="t('studies.sections.title')">
                <template #summary>
                    <span>{{ t('revenue.meetings.summary.facilities') }} <b dir="ltr">{{ form.facilities.length }}</b></span>
                    <span>{{ t('revenue.meetings.summary.seats') }} <b dir="ltr">{{ form.facilities.reduce((s, f) => s + f.capacity, 0) }}</b></span>
                </template>
            </SectionNav>

            <div class="sf-main">
                <!-- 1 · facilities and method -->
                <SectionCard id="gm-facilities" :number="1" :title="t('revenue.meetings.facilities.title')" :text="t('revenue.meetings.facilities.text')">
                    <p v-if="total" class="mute">{{ t('revenue.meetings.totalMode') }}</p>
                    <div class="rv-cards">
                        <article v-for="(f, i) in form.facilities" :key="f.key" class="rv-room" data-facility>
                            <header>
                                <h4><AppIcon name="present" :size="16" />{{ f.name }}</h4>
                                <p class="rv-facts">
                                    <span>{{ t('revenue.meetings.facilities.count') }} <b dir="ltr">{{ f.count }}</b></span>
                                    <span>{{ t('revenue.meetings.facilities.seatsEach') }} <b dir="ltr">{{ f.cover }}</b></span>
                                    <span>{{ t('revenue.meetings.facilities.seatsTotal') }} <b dir="ltr">{{ f.capacity }}</b></span>
                                </p>
                            </header>
                            <FormField :label="t('revenue.meetings.method')" :for="`gm-method-${i}`" required :hint="t('revenue.meetings.methodHint')" :error="form.errors[`facilities.${i}.method`]">
                                <SelectField :id="`gm-method-${i}`" v-model="f.method" :options="methodOptions" :disabled="locked" />
                            </FormField>
                            <button v-if="several && !locked" type="button" class="rv-link" @click="sameMethodForAll(f.method)">{{ t('revenue.meetings.sameForAll') }}</button>
                        </article>
                    </div>
                </SectionCard>

                <!-- 2 · value of a cover -->
                <SectionCard id="gm-value" :number="2" :title="t('revenue.meetings.cover.title')" :text="t('revenue.meetings.cover.text')">
                    <div class="rv-cards">
                        <article v-for="(f, i) in form.facilities" :key="f.key" class="rv-room">
                            <header><h4><AppIcon name="present" :size="16" />{{ f.name }}</h4></header>
                            <p v-if="f.method === 'percentage_from_f_b_revenue'" class="mute" data-no-value>{{ t('revenue.meetings.cover.notNeeded') }}</p>
                            <template v-else>
                            <div class="sf-grid">
                                <div class="c2">
                                    <FormField :label="t('revenue.meetings.cover.currency')" :for="`gm-cur-${i}`" required :error="form.errors[`facilities.${i}.currency`]">
                                        <SelectField :id="`gm-cur-${i}`" v-model="f.currency" :options="currencyOptions" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c2">
                                    <FormField :label="t(f.method === 'facility_count_daily_rent_occupancy_rate_method' ? 'revenue.meetings.cover.dailyRent' : 'revenue.meetings.cover.perGuest')" :for="`gm-val-${i}`" required :error="form.errors[`facilities.${i}.value`]">
                                        <NumberField :id="`gm-val-${i}`" v-model="f.value" :decimals="2" :min="0" :unit="currencyName(f.currency)" :invalid="!!form.errors[`facilities.${i}.value`]" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c2">
                                    <FormField :label="t('revenue.meetings.cover.escalation')" :for="`gm-esc-${i}`" required :hint="t('revenue.meetings.cover.escalationHint', { date: estimationDate })" :error="form.errors[`facilities.${i}.escalation`]">
                                        <NumberField :id="`gm-esc-${i}`" v-model="f.escalation" :decimals="2" :min="0" :max="100" unit="%" :invalid="!!form.errors[`facilities.${i}.escalation`]" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c2">
                                    <FormField :label="t('revenue.meetings.cover.annual')" :for="`gm-ann-${i}`" required :hint="t('revenue.meetings.cover.annualHint')" :error="form.errors[`facilities.${i}.annual`]">
                                        <NumberField :id="`gm-ann-${i}`" v-model="f.annual" :decimals="2" :min="0" :max="100" unit="%" :invalid="!!form.errors[`facilities.${i}.annual`]" :disabled="locked" />
                                    </FormField>
                                </div>
                            </div>
                            <p class="rv-result" data-value-op><span>{{ t('revenue.meetings.cover.atOperation') }}</span> <b dir="ltr">{{ money(adrAtOperation(f.value, f.escalation, daysDifference)) }} {{ currencyName(f.currency) }}</b></p>
                            </template>
                        </article>
                    </div>
                </SectionCard>

                <!-- 3 · yearly figures -->
                <SectionCard id="gm-yearly" :number="3" :title="t('revenue.meetings.yearly.title')" :text="t('revenue.meetings.yearly.text')">
                    <article v-for="(f, i) in form.facilities" :key="f.key" class="rv-yearly" data-yearly>
                        <h4 class="sf-sub"><AppIcon name="present" :size="15" />{{ f.name }} <small>{{ t(`revenue.meetings.methods.${f.method}`) }}</small></h4>
                        <YearGrid v-if="needs(f).occupancy" :rows="[{ key: 'x', label: t('revenue.meetings.yearly.occupancy') }]" :years="years" :values="f.occupancy" single suffix="%" :max="100" :disabled="locked" :errors="form.errors" :error-prefix="`facilities.${i}.occupancy`">
                            <template v-if="aids" #foot><AidRow :label="t('revenue.aids.days')" :years="years" :values="aids.days" /></template>
                        </YearGrid>
                        <template v-if="needs(f).fb">
                            <YearGrid :rows="[{ key: 'x', label: t('revenue.meetings.yearly.fb') }]" :years="years" :values="f.fb" single suffix="%" :max="1000" :disabled="locked" :errors="form.errors" :error-prefix="`facilities.${i}.fb`">
                                <template v-if="aids" #foot><AidRow :label="t('revenue.aids.foodsRevenue')" :years="years" :values="aids.foodsRevenue" /></template>
                            </YearGrid>
                            <p class="mute rv-cap">{{ t('revenue.meetings.yearly.fbHint') }}</p>
                        </template>
                    </article>
                </SectionCard>

                <!-- 4 · seasonality -->
                <SectionCard id="gm-season" :number="4" :title="t('revenue.meetings.season.title')" :text="t('revenue.meetings.season.text')">
                    <p v-if="!guestUsers.length && !rentUsers.length" class="mute">{{ t('revenue.meetings.season.none') }}</p>
                    <section v-if="guestUsers.length" class="rv-chan" data-plan="guest">
                        <h4>{{ t('revenue.meetings.methods.guest_count_charges_per_guest_occupancy_rate_method') }}</h4>
                        <Distribution :plan="form.guestPlan" :facilities="guestUsers" :allow-per-facility="!total" :disabled="locked" :errors="form.errors" error-prefix="guestPlan" :legend="t('revenue.meetings.season.title')" />
                    </section>
                    <section v-if="rentUsers.length" class="rv-chan" data-plan="rent">
                        <h4>{{ t('revenue.meetings.methods.facility_count_daily_rent_occupancy_rate_method') }}</h4>
                        <Distribution :plan="form.rentPlan" :facilities="rentUsers" :allow-per-facility="!total" :disabled="locked" :errors="form.errors" error-prefix="rentPlan" :legend="t('revenue.meetings.season.title')" />
                    </section>
                </SectionCard>

                <!-- 4 · collection -->
                <SectionCard id="gm-collect" :number="5" :title="t('revenue.collection.title')" :text="t('revenue.collection.text')">
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
