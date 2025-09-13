<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    Activity,
    BarChart2,
    BookOpen,
    ChartArea,
    CheckCircle2,
    Database,
    FileText,
    FolderTree,
    LayoutGrid,
    Ruler,
    Settings,
    Users,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Anggaran',
        href: '/anggaran',
        icon: ChartArea,
        children: [
            { title: 'Perencanaan', href: route('anggaran.perencanaan'), icon: FileText },
            { title: 'Evaluasi', href: route('evaluasi.index'), icon: CheckCircle2 },
            { title: 'Pelaporan', href: route('pelaporan.index'), icon: BarChart2 },
        ],
    },
    {
        title: 'Settings',
        href: '/settings',
        icon: Settings,
        children: [{ title: 'User', href: route('user'), icon: Users }],
    },
    {
        title: 'Master',
        href: '/master',
        icon: Database,
        children: [
            { title: 'Program', href: route('program'), icon: FolderTree },
            { title: 'Kegiatan', href: route('kegiatan.index'), icon: Activity },
            { title: 'Satuan', href: route('satuan.index'), icon: Ruler },
            { title: 'Rekening', href: route('rekening.index'), icon: FileText },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
