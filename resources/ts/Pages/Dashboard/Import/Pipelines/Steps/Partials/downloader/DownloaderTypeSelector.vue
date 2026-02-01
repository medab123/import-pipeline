<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card'
import { FormLabel, FormMessage } from '@/components/ui/form'
import { DOWNLOADER_TYPES } from '@/constants/downloader-types'

interface Props {
  selectedType: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:selectedType': [value: string]
}>()

const selectType = (type: string) => {
  emit('update:selectedType', type)
}
</script>

<template>
  <div class="mb-6 space-y-3">
    <div>
      <FormLabel class="text-base font-semibold">Downloader Type <span class="text-destructive ml-1">*</span></FormLabel>
      <p class="text-sm text-muted-foreground mt-1">
        Choose the type of downloader for your data source
      </p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
      <div
        v-for="(type, key) in DOWNLOADER_TYPES"
        :key="key"
        class="relative cursor-pointer group"
        @click="selectType(key)"
      >
        <Card 
          class="transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 border-2 h-full"
          :class="{
            'ring-2 ring-primary border-primary shadow-md bg-primary/5': selectedType === key,
            'hover:border-primary/50': selectedType !== key
          }"
        >
          <CardContent class="p-5">
            <div class="flex flex-col space-y-3">
              <div class="flex items-start justify-between">
                <component 
                  :is="type.icon" 
                  class="w-7 h-7 transition-all duration-300 group-hover:scale-110"
                  :class="{
                    'text-primary': selectedType === key,
                    'text-muted-foreground group-hover:text-primary': selectedType !== key
                  }"
                />
                <div 
                  v-if="selectedType === key" 
                  class="w-5 h-5 rounded-full bg-primary flex items-center justify-center"
                >
                  <svg class="w-3 h-3 text-primary-foreground" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <div class="space-y-1">
                <h3 class="font-semibold text-sm">{{ type.label }}</h3>
                <p class="text-xs text-muted-foreground leading-relaxed">{{ type.description }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
    
    <FormMessage for="downloader_type" />
  </div>
</template>
