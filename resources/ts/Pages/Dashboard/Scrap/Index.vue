<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableEmpty } from '@/components/ui/table'
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
  Eye,
  X,
  HardDrive,
} from 'lucide-vue-next'

interface ScrapItem {
  id: number
  dealerId: number
  dealerName: string
  ftpFilePath: string
  provider: string
  formattedCreatedAt: string
}

interface Props {
  scraps: ScrapItem[]
  paginator: {
    currentPage: number
    lastPage: number
    perPage: number
    total: number
    nextPageUrl: string | null
    previousPageUrl: string | null
  }
  filters: {
    search?: string
  }
}

const props = defineProps<Props>()

const searchQuery = ref(props.filters.search || '')
const performSearch = useDebounceFn(() => {
  router.get(
    route('dashboard.scraps.index'),
    { search: searchQuery.value },
    { preserveState: true, replace: true }
  )
}, 300)

watch(searchQuery, performSearch)

const clearSearch = () => {
  searchQuery.value = ''
}

const deleteDialogOpen = ref(false)
const itemToDelete = ref<ScrapItem | null>(null)

const openDeleteDialog = (item: ScrapItem) => {
  itemToDelete.value = item
  deleteDialogOpen.value = true
}

const confirmDelete = () => {
  if (!itemToDelete.value) return
  router.delete(route('dashboard.scraps.destroy', itemToDelete.value.id), {
    onSuccess: () => {
      deleteDialogOpen.value = false
      itemToDelete.value = null
    }
  })
}
</script>

<template>
  <Head title="Scrap Sources" />

  <Default>
    <PageHeader
      title="Scrap Sources"
      description="Manage FTP scrap data sources for your dealers."
    >
      <template #actions>
        <Button as-child>
          <Link :href="route('dashboard.scraps.create')">
            <Plus class="w-4 h-4 mr-2" />
            Add Scrap Source
          </Link>
        </Button>
      </template>
    </PageHeader>

    <Card class="shadow-sm">
      <CardHeader class="pb-4 border-b">
        <div class="flex items-center justify-between">
          <div class="space-y-1">
            <CardTitle class="text-xl font-bold">All Scrap Sources</CardTitle>
            <CardDescription>
              A list of all FTP scrap sources across dealers.
            </CardDescription>
          </div>
          <div class="relative w-full max-w-sm">
            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="searchQuery"
              type="search"
              placeholder="Search scraps..."
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
                <TableHead>Dealer</TableHead>
                <TableHead>Provider</TableHead>
                <TableHead>FTP File Path</TableHead>
                <TableHead>Created</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="scraps.length === 0" :colspan="5">
                <div class="text-center py-12">
                  <div class="bg-muted/50 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4">
                    <HardDrive class="w-6 h-6 text-muted-foreground" />
                  </div>
                  <h3 class="font-semibold text-lg">No scrap sources found</h3>
                  <p class="text-muted-foreground text-sm mt-1 mb-4">Start by adding your first scrap source.</p>
                  <Button as-child variant="outline">
                    <Link :href="route('dashboard.scraps.create')">
                      Add Scrap Source
                    </Link>
                  </Button>
                </div>
              </TableEmpty>
              <TableRow v-for="scrap in scraps" :key="scrap.id">
                <TableCell class="font-medium">{{ scrap.dealerName }}</TableCell>
                <TableCell>{{ scrap.provider }}</TableCell>
                <TableCell class="font-mono text-sm">{{ scrap.ftpFilePath }}</TableCell>
                <TableCell>{{ scrap.formattedCreatedAt }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="icon" as-child>
                      <Link :href="route('dashboard.scraps.show', scrap.id)">
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
                          <Link :href="route('dashboard.scraps.edit', scrap.id)">
                            <Edit class="w-4 h-4 mr-2" /> Edit
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem class="text-destructive" @click="openDeleteDialog(scrap)">
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

        <div v-if="paginator.total > paginator.perPage" class="mt-4">
          <Pagination :paginator="paginator" />
        </div>
      </CardContent>
    </Card>

    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Scrap Source?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete the scrap source <span class="font-semibold">{{ itemToDelete?.provider }}</span>?
            This action cannot be undone.
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
