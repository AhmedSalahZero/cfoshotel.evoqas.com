<!--
  CFO Hotels — Management fees ( /studies/{id}/management-fees )
  Location: resources/js/Pages/Studies/ManagementFees.vue
  Two tables, each with one fixed line and a percentage for every operating year: the base fee (a percentage of the total revenues, which are shown above it)
  and the incentive fee (a percentage of EBITDA, so no revenue row). The name of each line is fixed, as in the old screen.
  Server side: App\Http\Controllers\Hospitality\ManagementFeesController, rules in App\Http\Requests\Expenses\ManagementFeesRequest.
-->
<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import PercentTable from '@/Components/Expenses/PercentTable.vue';
import SectionCard from '@/Components/Form/SectionCard.vue';
import SectionNav from '@/Components/Form/SectionNav.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    readOnly: { type: Boolean, default: false },
    years: { type: Array, required: true },
    revenue: { type: Object, default: null },
    layout: { type: Array, required: true },
    form: { type: Object, required: true },
});

const { t } = useI18n();
const canEdit = computed(() => !props.readOnly && ['owner', 'admin', 'edit'].includes(props.access));
const locked = computed(() => !canEdit.value);
const form = useForm(JSON.parse(JSON.stringify(props.form)));

const sections = computed(() => props.layout.map((s, i) => {
    const base = s.name.startsWith('base');

    return { ...s, id: `mf-${i}`, number: i + 1, heading: t(`expenses.management.${base ? 'base' : 'incentive'}`), text: t(`expenses.management.${base ? 'baseHint' : 'incentiveHint'}`), label: t(`expenses.management.${base ? 'baseName' : 'incentiveName'}`) };
}));
const nav = computed(() => sections.value.map((s) => ({ id: s.id, title: s.heading, state: '' })));
const problems = computed(() => Object.keys(form.errors).length);
const errorList = computed(() => [...new Set(Object.values(form.errors))].slice(0, 6));
// the revenue arrives a moment after the page (null until then): say "no revenue" only once it is known
const noRevenue = computed(() => props.revenue !== null && Object.values(props.revenue).every((v) => !Number(v)));

// after = where the person goes once it is saved: 'stay' (this page) or 'next' (the next page of the study, in the order of the previous application)
function save(after = 'stay') {
    if (!canEdit.value) return;
    form.transform((data) => ({ ...data, after })).put(route('studies.management.update', props.study.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('expenses.management.title')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('expenses.management.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="expenses.management" />

        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="noRevenue" class="note" role="status" data-no-revenue><AppIcon name="info" :size="14" /> {{ t('expenses.noRevenue') }}</div>
        <div v-if="problems" class="note bad" role="alert">
            <b>{{ t('studies.fixErrors', { count: problems }) }}</b>
            <ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul>
        </div>

        <form class="sf" novalidate data-management-form @submit.prevent="save()">
            <SectionNav :items="nav" :title="t('studies.sections.title')" />

            <div class="sf-main">
                <SectionCard v-for="s in sections" :id="s.id" :key="s.id" :number="s.number" :title="s.heading" :text="s.text">
                    <PercentTable
                        :rows="form.sections[s.name]" :years="years" :revenue="revenue" :revenue-label="t('expenses.management.revenue')" :name-label="t('expenses.management.expenseItem')"
                        :disabled="locked" :errors="form.errors" :prefix="`sections.${s.name}`" locked-names hide-total :hide-revenue="s.hidesRevenue"
                    />
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
