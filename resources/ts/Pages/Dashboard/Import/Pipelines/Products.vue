<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableEmpty } from '@/components/ui/table'
import { Pagination } from '@/components/ui/pagination'
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
  Search,
  Package,
  Eye,
  RefreshCw,
  X,
  Copy,
  Check,
  Trash2
} from 'lucide-vue-next'
import { PipelineViewModel } from '@/types/generated'
import { format, formatDistance } from 'date-fns'

interface Product {
  uuid: string
  stockNumber: string
  productData: Record<string, unknown>
  createdAt: string
  updatedAt: string
}

interface Props {
  pipeline: PipelineViewModel
  products: Product[]
  paginator: {
    currentPage: number
    lastPage: number
    perPage: number
    total: number
    from: number | null
    to: number | null
  }
  filters: {
    search?: string
  }
}

const props = defineProps<Props>()

// Search
const searchQuery = ref(props.filters.search || '')
const performSearch = useDebounceFn(() => {
  router.get(
    route('dashboard.import.pipelines.products', { pipeline: props.pipeline.id }),
    { search: searchQuery.value || undefined },
    { preserveState: true, replace: true }
  )
}, 300)

watch(searchQuery, performSearch)

const clearSearch = () => {
  searchQuery.value = ''
}

// Copy JSON
const copiedId = ref<string | null>(null)
const copyJson = (product: Product) => {
  navigator.clipboard.writeText(JSON.stringify(product.productData, null, 2))
  copiedId.value = product.uuid
  setTimeout(() => {
    copiedId.value = null
  }, 2000)
}

// Get preview fields (first few keys from productData)
const getPreviewFields = (data: Record<string, unknown>, max = 3): { key: string; value: string }[] => {
  const entries = Object.entries(data)
  return entries.slice(0, max).map(([key, value]) => ({
    key,
    value: typeof value === 'string' ? value : JSON.stringify(value),
  }))
}

const truncate = (text: string, length = 40): string => {
  if (text.length <= length) return text
  return text.substring(0, length) + '...'
}

const refresh = () => {
  router.reload({ only: ['products', 'paginator'] })
}

// Delete
const deleteDialogOpen = ref(false)
const productToDelete = ref<Product | null>(null)

const openDeleteDialog = (product: Product) => {
  productToDelete.value = product
  deleteDialogOpen.value = true
}

const confirmDelete = () => {
  if (!productToDelete.value) return

  router.delete(
    route('dashboard.import.pipelines.products.destroy', {
      pipeline: props.pipeline.id,
      inventory: productToDelete.value.uuid,
    }),
    {
      onSuccess: () => {
        deleteDialogOpen.value = false
        productToDelete.value = null
      },
    }
  )
}
</script>

<template>
  <Head :title="`${props.pipeline.name} - Products`" />

  <Default>
    <PageHeader
      :title="`Products: ${props.pipeline.name}`"
      description="Inventory of products imported by this pipeline"
    >
      <template #actions>
        <Button variant="outline" size="sm" @click="refresh">
          <RefreshCw class="w-4 h-4 mr-2" />
          Refresh
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.show', { pipeline: props.pipeline.id })">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Pipeline
          </Link>
        </Button>
      </template>
    </PageHeader>

    <Card class="shadow-sm w-full">
      <CardHeader class="pb-4 border-b">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="space-y-1">
            <CardTitle class="text-xl font-bold">Inventory</CardTitle>
            <CardDescription class="text-sm">
              Products tracked across pipeline runs. Only updated when data changes.
            </CardDescription>
          </div>
          <div class="flex items-center gap-4">
            <div class="text-sm text-muted-foreground whitespace-nowrap">
              Total: {{ paginator.total }} {{ paginator.total === 1 ? 'product' : 'products' }}
            </div>
            <div class="relative w-full max-w-xs">
              <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                v-model="searchQuery"
                type="search"
                placeholder="Search stock number..."
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
        </div>
      </CardHeader>
      <CardContent class="pt-6">
        <div class="overflow-x-auto rounded-lg border bg-background">
          <Table class="min-w-full">
            <TableHeader>
              <TableRow>
                <TableHead class="w-[180px]">Stock Number</TableHead>
                <TableHead>Product Data</TableHead>
                <TableHead class="w-[180px]">Created</TableHead>
                <TableHead class="w-[180px]">Last Updated</TableHead>
                <TableHead class="w-[120px] text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="products.length === 0" :colspan="5">
                <div class="text-center py-16 px-4">
                  <div class="mx-auto w-20 h-20 rounded-full bg-muted flex items-center justify-center mb-6">
                    <Package class="h-10 w-10 text-muted-foreground" />
                  </div>
                  <h3 class="mt-2 text-xl font-bold">No products yet</h3>
                  <p class="mt-2 text-sm text-muted-foreground max-w-md mx-auto">
                    Products will appear here after the pipeline runs with a serial number field configured on your target fields.
                  </p>
                </div>
              </TableEmpty>
              <TableRow
                v-for="product in products"
                :key="product.uuid"
                class="hover:bg-muted/50 transition-colors"
              >
                <TableCell class="font-mono text-sm font-medium">
                  <Link
                    :href="route('dashboard.import.pipelines.products.show', { pipeline: props.pipeline.id, inventory: product.uuid })"
                    class="hover:text-primary hover:underline transition-colors"
                  >
                    {{ product.stockNumber }}
                  </Link>
                </TableCell>
                <TableCell class="text-sm">
                  <div class="flex flex-wrap gap-2">
                    <Badge
                      v-for="field in getPreviewFields(product.productData)"
                      :key="field.key"
                      variant="outline"
                      class="font-normal text-xs"
                    >
                      <span class="text-muted-foreground mr-1">{{ field.key }}:</span>
                      {{ truncate(field.value, 30) }}
                    </Badge>
                    <Badge
                      v-if="Object.keys(product.productData).length > 3"
                      variant="secondary"
                      class="text-xs"
                    >
                      +{{ Object.keys(product.productData).length - 3 }} more
                    </Badge>
                  </div>
                </TableCell>
                <TableCell class="text-sm">
                  <div class="space-y-0.5">
                    <div class="whitespace-nowrap">{{ format(new Date(product.createdAt), 'MMM dd, yyyy HH:mm') }}</div>
                    <div class="text-xs text-muted-foreground whitespace-nowrap">
                      {{ formatDistance(new Date(product.createdAt), new Date(), { addSuffix: true }) }}
                    </div>
                  </div>
                </TableCell>
                <TableCell class="text-sm">
                  <div class="space-y-0.5">
                    <div class="whitespace-nowrap">{{ format(new Date(product.updatedAt), 'MMM dd, yyyy HH:mm') }}</div>
                    <div class="text-xs text-muted-foreground whitespace-nowrap">
                      {{ formatDistance(new Date(product.updatedAt), new Date(), { addSuffix: true }) }}
                    </div>
                  </div>
                </TableCell>
                <TableCell class="text-right" @click.stop>
                  <div class="flex items-center justify-end gap-1">
                    <Button variant="ghost" size="icon" title="Copy JSON" @click="copyJson(product)">
                      <Check v-if="copiedId === product.uuid" class="w-4 h-4 text-green-600" />
                      <Copy v-else class="w-4 h-4" />
                      <span class="sr-only">Copy JSON</span>
                    </Button>
                    <Button variant="ghost" size="icon" title="View Details" as-child>
                      <Link :href="route('dashboard.import.pipelines.products.show', { pipeline: props.pipeline.id, inventory: product.uuid })">
                        <Eye class="w-4 h-4" />
                        <span class="sr-only">View Details</span>
                      </Link>
                    </Button>
                    <Button variant="ghost" size="icon" title="Delete" @click="openDeleteDialog(product)">
                      <Trash2 class="w-4 h-4 text-destructive" />
                      <span class="sr-only">Delete</span>
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Pagination -->
        <div v-if="paginator.total > paginator.perPage" class="mt-6 pt-4 border-t">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-muted-foreground">
              Showing
              <span class="font-semibold text-foreground">{{ paginator.from || 0 }}</span>
              to
              <span class="font-semibold text-foreground">{{ paginator.to || 0 }}</span>
              of
              <span class="font-semibold text-foreground">{{ paginator.total }}</span>
              {{ paginator.total === 1 ? 'product' : 'products' }}
            </div>
            <Pagination :paginator="paginator" />
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Product?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete product
            <span class="font-semibold text-foreground font-mono">{{ productToDelete?.stockNumber }}</span>?
            This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction
            @click="confirmDelete"
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            Delete
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </Default>
</template>
