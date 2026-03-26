<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref } from 'vue'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableEmpty } from '@/components/ui/table'
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
  ExternalLink,
  GitBranch,
  Copy,
  Check,
} from 'lucide-vue-next'

interface Transaction {
  id: number
  type: string
  amount: string
  status: string
  payment_method: string | null
  reference: string | null
  paid_at: string | null
  formatted_paid_at: string | null
  created_at: string
}

interface ScrapItem {
  id: number
  ftp_file_path: string
  provider: string
  created_at: string
  formatted_created_at: string
}

interface ImportPipelineItem {
  id: number
  name: string
  is_active: boolean
  last_executed_at: string | null
  next_execution_at: string | null
  frequency: string | null
}

interface DealerData {
  id: number
  name: string
  status: string
  notes: string | null
  postingAddress: string | null
  websiteUrls: string[]
  fbmpAppAccessToken: string | null
  fbmpAppUrl: string | null
  paymentPeriod: string
  formattedCreatedAt: string
  formattedUpdatedAt: string
}

const props = defineProps<{
  dealer: DealerData
  recentTransactions: Transaction[]
  scraps: ScrapItem[]
  importPipelines: ImportPipelineItem[]
}>()

const deleteDialogOpen = ref(false)
const fbmpTokenCopied = ref(false)

const copyFbmpToken = async () => {
  if (!props.dealer.fbmpAppAccessToken) return
  await navigator.clipboard.writeText(props.dealer.fbmpAppAccessToken)
  fbmpTokenCopied.value = true
  setTimeout(() => { fbmpTokenCopied.value = false }, 2000)
}

const confirmDelete = () => {
  router.delete(route('dashboard.dealers.destroy', props.dealer.id), {
    onSuccess: () => {
      deleteDialogOpen.value = false
    }
  })
}

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'active': return 'default'
    case 'completed': return 'default'
    case 'pending': return 'secondary'
    case 'failed': return 'destructive'
    case 'refunded': return 'outline'
    default: return 'secondary'
  }
}
</script>

<template>
  <Head :title="dealer.name" />

  <Default>
    <PageHeader
      :title="dealer.name"
      description="Dealer details and transaction history."
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.dealers.index')">
            <ArrowLeft class="w-4 h-4 mr-2" /> Back
          </Link>
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.dealers.edit', dealer.id)">
            <Edit class="w-4 h-4 mr-2" /> Edit
          </Link>
        </Button>
        <Button variant="destructive" size="sm" @click="deleteDialogOpen = true">
          <Trash2 class="w-4 h-4 mr-2" /> Delete
        </Button>
      </template>
    </PageHeader>

    <div class="w-full space-y-6">
      <!-- Dealer Information -->
      <Card>
        <CardHeader>
          <CardTitle>Dealer Information</CardTitle>
          <CardDescription>Details and configuration for this dealer.</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Name</h4>
              <div class="font-medium">{{ dealer.name }}</div>
            </div>
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Status</h4>
              <Badge :variant="getStatusVariant(dealer.status)">{{ dealer.status }}</Badge>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Payment Period</h4>
              <Badge variant="outline">{{ dealer.paymentPeriod }}</Badge>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Posting Address</h4>
              <div v-if="dealer.postingAddress">{{ dealer.postingAddress }}</div>
              <div v-else class="text-muted-foreground text-sm italic">Not set</div>
            </div>
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">Websites</h4>
              <div v-if="dealer.websiteUrls.length > 0" class="space-y-1">
                <a v-for="(url, index) in dealer.websiteUrls" :key="index" :href="url" target="_blank" class="text-sm text-primary hover:underline inline-flex items-center gap-1 block">
                  {{ url }}
                  <ExternalLink class="w-3 h-3" />
                </a>
              </div>
              <div v-else class="text-muted-foreground text-sm italic">Not set</div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">FBMP App Token</h4>
              <div v-if="dealer.fbmpAppAccessToken" class="flex items-center gap-2">
                <code class="text-sm bg-muted px-2 py-1 rounded font-mono break-all">{{ dealer.fbmpAppAccessToken }}</code>
                <Button variant="ghost" size="icon" class="h-7 w-7 shrink-0" @click="copyFbmpToken">
                  <Check v-if="fbmpTokenCopied" class="w-3.5 h-3.5 text-green-500" />
                  <Copy v-else class="w-3.5 h-3.5" />
                </Button>
              </div>
              <div v-else class="text-muted-foreground text-sm italic">Not generated yet</div>
            </div>
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground">FBMP App URL</h4>
              <a v-if="dealer.fbmpAppUrl" :href="dealer.fbmpAppUrl" target="_blank" class="text-sm text-primary hover:underline inline-flex items-center gap-1">
                {{ dealer.fbmpAppUrl }}
                <ExternalLink class="w-3 h-3" />
              </a>
              <div v-else class="text-muted-foreground text-sm italic">Not set</div>
            </div>
          </div>

          <div class="space-y-1">
            <h4 class="text-sm font-medium text-muted-foreground">Notes</h4>
            <p v-if="dealer.notes" class="text-sm whitespace-pre-wrap">{{ dealer.notes }}</p>
            <div v-else class="text-muted-foreground text-sm italic">No notes.</div>
          </div>
        </CardContent>
        <CardFooter class="text-xs text-muted-foreground border-t pt-4 flex justify-between">
          <span>Created: {{ dealer.formattedCreatedAt }}</span>
          <span>Last Updated: {{ dealer.formattedUpdatedAt }}</span>
        </CardFooter>
      </Card>

      <!-- Import Pipelines -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>Import Pipelines</CardTitle>
              <CardDescription>Automated import pipelines configured for this dealer.</CardDescription>
            </div>
            <Button variant="outline" size="sm" as-child>
              <Link :href="route('dashboard.import.pipelines.index')">
                <GitBranch class="w-4 h-4 mr-2" /> View All Pipelines
              </Link>
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto rounded-lg border bg-background">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="w-12">ID</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Frequency</TableHead>
                  <TableHead>Last Executed</TableHead>
                  <TableHead>Next Run</TableHead>
                  <TableHead class="w-12"></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableEmpty v-if="importPipelines.length === 0" :colspan="7">
                  <div class="text-center py-6">
                    <p class="text-muted-foreground text-sm">No import pipelines yet.</p>
                  </div>
                </TableEmpty>
                <TableRow v-for="pipeline in importPipelines" :key="pipeline.id">
                  <TableCell class="text-muted-foreground text-sm">#{{ pipeline.id }}</TableCell>
                  <TableCell class="font-medium">{{ pipeline.name }}</TableCell>
                  <TableCell>
                    <Badge :variant="pipeline.is_active ? 'default' : 'secondary'">
                      {{ pipeline.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline" class="capitalize">{{ pipeline.frequency ?? '—' }}</Badge>
                  </TableCell>
                  <TableCell class="text-sm">{{ pipeline.last_executed_at ?? '—' }}</TableCell>
                  <TableCell class="text-sm">{{ pipeline.next_execution_at ?? '—' }}</TableCell>
                  <TableCell>
                    <Button variant="ghost" size="sm" as-child>
                      <Link :href="route('dashboard.import.pipelines.show', pipeline.id)">
                        <ExternalLink class="w-3.5 h-3.5" />
                      </Link>
                    </Button>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <!-- Scrap Sources -->
      <Card>
        <CardHeader>
          <CardTitle>Scrap Sources</CardTitle>
          <CardDescription>FTP scrap sources linked to this dealer.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto rounded-lg border bg-background">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Provider</TableHead>
                  <TableHead>FTP File Path</TableHead>
                  <TableHead>Created</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableEmpty v-if="scraps.length === 0" :colspan="3">
                  <div class="text-center py-6">
                    <p class="text-muted-foreground text-sm">No scrap sources yet.</p>
                  </div>
                </TableEmpty>
                <TableRow v-for="scrap in scraps" :key="scrap.id">
                  <TableCell class="font-medium">{{ scrap.provider }}</TableCell>
                  <TableCell class="font-mono text-sm">{{ scrap.ftp_file_path }}</TableCell>
                  <TableCell>{{ scrap.formatted_created_at }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <!-- Recent Transactions -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>Recent Transactions</CardTitle>
              <CardDescription>Latest payment transactions for this dealer.</CardDescription>
            </div>
            <Button variant="outline" size="sm" as-child>
              <Link :href="route('dashboard.payment-transactions.create')">
                Add Transaction
              </Link>
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto rounded-lg border bg-background">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Type</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Method</TableHead>
                  <TableHead>Reference</TableHead>
                  <TableHead>Paid At</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableEmpty v-if="recentTransactions.length === 0" :colspan="6">
                  <div class="text-center py-6">
                    <p class="text-muted-foreground text-sm">No transactions yet.</p>
                  </div>
                </TableEmpty>
                <TableRow v-for="tx in recentTransactions" :key="tx.id">
                  <TableCell>
                    <Badge variant="outline">{{ tx.type }}</Badge>
                  </TableCell>
                  <TableCell class="font-medium">${{ tx.amount }}</TableCell>
                  <TableCell>
                    <Badge :variant="getStatusVariant(tx.status)">{{ tx.status }}</Badge>
                  </TableCell>
                  <TableCell>{{ tx.payment_method || '-' }}</TableCell>
                  <TableCell class="font-mono text-sm">{{ tx.reference || '-' }}</TableCell>
                  <TableCell>{{ tx.formatted_paid_at || '-' }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>

    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Dealer?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete <span class="font-semibold">{{ dealer.name }}</span>?
            This will also delete all related transactions and scrap sources. This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="confirmDelete" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Delete Dealer
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </Default>
</template>
