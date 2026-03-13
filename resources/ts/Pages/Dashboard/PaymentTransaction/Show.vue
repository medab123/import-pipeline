<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref } from 'vue'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
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

interface TransactionData {
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
  createdAt: string
  formattedCreatedAt: string
}

const props = defineProps<{
  transaction: TransactionData
}>()

const deleteDialogOpen = ref(false)

const confirmDelete = () => {
  router.delete(route('dashboard.payment-transactions.destroy', props.transaction.id), {
    onSuccess: () => {
      deleteDialogOpen.value = false
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
  <Head title="Payment Transaction" />

  <Default>
    <PageHeader
      title="Payment Transaction"
      :description="`Transaction #${transaction.id} for ${transaction.dealerName}`"
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.payment-transactions.index')">
            <ArrowLeft class="w-4 h-4 mr-2" /> Back
          </Link>
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.payment-transactions.edit', transaction.id)">
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
          <CardTitle>Transaction Information</CardTitle>
          <CardDescription>Details for this payment transaction.</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Dealer</h4>
              <div class="font-medium">
                <Link :href="route('dashboard.dealers.show', transaction.dealerId)" class="text-primary hover:underline">
                  {{ transaction.dealerName }}
                </Link>
              </div>
            </div>
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Type</h4>
              <Badge variant="outline">{{ transaction.type }}</Badge>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Amount</h4>
              <div class="font-medium text-lg">${{ transaction.amount }}</div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Status</h4>
              <Badge :variant="getStatusVariant(transaction.status)">{{ transaction.status }}</Badge>
            </div>
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Payment Method</h4>
              <div v-if="transaction.paymentMethod">{{ transaction.paymentMethod }}</div>
              <div v-else class="text-muted-foreground text-sm italic">Not set</div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Reference</h4>
              <div v-if="transaction.reference" class="font-mono text-sm bg-muted px-2 py-1 rounded w-fit">{{ transaction.reference }}</div>
              <div v-else class="text-muted-foreground text-sm italic">Not set</div>
            </div>
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Paid At</h4>
              <div v-if="transaction.formattedPaidAt">{{ transaction.formattedPaidAt }}</div>
              <div v-else class="text-muted-foreground text-sm italic">Not paid yet</div>
            </div>
          </div>
        </CardContent>
        <CardFooter class="text-xs text-muted-foreground border-t pt-4">
          <span>Created: {{ transaction.formattedCreatedAt }}</span>
        </CardFooter>
      </Card>
    </div>

    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Transaction?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete this transaction (${{ transaction.amount }})?
            This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="confirmDelete" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Delete Transaction
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </Default>
</template>
