<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { ArrowLeft } from 'lucide-vue-next'

interface Option {
  value: number
  label: string
}

const props = defineProps<{
  dealers: Option[]
}>()

const form = useForm({
  dealer_id: '',
  ftp_file_path: '',
  provider: '',
})

const submit = () => {
  form.post(route('dashboard.scraps.store'))
}
</script>

<template>
  <Head title="Create Scrap Source" />

  <Default>
    <PageHeader
      title="Create Scrap Source"
      description="Add a new FTP scrap data source."
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.scraps.index')">
            <ArrowLeft class="w-4 h-4 mr-2" /> Back
          </Link>
        </Button>
      </template>
    </PageHeader>

    <div class="w-full">
      <form @submit.prevent="submit">
        <Card>
          <CardHeader>
            <CardTitle>Scrap Source Details</CardTitle>
            <CardDescription>
              Configure the FTP scrap source.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="space-y-2">
              <Label for="dealer_id">Dealer</Label>
              <Select v-model="form.dealer_id">
                <SelectTrigger :class="{ 'border-destructive': form.errors.dealer_id }">
                  <SelectValue placeholder="Select dealer" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="d in dealers" :key="d.value" :value="String(d.value)">{{ d.label }}</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.dealer_id" class="text-sm text-destructive">{{ form.errors.dealer_id }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="provider">Provider</Label>
                <Input
                  id="provider"
                  v-model="form.provider"
                  placeholder="e.g. AutoTrader, Cars.com"
                  :class="{ 'border-destructive': form.errors.provider }"
                />
                <p v-if="form.errors.provider" class="text-sm text-destructive">{{ form.errors.provider }}</p>
              </div>
              <div class="space-y-2">
                <Label for="ftp_file_path">FTP File Path</Label>
                <Input
                  id="ftp_file_path"
                  v-model="form.ftp_file_path"
                  placeholder="/data/feeds/dealer_feed.csv"
                  class="font-mono text-sm"
                  :class="{ 'border-destructive': form.errors.ftp_file_path }"
                />
                <p v-if="form.errors.ftp_file_path" class="text-sm text-destructive">{{ form.errors.ftp_file_path }}</p>
              </div>
            </div>
          </CardContent>
          <CardFooter class="flex justify-end gap-2 border-t pt-4">
            <Button variant="outline" type="button" as-child>
              <Link :href="route('dashboard.scraps.index')">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Create Scrap Source' }}
            </Button>
          </CardFooter>
        </Card>
      </form>
    </div>
  </Default>
</template>
