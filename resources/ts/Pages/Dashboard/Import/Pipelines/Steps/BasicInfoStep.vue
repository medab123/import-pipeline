<script setup lang="ts">
import {useForm} from '@inertiajs/vue3'
import {route} from 'ziggy-js'
import PipelineStepLayout from '../Partials/PipelineStepLayout.vue'
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/components/ui/card'
import {Input} from '@/components/ui/input'
import {Textarea} from '@/components/ui/textarea'
import {Select, SelectContent, SelectItem, SelectTrigger, SelectValue} from '@/components/ui/select'
import {Switch} from '@/components/ui/switch'
import {Form, FormDescription, FormItem, FormLabel, FormMessage} from '@/components/ui/form'
import {Clock} from 'lucide-vue-next'
import {BasicInfoStepViewModel} from "@/types/generated";
import {computed} from "vue";

const props = defineProps<BasicInfoStepViewModel>()

const form = useForm({
  name: props.pipeline.name || '',
  description: props.pipeline.description || '',
  target_id: props.pipeline.targetId || null,
  frequency: props.pipeline.frequency || 'daily',
  start_time: props.pipeline.startTime || '09:00',
  is_active: props.pipeline.isActive ?? true,
})

const handleSave = () => {

  form.post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: 'basic-info'
  }), {
    preserveScroll: true,
    onSuccess: () => {
      // Handle success
    },
    onError: (errors) => {
      console.error('Form errors:', errors)
    }
  })
}

const selectedFrequencyLabel = computed(() => {
  const option = props.frequencies.find(opt => opt.value === form.frequency)
  return option ? option.label : ''
})

const handleSaveAndNext = () => {
  console.log(form.data())

  form.post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: props.stepper.current.step
  }))
}
</script>

<template>
  <PipelineStepLayout
      :stepper="props.stepper"
      @save="handleSave"
      @save-and-next="handleSaveAndNext"
  >
    <div class="space-y-8  mx-auto">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b">
        <div class="space-y-1.5">
          <h2 class="text-2xl font-bold tracking-tight">Pipeline Information</h2>
          <p class="text-sm text-muted-foreground leading-relaxed">
            Configure basic settings and schedule for your import pipeline
          </p>
        </div>
      </div>

      <Form :form="form" @submit="handleSaveAndNext" class="space-y-8">
        <!-- Basic Information Section -->
        <Card class="border-2 shadow-sm">
          <CardHeader class="pb-4">
            <CardTitle class="text-lg font-semibold">Basic Information</CardTitle>
            <CardDescription class="text-sm">
              Provide essential details about your import pipeline
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6 pt-0">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Pipeline Name -->
              <FormItem class="space-y-2">
                <FormLabel for="name" class="text-sm font-semibold">
                  Pipeline Name <span class="text-destructive ml-1">*</span>
                </FormLabel>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="e.g., Daily Product Import"
                    class="w-full h-10"
                    required
                />
                <p class="text-xs text-muted-foreground">
                  Choose a descriptive name for easy identification
                </p>
                <FormMessage for="name"/>
              </FormItem>

              <!-- Company -->
              <FormItem class="space-y-2">
                <FormLabel for="company" class="text-sm font-semibold">
                  Company <span class="text-destructive ml-1">*</span>
                </FormLabel>
                <Input
                    id="target_id"
                    v-model="form.target_id"
                    type="text"
                    placeholder="e.g., 102262"
                    class="w-full h-10"
                    required
                />
                <FormDescription>
                  Select the company this pipeline belongs to
                </FormDescription>
                <FormMessage for="target_id"/>
              </FormItem>
            </div>

            <!-- Description -->
            <FormItem class="space-y-2">
              <FormLabel for="description" class="text-sm font-semibold">
                Description
              </FormLabel>
              <Textarea
                  id="description"
                  v-model="form.description"
                  placeholder="Describe what this pipeline does, what data it imports, and any important notes..."
                  rows="4"
                  class="w-full resize-none"
              />
              <p class="text-xs text-muted-foreground">
                Optional: Add a description to help team members understand this pipeline
              </p>
              <FormMessage for="description"/>
            </FormItem>
          </CardContent>
        </Card>

        <!-- Schedule Configuration -->
        <Card class="border-2 shadow-sm">
          <CardHeader class="pb-4">
            <CardTitle class="text-lg font-semibold flex items-center gap-2">
              <Clock class="w-5 h-5 text-primary"/>
              Schedule Configuration
            </CardTitle>
            <CardDescription class="text-sm">
              Configure when and how often this pipeline should run
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6 pt-0">
            <!-- Schedule Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Frequency -->
              <FormItem class="space-y-2">
                <FormLabel for="frequency" class="text-sm font-semibold">
                  Frequency <span class="text-destructive ml-1">*</span>
                </FormLabel>
                <Select v-model="form.frequency" required class="w-full">
                  <SelectTrigger class="w-full h-10">
                    <SelectValue placeholder="Select a frequency">
                      {{ selectedFrequencyLabel }}
                    </SelectValue>
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                        v-for="frequency in props.frequencies"
                        :key="frequency.value"
                        :value="frequency.value"
                        :textValue="frequency.description"
                    >
                      <div class="py-1">
                        <div class="font-medium">{{ frequency.label }}</div>
                        <div class="text-sm text-muted-foreground">{{ frequency.description }}</div>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p class="text-xs text-muted-foreground">
                  How often should this pipeline run?
                </p>
                <FormMessage for="frequency"/>
              </FormItem>

              <!-- Start Time -->
              <FormItem class="space-y-2">
                <FormLabel for="start_time" class="text-sm font-semibold">
                  Start Time <span class="text-destructive ml-1">*</span>
                </FormLabel>
                <Input
                    id="start_time"
                    v-model="form.start_time"
                    type="time"
                    class="w-full h-10"
                    required
                />
                <p class="text-xs text-muted-foreground">
                  The time when the pipeline should start executing
                </p>
                <FormMessage for="start_time"/>
              </FormItem>
            </div>

            <!-- Active Status -->
            <FormItem>
              <div
                  class="flex items-center justify-between p-5 border-2 rounded-lg bg-gradient-to-r from-muted/30 to-muted/50 hover:from-muted/40 hover:to-muted/60 transition-all duration-200 group">
                <div class="space-y-1">
                  <FormLabel class="text-sm font-semibold">
                    Active Status
                  </FormLabel>
                  <p class="text-xs text-muted-foreground">
                    Enable or disable this pipeline. Disabled pipelines won't run automatically.
                  </p>
                </div>
                <Switch
                    v-model="form.is_active"
                    class="data-[state=checked]:bg-primary"
                />
              </div>
              <FormMessage for="is_active"/>
            </FormItem>
          </CardContent>
        </Card>
      </Form>
    </div>
  </PipelineStepLayout>
</template>
