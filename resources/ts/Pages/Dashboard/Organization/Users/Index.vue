<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, computed } from 'vue'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableEmpty } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  Plus,
  MoreHorizontal,
  Edit,
  Trash2,
  Users,
  Shield,
} from 'lucide-vue-next'
import { Pagination } from '@/components/ui/pagination'

interface OrgUser {
  id: number
  name: string
  email: string
  roles: string[]
  created_at: string
  is_current_user: boolean
}

interface Props {
  users: {
    data: OrgUser[]
  }
  paginator: {
    currentPage: number
    hasMorePages: boolean
    lastPage: number
    perPage: number
    total: number
    nextPageUrl: string
    previousPageUrl: string
  }
  assignableRoles: string[]
  organizationName: string
}

const props = defineProps<Props>()

// --- Create User Dialog ---
const createDialogOpen = ref(false)
const createForm = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
})

const openCreateDialog = () => {
  createForm.reset()
  createForm.clearErrors()
  createDialogOpen.value = true
}

const submitCreate = () => {
  createForm.post(route('dashboard.organization.users.store'), {
    preserveScroll: true,
    onSuccess: () => {
      createDialogOpen.value = false
      createForm.reset()
    },
  })
}

// --- Edit User Dialog ---
const editDialogOpen = ref(false)
const editingUser = ref<OrgUser | null>(null)
const editForm = useForm({
  name: '',
  email: '',
  role: '',
  password: '',
  password_confirmation: '',
})

const openEditDialog = (user: OrgUser) => {
  editingUser.value = user
  editForm.name = user.name
  editForm.email = user.email
  editForm.role = user.roles[0] ?? ''
  editForm.password = ''
  editForm.password_confirmation = ''
  editForm.clearErrors()
  editDialogOpen.value = true
}

const submitEdit = () => {
  if (!editingUser.value) return

  editForm.put(route('dashboard.organization.users.update',{ user: editingUser.value.id}), {
    preserveScroll: true,
    onSuccess: () => {
      editDialogOpen.value = false
      editingUser.value = null
    },
  })
}

// --- Delete User Dialog ---
const deleteDialogOpen = ref(false)
const deletingUser = ref<OrgUser | null>(null)

const openDeleteDialog = (user: OrgUser) => {
  deletingUser.value = user
  deleteDialogOpen.value = true
}

const confirmDelete = () => {
  if (!deletingUser.value) return

  router.delete(route('dashboard.organization.users.destroy', deletingUser.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      deleteDialogOpen.value = false
      deletingUser.value = null
    },
    onError: () => {
      deleteDialogOpen.value = false
      deletingUser.value = null
    },
  })
}

// --- Helpers ---
const getInitials = (name: string): string => {
  return name
    .split(' ')
    .map(word => word.charAt(0).toUpperCase())
    .slice(0, 2)
    .join('')
}

const getRoleBadgeVariant = (role: string): string => {
  switch (role) {
    case 'Super Admin':
    case 'Dev':
      return 'default'
    case 'Admin':
      return 'secondary'
    default:
      return 'outline'
  }
}
</script>

<template>
  <Head title="Organization Users" />

  <Default>
    <!-- Page Header -->
    <PageHeader
      title="Organization Users"
      :description="`Manage members of ${organizationName}`"
    >
      <template #actions>
        <Button @click="openCreateDialog">
          <Plus class="w-4 h-4 mr-2" />
          Add User
        </Button>
      </template>
    </PageHeader>

    <!-- Users Table -->
    <Card class="shadow-sm">
      <CardHeader class="pb-4 border-b">
        <div class="space-y-1">
          <CardTitle class="text-xl font-bold">Members</CardTitle>
          <CardDescription class="text-sm">
            {{ paginator.total }} {{ paginator.total === 1 ? 'member' : 'members' }} in your organization
          </CardDescription>
        </div>
      </CardHeader>
      <CardContent class="pt-6">
        <div class="overflow-x-auto rounded-lg border bg-background">
          <Table class="min-w-full">
            <TableHeader>
              <TableRow>
                <TableHead class="w-[300px]">User</TableHead>
                <TableHead class="w-[200px]">Role</TableHead>
                <TableHead class="w-[150px]">Joined</TableHead>
                <TableHead class="w-[100px] text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="users.data.length === 0" :colspan="4">
                <div class="text-center py-16 px-4">
                  <div class="mx-auto w-20 h-20 rounded-full bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center mb-6 ring-4 ring-primary/5">
                    <Users class="h-10 w-10 text-primary" />
                  </div>
                  <h3 class="mt-2 text-xl font-bold">No members yet</h3>
                  <p class="mt-2 text-sm text-muted-foreground max-w-md mx-auto leading-relaxed">
                    Add your first team member to get started.
                  </p>
                  <div class="mt-8">
                    <Button size="lg" class="shadow-md" @click="openCreateDialog">
                      <Plus class="w-4 h-4 mr-2" />
                      Add First Member
                    </Button>
                  </div>
                </div>
              </TableEmpty>
              <TableRow
                v-for="user in users.data"
                :key="user.id"
                class="group hover:bg-muted/50 transition-colors"
              >
                <TableCell>
                  <div class="flex items-center gap-3">
                    <Avatar class="h-9 w-9">
                      <AvatarFallback class="text-xs font-medium">
                        {{ getInitials(user.name) }}
                      </AvatarFallback>
                    </Avatar>
                    <div class="space-y-0.5">
                      <div class="font-medium flex items-center gap-2">
                        {{ user.name }}
                        <Badge v-if="user.is_current_user" variant="outline" class="text-xs px-1.5 py-0">
                          You
                        </Badge>
                      </div>
                      <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="flex flex-wrap gap-1">
                    <Badge
                      v-for="role in user.roles"
                      :key="role"
                      :variant="getRoleBadgeVariant(role)"
                      class="flex items-center gap-1"
                    >
                      <Shield class="h-3 w-3" />
                      {{ role }}
                    </Badge>
                    <span v-if="user.roles.length === 0" class="text-sm text-muted-foreground italic">
                      No role
                    </span>
                  </div>
                </TableCell>
                <TableCell class="text-sm text-muted-foreground whitespace-nowrap">
                  {{ user.created_at }}
                </TableCell>
                <TableCell class="text-right">
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button
                        variant="ghost"
                        size="sm"
                        class="opacity-0 group-hover:opacity-100 transition-all duration-200 hover:bg-accent"
                      >
                        <MoreHorizontal class="w-4 h-4" />
                        <span class="sr-only">Open menu</span>
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                      <DropdownMenuItem @click="openEditDialog(user)" class="flex items-center">
                        <Edit class="w-4 h-4 mr-2" />
                        Edit User
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        v-if="!user.is_current_user"
                        @click="openDeleteDialog(user)"
                        class="flex items-center text-destructive focus:text-destructive"
                      >
                        <Trash2 class="w-4 h-4 mr-2" />
                        Remove User
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Pagination -->
        <div v-if="paginator.total > paginator.perPage" class="mt-6 pt-4 border-t">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-muted-foreground">
              Showing
              <span class="font-semibold text-foreground">{{ ((paginator.currentPage - 1) * paginator.perPage) + 1 }}</span>
              to
              <span class="font-semibold text-foreground">{{ Math.min(paginator.currentPage * paginator.perPage, paginator.total) }}</span>
              of
              <span class="font-semibold text-foreground">{{ paginator.total }}</span>
              {{ paginator.total === 1 ? 'member' : 'members' }}
            </div>
            <Pagination :paginator="paginator" />
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Create User Dialog -->
    <Dialog v-model:open="createDialogOpen">
      <DialogContent class="sm:max-w-[480px]">
        <DialogHeader>
          <DialogTitle>Add New Member</DialogTitle>
          <DialogDescription>
            Create a new user account for your organization.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitCreate" class="space-y-4">
          <div class="space-y-2">
            <Label for="create-name">Full Name</Label>
            <Input
              id="create-name"
              v-model="createForm.name"
              type="text"
              required
              placeholder="John Doe"
              :class="{ 'border-destructive': createForm.errors.name }"
            />
            <p v-if="createForm.errors.name" class="text-sm text-destructive">{{ createForm.errors.name }}</p>
          </div>

          <div class="space-y-2">
            <Label for="create-email">Email</Label>
            <Input
              id="create-email"
              v-model="createForm.email"
              type="email"
              required
              placeholder="name@example.com"
              :class="{ 'border-destructive': createForm.errors.email }"
            />
            <p v-if="createForm.errors.email" class="text-sm text-destructive">{{ createForm.errors.email }}</p>
          </div>

          <div class="space-y-2">
            <Label for="create-role">Role</Label>
            <Select v-model="createForm.role" required>
              <SelectTrigger id="create-role" :class="{ 'border-destructive': createForm.errors.role }">
                <SelectValue placeholder="Select a role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="role in assignableRoles" :key="role" :value="role">
                  {{ role }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="createForm.errors.role" class="text-sm text-destructive">{{ createForm.errors.role }}</p>
          </div>

          <div class="space-y-2">
            <Label for="create-password">Password</Label>
            <Input
              id="create-password"
              v-model="createForm.password"
              type="password"
              required
              placeholder="Min. 8 characters"
              :class="{ 'border-destructive': createForm.errors.password }"
            />
            <p v-if="createForm.errors.password" class="text-sm text-destructive">{{ createForm.errors.password }}</p>
          </div>

          <div class="space-y-2">
            <Label for="create-password-confirm">Confirm Password</Label>
            <Input
              id="create-password-confirm"
              v-model="createForm.password_confirmation"
              type="password"
              required
              placeholder="Repeat password"
            />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="createDialogOpen = false">Cancel</Button>
            <Button type="submit" :disabled="createForm.processing">
              <span v-if="createForm.processing">Creating...</span>
              <span v-else>Create User</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit User Dialog -->
    <Dialog v-model:open="editDialogOpen">
      <DialogContent class="sm:max-w-[480px]">
        <DialogHeader>
          <DialogTitle>Edit User</DialogTitle>
          <DialogDescription>
            Update user details and role assignment.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitEdit" class="space-y-4">
          <div class="space-y-2">
            <Label for="edit-name">Full Name</Label>
            <Input
              id="edit-name"
              v-model="editForm.name"
              type="text"
              required
              :class="{ 'border-destructive': editForm.errors.name }"
            />
            <p v-if="editForm.errors.name" class="text-sm text-destructive">{{ editForm.errors.name }}</p>
          </div>

          <div class="space-y-2">
            <Label for="edit-email">Email</Label>
            <Input
              id="edit-email"
              v-model="editForm.email"
              type="email"
              required
              :class="{ 'border-destructive': editForm.errors.email }"
            />
            <p v-if="editForm.errors.email" class="text-sm text-destructive">{{ editForm.errors.email }}</p>
          </div>

          <div class="space-y-2">
            <Label for="edit-role">Role</Label>
            <Select v-model="editForm.role" required>
              <SelectTrigger id="edit-role" :class="{ 'border-destructive': editForm.errors.role }">
                <SelectValue placeholder="Select a role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="role in assignableRoles" :key="role" :value="role">
                  {{ role }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="editForm.errors.role" class="text-sm text-destructive">{{ editForm.errors.role }}</p>
          </div>

          <div class="space-y-2">
            <Label for="edit-password">New Password <span class="text-muted-foreground font-normal">(optional)</span></Label>
            <Input
              id="edit-password"
              v-model="editForm.password"
              type="password"
              placeholder="Leave blank to keep current"
              :class="{ 'border-destructive': editForm.errors.password }"
            />
            <p v-if="editForm.errors.password" class="text-sm text-destructive">{{ editForm.errors.password }}</p>
          </div>

          <div v-if="editForm.password" class="space-y-2">
            <Label for="edit-password-confirm">Confirm New Password</Label>
            <Input
              id="edit-password-confirm"
              v-model="editForm.password_confirmation"
              type="password"
              placeholder="Repeat new password"
            />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="editDialogOpen = false">Cancel</Button>
            <Button type="submit" :disabled="editForm.processing">
              <span v-if="editForm.processing">Saving...</span>
              <span v-else>Save Changes</span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Remove User</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to remove
            <span class="font-semibold text-foreground">"{{ deletingUser?.name }}"</span>
            from your organization? This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="deleteDialogOpen = false">Cancel</AlertDialogCancel>
          <AlertDialogAction
            @click="confirmDelete"
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            Remove User
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </Default>
</template>
