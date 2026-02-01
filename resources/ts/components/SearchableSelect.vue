<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { ChevronDown, X, Search, Loader2 } from 'lucide-vue-next'
import {
    Combobox,
    ComboboxAnchor,
    ComboboxList,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxViewport,
} from '@/components/ui/combobox'
import { ComboboxTrigger } from 'reka-ui'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

type FieldItem = Record<string, any>

interface Props {
    modelValue?: string | number
    items: FieldItem[]
    valueKey?: string
    displayKey?: string
    searchKeys?: string[]
    placeholder?: string
    class?: string
    disabled?: boolean
    emptyMessage?: string
    searchPlaceholder?: string
    getDisplayLabel?: (item: FieldItem) => string
    getItemValue?: (item: FieldItem) => string | number
    loading?: boolean
    clearable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select an option',
    disabled: false,
    items: () => [],
    valueKey: 'value',
    displayKey: 'label',
    searchKeys: undefined,
    emptyMessage: undefined,
    searchPlaceholder: 'Search...',
    getDisplayLabel: undefined,
    getItemValue: undefined,
    loading: false,
    clearable: true,
})

const emits = defineEmits<{
    'update:modelValue': [value: string | number | null]
}>()

const searchQuery = ref('')
const isOpen = ref(false)
const isFiltering = ref(false)

// Get searchable fields - use provided searchKeys or infer from items
const searchableFields = computed(() => {
    if (props.searchKeys && props.searchKeys.length > 0) {
        return props.searchKeys
    }

    // Auto-detect searchable fields from first item
    if (props.items.length > 0) {
        const firstItem = props.items[0]
        const keys = Object.keys(firstItem).filter(key => {
            const value = firstItem[key]
            return value !== null && value !== undefined && typeof value !== 'object'
        })
        return keys.slice(0, 5) // Limit to first 5 fields
    }

    return [props.displayKey, props.valueKey]
})

// Filter items based on search query
const filteredItems = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.items
    }

    const query = searchQuery.value.toLowerCase().trim()
    const filtered = props.items.filter((item) => {
        return searchableFields.value.some((field) => {
            const value = item[field]
            if (value === null || value === undefined) return false
            return String(value).toLowerCase().includes(query)
        })
    })

    // Simulate filtering delay for better UX
    if (query && filtered.length !== props.items.length) {
        isFiltering.value = true
        setTimeout(() => {
            isFiltering.value = false
        }, 150)
    }

    return filtered
})

// Highlight matching text in search results
const highlightText = (text: string, query: string): string => {
    if (!query.trim()) return text

    const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
    return text.replace(regex, '<mark class="bg-primary/20 text-primary font-medium rounded px-0.5">$1</mark>')
}

// Get display text for highlighting
const getDisplayText = (item: FieldItem): string => {
    if (props.getDisplayLabel) {
        return props.getDisplayLabel(item)
    }
    const display = item[props.displayKey]
    return display !== null && display !== undefined ? String(display) : ''
}

// Get the selected item
const selectedItem = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined) return null

    if (props.getItemValue) {
        return props.items.find((item) => props.getItemValue!(item) === props.modelValue)
    }

    return props.items.find((item) => {
        const itemValue = item[props.valueKey]
        return itemValue === props.modelValue || String(itemValue) === String(props.modelValue)
    })
})

// Handle selection
const handleSelect = (value: unknown) => {
    if (value !== null && value !== undefined) {
        const normalizedValue = typeof value === 'bigint' ? Number(value) : value
        if (typeof normalizedValue === 'string' || typeof normalizedValue === 'number') {
            emits('update:modelValue', normalizedValue)
            isOpen.value = false
            searchQuery.value = ''
        }
    }
}

// Handle clear
const handleClear = (e: Event) => {
    e.stopPropagation()
    emits('update:modelValue', null)
    searchQuery.value = ''
    isOpen.value = false
}

// Get display label
const displayLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || !selectedItem.value) {
        return props.placeholder
    }

    if (props.getDisplayLabel) {
        return props.getDisplayLabel(selectedItem.value)
    }

    const display = selectedItem.value[props.displayKey]
    return display !== null && display !== undefined ? String(display) : props.placeholder
})

// Get item value
// NOTE: ComboboxItem requires a non-empty string value. We therefore:
// - Prefer the explicit item value (via getItemValue or valueKey) when present
// - Fallback to a stable, non-empty string based on the display text or item contents
const getItemValue = (item: FieldItem): string | number => {
    const resolveValue = (raw: unknown): string | number => {
        if (raw === null || raw === undefined || raw === '') {
            const fallbackDisplay = getDisplayText(item)
            // Always provide a non-empty string as a last resort
            return fallbackDisplay !== '' ? fallbackDisplay : JSON.stringify(item)
        }

        return raw as string | number
    }

    if (props.getItemValue) {
        return resolveValue(props.getItemValue(item))
    }

    const value = item[props.valueKey]
    return resolveValue(value)
}

// Get empty message
const emptyMessageText = computed(() => {
    if (props.emptyMessage) {
        return props.emptyMessage
    }

    if (searchQuery.value) {
        return `No results found matching "${searchQuery.value}"`
    }

    if (props.items.length === 0) {
        return 'No options available'
    }

    return 'No results found'
})

// Check if has value
const hasValue = computed(() => {
    return props.modelValue !== null && props.modelValue !== undefined && selectedItem.value !== null
})

// Reset search when combobox closes
watch(isOpen, (newValue) => {
    if (!newValue) {
        searchQuery.value = ''
    }
})
</script>

<template>
    <Combobox
        v-model:open="isOpen"
        :model-value="modelValue"
        @update:model-value="handleSelect"
    >
        <ComboboxAnchor as-child>
            <ComboboxTrigger as-child>
                <Button
                    variant="outline"
                    role="combobox"
                    :aria-expanded="isOpen"
                    :aria-label="hasValue ? `Selected: ${displayLabel}. Click to change selection.` : placeholder"
                    :disabled="disabled || loading"
                    :class="cn(
            'w-full justify-between font-normal h-9 relative group',
            !modelValue && 'text-muted-foreground',
            isOpen && 'ring-2 ring-ring ring-offset-2',
            props.class
          )"
                >
                    <span class="truncate flex-1 text-left">{{ displayLabel }}</span>
                    <div class="flex items-center gap-1 shrink-0">
                        <button
                            v-if="hasValue && clearable && !disabled && !loading"
                            @click="handleClear"
                            class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 p-0.5 rounded-sm hover:bg-muted"
                            aria-label="Clear selection"
                            type="button"
                        >
                            <X class="h-3.5 w-3.5 text-muted-foreground hover:text-foreground" />
                        </button>
                        <Loader2
                            v-if="loading"
                            class="h-4 w-4 shrink-0 opacity-50 animate-spin"
                        />
                        <ChevronDown
                            v-else
                            class="h-4 w-4 shrink-0 opacity-50 transition-transform duration-200"
                            :class="{ 'rotate-180': isOpen }"
                        />
                    </div>
                </Button>
            </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxList
            class="w-[var(--reka-combobox-trigger-width)] p-0 max-h-[300px] shadow-lg border"
        >
            <div class="relative">
                <ComboboxInput
                    class="border-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 pr-8"
                    v-model="searchQuery"
                    :placeholder="searchPlaceholder"
                    :disabled="loading"
                />
                <Search
                    v-if="!searchQuery && !loading"
                    class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none"
                />
                <Loader2
                    v-if="loading || isFiltering"
                    class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground animate-spin"
                />
            </div>

            <ComboboxViewport class="max-h-[250px] overflow-y-auto">
                <ComboboxEmpty v-if="filteredItems.length === 0 && !loading">
                    <div class="py-8 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <Search class="h-8 w-8 text-muted-foreground/50" />
                            <p class="text-sm text-muted-foreground font-medium">
                                {{ emptyMessageText }}
                            </p>
                            <p
                                v-if="searchQuery"
                                class="text-xs text-muted-foreground/70 mt-1"
                            >
                                Try a different search term
                            </p>
                        </div>
                    </div>
                </ComboboxEmpty>

                <div v-if="loading && filteredItems.length === 0" class="py-8 text-center">
                    <Loader2 class="h-6 w-6 animate-spin text-muted-foreground mx-auto" />
                    <p class="text-sm text-muted-foreground mt-2">Loading options...</p>
                </div>

                <ComboboxItem
                    v-for="(item, index) in filteredItems"
                    :key="getItemValue(item) || index"
                    :value="getItemValue(item)"
                    class="py-2.5 cursor-pointer transition-colors hover:bg-accent focus:bg-accent"
                    :class="{
            'bg-accent': getItemValue(item) === modelValue
          }"
                >
                    <slot name="item" :item="item">
            <span
                class="text-sm"
                v-html="highlightText(getDisplayText(item), searchQuery)"
            />
                    </slot>
                </ComboboxItem>
            </ComboboxViewport>
        </ComboboxList>
    </Combobox>
</template>

