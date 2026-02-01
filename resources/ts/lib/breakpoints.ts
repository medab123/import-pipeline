import { ref, computed } from 'vue'

const breakpointValues = {
  xs: 0,
  sm: 640,
  md: 768,
  lg: 1024,
  xl: 1280,
  '2xl': 1536,
} as const

type BreakpointKey = keyof typeof breakpointValues

// Shared reactive state that doesn't require component context
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 0)

// Initialize window resize listener if we're in a browser environment
if (typeof window !== 'undefined') {
  const updateWindowWidth = () => {
    windowWidth.value = window.innerWidth
  }
  
  // Initialize with current width
  updateWindowWidth()
  
  // Set up resize listener
  window.addEventListener('resize', updateWindowWidth)
}

const breakpoints = {
  greater: (breakpoint: BreakpointKey) => ({
    value: computed(() => windowWidth.value >= breakpointValues[breakpoint])
  }),
  less: (breakpoint: BreakpointKey) => ({
    value: computed(() => windowWidth.value < breakpointValues[breakpoint])
  }),
  between: (min: BreakpointKey, max: BreakpointKey) => ({
    value: computed(() => 
      windowWidth.value >= breakpointValues[min] && 
      windowWidth.value < breakpointValues[max]
    )
  })
}

export default breakpoints
