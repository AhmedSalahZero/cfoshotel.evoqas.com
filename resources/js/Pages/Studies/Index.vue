<!--
  CFO Hotels — Studies ( /studies )
  Location: resources/js/Pages/Studies/Index.vue
  The studies the person may see: their own, those shared with them and (company administrator) every study of the
  company. Search, filter, sort; copy, share and delete from the row. Server side: App\Http\Controllers\StudyController.
-->
<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import AccessBadge from '@/Components/Studies/AccessBadge.vue';
import ShareDialog from '@/Components/Studies/ShareDialog.vue';
import StudyTimeline from '@/Components/Studies/StudyTimeline.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDate } from '@/Utils/format';
import { monthText } from '@/Utils/studyTimeline';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    studies: { type: Array, required: true },
    isAdmin: { type: Boolean, default: false },
    readOnly: { type: Boolean, default: false },
});

const { t, tr, locale } = useI18n();

// ── search, filter, sort ─────────────────────────────────────
const query = ref('');
const filter = ref('all');
const sortKey = ref('updated');
const sortDown = ref(true);

const filters = computed(() => [
    { key: 'all', text: t('studies.filters.all') },
    { key: 'mine', text: t('studies.filters.mine') },
    { key: 'shared', text: t('studies.filters.shared') },
    ...(props.isAdmin ? [{ key: 'others', text: t('studies.filters.others') }] : []),
]);

const fits = {
    all: () => true,
    mine: (s) => s.access === 'owner',
    shared: (s) => s.access === 'edit' || s.access === 'view',
    others: (s) => s.access === 'admin',
};

const sorters = {
    name: (a, b) => a.name.localeCompare(b.name),
    start: (a, b) => (a.start ?? '').localeCompare(b.start ?? ''),
    owner: (a, b) => (a.owner ?? '').localeCompare(b.owner ?? ''),
    updated: (a, b) => (a.updated ?? '').localeCompare(b.updated ?? ''),
};

const shown = computed(() => {
    const q = query.value.trim().toLowerCase();
    const found = props.studies
        .filter(fits[filter.value])
        .filter((s) => q === '' || [s.name, s.property, s.owner, s.country].some((v) => (v ?? '').toLowerCase().includes(q)));
    found.sort((a, b) => (sorters[sortKey.value](a, b)) * (sortDown.value ? -1 : 1));

    return found;
});

function sortBy(key) {
    if (sortKey.value === key) {
        sortDown.value = !sortDown.value;
    } else {
        sortKey.value = key;
        sortDown.value = key === 'updated';
    }
}
const arrow = (key) => (sortKey.value === key ? (sortDown.value ? '↓' : '↑') : '');
const ariaSort = (key) => (sortKey.value === key ? (sortDown.value ? 'descending' : 'ascending') : 'none');

const tiles = computed(() => [
    { text: t('studies.tiles.all'), value: props.studies.length },
    { text: t('studies.tiles.mine'), value: props.studies.filter(fits.mine).length },
    { text: t('studies.tiles.shared'), value: props.studies.filter(fits.shared).length },
    { text: t('studies.tiles.sharedByMe'), value: props.studies.filter((s) => s.access === 'owner' && s.shared_with > 0).length },
]);

// ── what is written in a row ─────────────────────────────────
const months = computed(() => tr('studies.monthsShort'));
const initials = (name) => (name ?? '').trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('') || '?';
const period = (s) => (s.start && s.end ? `${monthText(s.start, months.value)} – ${monthText(s.end, months.value)}` : '');

// ── copy, delete, share ──────────────────────────────────────
const copying = ref(null);
const copyName = ref('');
const copyError = ref('');
const deleting = ref(null);
const sharing = ref(null);
const busy = ref(false);

function askCopy(study) {
    copying.value = study;
    copyName.value = t('studies.copy.suggested', { name: study.name });
    copyError.value = '';
}

function doCopy() {
    busy.value = true;
    router.post(route('studies.copy', copying.value.id), { name: copyName.value }, {
        preserveScroll: true,
        onError: (errors) => { copyError.value = errors.name ?? ''; },
        onSuccess: () => { copying.value = null; },
        onFinish: () => { busy.value = false; },
    });
}

function doDelete() {
    busy.value = true;
    router.delete(route('studies.destroy', deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleting.value = null; },
        onFinish: () => { busy.value = false; },
    });
}
</script>

<template>
    <AppLayout :title="t('studies.title')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ t('studies.title') }}</h2>
                <p class="lead mb0">{{ t('studies.lead') }}</p>
            </div>
            <div v-if="!readOnly || studies.length > 1" class="actions">
                <Link v-if="studies.length > 1" :href="route('studies.compare')" class="btn btn-line" data-compare-studies><AppIcon name="trend" :size="15" />{{ t('studies.compare') }}</Link>
                <Link v-if="!readOnly" :href="route('studies.create')" class="btn"><AppIcon name="plus" :size="15" />{{ t('studies.new') }}</Link>
            </div>
        </div>

        <template v-if="studies.length">
            <div class="tiles" data-studies="tiles">
                <div v-for="tile in tiles" :key="tile.text" class="tile"><span>{{ tile.text }}</span><b>{{ tile.value }}</b></div>
            </div>

            <div class="st-tools">
                <div class="st-search">
                    <AppIcon name="search" :size="16" />
                    <input v-model="query" class="inp" type="search" :placeholder="t('studies.search')" :aria-label="t('studies.search')" autocomplete="off">
                </div>
                <div class="seg" role="group" :aria-label="t('studies.filter')">
                    <button
                        v-for="f in filters" :key="f.key" type="button" class="btn" :class="{ 'btn-line': filter !== f.key }"
                        :aria-pressed="filter === f.key" @click="filter = f.key"
                    >{{ f.text }}</button>
                </div>
                <label class="st-sort">
                    <span>{{ t('studies.sortBy') }}</span>
                    <select class="sel" :value="sortKey" @change="sortKey = $event.target.value">
                        <option value="updated">{{ t('studies.columns.updated') }}</option>
                        <option value="name">{{ t('studies.columns.study') }}</option>
                        <option value="start">{{ t('studies.columns.period') }}</option>
                        <option value="owner">{{ t('studies.columns.owner') }}</option>
                    </select>
                </label>
                <span class="st-count" role="status">{{ t('studies.showing', { shown: shown.length, total: studies.length }) }}</span>
            </div>

            <div class="st-cols" aria-hidden="true">
                <button type="button" tabindex="-1" :aria-sort="ariaSort('name')" @click="sortBy('name')">{{ t('studies.columns.study') }} {{ arrow('name') }}</button>
                <button type="button" tabindex="-1" :aria-sort="ariaSort('start')" @click="sortBy('start')">{{ t('studies.columns.period') }} {{ arrow('start') }}</button>
                <button type="button" tabindex="-1" :aria-sort="ariaSort('owner')" @click="sortBy('owner')">{{ t('studies.columns.owner') }} {{ arrow('owner') }}</button>
                <button type="button" tabindex="-1" :aria-sort="ariaSort('updated')" @click="sortBy('updated')">{{ t('studies.columns.updated') }} {{ arrow('updated') }}</button>
                <span />
            </div>

            <ul v-if="shown.length" class="st-list" data-studies="list">
                <li v-for="s in shown" :key="s.id" class="st-item" :class="`is-${s.access}`" :data-study="s.id">
                    <div class="st-name">
                        <Link :href="route('studies.edit', s.id)">{{ s.name }}</Link>
                        <div class="st-meta">
                            <span v-if="s.property">{{ s.property }}</span>
                            <span v-if="s.stars" class="st-stars" :aria-label="t('studies.stars', { count: s.stars })">{{ '★'.repeat(Math.min(s.stars, 5)) }}</span>
                            <span v-if="s.country">{{ s.country }}</span>
                            <span v-if="s.status">{{ t(`studies.status.${s.status}`) }}</span>
                        </div>
                    </div>

                    <div class="st-time">
                        <StudyTimeline :years="s.years" :development-start="s.development_start" :development-duration="s.development_duration" :operation-start="s.operation_start" />
                        <p><b>{{ period(s) }}</b> · {{ t('studies.years', { count: s.years }) }}</p>
                    </div>

                    <div class="st-who">
                        <div class="st-owner"><span class="st-av" aria-hidden="true">{{ initials(s.owner) }}</span><span>{{ s.owner ?? '—' }}</span></div>
                        <AccessBadge :access="s.access" />
                        <span v-if="s.shared_with" class="mute small">{{ t('studies.sharedWith', { count: s.shared_with }) }}</span>
                    </div>

                    <div class="st-upd">{{ formatDate(s.updated, locale) }}</div>

                    <div class="st-acts">
                        <Link :href="route('studies.edit', s.id)" class="st-ib" :title="s.can.edit ? t('studies.edit') : t('studies.open')" :aria-label="`${s.can.edit ? t('studies.edit') : t('studies.open')}: ${s.name}`">
                            <AppIcon :name="s.can.edit ? 'edit' : 'eye'" :size="16" />
                        </Link>
                        <a v-if="s.can.excel" :href="route('studies.excel', s.id)" class="st-ib" data-excel :title="t('studies.excel.hint')" :aria-label="`${t('studies.excel.button')}: ${s.name}`" download><AppIcon name="download" :size="16" /></a>
                        <button v-if="s.can.copy" type="button" class="st-ib" :title="t('studies.copy.button')" :aria-label="`${t('studies.copy.button')}: ${s.name}`" @click="askCopy(s)"><AppIcon name="copy" :size="16" /></button>
                        <button v-if="s.can.share" type="button" class="st-ib" :title="t('studies.share.button')" :aria-label="`${t('studies.share.button')}: ${s.name}`" @click="sharing = s"><AppIcon name="share" :size="16" /></button>
                        <button v-if="s.can.delete" type="button" class="st-ib danger" :title="t('studies.delete.button')" :aria-label="`${t('studies.delete.button')}: ${s.name}`" @click="deleting = s"><AppIcon name="trash" :size="16" /></button>
                    </div>
                </li>
            </ul>

            <div v-else class="note" role="status">
                {{ t('studies.noMatch') }}
                <button type="button" class="btn sm ms2" @click="query = ''; filter = 'all'">{{ t('studies.clearFilters') }}</button>
            </div>
        </template>

        <div v-else class="st-empty" data-studies="empty">
            <span class="st-empty-ico"><AppIcon name="building" :size="34" /></span>
            <h3>{{ t('studies.empty.title') }}</h3>
            <p>{{ readOnly ? t('studies.empty.readOnly') : t('studies.empty.text') }}</p>
            <Link v-if="!readOnly" :href="route('studies.create')" class="btn"><AppIcon name="plus" :size="15" />{{ t('studies.new') }}</Link>
        </div>

        <ConfirmDialog
            :open="!!copying" :title="t('studies.copy.title')" :confirm-label="t('studies.copy.confirm')" :busy="busy"
            @confirm="doCopy" @cancel="copying = null"
        >
            <p class="mute small">{{ t('studies.copy.help') }}</p>
            <div class="fx" style="margin-top:12px">
                <label for="copy-name">{{ t('studies.copy.name') }}</label>
                <input id="copy-name" v-model="copyName" class="inp" type="text" maxlength="190" :aria-invalid="copyError ? 'true' : undefined">
                <p v-if="copyError" class="fx-err" role="alert">{{ copyError }}</p>
            </div>
        </ConfirmDialog>

        <ConfirmDialog
            :open="!!deleting" :title="t('studies.delete.title')" :confirm-label="t('studies.delete.confirm')" danger :busy="busy"
            @confirm="doDelete" @cancel="deleting = null"
        >
            <p>{{ t('studies.delete.text', { name: deleting?.name ?? '' }) }}</p>
            <p v-if="deleting?.shared_with" class="note" style="margin-top:10px">{{ t('studies.delete.shared', { count: deleting.shared_with }) }}</p>
        </ConfirmDialog>

        <ShareDialog v-if="sharing" :study="sharing" @close="sharing = null" />
    </AppLayout>
</template>
