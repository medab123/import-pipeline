<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { ArrowLeft, Plus, X } from 'lucide-vue-next'

interface Option {
  value: string
  label: string
}

interface DealerData {
  id: number
  name: string
  notes: string | null
  postingAddress: string | null
  websiteUrls: string[]
  paymentPeriod: string
}

const props = defineProps<{
  dealer: DealerData
  paymentPeriods: Option[]
}>()

const form = useForm({
  name: props.dealer.name,
  notes: props.dealer.notes || '',
  posting_address: props.dealer.postingAddress || '',
  website_urls: props.dealer.websiteUrls.length > 0 ? [...props.dealer.websiteUrls] : [''],
  payment_period: props.dealer.paymentPeriod,
})

const addWebsite = () => {
  form.website_urls.push('')
}

const removeWebsite = (index: number) => {
  form.website_urls.splice(index, 1)
}

const submit = () => {
  form.put(route('dashboard.dealers.update', props.dealer.id))
}
</script>

<template>
  <Head title="Edit Dealer" />

  <Default>
    <PageHeader
      title="Edit Dealer"
      :description="`Update details for ${dealer.name}`"
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.dealers.index')">
            <ArrowLeft class="w-4 h-4 mr-2" /> Back
          </Link>
        </Button>
      </template>
    </PageHeader>

    <div class="w-full">
      <form @submit.prevent="submit">
        <Card>
          <CardHeader>
            <CardTitle>Dealer Details</CardTitle>
            <CardDescription>
              Update the dealer information below.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  placeholder="Dealer name"
                  :class="{ 'border-destructive': form.errors.name }"
                />
                <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="posting_address">Posting Address</Label>
                <Input
                  id="posting_address"
                  v-model="form.posting_address"
                  placeholder="123 Main St, City"
                />
                <p v-if="form.errors.posting_address" class="text-sm text-destructive">{{ form.errors.posting_address }}</p>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <Label>Website URLs</Label>
                <Button type="button" variant="outline" size="sm" @click="addWebsite">
                  <Plus class="w-4 h-4 mr-1" /> Add
                </Button>
              </div>
              <div v-for="(url, index) in form.website_urls" :key="index" class="flex items-center gap-2">
                <Input
                  v-model="form.website_urls[index]"
                  placeholder="https://example.com"
                  :class="{ 'border-destructive': form.errors[`website_urls.${index}`] }"
                />
                <Button
                  v-if="form.website_urls.length > 1"
                  type="button"
                  variant="ghost"
                  size="icon"
                  @click="removeWebsite(index)"
                >
                  <X class="w-4 h-4" />
                </Button>
              </div>
              <p v-if="form.errors.website_urls" class="text-sm text-destructive">{{ form.errors.website_urls }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="payment_period">Payment Period</Label>
                <Select v-model="form.payment_period">
                  <SelectTrigger :class="{ 'border-destructive': form.errors.payment_period }">
                    <SelectValue placeholder="Select period" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="p in paymentPeriods" :key="p.value" :value="p.value">{{ p.label }}</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.payment_period" class="text-sm text-destructive">{{ form.errors.payment_period }}</p>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="notes">Notes</Label>
              <Textarea
                id="notes"
                v-model="form.notes"
                placeholder="Additional notes about this dealer..."
                rows="3"
              />
              <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
            </div>
          </CardContent>
          <CardFooter class="flex justify-end gap-2 border-t pt-4">
            <Button variant="outline" type="button" as-child>
              <Link :href="route('dashboard.dealers.index')">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
              {{ form.processing ? 'Updating...' : 'Update Dealer' }}
            </Button>
          </CardFooter>
        </Card>
      </form>
    </div>
  </Default>
</template>
