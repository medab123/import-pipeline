<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import PipelineStepLayout from '../Partials/PipelineStepLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Plus, Trash2, Wand2, ChevronDown, X } from 'lucide-vue-next'
import type { MapperConfigStepViewModel } from '@/types/generated'
import { useTestDataMapper } from '@/composables/useTestDataMapper'
import SearchableSelect from '@/components/SearchableSelect.vue'

// Define the structure of feed keys with unique values
interface FeedKey {
  key: string
  preview: string
  display: string
  uniqueValues: string[]
}

// Extend the view model type to handle actual backend types
interface ExtendedMapperConfigStepViewModel extends Omit<MapperConfigStepViewModel, 'availableTransformations' | 'testResult'> {
  availableTransformations: Record<string, string> | Array<any>
  testResult: any | null
}

const props = defineProps<ExtendedMapperConfigStepViewModel>()

const emit = defineEmits<{
  save: []
  saveAndNext: []
}>()

const form = useForm({
  field_mappings: props.fieldMappings || []
})

// Track expanded/collapsed state for each mapping
const expandedMappings = ref<boolean[]>(
  props.fieldMappings?.map(() => true) || []
)

// Track test result collapsible state
const isTestResultOpen = ref(true)

// Test data mapper functionality
const { isTesting, testDataMapper: testDataMapperFn } = useTestDataMapper()

// Get test result from props
const testResult = computed(() => {
  const result = props.testResult as any
  // Handle null, empty array, or invalid data
  if (!result) {
    return null
  }
  // If result is an array, take the first element (shouldn't happen but handle it)
  const testData = Array.isArray(result) ? (result.length > 0 ? result[0] : null) : result
  if (!testData || typeof testData !== 'object') {
    return null
  }
  return {
    success: testData.success ?? false,
    message: testData.message ?? 'Test completed',
    details: testData.details ?? null
  }
})

const addMapping = () => {
  form.field_mappings.push({
    source_field: '',
    target_field: '',
    transformation: 'none',
    transformation_params: {},
    value_mapping: [],
    required: false,
    default_value: '',
    validation_rules: []
  })
  // Expand new mapping by default
  expandedMappings.value.push(true)
}

const removeMapping = (index: number) => {
  form.field_mappings.splice(index, 1)
  // Remove corresponding expanded state
  expandedMappings.value.splice(index, 1)
}

const getMappingSummary = (mapping: any): string => {
  const parts: string[] = []
  if (mapping.source_field) {
    parts.push(`Source: ${getSourceFieldLabel(mapping.source_field)}`)
  }
  if (mapping.target_field) {
    parts.push(`Target: ${getTargetFieldLabel(mapping.target_field)}`)
  }
  if (mapping.transformation && mapping.transformation !== 'none') {
    parts.push(`Transform: ${getTransformationLabel(mapping.transformation)}`)
  }
  if ((mapping.value_mapping?.length || 0) > 0) {
    parts.push(`${mapping.value_mapping.length} value mapping(s)`)
  }
  return parts.length > 0 ? parts.join(' • ') : 'Not configured'
}

// Helper function to get unique values for a source field
const getSourceFieldValues = (sourceField: string): string[] => {
  if (!sourceField) return []
  
  const field = props.feedKeys.find((f: any) => f.key === sourceField) as FeedKey | undefined
  return field?.uniqueValues || []
}

// Define the structure of target fields
interface TargetField {
  field: string  // The actual field name/key
  label?: string
  description?: string
  category?: string
  type?: string
  model?: string
}

// Computed properties for select labels
const getSourceFieldLabel = (sourceField: string): string => {
  if (!sourceField) return 'Select source field'
  const field = props.feedKeys.find((f: any) => f.key === sourceField) as FeedKey | undefined
  return field ? field.key : 'Select source field'
}

const getTargetFieldLabel = (targetField: string): string => {
  if (!targetField) return 'Select target field'
  const field = (props.targetFields || []).find((f: any) => f.field === targetField) as TargetField | undefined
  return field ? (field.label || field.field) : targetField || 'Select target field'
}

// Convert availableTransformations to an object if it's an array
const availableTransformationsObj = computed(() => {
  const transformations = props.availableTransformations as any
  // If it's already an object, return it
  if (transformations && typeof transformations === 'object' && !Array.isArray(transformations)) {
    return transformations
  }
  // If it's an array, convert to object (shouldn't happen but handle it)
  if (Array.isArray(transformations)) {
    return transformations.reduce((acc: Record<string, string>, item: any) => {
      if (typeof item === 'object' && item.key && item.label) {
        acc[item.key] = item.label
      }
      return acc
    }, {})
  }
  return {}
})

const getTransformationLabel = (transformation: string): string => {
  if (!transformation) return ''
  return availableTransformationsObj.value[transformation] || transformation
}

const getValueMappingLabel = (value: string): string => {
  return value || ''
}

// Get available source fields for a specific mapping rule (excluding already mapped fields)
const getAvailableSourceFields = (currentIndex: number): FeedKey[] => {
  // Get all source fields that are already mapped in other rules
  const mappedSourceFields = new Set<string>()
  
  form.field_mappings.forEach((mapping: any, index: number) => {
    // Skip the current mapping rule
    if (index !== currentIndex && mapping.source_field) {
      mappedSourceFields.add(mapping.source_field)
    }
  })
  
  // Filter out already mapped fields, but always include the current mapping's selected field
  const currentMapping = form.field_mappings[currentIndex]
  const currentSourceField = currentMapping?.source_field
  
  return props.feedKeys.filter((field: FeedKey) => {
    // Always include the currently selected field for this mapping
    if (currentSourceField && field.key === currentSourceField) {
      return true
    }
    // Exclude fields that are mapped in other rules
    return !mappedSourceFields.has(field.key)
  })
}

const testDataMapper = async () => {
  if (isTesting.value) {
    return
  }

  const data = form.data()
  testDataMapperFn(props.pipeline.id, data)
}

const save = () => {
  form.post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: 'mapper-config'
  }), {
    onSuccess: () => emit('save')
  })
}

const saveAndNext = () => {
  form.post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: 'mapper-config'
  }), {
    onSuccess: () => emit('saveAndNext')
  })
}
</script>

<template>
  <PipelineStepLayout
    :stepper="props.stepper"
    @save="save"
    @save-and-next="saveAndNext"
  >
    <div class="space-y-8 max-w-7xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b">
        <div class="space-y-1">
          <h2 class="text-2xl font-bold tracking-tight">Mapper Configuration</h2>
          <p class="text-sm text-muted-foreground">Map source fields to target fields and apply transformations</p>
        </div>
        <Button @click="addMapping" variant="outline" size="lg" class="flex items-center gap-2 w-full sm:w-auto">
          <Plus class="h-4 w-4" />
          Add Mapping
        </Button>
      </div>

      <!-- Empty State -->
      <div v-if="form.field_mappings.length === 0" class="text-center py-16 text-muted-foreground border-2 border-dashed rounded-lg bg-muted/20 hover:bg-muted/30 transition-colors">
        <div class="mx-auto w-16 h-16 rounded-full bg-muted flex items-center justify-center mb-4">
          <Wand2 class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-lg font-semibold mb-2">No mappings configured</p>
        <p class="text-sm max-w-md mx-auto mb-6">Add mappings to transform your data from source to target format</p>
        <Button @click="addMapping" variant="outline" size="lg">
          <Plus class="h-4 w-4 mr-2" />
          Add Your First Mapping
        </Button>
      </div>

      <!-- Mappings List -->
      <div v-else class="space-y-6">
        <div v-for="(mapping, index) in form.field_mappings" :key="index" class="space-y-4">
        <Card class="w-full">
          <Collapsible 
            v-model:open="expandedMappings[index]"
          >
            <CardHeader class="pb-4 relative">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 flex-1">
                  <CollapsibleTrigger as-child>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0 -ml-2"
                    >
                      <ChevronDown 
                        class="h-4 w-4 transition-transform duration-200"
                        :class="{ 'rotate-180': expandedMappings[index] !== false }"
                      />
                    </Button>
                  </CollapsibleTrigger>
                  <div class="flex-1">
                    <CardTitle class="text-base">Mapping {{ index + 1 }}</CardTitle>
                    <p v-if="expandedMappings[index] === false" class="text-sm text-muted-foreground mt-1">
                      {{ getMappingSummary(mapping) }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="text-sm text-muted-foreground">
                    Field {{ index + 1 }} of {{ form.field_mappings.length }}
                  </div>
                  <Button 
                    @click.stop="removeMapping(index)" 
                    variant="ghost" 
                    size="sm" 
                    class="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                    aria-label="Remove mapping"
                  >
                    <X class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CollapsibleContent>
              <CardContent class="space-y-4">
            <!-- Main Field Mapping Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
              <!-- Source Field -->
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <label class="text-sm font-medium">Source Field</label>
                  <span v-if="getAvailableSourceFields(index).length > 0" class="text-xs text-muted-foreground">
                    {{ getAvailableSourceFields(index).length }} available
                  </span>
                </div>
                <SearchableSelect
                  v-model="mapping.source_field"
                  :items="getAvailableSourceFields(index)"
                  value-key="key"
                  display-key="key"
                  :search-keys="['key', 'preview', 'display']"
                  placeholder="Select source field"
                  search-placeholder="Search fields..."
                  class="w-full"
                >
                  <template #item="{ item }">
                    <div class="flex items-center gap-2 w-full">
                      <span class="font-medium text-sm">{{ item.key }}</span>
                      <span
                        v-if="item.preview"
                        class="text-muted-foreground text-xs"
                      >
                        |
                      </span>
                      <span
                        v-if="item.preview"
                        class="text-muted-foreground text-xs truncate flex-1"
                        :title="item.preview"
                      >
                        {{ item.preview }}
                      </span>
                    </div>
                  </template>
                </SearchableSelect>
              </div>

              <!-- Target Field -->
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <label class="text-sm font-medium">Target Field</label>
                  <span v-if="(props.targetFields || []).length > 0" class="text-xs text-muted-foreground">
                    {{ (props.targetFields || []).length }} fields
                  </span>
                </div>
                <SearchableSelect
                  v-model="mapping.target_field"
                  :items="props.targetFields || []"
                  value-key="field"
                  display-key="label"
                  :search-keys="['field', 'label', 'description', 'category']"
                  placeholder="Select target field"
                  search-placeholder="Search fields..."
                  class="w-full"
                >
                  <template #item="{ item }">
                    <div class="flex flex-col gap-1 w-full">
                      <div class="flex items-center gap-2 w-full">
                        <span class="font-medium text-sm">{{ item.label || item.field }}</span>
                        <span
                          v-if="item.category"
                          class="text-xs text-muted-foreground px-1.5 py-0.5 bg-muted rounded shrink-0"
                        >
                          {{ item.category }}
                        </span>
                      </div>
                      <span
                        v-if="item.description"
                        class="text-muted-foreground text-xs line-clamp-1"
                        :title="item.description"
                      >
                        {{ item.description }}
                      </span>
                    </div>
                  </template>
                </SearchableSelect>
              </div>

              <!-- Transformation -->
              <div class="space-y-2">
                <label class="text-sm font-medium">Transformation</label>
                <Select v-model="mapping.transformation" class="w-full">
                  <SelectTrigger class="w-full">
                    <SelectValue>
                      {{ getTransformationLabel(mapping.transformation) }}
                    </SelectValue>
                  </SelectTrigger>
                  <SelectContent class="max-h-60 overflow-y-auto">
                    <SelectItem v-for="(label, value) in availableTransformationsObj" :key="value" :value="value">
                      {{ label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Default Value -->
              <div class="space-y-2">
                <label class="text-sm font-medium">Default Value</label>
                <Input 
                  v-model="mapping.default_value" 
                  placeholder="Optional default" 
                  class="w-full"
                />
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <Switch v-model="mapping.required" />
              <label class="text-sm font-medium">Required</label>
            </div>

            <!-- Value Mapping Section -->
            <div class="space-y-3">
              <div class="flex items-center justify-between mb-2">
                <div>
                  <label class="text-sm font-medium">Value Mapping</label>
                  <p class="text-xs text-muted-foreground mt-0.5">Map incoming values to desired outputs</p>
                </div>
                <Button @click="(mapping.value_mapping || (mapping.value_mapping = [])).push({ from: '', to: '' })" size="sm" variant="outline">
                  Add Pair
                </Button>
              </div>
              
              <div v-if="(mapping.value_mapping?.length || 0) > 0" class="space-y-2">
                <div v-for="(pair, pIndex) in (mapping.value_mapping || [])" :key="pIndex" class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr_auto] gap-3 items-end">
                  <!-- From Value -->
                  <div class="space-y-1.5">
                    <label class="text-sm font-medium">From</label>
                    <SearchableSelect
                      v-if="mapping.source_field && getSourceFieldValues(mapping.source_field).length > 0"
                      v-model="pair.from"
                      :items="getSourceFieldValues(mapping.source_field).map(value => ({ value, label: value }))"
                      value-key="value"
                      display-key="label"
                      placeholder="Select source value"
                      search-placeholder="Search values..."
                      empty-message="No values found"
                      class="w-full"
                    />
                    <div
                      v-else
                      class="flex h-9 w-full items-center justify-center rounded-md border border-input bg-muted/50 px-3 text-sm text-muted-foreground"
                    >
                      <span v-if="!mapping.source_field">Select a source field first</span>
                      <span v-else>No values available for this field</span>
                    </div>
                  </div>
                  
                  <!-- Arrow -->
                  <div class="flex items-center justify-center pb-1">
                    <span class="text-muted-foreground">→</span>
                  </div>
                  
                  <!-- To Value -->
                  <div class="space-y-1.5">
                    <label class="text-sm font-medium">To</label>
                    <Input 
                      v-model="pair.to" 
                      placeholder="mapped value" 
                      class="w-full"
                    />
                  </div>
                  
                  <!-- Remove Button -->
                  <div class="flex justify-end">
                    <Button 
                      @click="mapping.value_mapping.splice(pIndex, 1)" 
                      variant="outline" 
                      size="sm" 
                      class="text-destructive hover:text-destructive"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
              </CardContent>
            </CollapsibleContent>
          </Collapsible>
        </Card>
        </div>
        <!-- Bottom Add Mapping button for better UX on long lists -->
        <div class="flex justify-end pt-2">
          <Button @click="addMapping" variant="outline" size="lg" class="flex items-center gap-2">
            <Plus class="h-4 w-4" />
            Add Mapping
          </Button>
        </div>
      </div>

      <!-- Test Data Mapper Section -->
      <div v-if="form.field_mappings.length > 0" class="mt-8 p-5 border rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-semibold">Save & Test Data Mapper</h3>
            <p class="text-sm text-muted-foreground">
              Save configuration to pipeline and test the data mapper with your configured rules
            </p>
          </div>
          <Button
              type="button"
              variant="outline"
              :disabled="isTesting || form.processing"
              @click="testDataMapper"
          >
            <span v-if="isTesting">Saving & Testing...</span>
            <span v-else>Save & Test</span>
          </Button>
        </div>

        <!-- Test Result -->
        <Collapsible v-if="testResult" v-model:open="isTestResultOpen" class="mt-4">
          <div
              class="p-3 rounded-md"
              :class="{
                'bg-green-50 border border-green-200 text-green-800': testResult.success,
                'bg-red-50 border border-red-200 text-red-800': !testResult.success
              }"
          >
            <CollapsibleTrigger as-child>
              <button class="flex items-center w-full text-left">
                <div class="flex-shrink-0">
                  <svg
                      v-if="testResult.success"
                      class="h-5 w-5 text-green-400"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                  >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"
                    />
                  </svg>
                  <svg
                      v-else
                      class="h-5 w-5 text-red-400"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                  >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <div class="ml-3 flex-1">
                  <p class="text-sm font-medium">{{ testResult.message }}</p>
                </div>
                <ChevronDown 
                  class="h-4 w-4 transition-transform duration-200 ml-2"
                  :class="{ 'rotate-180': isTestResultOpen }"
                />
              </button>
            </CollapsibleTrigger>
          </div>
          <CollapsibleContent>
            <div
                class="p-3 rounded-md mt-2"
                :class="{
                  'bg-green-50 border border-green-200 text-green-800': testResult.success,
                  'bg-red-50 border border-red-200 text-red-800': !testResult.success
                }"
            >
              <div v-if="testResult.details" class="text-xs">
                <div class="space-y-3">
                      <!-- Stats -->
                      <div v-if="testResult.details.mapping_stats" class="grid grid-cols-2 gap-2 p-2 bg-white/50 rounded">
                        <div>
                          <span class="font-medium">Total Fields:</span>
                          <span class="ml-1">{{ testResult.details.mapping_stats.total_fields }}</span>
                        </div>
                        <div>
                          <span class="font-medium">Required Fields:</span>
                          <span class="ml-1">{{ testResult.details.mapping_stats.required_fields }}</span>
                        </div>
                        <div>
                          <span class="font-medium">Input Rows:</span>
                          <span class="ml-1">{{ testResult.details.input_rows }}</span>
                        </div>
                        <div>
                          <span class="font-medium">Mapped Rows:</span>
                          <span class="ml-1">{{ testResult.details.mapped_rows }}</span>
                        </div>
                        <div v-if="testResult.details.errors_count > 0" class="col-span-2 text-red-600">
                          <span class="font-medium">Errors:</span>
                          <span class="ml-1">{{ testResult.details.errors_count }}</span>
                        </div>
                      </div>
                      
                      <!-- Sample Raw Data -->
                      <div v-if="testResult.details.sample_raw_data" class="space-y-1">
                        <p class="font-medium">Sample Raw Data (first 3 rows):</p>
                        <pre class="p-2 bg-white/50 rounded text-xs overflow-x-auto">{{ JSON.stringify(testResult.details.sample_raw_data, null, 2) }}</pre>
                      </div>
                      
                      <!-- Sample Mapped Data -->
                      <div v-if="testResult.details.sample_mapped_data" class="space-y-1">
                        <p class="font-medium">Sample Mapped Data (first 5 rows):</p>
                        <pre class="p-2 bg-white/50 rounded text-xs overflow-x-auto">{{ JSON.stringify(testResult.details.sample_mapped_data, null, 2) }}</pre>
                      </div>
                      
                      <!-- Errors -->
                      <div v-if="testResult.details.errors && testResult.details.errors.length > 0" class="space-y-1">
                        <p class="font-medium text-red-600">Errors:</p>
                        <ul class="list-disc list-inside space-y-1">
                          <li v-for="(error, idx) in testResult.details.errors" :key="idx" class="text-xs">{{ error }}</li>
                        </ul>
                      </div>
                      
                      <!-- Full Details (JSON) -->
                      <details class="mt-2">
                        <summary class="hover:underline font-medium">View Full JSON</summary>
                        <pre class="mt-2 p-2 bg-white/50 rounded text-xs whitespace-pre-wrap overflow-x-auto">{{ JSON.stringify(testResult.details, null, 2) }}</pre>
                      </details>
                </div>
              </div>
            </div>
          </CollapsibleContent>
        </Collapsible>
      </div>
    </div>
  </PipelineStepLayout>
</template>


