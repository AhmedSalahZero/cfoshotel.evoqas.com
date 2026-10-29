<!--
  CFO Hotels — Study form ( /studies/create and /studies/{id}/edit )
  Location: resources/js/Pages/Studies/Form.vue
  One page in five numbered sections with a navigator at the side and a Save bar that stays in view:
    1 Main information · 2 Timing · 3 Money · 4 Revenue streams · 5 Sales channels
  A colleague who may only view the study sees the same page with every box locked.
  Server side: App\Http\Controllers\StudyController (the rules are in App\Http\Requests\Studies\StudyRequest).
-->
<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import FormField from '@/Components/Form/FormField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SearchSelect from '@/Components/Form/SearchSelect.vue';
import SectionCard from '@/Components/Form/SectionCard.vue';
import SectionNav from '@/Components/Form/SectionNav.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import SwitchField from '@/Components/Form/SwitchField.vue';
import MonthYearInput from '@/Components/MonthYearInput.vue';
import AccessBadge from '@/Components/Studies/AccessBadge.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import StreamEditor from '@/Components/Studies/StreamEditor.vue';
import StudyTimeline from '@/Components/Studies/StudyTimeline.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { addMonths } from '@/Utils/studyDates';
import { monthAfter, monthText, operationBeforeDevelopmentEnds } from '@/Utils/studyTimeline';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    study: { type: Object, required: true },
    options: { type: Object, required: true },
    access: { type: String, default: 'owner' },
    readOnly: { type: Boolean, default: false },
    menu: { type: Array, default: () => [] },
});

const { t, tr } = useI18n();

const isNew = !props.study.id;
const canEdit = computed(() => !props.readOnly && ['owner', 'admin', 'edit'].includes(props.access));
const locked = computed(() => !canEdit.value);

const { id: studyId, ...initial } = props.study;
const form = useForm(initial);

const months = computed(() => tr('studies.monthsShort'));

// ── lists for the drop-downs ─────────────────────────────────
const countryOptions = computed(() => props.options.countries.map((c) => ({ value: c.id, text: c.name })));
const states = ref(props.options.states ?? []);
const stateOptions = computed(() => states.value.map((s) => ({ value: s.id, text: s.name })));
const statusOptions = computed(() => props.options.statuses.map((v) => ({ value: v, text: t(`studies.status.${v}`) })));
const durationOptions = computed(() => props.options.durations.map((v) => ({ value: v, text: t('studies.years', { count: v }) })));
const monthOptions = computed(() => props.options.financial_year_starts.map((v) => ({ value: v, text: t(`studies.fy.${v}`) })));
const currencyOptions = computed(() => props.options.currencies.map((c) => ({ value: c.id, text: c.name })));
const additionalOptions = computed(() => currencyOptions.value.filter((c) => c.value !== form.main_functional_currency));
const currencyName = (id) => props.options.currencies.find((c) => c.id === id)?.name ?? '';

watch(() => form.country_id, async (country, before) => {
    if (country === before) return;
    form.state_id = null;
    states.value = [];
    if (!country) return;
    try {
        const { data } = await axios.get(route('studies.states', country));
        states.value = data;
    } catch {
        states.value = [];
    }
});

watch(() => form.main_functional_currency, (main) => {
    if (form.additional_currency === main) form.additional_currency = null;
});
watch(() => form.additional_currency, (extra) => {
    if (!extra) form.exchange_rate = 1;
});

const setStars = (n) => { if (!locked.value) form.star_rating = form.star_rating === n ? null : n; };

// ── the dates worked out while typing ────────────────────────
const endMonth = computed(() => addMonths(form.study_start, Number(form.duration_in_years) * 12 - 1));
const endText = computed(() => monthText(endMonth.value, months.value));
const developmentStartText = computed(() => monthAfter(form.study_start, form.development_start_month, months.value));
const developmentEndText = computed(() => (Number(form.development_duration) > 0
    ? monthAfter(form.study_start, Number(form.development_start_month) + Number(form.development_duration) - 1, months.value)
    : ''));
const operationStartText = computed(() => monthAfter(form.study_start, form.operation_start_month, months.value));
const overlap = computed(() => operationBeforeDevelopmentEnds({
    developmentStart: form.development_start_month, developmentDuration: form.development_duration, operationStart: form.operation_start_month,
}));
const lastStudyMonth = computed(() => Math.max(0, Number(form.duration_in_years) * 12 - 1));
const startsOutside = computed(() => Number(form.operation_start_month) > lastStudyMonth.value || Number(form.development_start_month) > lastStudyMonth.value);

// ── sections: which are fine, which have a problem ───────────
const sectionOf = (key) => {
    if (key.startsWith('streams')) return 'sec-streams';
    if (key.startsWith('channels')) return 'sec-channels';
    if (['study_start', 'duration_in_years', 'development_start_month', 'development_duration', 'operation_start_month'].includes(key)) return 'sec-timing';
    if (['financial_year_start_month', 'main_functional_currency', 'additional_currency', 'exchange_rate', 'corporate_taxes_rate', 'investment_return_rate', 'perpetual_growth_rate'].includes(key)) return 'sec-money';

    return 'sec-main';
};
const problems = computed(() => {
    const found = {};
    Object.keys(form.errors ?? {}).forEach((key) => { found[sectionOf(key)] = (found[sectionOf(key)] ?? 0) + 1; });

    return found;
});

const rowOk = (row, withType, withValue) => Number(row.count) > 0 && (!withValue || Number(row.value) > 0) && (!withType || !!row.type_id);
const streamOk = (key, withValue = true) => {
    const s = form.streams[key];
    if (s.on === false) return true;
    const rows = s.mode === 'total' ? s.rows.slice(0, 1) : s.rows;

    return rows.length > 0 && rows.every((r) => rowOk(r, s.mode === 'types', withValue));
};
const complete = computed(() => ({
    'sec-main': form.study_name.trim() !== '' && !!form.property_status,
    'sec-timing': !!form.study_start && Number(form.duration_in_years) > 0 && !startsOutside.value,
    'sec-money': !!form.main_functional_currency && form.exchange_rate > 0,
    'sec-streams': streamOk('rooms') && streamOk('foods') && streamOk('casinos') && streamOk('meetings') && streamOk('others', false),
    'sec-channels': !form.channels.on || form.channels.names.length > 0,
}));
const sections = computed(() => [
    ['sec-main', t('studies.sections.main')],
    ['sec-timing', t('studies.sections.timing')],
    ['sec-money', t('studies.sections.money')],
    ['sec-streams', t('studies.sections.streams')],
    ['sec-channels', t('studies.sections.channels')],
].map(([id, title]) => ({
    id, title,
    state: problems.value[id] ? 'bad' : (complete.value[id] ? 'done' : ''),
    badge: problems.value[id] ? String(problems.value[id]) : '',
})));
const problemCount = computed(() => Object.keys(form.errors ?? {}).length);

// ── save ─────────────────────────────────────────────────────
const dirty = computed(() => Boolean(form.isDirty));

// after = where the person goes once it is saved: 'next' (the Rooms page of the study), 'stay' (this page) or 'close' (the list of studies).
// Pressing Enter in a field sends the main button, "next".
function save(after) {
    if (!canEdit.value || form.processing) return;
    const where = typeof after === 'string' ? after : 'next';
    form.transform((data) => ({ ...data, after: where }));
    const options = {
        preserveScroll: true,
        onError: async () => {
            await nextTick();
            const first = sections.value.find((s) => s.state === 'bad');
            if (first) document.getElementById(first.id)?.scrollIntoView?.({ behavior: 'smooth', block: 'start' });
            document.querySelector('.sf [aria-invalid="true"]')?.focus?.({ preventScroll: true });
        },
    };
    if (isNew) form.post(route('studies.store'), options);
    else form.put(route('studies.update', studyId), options);
}

const warnLeave = (event) => {
    if (dirty.value && canEdit.value) {
        event.preventDefault();
        event.returnValue = '';
    }
};
onMounted(() => window.addEventListener('beforeunload', warnLeave));
onBeforeUnmount(() => window.removeEventListener('beforeunload', warnLeave));

const toggleChannel = (name) => {
    if (locked.value) return;
    const list = form.channels.names;
    form.channels.names = list.includes(name) ? list.filter((n) => n !== name) : [...list, name];
};

const title = computed(() => (isNew ? t('studies.newTitle') : (form.study_name || props.study.study_name)));
</script>

<template>
    <AppLayout :title="isNew ? t('studies.newTitle') : t('studies.editTitle')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ title }}</h2>
                <p class="lead mb0">
                    <Link :href="route('studies.index')" class="row" style="display:inline-flex;gap:6px"><AppIcon name="back" :size="14" />{{ t('studies.back') }}</Link>
                </p>
            </div>
            <div class="actions">
                <AccessBadge v-if="!isNew" :access="access" />
            </div>
        </div>

        <StudyTabs v-if="!isNew" :menu="menu" current="study" />

        <div v-if="readOnly && !isNew" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="form.hasErrors" class="note bad" role="alert">{{ t('studies.fixErrors', { count: problemCount }) }}</div>

        <form class="sf" novalidate data-study-form @submit.prevent="save">
            <SectionNav :items="sections" :title="t('studies.sections.title')">
                <template #summary>
                    <span>{{ t('studies.summary.ends') }} <b>{{ endText || '—' }}</b></span>
                    <span>{{ t('studies.summary.operation') }} <b>{{ operationStartText || '—' }}</b></span>
                    <span>{{ t('studies.summary.currency') }} <b>{{ currencyName(form.main_functional_currency) || '—' }}</b></span>
                </template>
            </SectionNav>

            <div class="sf-main">
                <!-- 1 · main information -->
                <SectionCard id="sec-main" :number="1" :title="t('studies.sections.main')" :text="t('studies.sections.mainText')">
                    <div class="sf-grid">
                        <div class="c6">
                            <FormField :label="t('studies.fields.study_name')" for="s-name" required :error="form.errors.study_name">
                                <input id="s-name" v-model="form.study_name" class="inp" type="text" maxlength="190" :placeholder="t('studies.placeholders.study_name')" :disabled="locked" :aria-invalid="form.errors.study_name ? 'true' : undefined" autocomplete="off">
                            </FormField>
                        </div>
                        <div class="c3">
                            <FormField :label="t('studies.fields.property_name')" for="s-property" optional :error="form.errors.property_name">
                                <input id="s-property" v-model="form.property_name" class="inp" type="text" maxlength="190" :placeholder="t('studies.placeholders.property_name')" :disabled="locked" autocomplete="off">
                            </FormField>
                        </div>
                        <div class="c3">
                            <FormField :label="t('studies.fields.property_status')" for="s-status" required :error="form.errors.property_status">
                                <SelectField id="s-status" v-model="form.property_status" :options="statusOptions" :invalid="!!form.errors.property_status" :disabled="locked" />
                            </FormField>
                        </div>
                        <div class="c6">
                            <FormField :label="t('studies.fields.star_rating')" optional :error="form.errors.star_rating">
                                <div class="fx-pills" role="group" :aria-label="t('studies.fields.star_rating')">
                                    <button v-for="n in options.star_ratings" :key="n" type="button" class="fx-pill" :aria-pressed="form.star_rating === n" :disabled="locked" @click="setStars(n)">
                                        {{ n }} <AppIcon name="star" :size="14" class="fx-star" />
                                    </button>
                                </div>
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.country_id')" for="s-country" optional :error="form.errors.country_id">
                                <SearchSelect id="s-country" v-model="form.country_id" :options="countryOptions" :placeholder="t('studies.choose')" clearable :disabled="locked" :invalid="!!form.errors.country_id" />
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.state_id')" for="s-state" optional :error="form.errors.state_id" :hint="!form.country_id ? t('studies.stateHint') : ''">
                                <SearchSelect id="s-state" v-model="form.state_id" :options="stateOptions" :placeholder="t('studies.choose')" clearable :disabled="locked || !form.country_id" :invalid="!!form.errors.state_id" />
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.region')" for="s-region" optional :error="form.errors.region">
                                <input id="s-region" v-model="form.region" class="inp" type="text" maxlength="190" :placeholder="t('studies.placeholders.region')" :disabled="locked" autocomplete="off">
                            </FormField>
                        </div>
                    </div>
                </SectionCard>

                <!-- 2 · timing -->
                <SectionCard id="sec-timing" :number="2" :title="t('studies.sections.timing')" :text="t('studies.sections.timingText')">
                    <div class="sf-grid">
                        <div class="c2">
                            <FormField :label="t('studies.fields.study_start')" for="s-start" required :error="form.errors.study_start">
                                <MonthYearInput id="s-start" v-model="form.study_start" :invalid="!!form.errors.study_start" :disabled="locked" />
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.duration_in_years')" for="s-years" required :error="form.errors.duration_in_years">
                                <SelectField id="s-years" v-model="form.duration_in_years" :options="durationOptions" :invalid="!!form.errors.duration_in_years" :disabled="locked" />
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.study_end')">
                                <div class="fx-readout" data-readout="end"><AppIcon name="calendar" :size="15" />{{ endText || '—' }}</div>
                            </FormField>
                        </div>
                    </div>

                    <h4 class="sf-sub">{{ t('studies.phases') }}</h4>
                    <div class="sf-grid">
                        <div class="c2">
                            <FormField :label="t('studies.fields.development_start_month')" for="s-dev-start" required :hint="developmentStartText ? t('studies.startsIn', { month: developmentStartText }) : ''" :error="form.errors.development_start_month">
                                <NumberField id="s-dev-start" v-model="form.development_start_month" :decimals="0" :min="0" :max="600" :unit="t('studies.months')" :invalid="!!form.errors.development_start_month" :disabled="locked" />
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.development_duration')" for="s-dev-len" required :hint="developmentEndText ? t('studies.endsIn', { month: developmentEndText }) : t('studies.noDevelopment')" :error="form.errors.development_duration">
                                <NumberField id="s-dev-len" v-model="form.development_duration" :decimals="0" :min="0" :max="600" :unit="t('studies.months')" :invalid="!!form.errors.development_duration" :disabled="locked" />
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.operation_start_month')" for="s-op-start" required :hint="operationStartText ? t('studies.startsIn', { month: operationStartText }) : ''" :error="form.errors.operation_start_month">
                                <NumberField id="s-op-start" v-model="form.operation_start_month" :decimals="0" :min="0" :max="600" :unit="t('studies.months')" :invalid="!!form.errors.operation_start_month" :disabled="locked" />
                            </FormField>
                        </div>
                    </div>

                    <div class="sf-time" data-timeline>
                        <StudyTimeline big :years="form.duration_in_years" :development-start="form.development_start_month" :development-duration="form.development_duration" :operation-start="form.operation_start_month" />
                        <div class="sf-time-key">
                            <span><i class="sf-dot all" />{{ t('studies.legend.study') }} <b>{{ t('studies.years', { count: form.duration_in_years }) }}</b></span>
                            <span v-if="Number(form.development_duration) > 0"><i class="sf-dot dev" />{{ t('studies.legend.development') }} <b>{{ t('studies.months_n', { count: form.development_duration }) }}</b></span>
                            <span><i class="sf-dot ops" />{{ t('studies.legend.operation') }} <b>{{ operationStartText || '—' }}</b></span>
                        </div>
                    </div>
                    <div v-if="startsOutside" class="note bad" role="alert" data-note="outside">{{ t('studies.warn.outside', { months: lastStudyMonth }) }}</div>
                    <div v-else-if="overlap" class="note" role="status" data-note="overlap">{{ t('studies.warn.overlap') }}</div>
                    <div v-if="!isNew" class="note" role="status">{{ t('studies.warn.movesFigures') }}</div>
                </SectionCard>

                <!-- 3 · money -->
                <SectionCard id="sec-money" :number="3" :title="t('studies.sections.money')" :text="t('studies.sections.moneyText')">
                    <div class="sf-grid">
                        <div class="c2">
                            <FormField :label="t('studies.fields.financial_year_start_month')" for="s-fy" required :error="form.errors.financial_year_start_month">
                                <SelectField id="s-fy" v-model="form.financial_year_start_month" :options="monthOptions" :invalid="!!form.errors.financial_year_start_month" :disabled="locked" />
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.main_functional_currency')" for="s-main-cur" required :error="form.errors.main_functional_currency">
                                <SelectField id="s-main-cur" v-model="form.main_functional_currency" :options="currencyOptions" :invalid="!!form.errors.main_functional_currency" :disabled="locked" />
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.additional_currency')" for="s-add-cur" optional :error="form.errors.additional_currency">
                                <SelectField id="s-add-cur" v-model="form.additional_currency" :options="additionalOptions" :placeholder="t('studies.noneChosen')" :invalid="!!form.errors.additional_currency" :disabled="locked" />
                            </FormField>
                        </div>
                        <div v-if="form.additional_currency" class="c3" data-field="exchange">
                            <FormField :label="t('studies.exchangeLabel', { from: currencyName(form.additional_currency), to: currencyName(form.main_functional_currency) })" for="s-fx" required :hint="t('studies.exchangeHint', { from: currencyName(form.additional_currency), to: currencyName(form.main_functional_currency) })" :error="form.errors.exchange_rate">
                                <NumberField id="s-fx" v-model="form.exchange_rate" :decimals="2" :min="0.01" :max="99999999" :unit="currencyName(form.main_functional_currency)" :invalid="!!form.errors.exchange_rate" :disabled="locked" />
                            </FormField>
                        </div>
                    </div>

                    <h4 class="sf-sub">{{ t('studies.rates') }}</h4>
                    <div class="sf-grid">
                        <div class="c2">
                            <FormField :label="t('studies.fields.corporate_taxes_rate')" for="s-tax" required :error="form.errors.corporate_taxes_rate">
                                <NumberField id="s-tax" v-model="form.corporate_taxes_rate" unit="%" :decimals="2" :min="0" :max="100" :invalid="!!form.errors.corporate_taxes_rate" :disabled="locked" />
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.investment_return_rate')" for="s-return" required :hint="t('studies.returnHint')" :error="form.errors.investment_return_rate">
                                <NumberField id="s-return" v-model="form.investment_return_rate" unit="%" :decimals="2" :min="0" :max="100" :invalid="!!form.errors.investment_return_rate" :disabled="locked" />
                            </FormField>
                        </div>
                        <div class="c2">
                            <FormField :label="t('studies.fields.perpetual_growth_rate')" for="s-growth" required :hint="t('studies.growthHint')" :error="form.errors.perpetual_growth_rate">
                                <NumberField id="s-growth" v-model="form.perpetual_growth_rate" unit="%" :decimals="2" :min="0" :max="100" :invalid="!!form.errors.perpetual_growth_rate" :disabled="locked" />
                            </FormField>
                        </div>
                    </div>
                </SectionCard>

                <!-- 4 · revenue streams -->
                <SectionCard id="sec-streams" :number="4" :title="t('studies.sections.streams')" :text="t('studies.sections.streamsText')">
                    <StreamEditor stream-key="rooms" icon="bed" :title="t('studies.streams.rooms.title')" :stream="form.streams.rooms" :types="options.types.rooms" :errors="form.errors" :disabled="locked" :value-decimals="1" :value-max="20" />
                    <StreamEditor stream-key="foods" icon="utensils" :title="t('studies.streams.foods.title')" :stream="form.streams.foods" :types="options.types.foods" :errors="form.errors" :disabled="locked" />
                    <StreamEditor stream-key="casinos" icon="dice" optional :title="t('studies.streams.casinos.title')" :stream="form.streams.casinos" :types="options.types.casinos" :errors="form.errors" :disabled="locked" />
                    <StreamEditor stream-key="meetings" icon="present" optional :title="t('studies.streams.meetings.title')" :stream="form.streams.meetings" :types="options.types.meetings" :errors="form.errors" :disabled="locked" />
                    <StreamEditor stream-key="others" icon="spark" optional :has-value="false" :title="t('studies.streams.others.title')" :stream="form.streams.others" :types="options.types.others" :errors="form.errors" :disabled="locked" />
                </SectionCard>

                <!-- 5 · sales channels -->
                <SectionCard id="sec-channels" :number="5" :title="t('studies.sections.channels')" :text="t('studies.sections.channelsText')">
                    <template #actions>
                        <SwitchField v-model="form.channels.on" :disabled="locked">{{ form.channels.on ? t('studies.on') : t('studies.off') }}</SwitchField>
                    </template>
                    <div v-if="form.channels.on">
                        <FormField :label="t('studies.channelsPick')" :error="form.errors['channels.names']" :hint="t('studies.channelsHint')">
                            <div class="fx-pills" role="group" :aria-label="t('studies.channelsPick')">
                                <button v-for="name in options.channels" :key="name" type="button" class="fx-pill" :aria-pressed="form.channels.names.includes(name)" :disabled="locked" @click="toggleChannel(name)">
                                    <AppIcon v-if="form.channels.names.includes(name)" name="check" :size="14" />{{ t(`studies.channels.${name}`) }}
                                </button>
                            </div>
                        </FormField>
                    </div>
                    <p v-else class="mute">{{ t('studies.channelsOff') }}</p>
                </SectionCard>

                <div v-if="canEdit" class="sf-bar" data-savebar>
                    <p class="sf-bar-msg" :class="problemCount ? 'bad' : (dirty ? 'dirty' : (isNew ? '' : 'ok'))" role="status">
                        <span class="sf-bar-dot" />
                        <span v-if="form.processing">{{ t('studies.saving') }}</span>
                        <span v-else-if="problemCount">{{ t('studies.fixErrors', { count: problemCount }) }}</span>
                        <span v-else-if="dirty">{{ t('studies.unsaved') }}</span>
                        <span v-else>{{ isNew ? t('studies.fillIn') : t('studies.allSaved') }}</span>
                    </p>
                    <Link :href="route('studies.index')" class="btn btn-line">{{ t('common.cancel') }}</Link>
                    <button v-if="!isNew" type="button" class="btn btn-line" :disabled="form.processing" data-save-stay @click="save('stay')">
                        <AppIcon name="check" :size="15" />{{ t('studies.save') }}
                    </button>
                    <button type="button" class="btn btn-line" :disabled="form.processing" data-save-close @click="save('close')">
                        {{ isNew ? t('studies.createClose') : t('studies.saveClose') }}
                    </button>
                    <button type="submit" class="btn" :disabled="form.processing" data-save-next>
                        <AppIcon name="check" :size="15" />{{ isNew ? t('studies.createNext') : t('studies.saveNext') }}
                    </button>
                </div>
                <div v-else class="sf-bar">
                    <p class="sf-bar-msg"><span class="sf-bar-dot" /><span>{{ t('studies.viewOnlyBar') }}</span></p>
                    <Link :href="route('studies.index')" class="btn btn-line">{{ t('studies.back') }}</Link>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
