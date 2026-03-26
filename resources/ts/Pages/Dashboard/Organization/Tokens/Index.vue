<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref } from 'vue'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableEmpty } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
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
import { Pagination } from '@/components/ui/pagination'
import {
  Plus,
  MoreHorizontal,
  Trash2,
  Key,
  Copy,
  Check,
  AlertTriangle,
} from 'lucide-vue-next'
import { useClipboard } from '@vueuse/core'

interface ApiToken {
  id: number
  name: string
  description: string | null
  token: string
  token_preview: string
  last_used_at: string | null
  created_at: string
}

interface Props {
  tokens: {
    data: ApiToken[]
  }
  paginator: {
    currentPage: number
    hasMorePages: boolean
    lastPage: number
    perPage: number
    total: number
    nextPageUrl: string
    previousPageUrl: string
  }
  organizationName: string
  newToken: string | null
}

const props = defineProps<Props>()

const { copy, copied } = useClipboard()

// --- Copy token per row ---
const copiedTokenId = ref<number | null>(null)

const copyToken = (token: ApiToken) => {
  copy(token.token)
  copiedTokenId.value = token.id
  setTimeout(() => {
    copiedTokenId.value = null
  }, 2000)
}

// --- Create Token Dialog ---
const createDialogOpen = ref(false)
const createForm = useForm({
  name: '',
  description: '',
})

const openCreateDialog = () => {
  createForm.reset()
  createForm.clearErrors()
  createDialogOpen.value = true
}

const submitCreate = () => {
  createForm.post(route('dashboard.organization.tokens.store'), {
    preserveScroll: true,
    onSuccess: () => {
      createDialogOpen.value = false
      createForm.reset()
      // newToken will be available via props after redirect
      tokenCreatedDialogOpen.value = true
    },
  })
}

// --- Token Created Dialog (show token once) ---
const tokenCreatedDialogOpen = ref(!!props.newToken)

const copyNewToken = () => {
  if (props.newToken) {
    copy(props.newToken)
  }
}

// --- Delete Token Dialog ---
const deleteDialogOpen = ref(false)
const deletingToken = ref<ApiToken | null>(null)

const openDeleteDialog = (token: ApiToken) => {
  deletingToken.value = token
  deleteDialogOpen.value = true
}

const confirmDelete = () => {
  if (!deletingToken.value) return

  router.delete(route('dashboard.organization.tokens.destroy', deletingToken.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      deleteDialogOpen.value = false
      deletingToken.value = null
    },
    onError: () => {
      deleteDialogOpen.value = false
      deletingToken.value = null
    },
  })
}
</script>

<template>
  <Head title="API Tokens" />

  <Default>
    <!-- Page Header -->
    <PageHeader
      title="API Tokens"
      :description="`Manage API tokens for ${organizationName}`"
    >
      <template #actions>
        <Button @click="openCreateDialog">
          <Plus class="w-4 h-4 mr-2" />
          Create Token
        </Button>
      </template>
    </PageHeader>

    <!-- Tokens Table -->
    <Card class="shadow-sm">
      <CardHeader class="pb-4 border-b">
        <div class="space-y-1">
          <CardTitle class="text-xl font-bold">Tokens</CardTitle>
          <CardDescription class="text-sm">
            {{ paginator.total }} {{ paginator.total === 1 ? 'token' : 'tokens' }} in your organization.
            Each token grants API access to all pipelines.
          </CardDescription>
        </div>
      </CardHeader>
      <CardContent class="pt-6">
        <div class="overflow-x-auto rounded-lg border bg-background">
          <Table class="min-w-full">
            <TableHeader>
              <TableRow>
                <TableHead class="w-[200px]">Name</TableHead>
                <TableHead class="w-[250px]">Description</TableHead>
                <TableHead class="w-[200px]">Token</TableHead>
                <TableHead class="w-[150px]">Last Used</TableHead>
                <TableHead class="w-[120px]">Created</TableHead>
                <TableHead class="w-[80px] text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="tokens.data.length === 0" :colspan="6">
                <div class="text-center py-16 px-4">
                  <div class="mx-auto w-20 h-20 rounded-full bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center mb-6 ring-4 ring-primary/5">
                    <Key class="h-10 w-10 text-primary" />
                  </div>
                  <h3 class="mt-2 text-xl font-bold">No API tokens yet</h3>
                  <p class="mt-2 text-sm text-muted-foreground max-w-md mx-auto leading-relaxed">
                    Create your first API token to authenticate external requests to your organization's pipelines.
                  </p>
                  <div class="mt-8">
                    <Button size="lg" class="shadow-md" @click="openCreateDialog">
                      <Plus class="w-4 h-4 mr-2" />
                      Create First Token
                    </Button>
                  </div>
                </div>
              </TableEmpty>
              <TableRow
                v-for="token in tokens.data"
                :key="token.id"
                class="group hover:bg-muted/50 transition-colors"
              >
                <TableCell>
                  <div class="flex items-center gap-2">
                    <Key class="h-4 w-4 text-muted-foreground shrink-0" />
                    <span class="font-medium">{{ token.name }}</span>
                  </div>
                </TableCell>
                <TableCell>
                  <span v-if="token.description" class="text-sm text-muted-foreground">
                    {{ token.description }}
                  </span>
                  <span v-else class="text-sm text-muted-foreground italic">No description</span>
                </TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <code class="font-mono text-xs bg-muted px-2 py-1 rounded text-muted-foreground">
                      {{ token.token_preview }}
                    </code>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-7 w-7 p-0 shrink-0"
                      @click="copyToken(token)"
                    >
                      <Check v-if="copiedTokenId === token.id" class="w-3.5 h-3.5 text-green-500" />
                      <Copy v-else class="w-3.5 h-3.5 text-muted-foreground" />
                      <span class="sr-only">Copy token</span>
                    </Button>
                  </div>
                </TableCell>
                <TableCell class="text-sm text-muted-foreground whitespace-nowrap">
                  {{ token.last_used_at ?? 'Never' }}
                </TableCell>
                <TableCell class="text-sm text-muted-foreground whitespace-nowrap">
                  {{ token.created_at }}
                </TableCell>
                <TableCell class="text-right">
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button
                        variant="ghost"
                        size="sm"
                        class="opacity-0 group-hover:opacity-100 transition-all duration-200 hover:bg-accent"
                      >
                        <MoreHorizontal class="w-4 h-4" />
                        <span class="sr-only">Open menu</span>
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                      <DropdownMenuItem
                        @click="openDeleteDialog(token)"
                        class="flex items-center text-destructive focus:text-destructive"
                      >
                        <Trash2 class="w-4 h-4 mr-2" />
                        Revoke Token
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
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
              <span class="font-semibold text-foreground">{{ ((paginator.currentPage - 1) * paginator.perPage) + 1 }}</span>
              to
              <span class="font-semibold text-foreground">{{ Math.min(paginator.currentPage * paginator.perPage, paginator.total) }}</span>
              of
              <span class="font-semibold text-foreground">{{ paginator.total }}</span>
              {{ paginator.total === 1 ? 'token' : 'tokens' }}
            </div>
            <Pagination :paginator="paginator" />
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Create Token Dialog -->
    <Dialog v-model:open="createDialogOpen">
      <DialogContent class="sm:max-w-[480px]">
        <DialogHeader>
          <DialogTitle>Create API Token</DialogTitle>
          <DialogDescription>
            Create a new token to authenticate API access to your organization's pipelines.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitCreate" class="space-y-4">
          <div class="space-y-2">
            <Label for="create-name">Token Name</Label>
            <Input
              id="create-name"
              v-model="createForm.name"
              type="text"
              required
              placeholder="e.g., Production API, Staging Import"
              :class="{ 'border-destructive': createForm.errors.name }"
            />
            <p v-if="createForm.errors.name" class="text-sm text-destructive">{{ createForm.errors.name }}</p>
          </div>

          <div class="space-y-2">
            <Label for="create-description">
              Description
              <span class="text-muted-foreground font-normal">(optional)</span>
            </Label>
            <Textarea
              id="create-description"
              v-model="createForm.description"
              placeholder="What is this token used for?"
              rows="3"
              class="resize-none"
              :class="{ 'border-destructive': createForm.errors.description }"
            />
            <p v-if="createForm.errors.description" class="text-sm text-destructive">{{ createForm.errors.description }}</p>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="createDialogOpen = false">Cancel</Button>
            <Button type="submit" :disabled="createForm.processing">
              <span v-if="createForm.processing">Creating...</span>
              <span v-else>Create Token</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Token Created Dialog (show token once) -->
    <Dialog v-model:open="tokenCreatedDialogOpen">
      <DialogContent class="sm:max-w-[560px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Check class="w-5 h-5 text-green-500" />
            Token Created Successfully
          </DialogTitle>
          <DialogDescription>
            Copy your new API token now. You won't be able to see it again.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="flex items-center gap-2 p-3 bg-muted rounded-lg border">
            <code class="font-mono text-sm flex-1 break-all select-all">{{ newToken }}</code>
            <Button
              type="button"
              variant="outline"
              size="sm"
              class="shrink-0"
              @click="copyNewToken"
            >
              <Check v-if="copied" class="w-4 h-4 text-green-500" />
              <Copy v-else class="w-4 h-4" />
              <span class="ml-1">{{ copied ? 'Copied' : 'Copy' }}</span>
            </Button>
          </div>
          <div class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-950/20 rounded-lg border border-amber-200 dark:border-amber-800">
            <AlertTriangle class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" />
            <p class="text-sm text-amber-700 dark:text-amber-400">
              Make sure to copy your token now. For security reasons, it won't be displayed again.
            </p>
          </div>
        </div>
        <DialogFooter>
          <Button @click="tokenCreatedDialogOpen = false">Done</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Revoke API Token</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to revoke
            <span class="font-semibold text-foreground">"{{ deletingToken?.name }}"</span>?
            Any applications using this token will lose access immediately. This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="deleteDialogOpen = false">Cancel</AlertDialogCancel>
          <AlertDialogAction
            @click="confirmDelete"
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            Revoke Token
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </Default>
</template>
