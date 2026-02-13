<script setup lang="ts">
import { computed } from 'vue'
import type { SidebarProps } from '@/components/ui/sidebar'
import { route } from 'ziggy-js'

import {
    Upload,
    Users,
    Activity,
    Settings,
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

// Permission helpers (similar to Laravel's can/cant)
const { authUser, can } = usePermissions()

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
        }
    }

    return {
        name: user.name || 'User',
        email: user.email || '',
        avatar: null, // You can add avatar URL if available in the future
        initials: getUserInitials(user.name),
    }
})

const data = computed(() => {
    const navMain = [
        {
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
        },
    ]

    if (can('manage users')) {
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
            ],
        })

        // Settings Group
        navMain.push({
            title: "Settings",
            url: "#", // Placeholder or redirect to first item
            icon: Settings,
            isActive: route().current()?.startsWith('dashboard.organization.tokens') || route().current()?.startsWith('dashboard.organization.target-fields'),
            items: [
                {
                    title: "Target Fields",
                    url: route('dashboard.organization.target-fields.index'),
                },
                {
                    title: "Token Management",
                    url: route('dashboard.organization.tokens.index'),
                },
            ],
        })
    }
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
