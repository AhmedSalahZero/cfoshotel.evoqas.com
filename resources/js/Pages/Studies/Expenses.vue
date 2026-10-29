<!--
  CFO Hotels — Expense pages ( /studies/{id}/expenses/{page} )
  Location: resources/js/Pages/Studies/Expenses.vue
  One page for nine screens: the direct expenses of rooms, food and beverage, gaming, meetings and other revenue, and general, marketing, property and energy.
  The server says which tables a screen has (layout): expenses as a percentage of revenues, an amount per night or per guest or a fixed amount every month,
  disposable expenses, manpower, and the property taxes and insurance. Each table is a numbered section.
  Server side: App\Http\Controllers\Hospitality\ExpensesController (rules: App\Http\Requests\Expenses\ExpensesRequest).
-->
<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import CostTable from '@/Components/Expenses/CostTable.vue';
import DisposableTable from '@/Components/Expenses/DisposableTable.vue';
import ManpowerTables from '@/Components/Expenses/ManpowerTables.vue';
import PercentTable from '@/Components/Expenses/PercentTable.vue';
import PropertyTable from '@/Components/Expenses/PropertyTable.vue';
import SectionCard from '@/Components/Form/SectionCard.vue';
import SectionNav from '@/Components/Form/SectionNav.vue';
import SwitchField from '@/Components/Form/SwitchField.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    readOnly: { type: Boolean, default: false },
    page: { type: String, required: true },
    years: { type: Array, required: true },
    dates: { type: Array, default: () => [] },
    daysDifference: { type: Number, default: 0 },
    estimationDate: { type: String, default: '' },
    currencies: { type: Array, default: () => [] },
    revenue: { type: Object, default: null },
    days: { type: Array, default: () => [] },
    terms: { type: Array, default: () => [] },
    flag: { type: Boolean, default: null },
    layout: { type: Array, required: true },
    form: { type: Object, required: true },
});

const { t } = useI18n();
const canEdit = computed(() => !props.readOnly && ['owner', 'admin', 'edit'].includes(props.access));
const locked = computed(() => !canEdit.value);
const form = useForm(JSON.parse(JSON.stringify(props.form)));

const termOptions = computed(() => props.terms.map((v) => ({ value: v, text: t(`expenses.intervals.${v}`) })));
// the revenue arrives a moment after the page (null until then): say "no revenue" only once it is known
const noRevenue = computed(() => props.revenue !== null && Object.values(props.revenue).every((v) => !Number(v)));

const title = (s) => {
    if (s.kind === 'percent') return t(`expenses.pages.${props.page}.percent`);
    if (s.name === 'fixed_monthly_expenses') return t(`expenses.pages.${props.page}.fixed`);

    return t(`expenses.sections.${s.name}`);
};
const hint = (s) => (s.kind === 'cost' ? (s.style === 'fixed' ? t('expenses.hints.fixed') : t('expenses.hints.cost')) : t(`expenses.hints.${s.kind}`));
const head = (s) => (s.style === 'night' ? t('expenses.col.perNight') : s.style === 'fixed' ? t('expenses.col.fixed') : t('expenses.col.perGuest'));

const sections = computed(() => props.layout.map((s, i) => ({ ...s, id: `ex-${i}`, number: i + 1, heading: title(s), text: hint(s) })));
const nav = computed(() => sections.value.map((s) => ({ id: s.id, title: s.heading, state: '' })));

const problems = computed(() => Object.keys(form.errors).length);
const errorList = computed(() => [...new Set(Object.values(form.errors))].slice(0, 6));

// after = where the person goes once it is saved: 'stay' (this page) or 'next' (the next page of the study, in the order of the previous application)
function save(after = 'stay') {
    if (!canEdit.value) return;
    form.transform((data) => ({ ...data, after })).put(route('studies.expenses.update', [props.study.id, props.page]), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t(`expenses.pages.${page}.title`)" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t(`expenses.pages.${page}.lead`) }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" :current="`expenses.${page}`" />

        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="noRevenue" class="note" role="status" data-no-revenue><AppIcon name="info" :size="14" /> {{ t('expenses.noRevenue') }}</div>
        <div v-if="problems" class="note bad" role="alert">
            <b>{{ t('studies.fixErrors', { count: problems }) }}</b>
            <ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul>
        </div>

        <form class="sf" novalidate data-expenses-form @submit.prevent="save()">
            <SectionNav :items="nav" :title="t('studies.sections.title')" />

            <div class="sf-main">
                <SectionCard v-for="s in sections" :id="s.id" :key="s.id" :number="s.number" :title="s.heading" :text="s.text">
                    <template v-if="s.kind === 'manpower'" #actions>
                        <SwitchField v-model="form.flag" :disabled="locked">{{ t('expenses.manpower.switch') }}</SwitchField>
                    </template>

                    <PercentTable
                        v-if="s.kind === 'percent'" :rows="form.sections[s.name]" :years="years" :revenue="revenue" :revenue-label="t(`expenses.pages.${page}.revenue`)"
                        :disabled="locked" :errors="form.errors" :prefix="`sections.${s.name}`"
                    />
                    <CostTable
                        v-else-if="s.kind === 'cost'" :rows="form.sections[s.name]" :head="head(s)" :currencies="currencies" :estimation-date="estimationDate" :days-difference="daysDifference"
                        :with-terms="s.style === 'fixed'" :term-options="termOptions" :disabled="locked" :errors="form.errors" :prefix="`sections.${s.name}`"
                    />
                    <DisposableTable
                        v-else-if="s.kind === 'disposable'" :rows="form.sections[s.name]" :years="years" :days="days" :disabled="locked" :errors="form.errors" :prefix="`sections.${s.name}`"
                    />
                    <template v-else-if="s.kind === 'manpower'">
                        <ManpowerTables
                            v-if="form.flag" :rows="form.sections[s.name]" :dates="dates" :currencies="currencies" :estimation-date="estimationDate" :days-difference="daysDifference"
                            :disabled="locked" :errors="form.errors" :prefix="`sections.${s.name}`"
                        />
                        <p v-else-if="flag" class="mute" data-remove-note>{{ t('expenses.manpower.removeNote') }}</p>
                    </template>
                    <PropertyTable
                        v-else-if="s.kind === 'property'" :rows="form.sections[s.name]" :currencies="currencies" :disabled="locked" :errors="form.errors" :prefix="`sections.${s.name}`"
                    />
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
