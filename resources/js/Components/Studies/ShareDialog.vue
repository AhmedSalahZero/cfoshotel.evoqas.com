<!--
  CFO Hotels — ShareDialog (who may open this study)
  Location: resources/js/Components/Studies/ShareDialog.vue
  <ShareDialog :study="{ id, name }" @close="..." />
  Loads the colleagues of the company, lets the owner give each one: no access / can view / can view and edit,
  and sends the whole list at once. Company administrators already see every study, so they are listed as such.
-->
<script setup>
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    study: { type: Object, required: true },
});
const emit = defineEmits(['close']);

const { t } = useI18n();
const people = ref([]);
const access = ref({});
const loading = ref(true);
const failed = ref(false);
const busy = ref(false);

const initials = (name) => (name ?? '').trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('') || '?';

onMounted(async () => {
    try {
        const { data } = await axios.get(route('studies.share', props.study.id));
        people.value = data.people;
        access.value = Object.fromEntries(data.people.map((p) => [p.id, p.access]));
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
});

function save() {
    busy.value = true;
    router.put(route('studies.share.update', props.study.id), { access: access.value }, {
        preserveScroll: true,
        onFinish: () => { busy.value = false; },
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <ConfirmDialog open :title="t('studies.share.title', { name: study.name })" :confirm-label="t('studies.share.save')" :busy="busy || loading || failed" @confirm="save" @cancel="emit('close')">
        <p class="mute small">{{ t('studies.share.help') }}</p>
        <p v-if="loading" class="mute" role="status">{{ t('common.loading') }}</p>
        <p v-else-if="failed" class="note bad" role="alert">{{ t('studies.share.failed') }}</p>
        <p v-else-if="!people.length" class="note">{{ t('studies.share.nobody') }}</p>
        <ul v-else class="st-people">
            <li v-for="p in people" :key="p.id" class="st-person">
                <span class="st-av" aria-hidden="true">{{ initials(p.name) }}</span>
                <span style="min-width:0">
                    <b>{{ p.name }}</b>
                    <small>{{ p.job_title || p.email }}</small>
                </span>
                <span v-if="p.admin" class="st-badge admin">{{ t('studies.share.alwaysSees') }}</span>
                <select v-else v-model="access[p.id]" class="sel" :aria-label="t('studies.share.accessFor', { name: p.name })">
                    <option value="none">{{ t('studies.share.none') }}</option>
                    <option value="view">{{ t('studies.share.view') }}</option>
                    <option value="edit">{{ t('studies.share.edit') }}</option>
                </select>
            </li>
        </ul>
    </ConfirmDialog>
</template>
