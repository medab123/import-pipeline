<template>
  <div class="space-y-4">
    <h3 class="text-lg font-semibold">Pagination Example</h3>
    
    <!-- Pagination Info -->
    <div class="text-sm text-muted-foreground">
      Showing {{ paginator.from || 0 }} to {{ paginator.to || 0 }} 
      of {{ paginator.total }} results
    </div>
    
    <!-- Pagination Component -->
    <Pagination 
      :paginator="paginator"
      :sibling-count="2"
      :show-edges="true"
      @page-change="handlePageChange"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { PaginatorViewModel } from '@/types/generated'
import { Pagination } from '@/components/ui/pagination'

// Example paginator data
const paginator = ref<PaginatorViewModel>({
  currentPage: 1,
  hasMorePages: true,
  lastPage: 10,
  perPage: 10,
  total: 100,
  nextPageUrl: '/?page=2',
  previousPageUrl: null
})

const handlePageChange = (page: number) => {
  console.log('Page changed to:', page)
  // Update the paginator with new page
  paginator.value = {
    ...paginator.value,
    currentPage: page,
    hasMorePages: page < paginator.value.lastPage,
    nextPageUrl: page < paginator.value.lastPage ? `/?page=${page + 1}` : null,
    previousPageUrl: page > 1 ? `/?page=${page - 1}` : null
  }
}
</script>
