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
      <CardTitle>JSON Configuration</CardTitle>
      <CardDescription>
        Configure JSON parsing settings
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
              placeholder="inventory.listing"
            />
          </FormControl>
          <FormMessage for="entry_point" />
          <div class="text-sm text-muted-foreground">
            Use dot notation to access nested data (e.g., "inventory.listing" to get data from inventory.listing path)
          </div>
        </FormItem>
      </FormField>
    </CardContent>
  </Card>
</template>
