<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        items: NavItem[];
        masterItems?: NavItem[];
        settingItems?: NavItem[];
    }>(),
    {
        masterItems: () => [],
        settingItems: () => [],
    },
);

const page = usePage();
const open = ref<Record<string, boolean>>({});

function toggle(key: string) {
    open.value[key] = !open.value[key];
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <template v-if="item.children && item.children.length">
                    <div class="flex items-center justify-between">
                        <SidebarMenuButton as-child :is-active="item.href === page.url" :tooltip="item.title">
                            <button type="button" class="flex w-full items-center" @click.prevent="toggle(item.title)">
                                <component :is="item.icon" />
                                <span class="ml-2">{{ item.title }}</span>
                            </button>
                        </SidebarMenuButton>
                        <button type="button" class="text-sm text-gray-500" @click.prevent="toggle(item.title)">▾</button>
                    </div>

                    <SidebarMenu v-show="open[item.title]" class="pl-4">
                        <SidebarMenuItem v-for="child in item.children" :key="child.title">
                            <SidebarMenuButton as-child :is-active="child.href === page.url" :tooltip="child.title">
                                <Link :href="child.href">
                                    <component :is="child.icon" />
                                    <span>{{ child.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </template>
                <template v-else>
                    <SidebarMenuButton as-child :is-active="item.href === page.url" :tooltip="item.title">
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </template>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>

    <!-- <SidebarGroup v-if="props.masterItems && props.masterItems.length" class="px-2 py-0">
        <SidebarGroupLabel>Master</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="m in props.masterItems" :key="m.title">
                <SidebarMenuButton as-child :is-active="m.href === page.url" :tooltip="m.title">
                    <Link :href="m.href">
                        <component :is="m.icon" />
                        <span>{{ m.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup> -->

    <!-- <SidebarGroup v-if="props.settingItems && props.settingItems.length" class="px-2 py-0">
        <SidebarGroupLabel>Setting</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="s in props.settingItems" :key="s.title">
                <SidebarMenuButton as-child :is-active="s.href === page.url" :tooltip="s.title">
                    <Link :href="s.href">
                        <component :is="s.icon" />
                        <span>{{ s.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup> -->
</template>
