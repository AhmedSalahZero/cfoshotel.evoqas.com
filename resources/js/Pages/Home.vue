<!--
  CFO Hotels - Home ( /home )
  Location: resources/js/Pages/Home.vue
  The first screen after signing in: the quick actions, the studies the person worked on last, and a short reminder of how a study is built.
  Server side: App\Http\Controllers\HomeController.
-->
<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';

defineProps({
    recent: { type: Array, default: () => [] },
    studyCount: { type: Number, default: 0 },
    canCreate: { type: Boolean, default: false },
});

const { t } = useI18n();
const page = usePage();
const name = computed(() => page.props.auth.user.name);
const isCompanyAdmin = computed(() => page.props.auth.user.role === 'company_admin');
const flow = ['study', 'revenue', 'expenses', 'investment', 'results'];
</script>

<template>
    <AppLayout :title="t('home.title')" active="home">
        <div class="pagehead">
            <div>
                <h2>{{ t('home.welcome', { name }) }}</h2>
                <p class="lead mb0">{{ t('home.lead') }}</p>
            </div>
            <div class="actions">
                <Link v-if="canCreate" :href="route('studies.create')" class="btn" data-home-new><AppIcon name="plus" :size="15" />{{ t('home.newStudy') }}</Link>
                <Link :href="route('studies.index')" class="btn btn-line">{{ t('home.allStudies') }}</Link>
                <Link v-if="studyCount > 1" :href="route('studies.compare')" class="btn btn-line" data-home-compare><AppIcon name="trend" :size="15" />{{ t('home.compare') }}</Link>
                <template v-if="isCompanyAdmin">
                    <Link :href="route('types.index')" class="btn btn-line">{{ t('home.types') }}</Link>
                    <Link :href="route('users.index')" class="btn btn-line">{{ t('home.openPeople') }}</Link>
                </template>
            </div>
        </div>

        <section v-if="recent.length" class="card" style="--ca: var(--k1)" data-home="recent">
            <h3>{{ t('home.recentTitle') }}</h3>
            <p class="mute">{{ t('home.recentText') }}</p>
            <ul class="hm-list">
                <li v-for="s in recent" :key="s.id">
                    <Link :href="route('studies.edit', s.id)" class="hm-item">
                        <span class="hm-name">{{ s.name }}</span>
                        <span class="hm-meta">
                            <template v-if="s.property">{{ s.property }} &middot; </template>
                            <template v-if="s.stars">{{ t('studies.stars', { count: s.stars }) }} &middot; </template>{{ t('home.updated', { date: s.updated }) }}
                        </span>
                        <span class="hm-go">{{ t('home.open') }}</span>
                    </Link>
                </li>
            </ul>
        </section>

        <section v-else class="card" style="--ca: var(--k1)" data-home="empty">
            <h3>{{ t('home.emptyTitle') }}</h3>
            <p class="mute">{{ t('home.emptyText') }}</p>
            <Link v-if="canCreate" :href="route('studies.create')" class="btn"><AppIcon name="plus" :size="15" />{{ t('home.newStudy') }}</Link>
        </section>

        <section class="card" style="--ca: var(--k3)" data-home="flow">
            <h3>{{ t('home.flowTitle') }}</h3>
            <ol class="hm-flow">
                <li v-for="key in flow" :key="key">{{ t(`home.flow.${key}`) }}</li>
            </ol>
        </section>
    </AppLayout>
</template>

<style scoped>
.hm-list { margin: 8px 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 8px; }
.hm-item { display: grid; grid-template-columns: 1fr auto; gap: 2px 14px; align-items: center; padding: 12px 14px; border: 1px solid var(--line); border-radius: 12px; text-decoration: none; color: var(--ink); background: var(--card); }
.hm-item:hover { background: var(--hover); }
.hm-name { font-weight: 700; }
.hm-meta { grid-column: 1; font-size: 12.5px; color: var(--mute); }
.hm-go { grid-row: 1 / span 2; grid-column: 2; font-size: 13px; font-weight: 600; color: var(--k1); }
.hm-flow { margin: 8px 0 0; padding-inline-start: 22px; display: flex; flex-direction: column; gap: 6px; }
</style>
