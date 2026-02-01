
<script setup lang="ts">
import AppSidebar from "@/components/AppSidebar.vue"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
} from "@/components/ui/breadcrumb"
import { Separator } from "@/components/ui/separator"
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from "@/components/ui/sidebar"
import {usePage} from "@inertiajs/vue3";
import {watch} from "vue";
import { toast } from "vue-sonner";
import { Toaster } from '@/components/ui/sonner'
import 'vue-sonner/style.css' // vue-sonner v2 requires this import

interface ToastNotification {
  title: string;
  message: string;
  variant: 'default' | 'destructive';
}

watch(() => usePage().props.toastNotifications, (newPageNotifications: ToastNotification[]) => {
  if (newPageNotifications !== null) {
    setTimeout(() => {
      newPageNotifications.forEach((notification: ToastNotification) => {
        if (notification.variant === 'destructive') {
          toast.error(notification.title, {
            description: notification.message,
          });
        } else {
          toast.success(notification.title, {
            description: notification.message,
          });
        }
      });
    }, 100);
  }
}, { immediate: true });

</script>

<template>
  <Toaster position="top-right" />
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset>
      <header class="flex h-16 shrink-0 items-center gap-2 border-b bg-background/95 backdrop-blur-sm supports-[backdrop-filter]:bg-background/60 shadow-sm sticky top-0 z-40">
        <div class="flex items-center gap-2 px-4 w-full">
          <SidebarTrigger class="-ml-1 transition-all duration-200 hover:bg-accent rounded-md p-1.5 hover:scale-105" />
          <Separator orientation="vertical" class="mr-2 h-6" />
          <Breadcrumb class="flex-1">
            <BreadcrumbList>
              <BreadcrumbItem class="hidden md:block">
                <BreadcrumbLink href="#" class="transition-colors hover:text-foreground font-medium">
                  Inventory
                </BreadcrumbLink>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
        </div>
      </header>
      <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 pt-6 min-h-0 overflow-x-hidden">
        <slot/>
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>
