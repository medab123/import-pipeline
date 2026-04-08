<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref } from 'vue'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
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
import { Input } from '@/components/ui/input'
import {
  ArrowLeft,
  Package,
  Copy,
  Check,
  Download,
  Calendar,
  Hash,
  Trash2,
  Search,
  X,
} from 'lucide-vue-next'
import { PipelineViewModel } from '@/types/generated'
import { format, formatDistance } from 'date-fns'
import { computed } from 'vue'

interface Product {
  uuid: string
  stockNumber: string
  productData: Record<string, unknown>
  createdAt: string
  updatedAt: string
}

interface Props {
  pipeline: PipelineViewModel
  product: Product
}

const props = defineProps<Props>()

// Copy state
const copied = ref(false)

const copyJson = () => {
  navigator.clipboard.writeText(JSON.stringify(props.product.productData, null, 2))
  copied.value = true
  setTimeout(() => {
    copied.value = false
  }, 2000)
}

const downloadJson = () => {
  const blob = new Blob([JSON.stringify(props.product.productData, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `product-${props.product.stockNumber}.json`
  a.click()
  URL.revokeObjectURL(url)
}

// Separate images from other data
const isImageValue = (value: unknown): boolean => {
  if (typeof value !== 'string') return false
  return /^https?:\/\/.+\.(jpg|jpeg|png|gif|webp|svg|bmp|avif)/i.test(value)
}

const isImageArray = (value: unknown): boolean => {
  if (!Array.isArray(value)) return false
  return value.length > 0 && value.every(v => typeof v === 'string' && isImageValue(v))
}

const allProductFields = Object.entries(props.product.productData).filter(
  ([, value]) => !isImageValue(value) && !isImageArray(value)
)

const imageFields = Object.entries(props.product.productData).filter(
  ([, value]) => isImageValue(value) || isImageArray(value)
)

// Search fields
const fieldSearch = ref('')

const productFields = computed(() => {
  if (!fieldSearch.value) return allProductFields
  const q = fieldSearch.value.toLowerCase()
  return allProductFields.filter(([key, value]) => {
    const valStr = value === null || value === undefined ? '' : String(value).toLowerCase()
    return key.toLowerCase().includes(q) || valStr.includes(q)
  })
})

const getImageUrls = (value: unknown): string[] => {
  if (typeof value === 'string') return [value]
  if (Array.isArray(value)) return value.filter(v => typeof v === 'string')
  return []
}

const formatValue = (value: unknown): string => {
  if (value === null || value === undefined) return '-'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

// Delete
const deleteDialogOpen = ref(false)

const confirmDelete = () => {
  router.delete(
    route('dashboard.import.pipelines.products.destroy', {
      pipeline: props.pipeline.id,
      inventory: props.product.uuid,
    }),
    {
      onSuccess: () => {
        deleteDialogOpen.value = false
      },
    }
  )
}
</script>

<template>
  <Head :title="`Product ${product.stockNumber} - ${pipeline.name}`" />

  <Default>
    <PageHeader
      :title="`Product: ${product.stockNumber}`"
      :description="`Imported by pipeline: ${pipeline.name}`"
    >
      <template #actions>
        <Button variant="outline" size="sm" @click="copyJson">
          <Check v-if="copied" class="w-4 h-4 mr-2 text-green-600" />
          <Copy v-else class="w-4 h-4 mr-2" />
          {{ copied ? 'Copied!' : 'Copy JSON' }}
        </Button>
        <Button variant="outline" size="sm" @click="downloadJson">
          <Download class="w-4 h-4 mr-2" />
          Download JSON
        </Button>
        <Button variant="destructive" size="sm" @click="deleteDialogOpen = true">
          <Trash2 class="w-4 h-4 mr-2" />
          Delete
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.products', { pipeline: pipeline.id })">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Products
          </Link>
        </Button>
      </template>
    </PageHeader>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Product Info -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Package class="w-5 h-5" />
            Product Information
          </CardTitle>
          <CardDescription>Identity and tracking details</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Stock Number</div>
            <div class="flex items-center gap-2">
              <Hash class="w-4 h-4 text-muted-foreground" />
              <span class="text-lg font-semibold font-mono">{{ product.stockNumber }}</span>
            </div>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">UUID</div>
            <div class="font-mono text-xs text-muted-foreground bg-muted px-2 py-1 rounded w-fit">{{ product.uuid }}</div>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Total Fields</div>
            <Badge variant="secondary">{{ Object.keys(product.productData).length }} fields</Badge>
          </div>
        </CardContent>
        <CardFooter class="text-xs text-muted-foreground border-t pt-4 flex flex-col items-start gap-2">
          <div class="flex items-center gap-2">
            <Calendar class="w-3 h-3" />
            Created: {{ format(new Date(product.createdAt), 'PPpp') }}
            <span class="text-muted-foreground/60">({{ formatDistance(new Date(product.createdAt), new Date(), { addSuffix: true }) }})</span>
          </div>
          <div class="flex items-center gap-2">
            <Calendar class="w-3 h-3" />
            Updated: {{ format(new Date(product.updatedAt), 'PPpp') }}
            <span class="text-muted-foreground/60">({{ formatDistance(new Date(product.updatedAt), new Date(), { addSuffix: true }) }})</span>
          </div>
        </CardFooter>
      </Card>

      <!-- Pipeline Info -->
      <Card>
        <CardHeader>
          <CardTitle>Pipeline Source</CardTitle>
          <CardDescription>The pipeline that imports this product</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Pipeline Name</div>
            <Link
              :href="route('dashboard.import.pipelines.show', { pipeline: pipeline.id })"
              class="text-primary hover:underline font-medium"
            >
              {{ pipeline.name }}
            </Link>
          </div>
          <div v-if="pipeline.description" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Description</div>
            <div class="text-sm">{{ pipeline.description }}</div>
          </div>
          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Frequency</div>
            <Badge variant="outline">{{ pipeline.frequency }}</Badge>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Product Data Fields -->
    <Card>
      <CardHeader>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="space-y-1">
            <CardTitle>Product Data</CardTitle>
            <CardDescription>All fields and values for this product</CardDescription>
          </div>
          <div class="relative w-full max-w-xs">
            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="fieldSearch"
              type="search"
              placeholder="Filter fields..."
              class="pl-9 pr-9"
            />
            <button
              v-if="fieldSearch"
              @click="fieldSearch = ''"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div class="rounded-lg border overflow-hidden">
          <table class="w-full">
            <thead>
              <tr class="bg-muted/50">
                <th class="text-left text-sm font-medium text-muted-foreground px-4 py-3 w-[250px]">Field</th>
                <th class="text-left text-sm font-medium text-muted-foreground px-4 py-3">Value</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="([key, value], index) in productFields"
                :key="key"
                class="border-t hover:bg-muted/30 transition-colors"
                :class="{ 'bg-muted/10': index % 2 === 0 }"
              >
                <td class="px-4 py-3 text-sm font-mono font-medium text-muted-foreground align-top">
                  {{ key }}
                </td>
                <td class="px-4 py-3 text-sm break-all">
                  <span v-if="value === null || value === undefined" class="text-muted-foreground italic">null</span>
                  <Badge v-else-if="typeof value === 'boolean'" :variant="value ? 'default' : 'secondary'">
                    {{ value ? 'Yes' : 'No' }}
                  </Badge>
                  <pre v-else-if="typeof value === 'object'" class="bg-muted p-2 rounded text-xs font-mono overflow-auto max-h-40">{{ JSON.stringify(value, null, 2) }}</pre>
                  <span v-else>{{ formatValue(value) }}</span>
                </td>
              </tr>
              <tr v-if="productFields.length === 0" class="border-t">
                <td colspan="2" class="px-4 py-8 text-center text-sm text-muted-foreground">
                  {{ fieldSearch ? 'No fields match your search.' : 'No data fields found.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>

    <!-- Images -->
    <Card v-if="imageFields.length > 0">
      <CardHeader>
        <CardTitle>Images</CardTitle>
        <CardDescription>Image URLs found in product data</CardDescription>
      </CardHeader>
      <CardContent>
        <div v-for="[key, value] in imageFields" :key="key" class="mb-6 last:mb-0">
          <div class="text-sm font-mono font-medium text-muted-foreground mb-3">{{ key }}</div>
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div
              v-for="(url, idx) in getImageUrls(value)"
              :key="idx"
              class="group relative aspect-square rounded-lg border overflow-hidden bg-muted"
            >
              <img
                :src="url"
                :alt="`${key} ${idx + 1}`"
                class="w-full h-full object-cover"
                loading="lazy"
                @error="(e) => (e.target as HTMLImageElement).style.display = 'none'"
              />
              <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2">
                <a
                  :href="url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-white text-xs truncate hover:underline w-full"
                  @click.stop
                >
                  {{ url }}
                </a>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Raw JSON -->
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <div>
            <CardTitle>Raw JSON</CardTitle>
            <CardDescription>Complete product data as stored</CardDescription>
          </div>
          <Button variant="outline" size="sm" @click="copyJson">
            <Check v-if="copied" class="w-3 h-3 mr-1 text-green-600" />
            <Copy v-else class="w-3 h-3 mr-1" />
            {{ copied ? 'Copied!' : 'Copy' }}
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        <pre class="bg-muted p-4 rounded-lg text-sm overflow-auto max-h-[500px] font-mono">{{ JSON.stringify(product.productData, null, 2) }}</pre>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Product?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete product
            <span class="font-semibold text-foreground font-mono">{{ product.stockNumber }}</span>?
            This action cannot be undone. The product may reappear on the next pipeline run if it still exists in the source data.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction
            @click="confirmDelete"
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            Delete Product
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </Default>
</template>
