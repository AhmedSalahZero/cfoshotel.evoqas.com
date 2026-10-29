<!--
  CFO Hotels — StudyNotices (what the financial pages tell the person about the study, besides its figures)
  Location: resources/js/Components/Studies/StudyNotices.vue
  <StudyNotices :notices="notices" />   notices = { propertyMissing, propertyUrl } from App\Support\StudyNotices
  propertyMissing: the Property Acquisition Costs page was never saved, so the building is not carried to the fixed assets when the hotel opens.
-->
<script setup>
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import { useI18n } from '@/lang/i18n';

defineProps({
    notices: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
</script>

<template>
    <div v-if="notices?.propertyMissing" class="note bad" role="alert" data-property-missing>
        <AppIcon name="alert" :size="14" /> {{ t('fin.propertyMissing') }}
        <Link :href="notices.propertyUrl">{{ t('fin.propertyMissingLink') }}</Link>
    </div>
</template>
