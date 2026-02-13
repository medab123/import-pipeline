<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableEmpty } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Pagination } from '@/components/ui/pagination'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
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
  Plus,
  MoreHorizontal,
  Edit,
  Trash2,
  Search,
  Type,
  Eye,
  X,
  Database
} from 'lucide-vue-next'

interface TargetField {
  id: number
  field: string
  label: string
  category?: string
  description?: string
  type: string
  model?: string
  created_at: string
}

interface Props {
  targetFields: {
    data: TargetField[]
    currentPage: number
    lastPage: number
    perPage: number
    total: number
    nextPageUrl: string
    previousPageUrl: string
  }
  filters: {
    search?: string
  }
  organizationName: string
}

const props = defineProps<Props>()

// Search
const searchQuery = ref(props.filters.search || '')
const performSearch = useDebounceFn(() => {
  router.get(
    route('dashboard.organization.target-fields.index'),
    { search: searchQuery.value },
    { preserveState: true, replace: true }
  )
}, 300)

watch(searchQuery, performSearch)

const clearSearch = () => {
  searchQuery.value = ''
}

// Delete Dialog
const deleteDialogOpen = ref(false)
const fieldToDelete = ref<TargetField | null>(null)

const openDeleteDialog = (field: TargetField) => {
  fieldToDelete.value = field
  deleteDialogOpen.value = true
}

const confirmDelete = () => {
    if (!fieldToDelete.value) return 

    router.delete(route('dashboard.organization.target-fields.destroy', fieldToDelete.value.id), {
        onSuccess: () => {
            deleteDialogOpen.value = false
            fieldToDelete.value = null
        }
    })
}

// Helpers
const getTypeBadgeVariant = (type: string) => {
    switch (type) {
        case 'string': return 'default'
        case 'integer': return 'secondary'
        case 'boolean': return 'outline'
        case 'date': 
        case 'datetime': return 'outline'
        default: return 'secondary'
    }
}
</script>

<template>
  <Head title="Target Fields" />

  <Default>
    <PageHeader
      title="Target Fields"
      :description="`Manage data mapping fields for ${organizationName}`"
    >
      <template #actions>
        <Button as-child>
          <Link :href="route('dashboard.organization.target-fields.create')">
            <Plus class="w-4 h-4 mr-2" />
            Add Target Field
          </Link>
        </Button>
      </template>
    </PageHeader>

    <Card class="shadow-sm">
      <CardHeader class="pb-4 border-b">
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <CardTitle class="text-xl font-bold">Fields</CardTitle>
                <CardDescription>
                    Define the fields that your imports will map to.
                </CardDescription>
            </div>
            <div class="relative w-full max-w-sm">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="searchQuery"
                  type="search"
                  placeholder="Search fields..."
                  class="pl-9 pr-9"
                />
                 <button
                  v-if="searchQuery"
                  @click="clearSearch"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                >
                  <X class="h-4 w-4" />
                </button>
            </div>
        </div>
      </CardHeader>
      <CardContent class="pt-6">
        <div class="overflow-x-auto rounded-lg border bg-background">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Field Key</TableHead>
                        <TableHead>Label</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Model</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="targetFields.data.length === 0" :colspan="6">
                         <div class="text-center py-12">
                            <div class="bg-muted/50 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4">
                                <Database class="w-6 h-6 text-muted-foreground" />
                            </div>
                            <h3 class="font-semibold text-lg">No fields defined</h3>
                            <p class="text-muted-foreground text-sm mt-1 mb-4">Start by adding your first target field.</p>
                            <Button as-child variant="outline">
                                <Link :href="route('dashboard.organization.target-fields.create')">
                                    Create Field
                                </Link>
                            </Button>
                         </div>
                    </TableEmpty>
                    <TableRow v-for="field in targetFields.data" :key="field.id">
                        <TableCell class="font-mono text-sm">{{ field.field }}</TableCell>
                        <TableCell class="font-medium">{{ field.label }}</TableCell>
                        <TableCell>
                            <Badge variant="secondary" v-if="field.category">{{ field.category }}</Badge>
                            <span v-else class="text-muted-foreground">-</span>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="getTypeBadgeVariant(field.type)">{{ field.type }}</Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground text-sm">{{ field.model || '-' }}</TableCell>
                        <TableCell class="text-right">
                             <div class="flex justify-end gap-2">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="route('dashboard.organization.target-fields.show', field.id)">
                                        <Eye class="w-4 h-4" />
                                        <span class="sr-only">View</span>
                                    </Link>
                                </Button>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal class="w-4 h-4" />
                                            <span class="sr-only">Menu</span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <Link :href="route('dashboard.organization.target-fields.edit', field.id)">
                                                <Edit class="w-4 h-4 mr-2" /> Edit
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem class="text-destructive" @click="openDeleteDialog(field)">
                                            <Trash2 class="w-4 h-4 mr-2" /> Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                             </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
        
        <div v-if="targetFields.total > targetFields.perPage" class="mt-4">
             <Pagination :paginator="targetFields" />
        </div>
      </CardContent>
    </Card>

    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Target Field?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete <span class="font-semibold">{{ fieldToDelete?.label }}</span>? 
            This might affect existing import pipelines relying on this field.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="confirmDelete" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Delete
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </Default>
</template>
