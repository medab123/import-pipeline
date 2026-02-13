<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ArrowLeft, Copy, Download } from 'lucide-vue-next'
import { PipelineViewModel } from '@/types/generated'
import { toast } from 'vue-sonner'

interface Props {
  pipeline: PipelineViewModel
  execution: {
    id: number
    status: string
    startedAt: string | null
    completedAt: string | null
  }
  resultData: any
}

const props = defineProps<Props>()

const copyToClipboard = async () => {
  try {
    await navigator.clipboard.writeText(JSON.stringify(props.resultData, null, 2))
    toast.success('Copied!', {
      description: 'Result data copied to clipboard',
    })
  } catch (err) {
    toast.error('Failed to copy', {
      description: 'Could not copy to clipboard',
    })
  }
}

const downloadJson = () => {
  const data = JSON.stringify(props.resultData, null, 2)
  const blob = new Blob([data], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `execution-${props.execution.id}-results.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}
</script>

<template>
  <Head :title="`Execution #${execution.id} Results - ${pipeline.name}`" />
  
  <Default>
    <PageHeader 
      :title="`Execution #${execution.id} Results`"
      :description="`Result data for this pipeline execution`"
    >
      <template #actions>
        <Button variant="outline" size="sm" @click="downloadJson">
            <Download class="w-4 h-4 mr-2" />
            Download JSON
        </Button>
        <Button variant="outline" size="sm" @click="copyToClipboard">
            <Copy class="w-4 h-4 mr-2" />
            Copy JSON
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.executions', { pipeline: pipeline.id })">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Executions
          </Link>
        </Button>
      </template>
    </PageHeader>

    <Card>
      <CardHeader>
        <CardTitle>Result Data</CardTitle>
        <CardDescription>
            Full imported data.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="bg-muted/30 p-4 rounded-lg overflow-auto max-h-[80vh]">
            <pre class="text-xs font-mono whitespace-pre-wrap break-all">{{ JSON.stringify(resultData, null, 2) }}</pre>
        </div>
      </CardContent>
    </Card>
  </Default>
</template>
