<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  ArrowLeft,
  Edit,
  Trash2
} from 'lucide-vue-next'
import { ref } from 'vue'
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

const props = defineProps<{
    targetField: {
        id: number
        field: string
        label: string
        category?: string
        description?: string
        type: string
        model?: string
        created_at: string
        updated_at: string
    }
}>()

const deleteDialogOpen = ref(false)

const confirmDelete = () => {
    router.delete(route('dashboard.organization.target-fields.destroy', props.targetField.id), {
        onSuccess: () => {
            deleteDialogOpen.value = false
        }
    })
}
</script>

<template>
  <Head :title="targetField.label" />

  <Default>
    <PageHeader
      :title="targetField.label"
      :description="targetField.description || 'Target field details'"
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
            <Link :href="route('dashboard.organization.target-fields.index')">
                <ArrowLeft class="w-4 h-4 mr-2" /> Back
            </Link>
        </Button>
        <Button variant="outline" size="sm" as-child>
            <Link :href="route('dashboard.organization.target-fields.edit', targetField.id)">
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
                <CardTitle>Field Information</CardTitle>
                <CardDescription>Configuration details for this field.</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <h4 class="text-sm font-medium text-muted-foreground">Field Key</h4>
                        <div class="font-mono text-sm bg-muted px-2 py-1 rounded w-fit">{{ targetField.field }}</div>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-medium text-muted-foreground">Display Label</h4>
                        <div class="font-medium">{{ targetField.label }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <h4 class="text-sm font-medium text-muted-foreground">Data Type</h4>
                        <Badge variant="outline">{{ targetField.type }}</Badge>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-medium text-muted-foreground">Category</h4>
                        <div v-if="targetField.category">{{ targetField.category }}</div>
                        <div v-else class="text-muted-foreground text-sm italic">None</div>
                    </div>
                </div>

                <div class="space-y-1">
                    <h4 class="text-sm font-medium text-muted-foreground">Related Model</h4>
                    <div v-if="targetField.model" class="font-mono text-sm">{{ targetField.model }}</div>
                    <div v-else class="text-muted-foreground text-sm italic">None</div>
                </div>

                <div class="space-y-1">
                    <h4 class="text-sm font-medium text-muted-foreground">Description</h4>
                    <p v-if="targetField.description" class="text-sm">{{ targetField.description }}</p>
                    <div v-else class="text-muted-foreground text-sm italic">No description provided.</div>
                </div>
            </CardContent>
             <CardFooter class="text-xs text-muted-foreground border-t pt-4 flex justify-between">
                <span>Created: {{ new Date(targetField.created_at).toLocaleDateString() }}</span>
                <span>Last Updated: {{ new Date(targetField.updated_at).toLocaleDateString() }}</span>
            </CardFooter>
        </Card>
    </div>

    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Target Field?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete <span class="font-semibold">{{ targetField.label }}</span>? 
            This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="confirmDelete" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Delete Field
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

  </Default>
</template>
