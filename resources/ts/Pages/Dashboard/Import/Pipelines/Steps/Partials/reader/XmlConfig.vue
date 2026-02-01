<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Switch } from '@/components/ui/switch'

interface Props {
  form: any
}

defineProps<Props>()
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>XML Configuration</CardTitle>
      <CardDescription>
        Configure XML parsing settings
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <FormField name="entry_point">
        <FormItem>
          <FormLabel for="entry_point">Entry Point (Optional)</FormLabel>
          <FormControl>
            <Input
              id="entry_point"
              v-model="form.options.entry_point"
              placeholder="//location/units/unit"
            />
          </FormControl>
          <FormMessage for="entry_point" />
          <div class="text-sm text-muted-foreground">
            Use dot notation to access nested data (e.g., "//location/units/unit" to get data from //location/units/unit path)
          </div>
        </FormItem>
      </FormField>

      <FormField name="keep_root">
        <FormItem class="flex flex-row items-center justify-between rounded-lg border p-4">
          <div class="space-y-0.5">
            <FormLabel class="text-base">Keep Root Element</FormLabel>
            <div class="text-sm text-muted-foreground">
              Include the root element in the parsed output
            </div>
          </div>
          <FormControl>
            <Switch
              v-model="form.options.keep_root"
            />
          </FormControl>
        </FormItem>
      </FormField>
    </CardContent>
  </Card>
</template>
