<script setup lang="ts">
import { computed } from 'vue'
import type { SidebarProps } from '@/components/ui/sidebar'
import { route } from 'ziggy-js'

import {
    Upload,
    Users,
    Settings,
    Store,
} from "lucide-vue-next"
import NavMain from '@/components/NavMain.vue'
import NavUser from '@/components/NavUser.vue'
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar'
import { usePermissions } from '@/composables/usePermissions'
import {usePage} from "@inertiajs/vue3";

const props = withDefaults(defineProps<SidebarProps>(), {
    variant: "inset",
})

const { can, roles } = usePermissions()

// Generate user initials from name
const getUserInitials = (name: string | null | undefined): string => {
    if (!name) return 'U'
    return name
        .split(' ')
        .map(word => word.charAt(0).toUpperCase())
        .slice(0, 2)
        .join('')
}

const userData = computed(() => {
    const user = usePage().props.auth.user
    if (!user) {
        return {
            name: 'Guest',
            email: '',
            avatar: null,
            initials: 'G',
            role: '',
        }
    }

    return {
        name: user.name || 'User',
        email: user.email || '',
        avatar: null,
        initials: getUserInitials(user.name),
        role: roles.value[0] ?? '',
    }
})

const data = computed(() => {
    const navMain: Array<{
        title: string
        url: string
        icon: typeof Upload
        isActive: boolean
        items: Array<{ title: string; url: string }>
    }> = []

    // Import section - visible to users with 'view pipelines' permission
    if (can('view pipelines')) {
        navMain.push({
            title: "Import",
            url: route('dashboard.import.pipelines.index'),
            icon: Upload,
            isActive: true,
            items: [
                {
                    title: "List",
                    url: route('dashboard.import.pipelines.index'),
                },
            ],
        })
    }

    // Dealers section - visible to users with 'view dealers' permission
    if (can('view dealers')) {
        navMain.push({
            title: "Dealers",
            url: route('dashboard.dealers.index'),
            icon: Store,
            isActive: route().current()?.startsWith('dashboard.dealers') ?? false,
            items: [
                {
                    title: "All Dealers",
                    url: route('dashboard.dealers.index'),
                },
                {
                    title: "Transactions",
                    url: route('dashboard.payment-transactions.index'),
                },
                {
                    title: "Scrap Sources",
                    url: route('dashboard.scraps.index'),
                },
            ],
        })
    }

    // Organization & Settings - visible only to users with 'manage organization' permission
    if (can('manage organization')) {
        navMain.push({
            title: "Organization",
            url: route('dashboard.organization.users.index'),
            icon: Users,
            isActive: route().current()?.startsWith('dashboard.organization') ?? false,
            items: [
                {
                    title: "Users",
                    url: route('dashboard.organization.users.index'),
                },
                {
                    title: "API Tokens",
                    url: route('dashboard.organization.tokens.index'),
                },
            ],
        })

        navMain.push({
            title: "Settings",
            url: "#",
            icon: Settings,
            isActive: route().current()?.startsWith('dashboard.organization.target-fields') ?? false,
            items: [
                {
                    title: "Target Fields",
                    url: route('dashboard.organization.target-fields.index'),
                },
            ],
        })
    }


    return {
        user: userData.value,
        navMain,
    }
})
</script>

<template>
    <Sidebar v-bind="props">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <a href="#" class="w-full">
                            <div class="flex w-full items-center justify-center">
                                <img src="/logo.svg" alt="Logo" class="h-8 w-auto object-contain" />
                            </div>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <SidebarContent>
            <NavMain :items="data.navMain" />
        </SidebarContent>
        <SidebarFooter>
            <NavUser :user="data.user" />
        </SidebarFooter>
    </Sidebar>
</template>
