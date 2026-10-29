<!--
  CFO Hotels — Start-up cost ( /studies/{id}/start-up-cost )
  Location: resources/js/Pages/Studies/StartUpCost.vue
  Two tables of three lines each. Start-up cost: name, currency, cost amount, the month it is paid, cash payment % (the deferred % is the rest, shown live) and the due days.
  Pre-operating expense: name, currency and an amount for every month from the start of the study to the month before the opening.
  A line is used when it has a name; clear the name to remove the line.
  Server side: App\Http\Controllers\Hospitality\StartUpCostController, rules in App\Http\Requests\Expenses\StartUpCostRequest.
-->
<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import CopyInput from '@/Components/CopyInput.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SectionCard from '@/Components/Form/SectionCard.vue';
import SectionNav from '@/Components/Form/SectionNav.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    readOnly: { type: Boolean, default: false },
    currencies: { type: Array, default: () => [] },
    months: { type: Array, default: () => [] },
    dates: { type: Array, default: () => [] },
    days: { type: Array, default: () => [] },
    form: { type: Object, required: true },
});

const { t } = useI18n();
const canEdit = computed(() => !props.readOnly && ['owner', 'admin', 'edit'].includes(props.access));
const locked = computed(() => !canEdit.value);
const form = useForm(JSON.parse(JSON.stringify(props.form)));

const START = 'start_up_cost';
const PRE = 'pre_operating_expense';
const currencyOptions = computed(() => props.currencies.map((c) => ({ value: c.id, text: c.name })));
const monthOptions = computed(() => props.months.map((m) => ({ value: m.index, text: m.label })));
const dayOptions = computed(() => props.days.map((d) => ({ value: d, text: `${d} ${t('expenses.days')}` })));

const sections = computed(() => [
    { id: 'su-0', number: 1, key: START, heading: t('expenses.startup.startUp'), text: t('expenses.startup.startUpHint') },
    { id: 'su-1', number: 2, key: PRE, heading: t('expenses.startup.preOperating'), text: t('expenses.startup.preOperatingHint') },
]);
const nav = computed(() => sections.value.map((s) => ({ id: s.id, title: s.heading, state: '' })));

const bad = (section, i, field) => !!form.errors[`sections.${section}.${i}.${field}`];
const deferred = (row) => Math.round((100 - (Number(row.cash) || 0)) * 100) / 100;
const problems = computed(() => Object.keys(form.errors).length);
const errorList = computed(() => [...new Set(Object.values(form.errors))].slice(0, 6));

function copyRight(row, from) {
    const start = props.dates.findIndex((d) => d.index === from.index);
    for (let i = start + 1; i < props.dates.length; i += 1) row.amounts[props.dates[i].index] = row.amounts[from.index];
}

// after = where the person goes once it is saved: 'stay' (this page) or 'next' (the next page of the study, in the order of the previous application)
function save(after = 'stay') {
    if (!canEdit.value) return;
    form.transform((data) => ({ ...data, after })).put(route('studies.startup.update', props.study.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('expenses.startup.title')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('expenses.startup.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="expenses.startup" />

        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="problems" class="note bad" role="alert">
            <b>{{ t('studies.fixErrors', { count: problems }) }}</b>
            <ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul>
        </div>

        <form class="sf" novalidate data-startup-form @submit.prevent="save()">
            <SectionNav :items="nav" :title="t('studies.sections.title')" />

            <div class="sf-main">
                <SectionCard v-for="s in sections" :id="s.id" :key="s.id" :number="s.number" :title="s.heading" :text="s.text">
                    <div v-if="s.key === START" class="rv-scroll" role="region" :aria-label="s.heading" tabindex="0">
                        <table class="ex-grid ex-startup" data-startup-table>
                            <thead>
                                <tr>
                                    <th scope="col" class="rv-rowhead">{{ t('expenses.startup.name') }}</th>
                                    <th scope="col">{{ t('expenses.startup.currency') }}</th>
                                    <th scope="col">{{ t('expenses.startup.cost') }}</th>
                                    <th scope="col">{{ t('expenses.startup.date') }}</th>
                                    <th scope="col">{{ t('expenses.startup.cash') }}</th>
                                    <th scope="col">{{ t('expenses.startup.deferred') }}</th>
                                    <th scope="col">{{ t('expenses.startup.due') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in form.sections[START]" :key="i" data-startup-row>
                                    <th scope="row" class="rv-rowhead ex-name">
                                        <input v-model="row.name" class="inp" type="text" maxlength="120" :placeholder="t('expenses.expenseName')" :aria-label="t('expenses.startup.name')" :disabled="locked">
                                    </th>
                                    <td><SelectField v-model="row.currency" :options="currencyOptions" :disabled="locked" /></td>
                                    <td :class="{ bad: bad(START, i, 'cost') }"><NumberField v-model="row.cost" :min="0" :max="1000000000000" :invalid="bad(START, i, 'cost')" :disabled="locked" /></td>
                                    <td :class="{ bad: bad(START, i, 'date') }"><SelectField v-model="row.date" :options="monthOptions" :disabled="locked" /></td>
                                    <td :class="{ bad: bad(START, i, 'cash') }"><NumberField v-model="row.cash" unit="%" :min="0" :max="100" :invalid="bad(START, i, 'cash')" :disabled="locked" /></td>
                                    <td class="rv-num" dir="ltr" data-deferred>{{ deferred(row) }}%</td>
                                    <td><SelectField v-model="row.dueDays" :options="dayOptions" :disabled="locked" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <template v-else>
                        <p v-if="!dates.length" class="mute" data-no-months>{{ t('expenses.startup.noMonths') }}</p>
                        <div v-else class="rv-scroll" role="region" :aria-label="s.heading" tabindex="0">
                            <table class="ex-grid ex-heads" data-preop-table>
                                <thead>
                                    <tr>
                                        <th scope="col" class="rv-rowhead">{{ t('expenses.startup.nameShort') }}</th>
                                        <th scope="col">{{ t('expenses.startup.currency') }}</th>
                                        <th v-for="d in dates" :key="d.index" scope="col"><span dir="ltr">{{ d.label }}</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form.sections[PRE]" :key="i" data-preop-row>
                                        <th scope="row" class="rv-rowhead ex-name">
                                            <input v-model="row.name" class="inp" type="text" maxlength="120" :placeholder="t('expenses.startup.nameShort')" :aria-label="t('expenses.startup.nameShort')" :disabled="locked">
                                        </th>
                                        <td><SelectField v-model="row.currency" :options="currencyOptions" :disabled="locked" /></td>
                                        <td v-for="(d, k) in dates" :key="d.index" :class="{ bad: bad(PRE, i, `amounts.${d.index}`) }">
                                            <CopyInput v-model="row.amounts[d.index]" :copy="!locked && k < dates.length - 1" :invalid="bad(PRE, i, `amounts.${d.index}`)" :aria-label="`${row.name || t('expenses.startup.nameShort')} ${d.label}`" :disabled="locked" @copy="copyRight(row, d)" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </SectionCard>

                <p class="mute rv-cap">{{ t('expenses.rowsOf') }}</p>

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
