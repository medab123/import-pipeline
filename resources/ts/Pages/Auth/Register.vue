<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { FormMessage, Form } from '@/components/ui/form'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { ref } from 'vue'
import { Database } from 'lucide-vue-next'

const form = useForm({
  organization_name: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)

const submit = () => {
  form.post('/register', {
    onFinish: () => {
      form.reset('password', 'password_confirmation')
    },
  })
}
</script>

<template>
  <Head title="Register" />

  <div class="min-h-screen flex flex-col items-center justify-center bg-background p-4 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]"></div>

    <!-- Logo -->
    <div class="mb-8 flex items-center gap-2 font-bold text-2xl">
      <div class="h-8 w-8 bg-primary rounded-lg flex items-center justify-center text-primary-foreground">
        <Database class="h-5 w-5" />
      </div>
      ImportPipeline
    </div>

    <Card class="w-full max-w-md shadow-2xl border-muted/60">
      <CardHeader class="space-y-1 text-center">
        <CardTitle class="text-2xl font-bold">Create an account</CardTitle>
        <CardDescription>
          Set up your organization and start importing data
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Form :form="form" @submit.prevent="submit" class="space-y-4">
          <div class="space-y-2">
            <Label for="organization_name">Organization Name</Label>
            <Input
              id="organization_name"
              v-model="form.organization_name"
              type="text"
              required
              autofocus
              placeholder="Acme Inc."
              :class="{ 'border-destructive': form.errors.organization_name }"
            />
            <FormMessage for="organization_name"></FormMessage>
          </div>

          <div class="space-y-2">
            <Label for="name">Full Name</Label>
            <Input
              id="name"
              v-model="form.name"
              type="text"
              required
              autocomplete="name"
              placeholder="John Doe"
              :class="{ 'border-destructive': form.errors.name }"
            />
            <FormMessage for="name"></FormMessage>
          </div>

          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              required
              autocomplete="username"
              placeholder="name@example.com"
              :class="{ 'border-destructive': form.errors.email }"
            />
            <FormMessage for="email"></FormMessage>
          </div>

          <div class="space-y-2">
            <Label for="password">Password</Label>
            <div class="relative">
              <Input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="Create a password"
                :class="{ 'border-destructive': form.errors.password }"
              />
              <Button
                type="button"
                variant="ghost"
                size="sm"
                class="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                @click="showPassword = !showPassword"
              >
                <span class="text-xs">{{ showPassword ? 'Hide' : 'Show' }}</span>
              </Button>
            </div>
            <FormMessage for="password"></FormMessage>
          </div>

          <div class="space-y-2">
            <Label for="password_confirmation">Confirm Password</Label>
            <Input
              id="password_confirmation"
              v-model="form.password_confirmation"
              :type="showPassword ? 'text' : 'password'"
              required
              placeholder="Confirm your password"
            />
          </div>

          <Button
            type="submit"
            class="w-full"
            :disabled="form.processing"
          >
            <span v-if="form.processing">Creating account...</span>
            <span v-else>Create account</span>
          </Button>

          <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <Link href="/login" class="text-primary hover:underline font-medium">
              Log in
            </Link>
          </div>
        </Form>
      </CardContent>
    </Card>
  </div>
</template>
