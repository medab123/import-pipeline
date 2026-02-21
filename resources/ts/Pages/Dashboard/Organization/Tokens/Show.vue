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
  Trash2,
  Key,
  Clock,
  XCircle,
  CheckCircle2,
  Calendar,
  Activity,
  Database,
  Edit
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

interface Pipeline {
  id: number
  name: string
}

interface Token {
  id: number
  name: string
  created_at: string
  last_used_at: string | null
  expires_at: string | null
  is_expired: boolean
  is_valid: boolean
  pipelines: Pipeline[]
  has_all_pipelines_access: boolean
  pipeline_count: number
}

interface Props {
  token: Token
  organizationName: string
}

const props = defineProps<Props>()

const deleteDialogOpen = ref(false)

const formatDate = (dateString: string | null): string => {
  if (!dateString) return 'Never'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const confirmDelete = () => {
  router.delete(route('dashboard.organization.tokens.destroy', props.token.id), {
    onSuccess: () => {
      deleteDialogOpen.value = false
      router.visit(route('dashboard.organization.tokens.index'))
    }
  })
}
</script>

<template>
  <Head :title="`${token.name} - API Token`" />

  <Default>
    <PageHeader
      :title="token.name"
      :description="`API token details for ${organizationName}`"
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.organization.tokens.index')">
            <ArrowLeft class="w-4 h-4 mr-2" /> Back to Tokens
          </Link>
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.organization.tokens.edit', token.id)">
            <Edit class="w-4 h-4 mr-2" /> Edit Token
          </Link>
        </Button>
        <Button
          variant="destructive"
          size="sm"
          @click="deleteDialogOpen = true"
        >
          <Trash2 class="w-4 h-4 mr-2" /> Revoke Token
        </Button>
      </template>
    </PageHeader>

    <div class="space-y-6">
      <!-- Status Card -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Key class="w-5 h-5" />
            Token Status
          </CardTitle>
          <CardDescription>Current status and validity of this API token</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-4">
            <Badge
              v-if="token.is_expired"
              variant="destructive"
              class="text-sm px-3 py-1"
            >
              <XCircle class="w-4 h-4 mr-2" />
              Expired
            </Badge>
            <Badge
              v-else-if="token.is_valid"
              variant="default"
              class="text-sm px-3 py-1 bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20"
            >
              <CheckCircle2 class="w-4 h-4 mr-2" />
              Active
            </Badge>
            <Badge
              v-else
              variant="secondary"
              class="text-sm px-3 py-1"
            >
              Invalid
            </Badge>
          </div>
        </CardContent>
      </Card>

      <!-- Token Information -->
      <Card>
        <CardHeader>
          <CardTitle>Token Information</CardTitle>
          <CardDescription>Basic details about this API token</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <Key class="w-4 h-4" />
                Token Name
              </h4>
              <div class="font-medium text-base">{{ token.name }}</div>
            </div>
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <Calendar class="w-4 h-4" />
                Created At
              </h4>
              <div class="text-sm">{{ formatDate(token.created_at) }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <Activity class="w-4 h-4" />
                Last Used
              </h4>
              <div class="text-sm">{{ formatDate(token.last_used_at) }}</div>
            </div>
            <div class="space-y-1">
              <h4 class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <Clock class="w-4 h-4" />
                Expires At
              </h4>
              <div class="text-sm">
                <Badge
                  v-if="token.expires_at && token.is_expired"
                  variant="destructive"
                  class="text-xs"
                >
                  {{ formatDate(token.expires_at) }}
                </Badge>
                <Badge
                  v-else-if="token.expires_at"
                  variant="secondary"
                  class="text-xs"
                >
                  {{ formatDate(token.expires_at) }}
                </Badge>
                <span v-else class="text-muted-foreground">Never expires</span>
              </div>
            </div>
          </div>
        </CardContent>
        <CardFooter class="text-xs text-muted-foreground border-t pt-4">
          <div class="flex items-center gap-2">
            <Database class="w-3 h-3" />
            <span>Token ID: {{ token.id }}</span>
          </div>
        </CardFooter>
      </Card>

      <!-- Pipeline Access -->
      <Card>
        <CardHeader>
          <CardTitle>Pipeline Access</CardTitle>
          <CardDescription>
            Pipelines that this token can access via the API
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="token.has_all_pipelines_access" class="space-y-2">
            <Badge variant="secondary" class="text-sm px-3 py-1">
              <CheckCircle2 class="w-4 h-4 mr-2" />
              All Pipelines
            </Badge>
            <p class="text-sm text-muted-foreground mt-2">
              This token has access to all pipelines in the organization.
            </p>
          </div>
          <div v-else-if="token.pipelines && token.pipelines.length > 0" class="space-y-4">
            <div class="flex items-center gap-2 mb-2">
              <Badge variant="outline" class="text-sm">
                {{ token.pipeline_count }} {{ token.pipeline_count === 1 ? 'Pipeline' : 'Pipelines' }}
              </Badge>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div
                v-for="pipeline in token.pipelines"
                :key="pipeline.id"
                class="flex items-center gap-2 p-3 border rounded-md hover:bg-muted/50 transition-colors"
              >
                <Database class="w-4 h-4 text-muted-foreground" />
                <span class="font-medium">{{ pipeline.name }}</span>
              </div>
            </div>
          </div>
          <div v-else class="space-y-2">
            <Badge variant="outline" class="text-sm px-3 py-1">
              No Pipeline Access
            </Badge>
            <p class="text-sm text-muted-foreground mt-2">
              This token does not have access to any pipelines.
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Security Notice -->
      <Card class="bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800">
        <CardContent class="flex items-start gap-4 p-4">
          <Key class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" />
          <div class="text-sm text-blue-800 dark:text-blue-300">
            <p class="font-medium mb-1">Security Reminder</p>
            <p>
              API tokens provide full access to your organization's resources.
              Keep them secure and never share them publicly. If you suspect a token
              has been compromised, revoke it immediately.
            </p>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Revoke Confirmation Dialog -->
    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Revoke API Token?</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to revoke <span class="font-semibold text-foreground">{{ token.name }}</span>?
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
