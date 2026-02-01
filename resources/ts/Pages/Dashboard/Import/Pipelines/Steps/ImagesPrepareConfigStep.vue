<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import PipelineStepLayout from '../Partials/PipelineStepLayout.vue'
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Plus, Trash2 } from 'lucide-vue-next'
import type { ImagesPrepareConfigStepViewModel } from '@/types/generated'

const props = defineProps<ImagesPrepareConfigStepViewModel>()

const emit = defineEmits<{
  save: []
  saveAndNext: []
}>()

// Form state
const form = useForm({
  image_indexes_to_skip: props.imageIndexesToSkip || [],
  image_separator: props.imageSeparator || ',',
  active: props.active ?? false,
  download_mode: props.downloadMode || 'all',
})

// Download mode options
const downloadModeOptions = [
  { value: 'all', label: 'All Products' },
  { value: 'new_products_only', label: 'Just for New Products' },
  { value: 'products_without_images', label: 'Just Products Without Images' },
]

// Add index to skip
const addIndexToSkip = () => {
  form.image_indexes_to_skip.push(0)
}

// Remove index to skip
const removeIndexToSkip = (index: number) => {
  form.image_indexes_to_skip.splice(index, 1)
}



const handleSave = () => {
  if (form.processing) {
    return
  }

  form.post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: 'images-prepare-config'
  }), {
    onSuccess: () => {
      emit('save')
    },
    preserveState: true,
    preserveScroll: true,
  })
}

const handleSaveAndNext = () => {
  form.post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: 'images-prepare-config'
  }), {
    onSuccess: () => {
      emit('saveAndNext')
    }
  })
}
</script>

<template>
  <PipelineStepLayout
      :stepper="props.stepper"
      @save="handleSave"
      @save-and-next="handleSaveAndNext"
  >
    <div class="space-y-8 max-w-6xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b">
        <div class="space-y-1.5">
          <h2 class="text-2xl font-bold tracking-tight">Images Prepare Configuration</h2>
          <p class="text-sm text-muted-foreground leading-relaxed">
            Configure image preparation settings for the import pipeline
          </p>
        </div>
      </div>

      <Form :form="form" @submit="handleSaveAndNext">
        <!-- Image Download Activation -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Image Download</CardTitle>
            <CardDescription>
              Enable or disable image downloading for products during import
            </CardDescription>
          </CardHeader>
          <CardContent>
            <FormField name="active">
              <FormItem class="flex flex-row items-center justify-between rounded-lg border p-4">
                <div class="space-y-0.5">
                  <FormLabel class="text-base">Activate Image Download</FormLabel>
                  <FormDescription>
                    Enable image downloading for products during import
                  </FormDescription>
                </div>
                <FormControl>
                  <Switch
                      v-model:model-value="form.active"
                  />
                </FormControl>
              </FormItem>
            </FormField>
          </CardContent>
        </Card>

        <!-- Image Download Settings (shown only when active) -->
        <Transition
            enter-active-class="transition-all duration-300 ease-in-out"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-300 ease-in-out"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-4"
        >
          <div v-if="form.active" class="space-y-6">
            <!-- Download Mode Configuration -->
            <Card class="mb-6">
            <CardHeader>
              <CardTitle>Download Mode</CardTitle>
              <CardDescription>
                Choose which products should have their images downloaded
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <FormField name="download_mode">
                <FormItem>
                  <FormLabel for="download_mode">Download Mode</FormLabel>
                  <Select v-model="form.download_mode">
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue placeholder="Select download mode" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      <SelectItem
                          v-for="option in downloadModeOptions"
                          :key="option.value"
                          :value="option.value"
                      >
                        {{ option.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <FormDescription>
                    Choose which products should have their images downloaded
                  </FormDescription>
                  <FormMessage for="download_mode" />
                </FormItem>
              </FormField>
            </CardContent>
          </Card>

          <!-- Image Separator Configuration -->
          <Card class="mb-6">
          <CardHeader>
            <CardTitle>Image Separator</CardTitle>
            <CardDescription>
              Specify the character(s) used to separate multiple image URLs in a single field
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <FormField name="image_separator">
              <FormItem>
                <FormLabel for="image_separator">Separator Character</FormLabel>
                <FormControl>
                  <Input
                      id="image_separator"
                      v-model="form.image_separator"
                      type="text"
                      placeholder=","
                      maxlength="10"
                      class="w-full"
                  />
                </FormControl>
                <FormDescription>
                  Common separators: comma (,), semicolon (;), pipe (|), or space
                </FormDescription>
                <FormMessage for="image_separator" />
              </FormItem>
            </FormField>
          </CardContent>
        </Card>

          <!-- Image Indexes to Skip Configuration -->
          <Card>
          <CardHeader>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div class="space-y-1">
                <CardTitle>Image Indexes to Skip</CardTitle>
                <CardDescription>
                  Specify which image indexes (positions) should be skipped during processing.
                  Indexes start from 0 (first image = 0, second image = 1, etc.)
                </CardDescription>
              </div>
              <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  @click="addIndexToSkip"
                  class="w-full sm:w-auto"
              >
                <Plus class="w-4 h-4 mr-2" />
                Add Index
              </Button>
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <div v-if="form.image_indexes_to_skip.length === 0" class="text-sm text-muted-foreground py-4 text-center">
              No indexes to skip. Click "Add Index" to add one.
            </div>
            <div v-else class="space-y-3">
              <div
                  v-for="(_index, idx) in form.image_indexes_to_skip"
                  :key="idx"
                  class="flex items-center gap-3 p-3 border rounded-lg bg-card"
              >
                <FormField :name="`image_indexes_to_skip.${idx}`">
                  <FormItem class="flex-1">
                    <FormLabel :for="`index_${idx}`">Index {{ idx + 1 }}</FormLabel>
                    <FormControl>
                      <Input
                          :id="`index_${idx}`"
                          v-model="form.image_indexes_to_skip[idx]"
                          type="number"
                          min="0"
                          placeholder="0"
                          class="w-full"
                      />
                    </FormControl>
                    <FormMessage :for="`image_indexes_to_skip.${idx}`" />
                  </FormItem>
                </FormField>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    @click="removeIndexToSkip(idx)"
                    class="mt-6"
                >
                  <Trash2 class="w-4 h-4 text-destructive" />
                </Button>
              </div>
            </div>
          </CardContent>
          </Card>
          </div>
        </Transition>
      </Form>
    </div>
  </PipelineStepLayout>
</template>

