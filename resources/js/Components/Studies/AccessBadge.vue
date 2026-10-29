<!--
  CFO Hotels — AccessBadge (what the signed-in person is to a study)
  Location: resources/js/Components/Studies/AccessBadge.vue
  <AccessBadge access="owner" />  → owner | admin | edit | view
-->
<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import { useI18n } from '@/lang/i18n';

defineProps({ access: { type: String, default: null } });
const { t } = useI18n();
const icons = { owner: 'star', admin: 'users', edit: 'edit', view: 'eye' };
</script>

<template>
    <span v-if="access" class="st-badge" :class="access"><AppIcon :name="icons[access] ?? 'eye'" :size="12" />{{ t(`studies.access.${access}`) }}</span>
</template>
