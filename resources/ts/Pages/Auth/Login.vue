<script setup lang="ts">
import { Head, Link, useForm} from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {FormMessage, Form} from '@/components/ui/form'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { AlertCircle } from 'lucide-vue-next'
import { ref } from 'vue'

interface Props {
  status?: string
  canResetPassword?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  status: undefined,
  canResetPassword: false,
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

const submit = () => {
  form.post('/login', {
    onFinish: () => {
      form.reset('password')
    },
  })
}
</script>

<template>
  <Head title="Log in" />

  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-muted/20 p-4">
    <Card class="w-full max-w-md shadow-lg">
      <CardHeader class="space-y-1">
        <CardTitle class="text-2xl font-bold text-center">Welcome back</CardTitle>
        <CardDescription class="text-center">
          Enter your credentials to access your account
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Form :form="form" @submit.prevent="submit" class="space-y-4">
          <div v-if="status" class="mb-4">
            <Alert>
              <AlertCircle class="h-4 w-4" />
              <AlertDescription>{{ status }}</AlertDescription>
            </Alert>
          </div>

          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              required
              autofocus
              autocomplete="username"
              placeholder="name@example.com"
              :class="{ 'border-destructive': form.errors.email }"
            />
            <FormMessage for="email"></FormMessage>
          </div>

          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <Label for="password">Password</Label>
              <Link
                v-if="canResetPassword"
                href="/forgot-password"
                class="text-sm text-primary hover:underline"
              >
                Forgot password?
              </Link>
            </div>
            <div class="relative">
              <Input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="Enter your password"
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

          <div class="flex items-center space-x-2">
            <input
              id="remember"
              v-model="form.remember"
              type="checkbox"
              class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
            />
            <Label for="remember" class="text-sm font-normal cursor-pointer">
              Remember me
            </Label>
          </div>

          <Button
            type="submit"
            class="w-full"
            :disabled="form.processing"
          >
            <span v-if="form.processing">Logging in...</span>
            <span v-else>Log in</span>
          </Button>
        </Form>
      </CardContent>
    </Card>
  </div>
</template>
