<script setup lang="ts">
import type { PaginatorViewModel } from '@/types/generated'
import {
  RootPagination,
  PaginationEllipsis,
  PaginationFirst,
  PaginationLast,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination'
import { route } from 'ziggy-js'
import { router } from '@inertiajs/vue3'
import breakpoints from '@/lib/breakpoints'

const props = defineProps<{
  paginator: PaginatorViewModel
}>()

const updatePage = (page: number) => {
  const currentRouteName = route().current()
  const currentParams = route().params

  if (!currentRouteName) {
    console.error('Current route name is not defined')
    return
  }

  // Preserve existing query parameters from URL
  const urlParams = new URLSearchParams(window.location.search)
  const queryParams: Record<string, string> = {}
  urlParams.forEach((value, key) => {
    if (key !== 'page') {
      queryParams[key] = value
    }
  })
  
  // Add page parameter
  queryParams.page = page.toString()

  router.visit(route(currentRouteName, {
    ...currentParams,
    ...queryParams,
  }))
}
</script>

<template>
  <RootPagination
    v-slot="{ page }"
    :items-per-page="props.paginator.perPage"
    :total="props.paginator.total"
    :sibling-count="1"
    :show-edges="breakpoints.greater('md').value.value"
    :page="props.paginator.currentPage"
    @update:page="updatePage"
  >
    <PaginationContent
      v-slot="{ items }"
      class="flex items-center gap-1"
    >
      <PaginationFirst />
      <PaginationPrevious />

      <template v-for="(item, index) in items">
        <PaginationItem
          v-if="item.type === 'page'"
          :key="index"
          :value="item.value"
          :is-active="item.value === page"
          @click="() => updatePage(item.value)"
        >
          {{ item.value }}
        </PaginationItem>
        <PaginationEllipsis
          v-else
          :key="item.type"
          :index="index"
        />
      </template>

      <PaginationNext />
      <PaginationLast />
    </PaginationContent>
  </RootPagination>
</template>