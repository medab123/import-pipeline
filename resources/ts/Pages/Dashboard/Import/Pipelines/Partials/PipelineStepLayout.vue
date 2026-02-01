<script setup lang="ts">
import {computed, ref} from 'vue'
import {Head, Link} from '@inertiajs/vue3'
import {route} from 'ziggy-js'
import Default from "@/components/Layoute/Default.vue"
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/components/ui/card'
import {Button} from '@/components/ui/button'
import {Badge} from '@/components/ui/badge'
import {Separator} from '@/components/ui/separator'
import {
  CheckCircle,
  Circle,
  ArrowLeft,
  ArrowRight,
  Save,
  Loader2
} from 'lucide-vue-next'
import type {CreateStepViewModel} from '@/types/generated'

interface Props {
  stepper: CreateStepViewModel
}

const props = defineProps<Props>()

const currentStepInfo = computed(() => props.stepper.current)

const totalSteps = computed(() => props.stepper.steps.length)

const emit = defineEmits<{
  save: []
  saveAndNext: []
}>()

const isSaving = ref(false)
const isSavingAndNext = ref(false)

const handleSave = async () => {
  if (isSaving.value) return
  isSaving.value = true
  try {
    emit('save')
    setTimeout(() => {
      isSaving.value = false
    }, 500)
  } catch (error) {
    isSaving.value = false
  }
}

const handleSaveAndNext = async () => {
  if (isSavingAndNext.value) return
  isSavingAndNext.value = true
  emit('saveAndNext')
  setTimeout(() => {
    isSavingAndNext.value = false
  }, 500)
}
</script>

<template>
  <Head :title="`${currentStepInfo?.title} - Pipeline Configuration`"/>

  <Default>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
      <div class="flex items-center gap-4 flex-wrap">
        <Link
            :href="route('dashboard.import.pipelines.index')"
            class="flex items-center text-muted-foreground hover:text-foreground transition-all duration-200 hover:translate-x-[-2px] group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-sm px-1 -ml-1"
        >
          <ArrowLeft class="w-4 h-4 mr-2 transition-transform group-hover:-translate-x-1"/>
          <span class="font-medium">Back to Pipelines</span>
        </Link>
        <Separator orientation="vertical" class="h-6 hidden sm:block"/>
        <div class="space-y-1 min-w-0">
          <h1 class="text-3xl font-bold tracking-tight break-words">{{ props.stepper.title }}</h1>
          <p class="text-sm text-muted-foreground">
            Configure your import pipeline step by step
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <Badge variant="secondary" class="text-sm font-medium px-3 py-1.5 shadow-sm">
          Step {{ currentStepInfo?.index }} of {{ totalSteps }}
        </Badge>
      </div>
    </div>

    <!-- Horizontal Stepper -->
    <div class="mb-10 pb-4 -mx-6 px-6">
      <div class="flex items-start justify-between min-w-max">
        <div
            v-for="(step, index) in props.stepper.steps"
            :key="step.step"
            class="flex items-start min-w-0"
            :class="{ 'flex-1 min-w-[120px] max-w-[180px]': index < props.stepper.steps.length - 1 }"
        >
          <!-- Step Circle -->
          <div class="flex flex-col items-center relative z-10 w-full">
            <Link
                v-if="step.isAvailable"
                :href="step.route"
                class="group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-full"
            >
              <div
                  class="flex items-center justify-center w-12 h-12 rounded-full border-2 transition-all duration-300 shadow-sm relative"
                  :class="{
                  'bg-primary border-primary text-primary-foreground shadow-md scale-110 ring-4 ring-primary/20': step.step === currentStepInfo?.step,
                  'bg-green-500 border-green-500 text-white hover:scale-110 hover:shadow-md hover:ring-2 hover:ring-green-500/30': step.isAvailable && step.step !== currentStepInfo?.step,
                  'border-muted-foreground text-muted-foreground bg-muted/30': !step.isAvailable
                }"
              >
                <CheckCircle
                    v-if="step.isAvailable && step.step !== currentStepInfo?.step"
                    class="w-6 h-6"
                />
                <Circle
                    v-else-if="step.step === currentStepInfo?.step"
                    class="w-6 h-6 fill-current"
                />
                <span v-else class="text-sm font-semibold">{{ step.index }}</span>

                <!-- Active step pulse animation -->
                <span
                    v-if="step.step === currentStepInfo?.step"
                    class="absolute inset-0 rounded-full bg-primary animate-ping opacity-20"
                />
              </div>
            </Link>
            <div
                v-else
                class="flex items-center justify-center w-12 h-12 rounded-full border-2 transition-all duration-300 shadow-sm border-muted-foreground text-muted-foreground bg-muted/30"
            >
              <span class="text-sm font-semibold">{{ step.index }}</span>
            </div>

            <!-- Step Info -->
            <div class="mt-4 text-center w-full px-1">
              <p
                  class="text-sm font-semibold transition-colors break-words"
                  :class="{
                  'text-foreground font-bold': step.step === currentStepInfo?.step,
                  'text-green-600 dark:text-green-400': step.isAvailable && step.step !== currentStepInfo?.step,
                  'text-muted-foreground': !step.isAvailable
                }"
              >
                {{ step.title }}
              </p>
            </div>
          </div>

          <!-- Connector Line -->
          <div
              v-if="index < props.stepper.steps.length - 1"
              class="flex-1 h-0.5 mx-4 mt-6 relative min-w-[40px] max-w-[80px]"
          >
            <div
                class="absolute inset-0 transition-all duration-500 rounded-full"
                :class="{
                'bg-green-500 shadow-sm': step.isAvailable,
                'bg-muted': !step.isAvailable
              }"
            />
            <!-- Animated progress indicator -->
            <div
                v-if="step.isAvailable"
                class="absolute inset-0 bg-green-500 rounded-full animate-pulse opacity-50"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Step Content -->
    <Card class="mb-8 shadow-sm border-2 transition-all duration-200 hover:shadow-md">
      <CardHeader class="pb-4 border-b">
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <CardTitle class="text-xl font-bold mb-2">{{ currentStepInfo?.title }}</CardTitle>
            <CardDescription class="text-sm leading-relaxed">
              {{ currentStepInfo?.description }}
            </CardDescription>
          </div>
          <Badge
              variant="outline"
              class="shrink-0 text-xs font-medium px-2.5 py-1"
          >
            Step {{ currentStepInfo?.index }}
          </Badge>
        </div>
      </CardHeader>
      <CardContent class="pt-6">
        <slot/>
      </CardContent>
    </Card>

    <!-- Navigation Footer -->
    <div
        class="flex flex-col sm:flex-row items-center justify-between gap-4 p-6 border-t bg-background/50 backdrop-blur-sm px-6 sticky bottom-0 z-50 shadow-lg">
      <div class="w-full sm:w-auto">
        <Link
            v-if="props.stepper.canGoPrevious && props.stepper.previousStepUrl"
            :href="props.stepper.previousStepUrl"
            class="inline-block"
        >
          <Button
              variant="outline"
              size="lg"
              class="group w-full sm:w-auto"
              :disabled="isSaving || isSavingAndNext"
          >
            <ArrowLeft class="w-4 h-4 mr-2 transition-transform group-hover:-translate-x-1"/>
            Previous Step
          </Button>
        </Link>
        <div
            v-else
            class="h-10"
        />
      </div>

      <div class="flex items-center gap-3 w-full sm:w-auto">
        <Button
            variant="outline"
            size="lg"
            @click="handleSave"
            class="flex-1 sm:flex-initial min-w-[120px]"
            :disabled="isSaving || isSavingAndNext"
        >
          <Loader2 v-if="isSaving" class="w-4 h-4 mr-2 animate-spin"/>
          <Save v-else class="w-4 h-4 mr-2"/>
          <span>{{ isSaving ? 'Saving...' : 'Save Draft' }}</span>
        </Button>

        <Button
            size="lg"
            @click="handleSaveAndNext"
            class="flex-1 sm:flex-initial group min-w-[140px]"
            :disabled="isSaving || isSavingAndNext"
        >
          <Loader2 v-if="isSavingAndNext" class="w-4 h-4 mr-2 animate-spin"/>
          <span>{{ isSavingAndNext ? 'Saving...' : 'Save & Continue' }}</span>
          <ArrowRight
              v-if="!isSavingAndNext"
              class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1"
          />
        </Button>
      </div>
    </div>
  </Default>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
