import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

interface AuthUser {
  id: number
  name: string | null
  email: string
  email_verified_at: string | null
  permissions: string[]
  roles: string[]
}

interface PageProps {
  auth: {
    user: AuthUser | null
  }
  [key: string]: unknown
}

/**
 * Composable for checking user permissions
 * Similar to Laravel's can() helper function
 *
 * @returns Object with can() function to check permissions
 */
export function usePermissions() {
  const page = usePage<PageProps>()

  const authUser = computed(() => {
    return page.props.auth?.user ?? null
  })

  const permissions = computed(() => {
    return page.props.auth?.user?.permissions ?? []
  })

  const roles = computed(() => {
    return page.props.auth?.user?.roles ?? []
  })

  /**
   * Check if the user has a specific permission
   *
   * @param permission - The permission name to check (e.g., 'import pipelines')
   * @returns true if the user has the permission, false otherwise
   */
  const can = (permission: string): boolean => {
    if (!page.props.auth?.user) {
      return false
    }

    return permissions.value.includes(permission)
  }

  /**
   * Check if the user does NOT have a specific permission
   *
   * @param permission - The permission name to check
   * @returns true if the user does NOT have the permission, false otherwise
   */
  const cannot = (permission: string): boolean => {
    return !can(permission)
  }

  /**
   * Check if the user has a specific role
   *
   * @param role - The role name to check
   * @returns true if the user has the role, false otherwise
   */
  const hasRole = (role: string): boolean => {
    return roles.value.includes(role)
  }

  return {
    authUser,
    can,
    cannot,
    hasRole,
    permissions,
    roles,
  }
}
