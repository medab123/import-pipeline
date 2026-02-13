<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableEmpty } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Pagination } from '@/components/ui/pagination'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
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
  Trash2,
  Key,
  Copy,
  CheckCircle2,
  AlertTriangle
} from 'lucide-vue-next'
import { ref } from 'vue'
import { useClipboard } from '@vueuse/core'

interface Token {
  id: number
  name: string
  created_at: string
  last_used_at: string
  expires_at?: string
}

interface Props {
  tokens: {
    data: Token[]
    currentPage: number
    lastPage: number
    perPage: number
    total: number
    nextPageUrl: string
    previousPageUrl: string
  }
  organizationName: string
  newlyCreatedToken?: {
    name: string
    token: string
  }
}

const props = defineProps<Props>()

// Create Token
const createDialogOpen = ref(false)
const createForm = useForm({
  name: '',
})

const submitCreate = () => {
    createForm.post(route('dashboard.organization.tokens.store'), {
        onSuccess: () => {
            createDialogOpen.value = false
            createForm.reset()
            showTokenDialog.value = true
        }
    })
}

// Show Token Dialog (after creation)
const showTokenDialog = ref(!!props.newlyCreatedToken)
const { copy, copied } = useClipboard()

const copyToken = () => {
    if (props.newlyCreatedToken?.token) {
        copy(props.newlyCreatedToken.token)
    }
}

// Delete Token
const deleteDialogOpen = ref(false)
const tokenToDelete = ref<Token | null>(null)

const openDeleteDialog = (token: Token) => {
    tokenToDelete.value = token
    deleteDialogOpen.value = true
}

const confirmDelete = () => {
    if (!tokenToDelete.value) return

    router.delete(route('dashboard.organization.tokens.destroy', tokenToDelete.value.id), {
        onSuccess: () => {
            deleteDialogOpen.value = false
            tokenToDelete.value = null
        }
    })
}
</script>

<template>
  <Head title="API Tokens" />

  <Default>
    <PageHeader
      title="API Tokens"
      :description="`Manage API access tokens for ${organizationName}`"
    >
      <template #actions>
        <Button @click="createDialogOpen = true">
          <Plus class="w-4 h-4 mr-2" />
          Generate New Token
        </Button>
      </template>
    </PageHeader>

    <div class="space-y-6">
        <Card class="bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800">
            <CardContent class="flex items-start gap-4 p-4">
                <AlertTriangle class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" />
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-medium mb-1">Security Notice</p>
                    <p>API tokens grant full access to your organization's resources. Treat them like passwords. If a token is compromised, revoke it immediately.</p>
                </div>
            </CardContent>
        </Card>

        <Card class="shadow-sm">
            <CardHeader class="pb-4 border-b">
                <CardTitle class="text-xl font-bold">Active Tokens</CardTitle>
                <CardDescription>
                    List of active API tokens created for this organization.
                </CardDescription>
            </CardHeader>
            <CardContent class="pt-6">
                <div class="overflow-x-auto rounded-lg border bg-background">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[300px]">Token Name</TableHead>
                                <TableHead>Last Used</TableHead>
                                <TableHead>Created At</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                             <TableEmpty v-if="tokens.data.length === 0" :colspan="4">
                                <div class="text-center py-12">
                                    <div class="bg-muted/50 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4">
                                        <Key class="w-6 h-6 text-muted-foreground" />
                                    </div>
                                    <h3 class="font-semibold text-lg">No active tokens</h3>
                                    <p class="text-muted-foreground text-sm mt-1 mb-4">Generate your first API token to connect external services.</p>
                                    <Button @click="createDialogOpen = true" variant="outline">
                                        Generate Token
                                    </Button>
                                </div>
                             </TableEmpty>
                             <TableRow v-for="token in tokens.data" :key="token.id">
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <Key class="w-4 h-4 text-muted-foreground" />
                                        {{ token.name }}
                                    </div>
                                </TableCell>
                                <TableCell>{{ token.last_used_at }}</TableCell>
                                <TableCell>{{ token.created_at }}</TableCell>
                                <TableCell class="text-right">
                                    <Button 
                                        variant="ghost" 
                                        size="sm" 
                                        class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                        @click="openDeleteDialog(token)"
                                    >
                                        <Trash2 class="w-4 h-4 mr-2" /> Revoke
                                    </Button>
                                </TableCell>
                             </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="tokens.total > tokens.perPage" class="mt-4">
                    <Pagination :paginator="tokens" />
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Create Token Dialog -->
    <Dialog v-model:open="createDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Generate API Token</DialogTitle>
                <DialogDescription>
                    Give your token a descriptive name to identify its usage.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div class="space-y-2">
                     <label for="name" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Token Name</label>
                     <Input 
                        id="name" 
                        v-model="createForm.name" 
                        placeholder="e.g. CI/CD Pipeline, External Importer" 
                        autofocus
                    />
                     <p v-if="createForm.errors.name" class="text-sm text-destructive">{{ createForm.errors.name }}</p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="createDialogOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Generating...' : 'Generate Token' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Show Generated Token Dialog -->
     <Dialog v-model:open="showTokenDialog">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Token Generated Successfully</DialogTitle>
                <DialogDescription>
                    Please copy your new API token. For security reasons, it will not be shown again.
                </DialogDescription>
            </DialogHeader>
            <div class="flex items-center space-x-2 mt-4">
                <div class="grid flex-1 gap-2">
                    <label for="link" class="sr-only">Link</label>
                    <Input
                        id="link"
                        :defaultValue="newlyCreatedToken?.token"
                        readOnly
                        class="font-mono text-sm bg-muted"
                    />
                </div>
                <Button type="submit" size="sm" class="px-3" @click="copyToken">
                    <span class="sr-only">Copy</span>
                    <CheckCircle2 v-if="copied" class="h-4 w-4 text-green-500" />
                    <Copy v-else class="h-4 w-4" />
                </Button>
            </div>
            <DialogFooter class="sm:justify-start">
                <Button type="button" variant="secondary" @click="showTokenDialog = false">
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Revoke Confirmation Dialog -->
    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Revoke API Token?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to revoke <span class="font-semibold text-foreground">{{ tokenToDelete?.name }}</span>? 
            Any services using this token will lose access immediately. This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
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
