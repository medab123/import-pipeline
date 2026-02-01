<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import PipelineStepLayout from '../Partials/PipelineStepLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Plus, Trash2, Filter, Loader2, CheckCircle2, XCircle, ChevronDown } from 'lucide-vue-next'
import { useTestFilter } from '@/composables/useTestFilter'
import { FilterConfigStepViewModel } from '@/types/generated'
import SearchableSelect from '@/components/SearchableSelect.vue'
import MultiSelect from '@/components/MultiSelect.vue'
import ComboInput from '@/components/ComboInput.vue'
import DualSelect from '@/components/DualSelect.vue'

const props = defineProps<FilterConfigStepViewModel>()

const emit = defineEmits<{
  save: []
  saveAndNext: []
}>()

// Helper functions
// Check if operator is multi-value (in/not_in)
const isMultiValueOperator = (operator: string): boolean => {
  return ['in', 'not_in'].includes(operator)
}

// Check if operator is between (requires exactly 2 values)
const isBetweenOperator = (operator: string): boolean => {
  return ['between', 'not_between'].includes(operator)
}

// Check if operator is regex
const isRegexOperator = (operator: string): boolean => {
  return ['regex', 'not_regex'].includes(operator)
}

// Check if operator should use text input (not select dropdown)
const shouldUseTextInput = (operator: string): boolean => {
  return ['regex', 'not_regex', 'starts_with', 'ends_with'].includes(operator)
}

// Check if operator should use combobox (select or type custom)
const shouldUseComboInput = (operator: string): boolean => {
  return ['contains', 'not_contains'].includes(operator)
}

// Normalize rules data - ensure values are in correct format
const normalizeRules = (rules: any[]): any[] => {
  if (!Array.isArray(rules)) return []
  
  return rules.map((rule: any) => {
    const normalized = { ...rule }
    
    // Ensure value is in correct format based on operator
    if (isMultiValueOperator(rule.operator)) {
      // For in/not_in, value should be an array
      if (!Array.isArray(normalized.value)) {
        normalized.value = normalized.value ? [normalized.value] : []
      }
    } else if (isBetweenOperator(rule.operator)) {
      // For between/not_between, value should be an array with max 2 values
      if (!Array.isArray(normalized.value)) {
        normalized.value = normalized.value ? [normalized.value] : []
      }
      // Limit to 2 values
      if (normalized.value.length > 2) {
        normalized.value = normalized.value.slice(0, 2)
      }
    } else {
      // For other operators, value should be a string
      if (Array.isArray(normalized.value)) {
        normalized.value = normalized.value[0] || ''
      } else if (normalized.value === null || normalized.value === undefined) {
        normalized.value = ''
      } else {
        normalized.value = String(normalized.value)
      }
    }
    
    return normalized
  })
}

// Form state (flat rules). Fall back to legacy ruleGroups if needed
const initialRules = (props as any).rules ?? ((props as any).ruleGroups ? ((props as any).ruleGroups as any[]).flatMap((g: any) => g.rules ?? []) : [])
const form = useForm({
  rules: normalizeRules(initialRules)
})

// Add rule
const addRule = () => {
  form.rules.push({
    key: '',
    operator: 'equals',
    value: '',
    description: '',
    case_sensitive: false,
    regex_flags: ''
  })
}

// Handle operator change - reset value format based on operator
const handleOperatorChange = (ruleIndex: number, newOperator: string) => {
  const rule = form.rules[ruleIndex]
  if (rule) {
    rule.operator = newOperator
    
    // Reset value based on operator type
    if (isMultiValueOperator(newOperator) || isBetweenOperator(newOperator)) {
      rule.value = []
    } else {
      rule.value = ''
    }
  }
}

// Handle field key change - reset value when field changes
const handleFieldKeyChange = (ruleIndex: number, newKey: string) => {
  const rule = form.rules[ruleIndex]
  if (rule) {
    rule.key = newKey
    // Reset value when field changes
    if (isMultiValueOperator(rule.operator)) {
      rule.value = []
    } else {
      rule.value = ''
    }
  }
}

// Remove rule
const removeRule = (ruleIndex: number) => {
  form.rules.splice(ruleIndex, 1)
}

// Check if operator needs value
const needsValue = (operator: string): boolean => {
  const noValueOperators = ['is_null', 'is_not_null', 'is_empty', 'is_not_empty']
  return !noValueOperators.includes(operator)
}

// Check if operator needs regex flags
const needsRegexFlags = (operator: string): boolean => {
  return ['regex', 'not_regex'].includes(operator)
}

// Check if operator is case sensitive
const isCaseSensitiveOperator = (operator: string): boolean => {
  const caseSensitiveOperators = ['contains', 'not_contains', 'starts_with', 'ends_with', 'equals', 'not_equals']
  return caseSensitiveOperators.includes(operator)
}

// Get unique values for a selected field
const getFieldUniqueValues = (fieldKey: string): Array<{ value: string; label: string }> => {
  if (!fieldKey) return []
  
  const feedKeys = (props as any).feedKeys || []
  if (!Array.isArray(feedKeys) || feedKeys.length === 0) return []
  
  const field = feedKeys.find((f: any) => f.key === fieldKey)
  if (!field || !field.uniqueValues) return []
  
  return field.uniqueValues.map((val: any) => ({
    value: String(val),
    label: String(val)
  }))
}

// Computed property to normalize operators for display
const normalizedOperators = computed(() => {
  const operators = props.availableOperators as any
  if (!operators || typeof operators !== 'object') return {}
  
  const normalized: Record<string, string> = {}
  for (const [key, value] of Object.entries(operators)) {
    if (typeof value === 'string') {
      normalized[key] = value
    } else if (value && typeof value === 'object') {
      normalized[key] = (value as any).label || (value as any).name || key
    } else {
      normalized[key] = key
    }
  }
  return normalized
})

// Computed property for operator label
const getOperatorLabel = (operator: string): string => {
  if (!operator) return ''
  return normalizedOperators.value[operator] || operator
}

// Watch for field key changes to reset value
watch(() => form.rules, (rules) => {
  rules.forEach((rule: any) => {
    if (rule.key && !isRegexOperator(rule.operator) && !isMultiValueOperator(rule.operator) && !isBetweenOperator(rule.operator)) {
      // Ensure value is a string for single select
      if (Array.isArray(rule.value)) {
        rule.value = rule.value[0] || ''
      }
    } else if (rule.key && isMultiValueOperator(rule.operator)) {
      // Ensure value is an array for multi-select
      if (!Array.isArray(rule.value)) {
        rule.value = rule.value ? [rule.value] : []
      }
    } else if (rule.key && isBetweenOperator(rule.operator)) {
      // Ensure value is an array with max 2 values for between
      if (!Array.isArray(rule.value)) {
        rule.value = rule.value ? [rule.value] : []
      }
      // Limit to 2 values
      if (rule.value.length > 2) {
        rule.value = rule.value.slice(0, 2)
      }
    }
  })
}, { deep: true })

// Transform form data before submission
const transformFormData = (data: any) => {
  const transformed = { ...data }
  
  if (transformed.rules && Array.isArray(transformed.rules)) {
    transformed.rules = transformed.rules.map((rule: any) => {
      const transformedRule = { ...rule }
      
      // For multi-value operators (in/not_in), ensure value is an array
      if (isMultiValueOperator(rule.operator)) {
        if (!Array.isArray(transformedRule.value)) {
          transformedRule.value = transformedRule.value ? [transformedRule.value] : []
        }
      } else if (isBetweenOperator(rule.operator)) {
        // For between/not_between, ensure value is an array with exactly 2 values
        if (!Array.isArray(transformedRule.value)) {
          transformedRule.value = transformedRule.value ? [transformedRule.value] : []
        }
        // Ensure exactly 2 values (pad with empty string if needed, or limit to 2)
        if (transformedRule.value.length === 0) {
          transformedRule.value = ['', '']
        } else if (transformedRule.value.length === 1) {
          transformedRule.value = [transformedRule.value[0], '']
        } else if (transformedRule.value.length > 2) {
          transformedRule.value = transformedRule.value.slice(0, 2)
        }
      } else {
        // For other operators, ensure value is a string
        if (Array.isArray(transformedRule.value)) {
          transformedRule.value = transformedRule.value[0] || ''
        } else if (transformedRule.value === null || transformedRule.value === undefined) {
          transformedRule.value = ''
        } else {
          transformedRule.value = String(transformedRule.value)
        }
      }
      
      return transformedRule
    })
  }
  
  return transformed
}

// Save form
const save = () => {
  // Get current form data and ensure it's properly structured
  const currentData = { ...form.data() }
  const transformedData = transformFormData(currentData)
  
  form.transform(() => transformedData).post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: 'filter-config'
  }), {
    onSuccess: () => {
      emit('save')
    }
  })
}

// Save and go to next step
const saveAndNext = () => {
  // Get current form data and ensure it's properly structured
  const currentData = { ...form.data() }
  const transformedData = transformFormData(currentData)
  
  form.transform(() => transformedData).post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: 'filter-config'
  }), {
    onSuccess: () => {
      emit('saveAndNext')
    }
  })
}

// Track test result collapsible state
const isTestResultOpen = ref(true)

// Test filter functionality
const { isTesting, testFilter: testFilterFn } = useTestFilter()

// Get test result from props
const testResult = computed(() => {
  const result = (props as any).testResult
  if (!result) {
    return null
  }
  return {
    success: result.success,
    message: result.message,
    details: result.details
  }
})

const testFilter = () => {
  if (isTesting.value) {
    return
  }

  const data = form.data()
  testFilterFn(props.pipeline.id, data)
}
</script>

<template>
  <PipelineStepLayout
    :pipeline="props.pipeline"
    :stepper="props.stepper"
    @save="save"
    @save-and-next="saveAndNext"
  >
    <div class="space-y-8 max-w-6xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b">
        <div class="space-y-1.5">
          <h2 class="text-2xl font-bold tracking-tight">Filter Configuration</h2>
          <p class="text-sm text-muted-foreground leading-relaxed">
            Define rules to filter your imported data based on field values and conditions
          </p>
        </div>
        <Button 
          @click="testFilter" 
          variant="outline" 
          size="lg" 
          class="flex items-center gap-2 w-full sm:w-auto" 
          :disabled="isTesting"
        >
          <Loader2 v-if="isTesting" class="h-4 w-4 animate-spin" />
          <Filter v-else class="h-4 w-4" />
          {{ isTesting ? 'Testing...' : 'Test Filter' }}
        </Button>
      </div>

      <!-- Rules -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Filter Rules</h3>
          <Button @click="addRule" variant="outline" size="sm" class="flex items-center gap-2">
            <Plus class="h-4 w-4" />
            Add Rule
          </Button>
        </div>

        <!-- No rules message -->
        <div v-if="form.rules.length === 0" class="text-center py-16 text-muted-foreground border-2 border-dashed rounded-lg bg-gradient-to-br from-muted/20 to-muted/10 hover:from-muted/30 hover:to-muted/20 transition-all duration-200">
          <div class="mx-auto w-20 h-20 rounded-full bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center mb-6 ring-4 ring-primary/5">
            <Filter class="h-10 w-10 text-primary opacity-70" />
          </div>
          <p class="text-xl font-bold mb-2 text-foreground">No filter rules configured</p>
          <p class="text-sm max-w-md mx-auto leading-relaxed">Add rules to filter your data based on field values and conditions</p>
          <Button @click="addRule" variant="outline" size="lg" class="mt-8 shadow-sm">
            <Plus class="h-4 w-4 mr-2" />
            Add Your First Rule
          </Button>
        </div>

        <!-- Rules List -->
        <div v-for="(rule, ruleIndex) in form.rules" :key="ruleIndex" class="space-y-4">
          <Card class="transition-all duration-300 hover:shadow-lg border-2 hover:-translate-y-0.5">
            <CardContent class="space-y-4 p-6">
                <div class="flex items-center justify-between">
                  <h4 class="font-medium">Rule {{ ruleIndex + 1 }}</h4>
                  <Button
                    @click="removeRule(ruleIndex)"
                    variant="outline"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
                  <!-- Field Key -->
                  <div class="space-y-2">
                    <label class="text-sm font-medium">Field</label>
                    <SearchableSelect
                      :model-value="rule.key"
                      @update:model-value="(val) => handleFieldKeyChange(ruleIndex, val as string)"
                      :items="(props as any).feedKeys || []"
                      value-key="key"
                      display-key="key"
                      :search-keys="['key', 'preview', 'display']"
                      placeholder="Select field"
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

                  <!-- Operator -->
                  <div class="space-y-2">
                    <label class="text-sm font-medium">Operator</label>
                    <Select 
                      v-model="rule.operator" 
                      @update:model-value="(val) => handleOperatorChange(ruleIndex, val as string)"
                      class="w-full"
                    >
                      <SelectTrigger class="w-full">
                        <SelectValue>
                          {{ getOperatorLabel(rule.operator) }}
                        </SelectValue>
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="(label, operatorName) in normalizedOperators"
                          :key="operatorName"
                          :value="operatorName"
                        >
                          {{ label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Value - Different input types based on operator -->
                  <div v-if="needsValue(rule.operator)" class="space-y-2">
                    <label class="text-sm font-medium">Value</label>
                    
                    <!-- Dual select (max 2 values) for between/not_between operators -->
                    <DualSelect
                      v-if="isBetweenOperator(rule.operator) && rule.key"
                      v-model="rule.value"
                      :items="getFieldUniqueValues(rule.key)"
                      value-key="value"
                      display-key="label"
                      placeholder="Select 2 values (min - max)"
                      search-placeholder="Search values..."
                      class="w-full"
                      :max-selections="2"
                    />
                    
                    <!-- Multi-select for in/not_in operators -->
                    <MultiSelect
                      v-else-if="isMultiValueOperator(rule.operator) && rule.key"
                      v-model="rule.value"
                      :items="getFieldUniqueValues(rule.key)"
                      value-key="value"
                      display-key="label"
                      placeholder="Select values"
                      search-placeholder="Search values..."
                      class="w-full"
                    />
                    
                    <!-- Combo input (select or type custom) for contains/not_contains operators -->
                    <ComboInput
                      v-else-if="shouldUseComboInput(rule.operator) && rule.key"
                      v-model="rule.value"
                      :items="getFieldUniqueValues(rule.key)"
                      value-key="value"
                      display-key="label"
                      placeholder="Select or type a value"
                      search-placeholder="Search or type..."
                      class="w-full"
                      :allow-custom="true"
                    />
                    
                    <!-- Text input for regex, starts_with, and ends_with operators -->
                    <Input
                      v-else-if="shouldUseTextInput(rule.operator)"
                      v-model="rule.value"
                      :placeholder="isRegexOperator(rule.operator) ? 'Enter regex pattern' : 'Enter value'"
                      class="w-full"
                    />
                    
                    <!-- Single select for other operators with field selected -->
                    <SearchableSelect
                      v-else-if="rule.key && getFieldUniqueValues(rule.key).length > 0"
                      v-model="rule.value"
                      :items="getFieldUniqueValues(rule.key)"
                      value-key="value"
                      display-key="label"
                      placeholder="Select value"
                      search-placeholder="Search values..."
                      class="w-full"
                    />
                    
                    <!-- Text input fallback -->
                    <Input
                      v-else
                      v-model="rule.value"
                      placeholder="Enter value"
                      class="w-full"
                    />
                  </div>

                  <!-- Regex Flags -->
                  <div v-if="needsRegexFlags(rule.operator)" class="space-y-2">
                    <label class="text-sm font-medium">Regex Flags</label>
                    <Input
                      v-model="rule.regex_flags"
                      placeholder="i, m, etc."
                      class="w-full"
                    />
                  </div>
                </div>

                <!-- Case Sensitive -->
                <div v-if="isCaseSensitiveOperator(rule.operator)" class="flex items-center space-x-2 p-4 border rounded-lg bg-muted/50">
                  <Switch v-model="rule.case_sensitive" />
                  <div class="space-y-0.5">
                    <label class="text-sm font-medium">Case Sensitive</label>
                    <p class="text-xs text-muted-foreground">Match case when comparing values</p>
                  </div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                  <label class="text-sm font-medium">Description (Optional)</label>
                  <Textarea
                    v-model="rule.description"
                    placeholder="Describe what this rule does"
                    class="w-full"
                    rows="2"
                  />
                </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Test Result -->
      <Card v-if="testResult" class="mt-6 border-2 shadow-sm">
        <Collapsible v-model:open="isTestResultOpen">
          <CardHeader class="pb-4">
            <CollapsibleTrigger as-child>
              <button class="w-full text-left">
                <CardTitle class="text-lg font-semibold flex items-center gap-2">
                  <CheckCircle2 v-if="testResult.success" class="h-5 w-5 text-green-600 dark:text-green-400" />
                  <XCircle v-else class="h-5 w-5 text-red-600 dark:text-red-400" />
                  Test Result
                  <ChevronDown 
                    class="h-4 w-4 transition-transform duration-200 ml-auto"
                    :class="{ 'rotate-180': isTestResultOpen }"
                  />
                </CardTitle>
                <CardDescription class="text-sm mt-1">
                  {{ testResult.success ? 'Filter test completed successfully' : 'Filter test encountered an error' }}
                </CardDescription>
              </button>
            </CollapsibleTrigger>
          </CardHeader>
          <CollapsibleContent>
            <CardContent class="pt-0">
              <div class="p-4 bg-muted/50 rounded-lg border">
                <pre class="text-xs font-mono whitespace-pre-wrap break-words overflow-x-auto">
                  {{ JSON.stringify(testResult, null, 2) }}
                </pre>
              </div>
            </CardContent>
          </CollapsibleContent>
        </Collapsible>
      </Card>
    </div>
  </PipelineStepLayout>
</template>
