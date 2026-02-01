<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Button } from '@/components/ui/button'

interface Props {
  form: any
  headers: Array<{ key: string; value: string }>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:headers': [value: Array<{ key: string; value: string }>]
}>()

const addHeader = () => {
  const newHeaders = [...props.headers, { key: '', value: '' }]
  emit('update:headers', newHeaders)
}

const removeHeader = (index: number) => {
  const newHeaders = props.headers.filter((_, i) => i !== index)
  emit('update:headers', newHeaders)
}

const updateHeader = (index: number, field: 'key' | 'value', value: string) => {
  const newHeaders = [...props.headers]
  newHeaders[index][field] = value
  emit('update:headers', newHeaders)
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>HTTP/HTTPS Configuration</CardTitle>
      <CardDescription>
        Configure HTTP/HTTPS download settings
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-6">
      <!-- URL Field -->
      <FormField name="source">
        <FormItem>
          <FormLabel for="source">URL <span class="text-destructive">*</span></FormLabel>
          <FormControl>
            <Input
              id="source"
              v-model="form.options.source"
              type="url"
              placeholder="https://example.com/data.csv"
              class="w-full"
              required
            />
          </FormControl>
          <FormMessage for="form-field" />
        </FormItem>
      </FormField>

      <!-- HTTP Method and Timeout Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <FormField name="method">
          <FormItem>
            <FormLabel for="method">HTTP Method</FormLabel>
            <Select v-model="form.options.method" class="w-full">
              <SelectTrigger class="w-full">
                <SelectValue placeholder="Select method" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="GET">GET</SelectItem>
                <SelectItem value="POST">POST</SelectItem>
                <SelectItem value="PUT">PUT</SelectItem>
                <SelectItem value="PATCH">PATCH</SelectItem>
                <SelectItem value="DELETE">DELETE</SelectItem>
                <SelectItem value="HEAD">HEAD</SelectItem>
              </SelectContent>
            </Select>
            <FormMessage for="form-field" />
          </FormItem>
        </FormField>

        <FormField name="timeout">
          <FormItem>
            <FormLabel for="timeout">Timeout (seconds)</FormLabel>
            <FormControl>
              <Input
                id="timeout"
                v-model.number="form.options.timeout"
                type="number"
                min="1"
                max="300"
                class="w-full"
              />
            </FormControl>
            <FormMessage for="form-field" />
          </FormItem>
        </FormField>
      </div>

      <!-- Headers Section -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <FormLabel class="text-base">Headers</FormLabel>
          <Button
            type="button"
            variant="outline"
            size="sm"
            @click="addHeader"
          >
            Add Header
          </Button>
        </div>
        
        <div v-if="headers.length === 0" class="text-center py-4 text-muted-foreground border-2 border-dashed rounded-lg">
          No headers configured. Click "Add Header" to add custom headers.
        </div>
        
        <div v-else class="space-y-3">
          <div
            v-for="(header, index) in headers"
            :key="index"
            class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end"
          >
            <div class="space-y-2">
              <label class="text-sm font-medium">Header Name</label>
              <Input
                :model-value="header.key"
                @update:model-value="updateHeader(index, 'key', String($event))"
                placeholder="Header name"
                class="w-full"
              />
            </div>
            
            <div class="flex items-center justify-center">
              <span class="text-muted-foreground">:</span>
            </div>
            
            <div class="space-y-2">
              <label class="text-sm font-medium">Header Value</label>
              <Input
                :model-value="header.value"
                @update:model-value="updateHeader(index, 'value', String($event))"
                placeholder="Header value"
                class="w-full"
              />
            </div>
            
            <div class="flex justify-end">
              <Button
                type="button"
                variant="outline"
                size="sm"
                @click="removeHeader(index)"
                class="text-destructive hover:text-destructive w-full md:w-auto"
              >
                Remove
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Request Body -->
      <FormField name="body">
        <FormItem>
          <FormLabel for="body">Request Body (for POST/PUT/PATCH)</FormLabel>
          <FormControl>
            <Textarea
              id="body"
              v-model="form.options.body"
              placeholder="Enter request body content..."
              rows="4"
              class="w-full"
            />
          </FormControl>
          <FormMessage for="form-field" />
        </FormItem>
      </FormField>

      <!-- HTTP Options -->
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex items-center space-x-2 p-4 border rounded-lg bg-muted/50">
            <Switch
              v-model="form.options.verify_ssl"
              :disabled="form.downloader_type === 'http'"
            />
            <div class="space-y-0.5">
              <FormLabel class="text-base">Verify SSL Certificate</FormLabel>
              <p class="text-sm text-muted-foreground">Validate SSL certificates for HTTPS requests</p>
            </div>
          </div>
          <div class="flex items-center space-x-2 p-4 border rounded-lg bg-muted/50">
            <Switch v-model="form.options.follow_redirects" />
            <div class="space-y-0.5">
              <FormLabel class="text-base">Follow Redirects</FormLabel>
              <p class="text-sm text-muted-foreground">Automatically follow HTTP redirects</p>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
