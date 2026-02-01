<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'

interface Props {
  form: any
}

defineProps<Props>()
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>CSV Configuration</CardTitle>
      <CardDescription>
        Configure CSV parsing settings
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <FormField name="delimiter">
          <FormItem>
            <FormLabel for="delimiter">Delimiter <span class="text-destructive">*</span></FormLabel>
            <FormControl>
              <Select v-model="form.options.delimiter" required>
                <SelectTrigger>
                  <SelectValue placeholder="Select delimiter" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value=",">Comma (,)</SelectItem>
                  <SelectItem value=";">Semicolon (;)</SelectItem>
                  <SelectItem value="\t">Tab (\t)</SelectItem>
                  <SelectItem value="|">Pipe (|)</SelectItem>
                </SelectContent>
              </Select>
            </FormControl>
            <FormMessage for="delimiter" />
          </FormItem>
        </FormField>

        <FormField name="enclosure">
          <FormItem>
            <FormLabel for="enclosure">Enclosure</FormLabel>
            <FormControl>
              <Select v-model="form.options.enclosure">
                <SelectTrigger>
                  <SelectValue placeholder="Select enclosure" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='"'>Double Quote (")</SelectItem>
                  <SelectItem value="'">Single Quote (')</SelectItem>
                </SelectContent>
              </Select>
            </FormControl>
            <FormMessage for="enclosure" />
          </FormItem>
        </FormField>

        <FormField name="escape">
          <FormItem>
            <FormLabel for="escape">Escape Character</FormLabel>
            <FormControl>
              <Input
                id="escape"
                v-model="form.options.escape"
                placeholder="\"
                maxlength="1"
              />
            </FormControl>
            <FormMessage for="escape" />
          </FormItem>
        </FormField>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField name="has_header">
          <FormItem class="flex flex-row items-center justify-between rounded-lg border p-4">
            <div class="space-y-0.5">
              <FormLabel class="text-base">Has Header Row</FormLabel>
              <div class="text-sm text-muted-foreground">
                First row contains column headers
              </div>
            </div>
            <FormControl>
              <Switch
                v-model="form.options.has_header"
              />
            </FormControl>
          </FormItem>
        </FormField>

        <FormField name="trim">
          <FormItem class="flex flex-row items-center justify-between rounded-lg border p-4">
            <div class="space-y-0.5">
              <FormLabel class="text-base">Trim Whitespace</FormLabel>
              <div class="text-sm text-muted-foreground">
                Remove leading/trailing spaces from values
              </div>
            </div>
            <FormControl>
              <Switch
                v-model="form.options.trim"
              />
            </FormControl>
          </FormItem>
        </FormField>
      </div>
    </CardContent>
  </Card>
</template>
