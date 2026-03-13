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
  Eye,
  X,
  CreditCard,
} from 'lucide-vue-next'

interface TransactionItem {
  id: number
  dealerId: number
  dealerName: string
  type: string
  amount: string
  status: string
  paymentMethod: string | null
  reference: string | null
  paidAt: string | null
  formattedPaidAt: string | null
  formattedCreatedAt: string
}

interface Props {
  transactions: TransactionItem[]
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
    route('dashboard.payment-transactions.index'),
    { search: searchQuery.value },
    { preserveState: true, replace: true }
  )
}, 300)

watch(searchQuery, performSearch)

const clearSearch = () => {
  searchQuery.value = ''
}

const deleteDialogOpen = ref(false)
const itemToDelete = ref<TransactionItem | null>(null)

const openDeleteDialog = (item: TransactionItem) => {
  itemToDelete.value = item
  deleteDialogOpen.value = true
}

const confirmDelete = () => {
  if (!itemToDelete.value) return
  router.delete(route('dashboard.payment-transactions.destroy', itemToDelete.value.id), {
    onSuccess: () => {
      deleteDialogOpen.value = false
      itemToDelete.value = null
    }
  })
}

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'completed': return 'default'
    case 'pending': return 'secondary'
    case 'failed': return 'destructive'
    case 'refunded': return 'outline'
    default: return 'secondary'
  }
}
</script>

<template>
  <Head title="Payment Transactions" />

  <Default>
    <PageHeader
      title="Payment Transactions"
      description="Track and manage all payment transactions."
    >
      <template #actions>
        <Button as-child>
          <Link :href="route('dashboard.payment-transactions.create')">
            <Plus class="w-4 h-4 mr-2" />
            Add Transaction
          </Link>
        </Button>
      </template>
    </PageHeader>

    <Card class="shadow-sm">
      <CardHeader class="pb-4 border-b">
        <div class="flex items-center justify-between">
          <div class="space-y-1">
            <CardTitle class="text-xl font-bold">All Transactions</CardTitle>
            <CardDescription>
              A list of all payment transactions across dealers.
            </CardDescription>
          </div>
          <div class="relative w-full max-w-sm">
            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="searchQuery"
              type="search"
              placeholder="Search transactions..."
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
                <TableHead>Type</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Method</TableHead>
                <TableHead>Reference</TableHead>
                <TableHead>Paid At</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="transactions.length === 0" :colspan="8">
                <div class="text-center py-12">
                  <div class="bg-muted/50 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4">
                    <CreditCard class="w-6 h-6 text-muted-foreground" />
                  </div>
                  <h3 class="font-semibold text-lg">No transactions found</h3>
                  <p class="text-muted-foreground text-sm mt-1 mb-4">Start by adding your first payment transaction.</p>
                  <Button as-child variant="outline">
                    <Link :href="route('dashboard.payment-transactions.create')">
                      Add Transaction
                    </Link>
                  </Button>
                </div>
              </TableEmpty>
              <TableRow v-for="tx in transactions" :key="tx.id">
                <TableCell class="font-medium">{{ tx.dealerName }}</TableCell>
                <TableCell>
                  <Badge variant="outline">{{ tx.type }}</Badge>
                </TableCell>
                <TableCell class="font-medium">${{ tx.amount }}</TableCell>
                <TableCell>
                  <Badge :variant="getStatusVariant(tx.status)">{{ tx.status }}</Badge>
                </TableCell>
                <TableCell>{{ tx.paymentMethod || '-' }}</TableCell>
                <TableCell class="font-mono text-sm">{{ tx.reference || '-' }}</TableCell>
                <TableCell>{{ tx.formattedPaidAt || '-' }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="icon" as-child>
                      <Link :href="route('dashboard.payment-transactions.show', tx.id)">
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
                          <Link :href="route('dashboard.payment-transactions.edit', tx.id)">
                            <Edit class="w-4 h-4 mr-2" /> Edit
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem class="text-destructive" @click="openDeleteDialog(tx)">
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
          <AlertDialogTitle>Delete Transaction?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete this transaction (${{ itemToDelete?.amount }})?
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
