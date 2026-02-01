<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'

interface Props {
  form: any
}

defineProps<Props>()
</script>

<template>
  <Card class="mt-6 border-2 shadow-sm">
    <CardHeader class="pb-4">
      <CardTitle class="text-lg font-semibold">Common Settings</CardTitle>
      <CardDescription class="text-sm">
        General configuration options for error handling and performance
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-6 pt-0">
      <FormField name="retry_attempts">
        <FormItem class="space-y-2">
          <FormLabel for="retry_attempts" class="text-sm font-semibold">
            Retry Attempts
          </FormLabel>
          <FormControl>
            <Input
              id="retry_attempts"
              v-model.number="form.options.retry_attempts"
              type="number"
              min="0"
              max="10"
              class="w-full h-10"
              placeholder="0"
            />
          </FormControl>
          <FormDescription class="text-xs">
            Number of retry attempts if download fails (0-10)
          </FormDescription>
          <FormMessage for="form-field" />
        </FormItem>
      </FormField>
    </CardContent>
  </Card>
</template>
