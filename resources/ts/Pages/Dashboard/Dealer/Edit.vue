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
import { ArrowLeft } from 'lucide-vue-next'

interface Option {
  value: string
  label: string
}

interface DealerData {
  id: number
  name: string
  status: string
  notes: string | null
  postingAddress: string | null
  websiteUrl: string | null
  fbmpAppAccessToken: string | null
  fbmpAppUrl: string | null
  paymentPeriod: string
}

const props = defineProps<{
  dealer: DealerData
  statuses: Option[]
  paymentPeriods: Option[]
}>()

const form = useForm({
  name: props.dealer.name,
  status: props.dealer.status,
  notes: props.dealer.notes || '',
  posting_address: props.dealer.postingAddress || '',
  website_url: props.dealer.websiteUrl || '',
  fbmp_app_access_token: props.dealer.fbmpAppAccessToken || '',
  fbmp_app_url: props.dealer.fbmpAppUrl || '',
  payment_period: props.dealer.paymentPeriod,
})

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
              <div class="space-y-2">
                <Label for="status">Status</Label>
                <Select v-model="form.status">
                  <SelectTrigger :class="{ 'border-destructive': form.errors.status }">
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.status" class="text-sm text-destructive">{{ form.errors.status }}</p>
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
              <div class="space-y-2">
                <Label for="website_url">Website URL</Label>
                <Input
                  id="website_url"
                  v-model="form.website_url"
                  placeholder="https://example.com"
                />
                <p v-if="form.errors.website_url" class="text-sm text-destructive">{{ form.errors.website_url }}</p>
              </div>
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="fbmp_app_url">FBMP App URL</Label>
                <Input
                  id="fbmp_app_url"
                  v-model="form.fbmp_app_url"
                  placeholder="https://fbmp-app.example.com"
                />
                <p v-if="form.errors.fbmp_app_url" class="text-sm text-destructive">{{ form.errors.fbmp_app_url }}</p>
              </div>
              <div class="space-y-2">
                <Label for="fbmp_app_access_token">FBMP App Access Token</Label>
                <Input
                  id="fbmp_app_access_token"
                  v-model="form.fbmp_app_access_token"
                  placeholder="Access token"
                  class="font-mono text-sm"
                />
                <p v-if="form.errors.fbmp_app_access_token" class="text-sm text-destructive">{{ form.errors.fbmp_app_access_token }}</p>
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
