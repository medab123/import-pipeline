<script setup lang="ts">
import {computed, ref, watch} from 'vue'
import {useForm} from '@inertiajs/vue3'
import PipelineStepLayout from '../Partials/PipelineStepLayout.vue'
import DownloaderTypeSelector from './Partials/downloader/DownloaderTypeSelector.vue'
import HttpHttpsConfig from './Partials/downloader/HttpHttpsConfig.vue'
import FtpConfig from './Partials/downloader/FtpConfig.vue'
import SftpConfig from './Partials/downloader/SftpConfig.vue'
import LocalFileConfig from './Partials/downloader/LocalFileConfig.vue'
import CommonSettings from './Partials/CommonSettings.vue'
import {Form} from '@/components/ui/form'
import {Button} from '@/components/ui/button'
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/components/ui/card'
import {Collapsible, CollapsibleContent, CollapsibleTrigger} from '@/components/ui/collapsible'
import {Loader2, CheckCircle2, XCircle, ChevronDown} from 'lucide-vue-next'
import {DownloaderConfigStepViewModel} from '@/types/generated'
import {useTestDownloader} from '@/composables/useTestDownloader'
import {DOWNLOADER_TYPES} from '@/constants/downloader-types'

const props = defineProps<DownloaderConfigStepViewModel>()

const emit = defineEmits<{
  save: []
  saveAndNext: []
}>()

// Form state
const form = useForm({
  downloader_type: props.downloaderType || 'https',
  options: {
    source: props.source,
    host: props.host || '',
    port: props.port || 443,
    username: props.username || '',
    password: props.password || '',
    file: props.file || '',
    timeout: props.timeout,
    retry_attempts: props.retryAttempts,
    method: props.method,
    headers: props.headers,
    body: props.body || '',
    verify_ssl: props.verifySsl,
    follow_redirects: props.followRedirects,
  }
})

// Dynamic headers and query params
const headers = ref<Array<{ key: string; value: string }>>(
    Object.entries(props.headers).map(([key, value]) => ({key, value}))
)
const queryParams = ref<Array<{ key: string; value: string }>>(
    Object.entries(props.queryParams).map(([key, value]) => ({key, value}))
)

// Test downloader functionality
// Track test result collapsible state
const isTestResultOpen = ref(true)

const {isTesting, testDownloader: testDownloaderFn} = useTestDownloader()

// Get test result from props (now array data)
const testResult = computed(() => {
  const result = props.testResult as any
  if (!result) {
    return null
  }
  return {
    success: result.success,
    message: result.message,
    details: result.details
  }
})

// Computed properties
const isHttpType = computed(() => ['http', 'https'].includes(form.downloader_type))
const isFtpType = computed(() => form.downloader_type === 'ftp')
const isSftpType = computed(() => form.downloader_type === 'sftp')
const isLocalType = computed(() => form.downloader_type === 'local')

// Methods
const updateHeaders = (newHeaders: Array<{ key: string; value: string }>) => {
  headers.value = newHeaders
}

const updatePortForDownloaderType = (downloaderType: string) => {
  const downloaderConfig = DOWNLOADER_TYPES[downloaderType]
  if (downloaderConfig?.defaultPort) {
    form.options.port = downloaderConfig.defaultPort
  }
}

// Watch for downloader type changes and update port accordingly
watch(() => form.downloader_type, (newType) => {
  updatePortForDownloaderType(newType)
})

const testDownloader = async () => {

  if (isTesting.value) {
    return
  }

  const headersObj = getHeaders()
  const data  = form.data()
  const transformed = {
    ...data,
    options: {
      ...data.options,
      headers: headersObj,
    }
  }
  testDownloaderFn(props.pipeline.id, transformed);
}


const getHeaders = () => {
  const headersObj: Record<string, string> = {}
  headers.value.forEach(header => {
    if (header.key && header.value) {
      headersObj[header.key] = header.value
    }
  })

  return headersObj;
}

const handleSave = () => {
  if (form.processing) {
    return
  }
  // Convert headers and query params to objects
  const headersObj = getHeaders()
  // Update form data
  form.transform((data) => ({
    ...data,
    options: {
      ...data.options,
      headers: headersObj,
    }
  }))
      .post(route('dashboard.import.pipelines.step.store', {
        pipeline: props.pipeline.id,
        step: 'downloader-config'
      }), {
        onSuccess: () => {
          emit('save')
        },
        preserveState: true,
        preserveScroll: true,
      })
}


const handleSaveAndNext = () => {

  // Update form data
  form.transform((data) => ({
    ...data,
    options: {
      ...data.options,
      headers: getHeaders(),
    }
  }))

  form.post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: 'downloader-config'
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
          <h2 class="text-2xl font-bold tracking-tight">Data Downloader</h2>
          <p class="text-sm text-muted-foreground leading-relaxed">
            Configure how to download your data source
          </p>
        </div>
      </div>

      <Form :form="form" @submit="handleSaveAndNext">
        <!-- Downloader Type Selection -->
        <DownloaderTypeSelector
            :selected-type="form.downloader_type"
            @update:selected-type="form.downloader_type = $event"
        />

        <!-- Configuration based on downloader type -->
        <div class="mt-6">
          <!-- HTTP/HTTPS Configuration -->
          <div v-if="isHttpType">
            <HttpHttpsConfig
                :form="form"
                :headers="headers"
                :query-params="queryParams"
                @update:headers="updateHeaders"
            />
          </div>

          <!-- FTP Configuration -->
          <div v-else-if="isFtpType">
            <FtpConfig :form="form"/>
          </div>

          <!-- SFTP Configuration -->
          <div v-else-if="isSftpType">
            <SftpConfig :form="form"/>
          </div>

          <!-- Local File Configuration -->
          <div v-else-if="isLocalType">
            <LocalFileConfig :form="form"/>
          </div>
        </div>

        <!-- Common Settings -->
        <CommonSettings :form="form"/>

        <!-- Test Downloader Section -->
        <Card class="mt-8 border-2 shadow-sm">
          <CardHeader class="pb-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div class="space-y-1">
                <CardTitle class="text-lg font-semibold">Test Downloader</CardTitle>
                <CardDescription class="text-sm">
                  Save configuration and test the downloader connection
                </CardDescription>
              </div>
              <Button
                  type="button"
                  variant="outline"
                  size="lg"
                  :disabled="isTesting || form.processing"
                  @click="testDownloader"
                  class="w-full sm:w-auto"
              >
                <Loader2 v-if="isTesting" class="w-4 h-4 mr-2 animate-spin" />
                <span v-if="isTesting">Testing...</span>
                <span v-else>Save & Test</span>
              </Button>
            </div>
          </CardHeader>
          <CardContent class="pt-0">
            <!-- Test Result -->
            <Collapsible v-if="testResult" v-model:open="isTestResultOpen" class="mt-4">
              <div
                  class="p-4 rounded-lg border-2 transition-all duration-200"
                  :class="{
                  'bg-green-50/50 dark:bg-green-950/20 border-green-500/50': testResult.success,
                  'bg-red-50/50 dark:bg-red-950/20 border-red-500/50': !testResult.success
                }"
              >
                <CollapsibleTrigger as-child>
                  <button class="flex items-start gap-3 w-full text-left">
                    <div class="flex-shrink-0 mt-0.5">
                      <CheckCircle2
                          v-if="testResult.success"
                          class="h-5 w-5 text-green-600 dark:text-green-400"
                      />
                      <XCircle
                          v-else
                          class="h-5 w-5 text-red-600 dark:text-red-400"
                      />
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-semibold mb-1" :class="{
                        'text-green-900 dark:text-green-100': testResult.success,
                        'text-red-900 dark:text-red-100': !testResult.success
                      }">
                        {{ testResult.message }}
                      </p>
                    </div>
                    <ChevronDown 
                      class="h-4 w-4 transition-transform duration-200 mt-0.5 shrink-0"
                      :class="{ 'rotate-180': isTestResultOpen }"
                    />
                  </button>
                </CollapsibleTrigger>
                <CollapsibleContent>
                  <div v-if="testResult.details" class="mt-3">
                    <div class="p-3 bg-background/50 rounded border text-xs font-mono overflow-x-auto">
                      <pre class="whitespace-pre-wrap break-words">{{ JSON.stringify(testResult.details, null, 2) }}</pre>
                    </div>
                  </div>
                </CollapsibleContent>
              </div>
            </Collapsible>
            <div v-else-if="!isTesting" class="text-center py-8 text-sm text-muted-foreground">
              Click "Save & Test" to verify your downloader configuration
            </div>
          </CardContent>
        </Card>
      </Form>
    </div>
  </PipelineStepLayout>
</template>
