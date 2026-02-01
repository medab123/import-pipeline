<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'

interface Props {
  form: any
}

defineProps<Props>()
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>SFTP Configuration</CardTitle>
      <CardDescription>
        Configure SFTP download settings
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField name="host">
          <FormItem>
            <FormLabel for="host">Host <span class="text-destructive">*</span></FormLabel>
            <FormControl>
              <Input
                id="host"
                v-model="form.options.host"
                placeholder="sftp.example.com"
                required
              />
            </FormControl>
            <FormMessage for="form-field" />
          </FormItem>
        </FormField>

        <FormField name="port">
          <FormItem>
            <FormLabel for="port">Port</FormLabel>
            <FormControl>
              <Input
                id="port"
                v-model.number="form.options.port"
                type="number"
                min="1"
                max="65535"
              />
            </FormControl>
            <FormMessage for="form-field" />
          </FormItem>
        </FormField>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField name="username">
          <FormItem>
            <FormLabel for="username">Username</FormLabel>
            <FormControl>
              <Input
                id="username"
                v-model="form.options.username"
                placeholder="sftp_user"
              />
            </FormControl>
            <FormMessage for="form-field" />
          </FormItem>
        </FormField>

        <FormField name="password">
          <FormItem>
            <FormLabel for="password">Password</FormLabel>
            <FormControl>
              <Input
                id="password"
                v-model="form.options.password"
                type="password"
                placeholder="sftp_password"
              />
            </FormControl>
            <FormMessage for="form-field" />
          </FormItem>
        </FormField>
      </div>

      <FormField name="source">
        <FormItem>
          <FormLabel for="source">File Path <span class="text-destructive">*</span></FormLabel>
          <FormControl>
            <Input
              id="source"
              v-model="form.options.file"
              placeholder="/path/to/file.csv"
              required
            />
          </FormControl>
          <FormMessage for="form-field" />
        </FormItem>
      </FormField>
      sftp://{{ form.options.username }}:{{ form.options.password }}@{{ form.options.host }}/{{ form.options.file }}

    </CardContent>
  </Card>
</template>
