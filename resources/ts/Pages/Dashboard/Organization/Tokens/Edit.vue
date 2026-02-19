<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  ArrowLeft,
  Key,
  Save
} from 'lucide-vue-next'
import { ref } from 'vue'

interface Pipeline {
  id: number
  name: string
}

interface Token {
  id: number
  name: string
  expires_at: string | null
  expires_in_days: number | null
  pipeline_ids: number[]
  has_expiration: boolean
}

interface Props {
  token: Token
  availablePipelines: Pipeline[]
  organizationName: string
}

const props = defineProps<Props>()

const expirationType = ref<'none' | 'date' | 'days'>(
  props.token.has_expiration 
    ? (props.token.expires_at ? 'date' : 'days')
    : 'none'
)

const editForm = useForm({
  name: props.token.name,
  expires_at: props.token.expires_at,
  expires_in_days: props.token.expires_in_days,
  pipeline_ids: [...props.token.pipeline_ids],
})

const togglePipeline = (pipelineId: number) => {
  const index = editForm.pipeline_ids.indexOf(pipelineId)
  if (index > -1) {
    editForm.pipeline_ids.splice(index, 1)
  } else {
    editForm.pipeline_ids.push(pipelineId)
  }
}

const isPipelineSelected = (pipelineId: number) => {
  return editForm.pipeline_ids.includes(pipelineId)
}

const submitUpdate = () => {
  // Clean up form data based on expiration type
  const formData: any = {
    name: editForm.name,
  }

  if (expirationType.value === 'date' && editForm.expires_at) {
    formData.expires_at = editForm.expires_at
  } else if (expirationType.value === 'days' && editForm.expires_in_days) {
    formData.expires_in_days = editForm.expires_in_days
  } else if (expirationType.value === 'none') {
    formData.expires_at = null
  }

  if (editForm.pipeline_ids.length > 0) {
    formData.pipeline_ids = editForm.pipeline_ids
  } else {
    formData.pipeline_ids = []
  }

  editForm.transform(() => formData).put(route('dashboard.organization.tokens.update', props.token.id))
}
</script>

<template>
  <Head :title="`Edit ${token.name} - API Token`" />

  <Default>
    <PageHeader
      :title="`Edit Token: ${token.name}`"
      :description="`Update token settings for ${organizationName}`"
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.organization.tokens.index')">
            <ArrowLeft class="w-4 h-4 mr-2" /> Back to Tokens
          </Link>
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.organization.tokens.show', token.id)">
            <ArrowLeft class="w-4 h-4 mr-2" /> View Token
          </Link>
        </Button>
      </template>
    </PageHeader>

    <div class="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Key class="w-5 h-5" />
            Token Settings
          </CardTitle>
          <CardDescription>
            Update the token name, expiration, and pipeline access. The token value itself cannot be changed.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submitUpdate" class="space-y-6">
            <!-- Token Name -->
            <div class="space-y-2">
              <Label for="name" class="text-sm font-medium leading-none">
                Token Name
              </Label>
              <Input 
                id="name" 
                v-model="editForm.name" 
                placeholder="e.g. CI/CD Pipeline, External Importer" 
                :class="{ 'border-destructive': editForm.errors.name }"
              />
              <p v-if="editForm.errors.name" class="text-sm text-destructive">
                {{ editForm.errors.name }}
              </p>
            </div>

            <!-- Expiration Settings -->
            <div class="space-y-2">
              <Label class="text-sm font-medium leading-none">Expiration</Label>
              <Select v-model="expirationType">
                <SelectTrigger>
                  <SelectValue placeholder="Select expiration type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">No Expiration</SelectItem>
                  <SelectItem value="date">Specific Date</SelectItem>
                  <SelectItem value="days">Days from Now</SelectItem>
                </SelectContent>
              </Select>

              <div v-if="expirationType === 'date'" class="space-y-2">
                <Input 
                  type="datetime-local"
                  v-model="editForm.expires_at"
                  :min="new Date().toISOString().slice(0, 16)"
                  :class="{ 'border-destructive': editForm.errors.expires_at }"
                />
                <p v-if="editForm.errors.expires_at" class="text-sm text-destructive">
                  {{ editForm.errors.expires_at }}
                </p>
              </div>

              <div v-if="expirationType === 'days'" class="space-y-2">
                <Input 
                  type="number"
                  v-model.number="editForm.expires_in_days"
                  placeholder="Enter number of days (1-3650)"
                  min="1"
                  max="3650"
                  :class="{ 'border-destructive': editForm.errors.expires_in_days }"
                />
                <p v-if="editForm.errors.expires_in_days" class="text-sm text-destructive">
                  {{ editForm.errors.expires_in_days }}
                </p>
              </div>
            </div>

            <!-- Pipeline Access -->
            <div class="space-y-2">
              <Label class="text-sm font-medium leading-none">Pipeline Access</Label>
              <p class="text-xs text-muted-foreground">
                Select specific pipelines this token can access. Leave empty to allow access to all pipelines.
              </p>
              <div v-if="availablePipelines && availablePipelines.length > 0" class="max-h-64 overflow-y-auto border rounded-md p-3 space-y-2">
                <div 
                  v-for="pipeline in availablePipelines" 
                  :key="pipeline.id"
                  class="flex items-center space-x-2"
                >
                  <Checkbox 
                    :id="`pipeline-${pipeline.id}`"
                    :checked="isPipelineSelected(pipeline.id)"
                    @update:checked="() => togglePipeline(pipeline.id)"
                  />
                  <Label 
                    :for="`pipeline-${pipeline.id}`"
                    class="text-sm font-normal cursor-pointer"
                  >
                    {{ pipeline.name }}
                  </Label>
                </div>
              </div>
              <div v-else class="text-sm text-muted-foreground p-3 border rounded-md">
                No pipelines available
              </div>
              <p v-if="editForm.errors.pipeline_ids" class="text-sm text-destructive">
                {{ editForm.errors.pipeline_ids }}
              </p>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t">
              <Button 
                type="button" 
                variant="outline" 
                as-child
              >
                <Link :href="route('dashboard.organization.tokens.show', token.id)">
                  Cancel
                </Link>
              </Button>
              <Button 
                type="submit" 
                :disabled="editForm.processing"
              >
                <Save class="w-4 h-4 mr-2" />
                {{ editForm.processing ? 'Updating...' : 'Update Token' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Info Card -->
      <Card class="bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800">
        <CardContent class="flex items-start gap-4 p-4">
          <Key class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" />
          <div class="text-sm text-blue-800 dark:text-blue-300">
            <p class="font-medium mb-1">Note</p>
            <p>
              You can update the token name, expiration date, and pipeline access. 
              The token value itself cannot be changed for security reasons. 
              If you need a new token value, you must revoke this token and create a new one.
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  </Default>
</template>
