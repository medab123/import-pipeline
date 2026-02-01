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
  <Card>
    <CardHeader>
      <CardTitle>Local File Configuration</CardTitle>
      <CardDescription>
        Configure local file path
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <FormField name="source">
        <FormItem>
          <FormLabel for="source">File Path <span class="text-destructive">*</span></FormLabel>
          <FormControl>
            <Input
              id="source"
              v-model="form.options.source"
              placeholder="/path/to/your/file.csv"
              required
            />
          </FormControl>
          <FormDescription>
            Enter the absolute path to the file on the server
          </FormDescription>
          <FormMessage for="form-field" />
        </FormItem>
      </FormField>
    </CardContent>
  </Card>
</template>
