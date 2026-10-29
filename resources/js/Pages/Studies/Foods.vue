<!--
  CFO Hotels — Food and beverage revenue ( /studies/{id}/foods )
  Location: resources/js/Pages/Studies/Foods.vue
  Three numbered sections: 1 Facilities and projection method · 2 Value of a cover · 3 Yearly figures (the ones the chosen method needs) · 4 When the money is collected.
  Each facility (restaurant, café, ...) chooses one of four ways to work out its sales. The number of facilities and their seats come from the study.
  Server side: App\Http\Controllers\Hospitality\FoodsController (rules: App\Http\Requests\Revenue\FoodsRevenueRequest).
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
const methodOptions = computed(() => props.methods.map((m) => ({ value: m, text: t(`revenue.foods.methods.${m}`) })));
const collectionChoices = computed(() => ['general', 'per_facility'].map((v) => ({ value: v, title: t(`revenue.foods.collection.${v}`), text: t(`revenue.foods.collection.${v}Text`) })));
const several = computed(() => form.facilities.length > 1);

// which yearly figures a method needs
const needs = (facility) => ({
    capture: ['guest_capture_x_cover_value_per_guest_method', 'guest_capture_x_meals_per_guest_x_cover_value_per_meal_method'].includes(facility.method),
    meals: facility.method === 'guest_capture_x_meals_per_guest_x_cover_value_per_meal_method',
    target: facility.method === 'cover_count_target_per_day_x_cover_value_method',
    rooms: facility.method === 'percentage_from_rooms_revenue',
});
const valueWord = (facility) => (facility.method === 'guest_capture_x_meals_per_guest_x_cover_value_per_meal_method' ? 'perMeal' : (facility.method === 'guest_capture_x_cover_value_per_guest_method' ? 'perGuest' : 'perCover'));

function sameMethodForAll(method) {
    form.facilities.forEach((f) => { f.method = method; });
}

const sections = computed(() => [
    { id: 'fb-facilities', title: t('revenue.foods.facilities.title'), state: '' },
    { id: 'fb-value', title: t('revenue.foods.cover.title'), state: '' },
    { id: 'fb-yearly', title: t('revenue.foods.yearly.title'), state: '' },
    { id: 'fb-collect', title: t('revenue.collection.title'), state: '' },
]);

const problems = computed(() => Object.keys(form.errors).length);
const errorList = computed(() => [...new Set(Object.values(form.errors))].slice(0, 6));

// after = where the person goes once it is saved: 'stay' (this page) or 'next' (the next page of the study, in the order of the previous application)
function save(after = 'stay') {
    if (!canEdit.value) return;
    form.transform((data) => ({ ...data, after })).put(route('studies.foods.update', props.study.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('revenue.foods.pageTitle')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('revenue.foods.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="foods" />

        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="problems" class="note bad" role="alert">
            <b>{{ t('studies.fixErrors', { count: problems }) }}</b>
            <ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul>
        </div>

        <form class="sf" novalidate data-foods-form @submit.prevent="save()">
            <SectionNav :items="sections" :title="t('studies.sections.title')">
                <template #summary>
                    <span>{{ t('revenue.foods.summary.facilities') }} <b dir="ltr">{{ form.facilities.length }}</b></span>
                    <span>{{ t('revenue.foods.summary.seats') }} <b dir="ltr">{{ form.facilities.reduce((s, f) => s + f.capacity, 0) }}</b></span>
                </template>
            </SectionNav>

            <div class="sf-main">
                <!-- 1 · facilities and method -->
                <SectionCard id="fb-facilities" :number="1" :title="t('revenue.foods.facilities.title')" :text="t('revenue.foods.facilities.text')">
                    <p v-if="total" class="mute">{{ t('revenue.foods.totalMode') }}</p>
                    <div class="rv-cards">
                        <article v-for="(f, i) in form.facilities" :key="f.key" class="rv-room" data-facility>
                            <header>
                                <h4><AppIcon name="utensils" :size="16" />{{ f.name }}</h4>
                                <p class="rv-facts">
                                    <span>{{ t('revenue.foods.facilities.count') }} <b dir="ltr">{{ f.count }}</b></span>
                                    <span>{{ t('revenue.foods.facilities.seatsEach') }} <b dir="ltr">{{ f.cover }}</b></span>
                                    <span>{{ t('revenue.foods.facilities.seatsTotal') }} <b dir="ltr">{{ f.capacity }}</b></span>
                                </p>
                            </header>
                            <FormField :label="t('revenue.foods.method')" :for="`fb-method-${i}`" required :hint="t('revenue.foods.methodHint')" :error="form.errors[`facilities.${i}.method`]">
                                <SelectField :id="`fb-method-${i}`" v-model="f.method" :options="methodOptions" :disabled="locked" />
                            </FormField>
                            <button v-if="several && !locked" type="button" class="rv-link" @click="sameMethodForAll(f.method)">{{ t('revenue.foods.sameForAll') }}</button>
                        </article>
                    </div>
                </SectionCard>

                <!-- 2 · value of a cover -->
                <SectionCard id="fb-value" :number="2" :title="t('revenue.foods.cover.title')" :text="t('revenue.foods.cover.text')">
                    <div class="rv-cards">
                        <article v-for="(f, i) in form.facilities" :key="f.key" class="rv-room">
                            <header><h4><AppIcon name="utensils" :size="16" />{{ f.name }}</h4></header>
                            <p v-if="f.method === 'percentage_from_rooms_revenue'" class="mute" data-no-value>{{ t('revenue.foods.cover.notNeeded') }}</p>
                            <template v-else>
                            <div class="sf-grid">
                                <div class="c2">
                                    <FormField :label="t('revenue.foods.cover.currency')" :for="`fb-cur-${i}`" required :error="form.errors[`facilities.${i}.currency`]">
                                        <SelectField :id="`fb-cur-${i}`" v-model="f.currency" :options="currencyOptions" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c2">
                                    <FormField :label="t(`revenue.foods.cover.${valueWord(f)}`)" :for="`fb-val-${i}`" required :error="form.errors[`facilities.${i}.value`]">
                                        <NumberField :id="`fb-val-${i}`" v-model="f.value" :decimals="2" :min="0" :unit="currencyName(f.currency)" :invalid="!!form.errors[`facilities.${i}.value`]" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c2">
                                    <FormField :label="t('revenue.foods.cover.escalation')" :for="`fb-esc-${i}`" required :hint="t('revenue.foods.cover.escalationHint', { date: estimationDate })" :error="form.errors[`facilities.${i}.escalation`]">
                                        <NumberField :id="`fb-esc-${i}`" v-model="f.escalation" :decimals="2" :min="0" :max="100" unit="%" :invalid="!!form.errors[`facilities.${i}.escalation`]" :disabled="locked" />
                                    </FormField>
                                </div>
                                <div class="c2">
                                    <FormField :label="t('revenue.foods.cover.annual')" :for="`fb-ann-${i}`" required :hint="t('revenue.foods.cover.annualHint')" :error="form.errors[`facilities.${i}.annual`]">
                                        <NumberField :id="`fb-ann-${i}`" v-model="f.annual" :decimals="2" :min="0" :max="100" unit="%" :invalid="!!form.errors[`facilities.${i}.annual`]" :disabled="locked" />
                                    </FormField>
                                </div>
                            </div>
                            <p class="rv-result" data-value-op><span>{{ t('revenue.foods.cover.atOperation') }}</span> <b dir="ltr">{{ money(adrAtOperation(f.value, f.escalation, daysDifference)) }} {{ currencyName(f.currency) }}</b></p>
                            </template>
                        </article>
                    </div>
                </SectionCard>

                <!-- 3 · yearly figures -->
                <SectionCard id="fb-yearly" :number="3" :title="t('revenue.foods.yearly.title')" :text="t('revenue.foods.yearly.text')">
                    <article v-for="(f, i) in form.facilities" :key="f.key" class="rv-yearly" data-yearly>
                        <h4 class="sf-sub"><AppIcon name="utensils" :size="15" />{{ f.name }} <small>{{ t(`revenue.foods.methods.${f.method}`) }}</small></h4>
                        <template v-if="needs(f).capture">
                            <YearGrid :rows="[{ key: 'x', label: t('revenue.foods.yearly.capture') }]" :years="years" :values="f.capture" single suffix="%" :max="100" :disabled="locked" :errors="form.errors" :error-prefix="`facilities.${i}.capture`">
                                <template v-if="aids" #foot><AidRow :label="t('revenue.aids.guests')" :years="years" :values="aids.guests" /></template>
                            </YearGrid>
                        </template>
                        <template v-if="needs(f).meals">
                            <YearGrid :rows="[{ key: 'x', label: t('revenue.foods.yearly.meals') }]" :years="years" :values="f.meals" single :max="100" :disabled="locked" :errors="form.errors" :error-prefix="`facilities.${i}.meals`">
                                <template v-if="aids" #foot><AidRow :label="t('revenue.aids.guests')" :years="years" :values="aids.guests" /></template>
                            </YearGrid>
                        </template>
                        <template v-if="needs(f).target">
                            <YearGrid :rows="[{ key: 'x', label: t('revenue.foods.yearly.target') }]" :years="years" :values="f.target" single :disabled="locked" :errors="form.errors" :error-prefix="`facilities.${i}.target`" />
                            <p class="mute rv-cap">{{ t('revenue.foods.yearly.targetHint', { seats: f.capacity }) }}</p>
                        </template>
                        <template v-if="needs(f).rooms">
                            <YearGrid :rows="[{ key: 'x', label: t('revenue.foods.yearly.rooms') }]" :years="years" :values="f.rooms" single suffix="%" :max="1000" :disabled="locked" :errors="form.errors" :error-prefix="`facilities.${i}.rooms`">
                                <template v-if="aids" #foot><AidRow :label="t('revenue.aids.roomsRevenue')" :years="years" :values="aids.roomsRevenue" /></template>
                            </YearGrid>
                            <p class="mute rv-cap">{{ t('revenue.foods.yearly.roomsHint') }}</p>
                        </template>
                    </article>
                </SectionCard>

                <!-- 4 · collection -->
                <SectionCard id="fb-collect" :number="4" :title="t('revenue.collection.title')" :text="t('revenue.collection.text')">
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
