<!--
  CFO Hotels — StudyTabs (the pages of one study, across the top of each of them)
  Location: resources/js/Components/Studies/StudyTabs.vue
  <StudyTabs :menu="menu" current="rooms" />   menu = [{ key, href, done }] from App\Support\StudyMenu
  The pages come in groups, in the order the work is done: Study · Revenue (the inputs) · Expenses · Results · Investment · Statements · Financial statements · Dashboard.
  The first row shows the groups, the second the pages of the group you are in.
  A green tick marks a page that has been saved at least once (a group is ticked when all its pages are).
-->
<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    menu: { type: Array, default: () => [] },
    current: { type: String, required: true },
});

const { t } = useI18n();
const icons = { study: 'building', rooms: 'bed', foods: 'utensils', casinos: 'dice', meetings: 'present', others: 'spark', revenue: 'coins', general: 'layers', marketing: 'spark', property: 'building', energy: 'spark', management: 'coins', startup: 'building', results: 'coins', land: 'building', ffe: 'layers', income: 'trend', cash: 'coins', balance: 'layers', ratios: 'star', dashboard: 'present', collection: 'coins', inventory: 'layers', disposables: 'tag', prepaid: 'calendar', fees: 'star', taxes: 'bank', assets: 'building', loans: 'trend' };

// the groups in the order the work is done: what you enter, what it costs, what it gives, what it needs, then the statements and the dashboard
const ORDER = ['study', 'inputs', 'expenses', 'results', 'investment', 'statements', 'financial', 'dashboard'];
const groupOf = (key) => (key === 'study' ? 'study' : key === 'dashboard' ? 'dashboard' : key === 'revenue' || key === 'expenses.results' ? 'results' : key.startsWith('investment.') ? 'investment' : key.startsWith('statements.') ? 'statements' : key.startsWith('financial.') ? 'financial' : key.startsWith('expenses.') ? 'expenses' : 'inputs');
const iconOf = (key) => icons[key.replace(/^(expenses|investment|statements|financial)\./, '')] ?? 'layers';
const labelOf = (key) => (key.startsWith('financial.') ? t(`fin.menu.${key.slice(10)}`) : key.startsWith('statements.') ? t(`stmt.menu.${key.slice(11)}`) : key.startsWith('investment.') ? t(`investment.menu.${key.slice(11)}`) : key.startsWith('expenses.') ? t(`expenses.menu.${key.slice(9)}`) : t(`revenue.menu.${key}`));

const groups = computed(() => {
    const out = [];
    for (const item of props.menu) {
        const id = groupOf(item.key);
        let group = out.find((g) => g.id === id);
        if (!group) {
            group = { id, items: [] };
            out.push(group);
        }
        group.items.push(item);
    }

    return out.sort((a, b) => ORDER.indexOf(a.id) - ORDER.indexOf(b.id));
});
const active = computed(() => groups.value.find((g) => g.items.some((i) => i.key === props.current)) ?? groups.value[0]);
const ticked = (item) => item.done && item.key !== 'study';
</script>

<template>
    <div v-if="menu.length" class="rv-tabset">
        <nav v-if="groups.length > 1" class="rv-tabs rv-groups" :aria-label="t('revenue.menu.title')">
            <Link
                v-for="g in groups" :key="g.id" :href="g.items[0].href" :aria-current="g.id === active?.id ? 'true' : undefined"
                :class="{ done: g.id !== 'study' && g.items.every((i) => i.done) }"
            >{{ t(`expenses.groups.${g.id}`) }}</Link>
        </nav>
        <nav v-if="active && active.items.length > 1" class="rv-tabs" :aria-label="t(`expenses.groups.${active.id}`)">
            <Link v-for="item in active.items" :key="item.key" :href="item.href" :aria-current="item.key === current ? 'page' : undefined" :class="{ done: ticked(item) }">
                <AppIcon :name="iconOf(item.key)" :size="15" />{{ labelOf(item.key) }}
                <AppIcon v-if="ticked(item)" name="check" :size="12" class="rv-tick" />
            </Link>
        </nav>
    </div>
</template>
