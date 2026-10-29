<!--
  CFO Hotels — StudyTimeline (the study as a bar: development in amber, operation in green)
  Location: resources/js/Components/Studies/StudyTimeline.vue
  <StudyTimeline :years="5" :development-start="4" :development-duration="18" :operation-start="24" />
  Small on the list of studies, big (big) on the study form.
-->
<script setup>
import { computed } from 'vue';
import { timeline } from '@/Utils/studyTimeline';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    years: { type: [Number, String], required: true },
    developmentStart: { type: [Number, String], default: 0 },
    developmentDuration: { type: [Number, String], default: 0 },
    operationStart: { type: [Number, String], default: 0 },
    big: { type: Boolean, default: false },
});

const { t } = useI18n();
const parts = computed(() => timeline({
    years: props.years, developmentStart: props.developmentStart, developmentDuration: props.developmentDuration, operationStart: props.operationStart,
}));
const label = computed(() => t('studies.timelineLabel', {
    years: props.years,
    dev: props.developmentDuration || 0,
    op: props.operationStart || 0,
}));
</script>

<template>
    <div :class="big ? 'sf-time-bar' : 'st-tl'" role="img" :aria-label="label">
        <i v-if="parts.dev" class="dev" :style="{ '--a': `${parts.dev.a}%`, '--w': `${parts.dev.w}%` }" />
        <i v-if="parts.ops" class="ops" :style="{ '--a': `${parts.ops.a}%`, '--w': `${parts.ops.w}%` }" />
    </div>
</template>
