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

const props = defineProps<{
    targetField: {
        id: number
        field: string
        label: string
        category?: string
        description?: string
        type: string
        model?: string
    }
}>()

const form = useForm({
  field: props.targetField.field,
  label: props.targetField.label,
  category: props.targetField.category || '',
  description: props.targetField.description || '',
  type: props.targetField.type,
  model: props.targetField.model || ''
})

const submit = () => {
  form.put(route('dashboard.organization.target-fields.update', props.targetField.id))
}
</script>

<template>
  <Head title="Edit Target Field" />

  <Default>
    <PageHeader
      title="Edit Target Field"
      :description="`Update configuration for ${targetField.label}`"
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
            <Link :href="route('dashboard.organization.target-fields.index')">
                <ArrowLeft class="w-4 h-4 mr-2" /> Back
            </Link>
        </Button>
      </template>
    </PageHeader>

    <div class="w-full">
        <form @submit.prevent="submit">
            <Card>
                <CardHeader>
                    <CardTitle>Field Details</CardTitle>
                    <CardDescription>
                        Update the target field configuration.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="field">Field Key (Snake Case)</Label>
                            <Input 
                                id="field" 
                                v-model="form.field" 
                                placeholder="e.g. product_sku" 
                                :class="{ 'border-destructive': form.errors.field }"
                            />
                            <p v-if="form.errors.field" class="text-sm text-destructive">{{ form.errors.field }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="label">Display Label</Label>
                            <Input 
                                id="label" 
                                v-model="form.label" 
                                placeholder="e.g. Product SKU" 
                                :class="{ 'border-destructive': form.errors.label }"
                            />
                            <p v-if="form.errors.label" class="text-sm text-destructive">{{ form.errors.label }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="type">Data Type</Label>
                            <Select v-model="form.type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="string">String</SelectItem>
                                    <SelectItem value="integer">Integer</SelectItem>
                                    <SelectItem value="float">Float</SelectItem>
                                    <SelectItem value="boolean">Boolean</SelectItem>
                                    <SelectItem value="date">Date</SelectItem>
                                    <SelectItem value="datetime">DateTime</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.type" class="text-sm text-destructive">{{ form.errors.type }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="category">Category (Optional)</Label>
                            <Input 
                                id="category" 
                                v-model="form.category" 
                                placeholder="e.g. Product, User, Order" 
                            />
                        </div>
                    </div>

                     <div class="space-y-2">
                        <Label for="model">Related Model (Optional)</Label>
                        <Input 
                            id="model" 
                            v-model="form.model" 
                            placeholder="e.g. App\Models\Product" 
                            class="font-mono text-sm"
                        />
                         <p class="text-xs text-muted-foreground">The Eloquent model this field belongs to, if applicable.</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Textarea 
                            id="description" 
                            v-model="form.description" 
                            placeholder="Describe what this field is used for..." 
                            rows="3"
                        />
                    </div>
                </CardContent>
                <CardFooter class="flex justify-end gap-2 border-t pt-4">
                    <Button variant="outline" type="button" as-child>
                         <Link :href="route('dashboard.organization.target-fields.index')">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Updating...' : 'Update Field' }}
                    </Button>
                </CardFooter>
            </Card>
        </form>
    </div>
  </Default>
</template>
