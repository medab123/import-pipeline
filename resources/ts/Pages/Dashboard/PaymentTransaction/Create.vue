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
  value: string | number
  label: string
}

const props = defineProps<{
  dealers: Option[]
  statuses: Option[]
  types: Option[]
}>()

const form = useForm({
  dealer_id: '',
  type: 'dealer_payment',
  amount: 0,
  status: 'pending',
  payment_method: '',
  reference: '',
  paid_at: '',
})

const submit = () => {
  form.post(route('dashboard.payment-transactions.store'))
}
</script>

<template>
  <Head title="Create Payment Transaction" />

  <Default>
    <PageHeader
      title="Create Payment Transaction"
      description="Record a new payment transaction."
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.payment-transactions.index')">
            <ArrowLeft class="w-4 h-4 mr-2" /> Back
          </Link>
        </Button>
      </template>
    </PageHeader>

    <div class="w-full">
      <form @submit.prevent="submit">
        <Card>
          <CardHeader>
            <CardTitle>Transaction Details</CardTitle>
            <CardDescription>
              Enter the payment transaction information below.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
              <div class="space-y-2">
                <Label for="type">Type</Label>
                <Select v-model="form.type">
                  <SelectTrigger :class="{ 'border-destructive': form.errors.type }">
                    <SelectValue placeholder="Select type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="t in types" :key="String(t.value)" :value="String(t.value)">{{ t.label }}</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.type" class="text-sm text-destructive">{{ form.errors.type }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="amount">Amount</Label>
                <Input
                  id="amount"
                  v-model="form.amount"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  :class="{ 'border-destructive': form.errors.amount }"
                />
                <p v-if="form.errors.amount" class="text-sm text-destructive">{{ form.errors.amount }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="status">Status</Label>
                <Select v-model="form.status">
                  <SelectTrigger :class="{ 'border-destructive': form.errors.status }">
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="s in statuses" :key="String(s.value)" :value="String(s.value)">{{ s.label }}</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.status" class="text-sm text-destructive">{{ form.errors.status }}</p>
              </div>
              <div class="space-y-2">
                <Label for="payment_method">Payment Method</Label>
                <Input
                  id="payment_method"
                  v-model="form.payment_method"
                  placeholder="e.g. bank_transfer, credit_card"
                />
                <p v-if="form.errors.payment_method" class="text-sm text-destructive">{{ form.errors.payment_method }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="reference">Reference</Label>
                <Input
                  id="reference"
                  v-model="form.reference"
                  placeholder="Transaction reference"
                  class="font-mono text-sm"
                />
                <p v-if="form.errors.reference" class="text-sm text-destructive">{{ form.errors.reference }}</p>
              </div>
              <div class="space-y-2">
                <Label for="paid_at">Paid At</Label>
                <Input
                  id="paid_at"
                  v-model="form.paid_at"
                  type="datetime-local"
                />
                <p v-if="form.errors.paid_at" class="text-sm text-destructive">{{ form.errors.paid_at }}</p>
              </div>
            </div>
          </CardContent>
          <CardFooter class="flex justify-end gap-2 border-t pt-4">
            <Button variant="outline" type="button" as-child>
              <Link :href="route('dashboard.payment-transactions.index')">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Create Transaction' }}
            </Button>
          </CardFooter>
        </Card>
      </form>
    </div>
  </Default>
</template>
