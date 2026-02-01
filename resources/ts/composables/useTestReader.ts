import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export function useTestReader() {
  const isTesting = ref(false)

  const testReader = (pipelineId: number, config: any) => {
    if (isTesting.value) {
      return
    }

    isTesting.value = true

    router.post(route('dashboard.import.pipelines.reader.test', { pipeline: pipelineId }), config, {
      onFinish: () => {
        isTesting.value = false
      },
      preserveState: true,
      preserveScroll: true,
    })
  }

  return {
    isTesting,
    testReader
  }
}
