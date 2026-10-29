<!--
  CFO Hotels — SectionNav (the side navigator of a long form)
  Location: resources/js/Components/Form/SectionNav.vue
  <SectionNav :items="[{ id: 'sec-main', title: '...', state: 'done' | 'bad' | '' , badge: '2' }]"> <template #summary>...</template> </SectionNav>
  · Stays in view while the form scrolls (on a phone it becomes a strip across the top).
  · Highlights the section being read and scrolls smoothly to the one you click.
  · A section with a problem shows a red mark; a completed one a green mark.
  · Can be folded to a narrow rail of numbers so the form gets the width (the choice is remembered on this browser, for every form).
-->
<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useI18n } from '@/lang/i18n';
import { storageGet, storageSet } from '@/Utils/safeStorage';

const props = defineProps({
    items: { type: Array, required: true },
    title: { type: String, default: '' },
});

const STORAGE_KEY = 'zavero.sectionNav';

const { t } = useI18n();
const current = ref(props.items[0]?.id ?? '');
const collapsed = ref(storageGet(STORAGE_KEY) === 'collapsed');
let observer = null;

function go(event, id) {
    event.preventDefault();
    current.value = id;
    document.getElementById(id)?.scrollIntoView?.({ behavior: 'smooth', block: 'start' });
}

function toggle() {
    collapsed.value = !collapsed.value;
    storageSet(STORAGE_KEY, collapsed.value ? 'collapsed' : 'open');
}

onMounted(() => {
    if (typeof IntersectionObserver === 'undefined') return;
    observer = new IntersectionObserver((entries) => {
        const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
        if (visible) current.value = visible.target.id;
    }, { rootMargin: '-90px 0px -60% 0px', threshold: 0 });
    props.items.forEach((item) => {
        const el = document.getElementById(item.id);
        if (el) observer.observe(el);
    });
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <nav class="sf-nav" :class="{ 'is-collapsed': collapsed }" :aria-label="title || t('studies.sections.title')">
        <div class="sf-nav-head">
            <div v-if="title" class="sf-nav-title">{{ title }}</div>
            <button
                type="button" class="sf-nav-toggle" data-nav-toggle :aria-expanded="collapsed ? 'false' : 'true'"
                :title="collapsed ? t('studies.sections.expand') : t('studies.sections.collapse')"
                :aria-label="collapsed ? t('studies.sections.expand') : t('studies.sections.collapse')" @click="toggle"
            >
                <AppIcon name="chevron" :size="16" />
            </button>
        </div>
        <a
            v-for="(item, i) in items" :key="item.id" :href="`#${item.id}`" :class="item.state"
            :title="collapsed ? item.title : undefined" :aria-label="collapsed ? item.title : undefined"
            :aria-current="current === item.id ? 'true' : undefined" @click="go($event, item.id)"
        >
            <span class="sf-nav-no" aria-hidden="true">{{ item.state === 'done' ? '✓' : (item.state === 'bad' ? '!' : i + 1) }}</span>
            <span class="sf-nav-t">{{ item.title }}</span>
            <span v-if="item.badge" class="sf-nav-st">{{ item.badge }}</span>
        </a>
        <div v-if="$slots.summary" class="sf-nav-sum"><slot name="summary" /></div>
    </nav>
</template>
