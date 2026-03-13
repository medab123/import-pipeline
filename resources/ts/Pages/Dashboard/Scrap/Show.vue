<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref } from 'vue'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
  ArrowLeft,
  Edit,
  Trash2,
} from 'lucide-vue-next'

interface ScrapData {
  id: number
  dealerId: number
  dealerName: string
  ftpFilePath: string
  provider: string
  createdAt: string
  formattedCreatedAt: string
  updatedAt: string
  formattedUpdatedAt: string
}

const props = defineProps<{
  scrap: ScrapData
}>()

const deleteDialogOpen = ref(false)

const confirmDelete = () => {
  router.delete(route('dashboard.scraps.destroy', props.scrap.id), {
    onSuccess: () => {
      deleteDialogOpen.value = false
    }
  })
}
</script>

<template>
  <Head title="Scrap Source" />

  <Default>
    <PageHeader
      :title="`${scrap.provider} Scrap Source`"
      :description="`Scrap source details for ${scrap.dealerName}`"
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.scraps.index')">
            <ArrowLeft class="w-4 h-4 mr-2" /> Back
          </Link>
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.scraps.edit', scrap.id)">
            <Edit class="w-4 h-4 mr-2" /> Edit
          </Link>
        </Button>
        <Button variant="destructive" size="sm" @click="deleteDialogOpen = true">
          <Trash2 class="w-4 h-4 mr-2" /> Delete
        </Button>
      </template>
    </PageHeader>

    <div class="w-full space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Scrap Source Information</CardTitle>
          <CardDescription>Details for this FTP scrap data source.</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Dealer</h4>
              <div class="font-medium">
                <Link :href="route('dashboard.dealers.show', scrap.dealerId)" class="text-primary hover:underline">
                  {{ scrap.dealerName }}
                </Link>
              </div>
            </div>
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Provider</h4>
              <div class="font-medium">{{ scrap.provider }}</div>
            </div>
          </div>

          <div class="space-y-1">
            <h4 class="text-sm font-medium text-muted-foreground">FTP File Path</h4>
            <div class="font-mono text-sm bg-muted px-3 py-2 rounded w-fit">{{ scrap.ftpFilePath }}</div>
          </div>
        </CardContent>
        <CardFooter class="text-xs text-muted-foreground border-t pt-4 flex justify-between">
          <span>Created: {{ scrap.formattedCreatedAt }}</span>
          <span>Last Updated: {{ scrap.formattedUpdatedAt }}</span>
        </CardFooter>
      </Card>
    </div>

    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Scrap Source?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete the <span class="font-semibold">{{ scrap.provider }}</span> scrap source?
            This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="confirmDelete" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Delete Scrap Source
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </Default>
</template>
