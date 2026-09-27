<script setup lang="ts">
import { useDark } from '@vueuse/core'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import SunIcon from '@/components/icons/SunIcon.vue'
import MoonIcon from '@/components/icons/MoonIcon.vue'

// Toggles the `.dark` class on <html> (which the CSS variables in style.css
// key off) and persists the choice in localStorage.
const isDark = useDark({ initialValue: 'dark' })
</script>

<template>
  <div class="mx-auto my-16 flex max-w-lg flex-col gap-3">
    <div class="flex justify-end">
      <Button
        variant="ghost"
        size="icon"
        class="rounded-full"
        :aria-label="isDark ? 'Switch to light theme' : 'Switch to dark theme'"
        @click="isDark = !isDark"
      >
        <MoonIcon v-if="isDark" />
        <SunIcon v-else />
      </Button>
    </div>
    <Card>
      <CardHeader>
        <CardTitle>Schedule</CardTitle>
      </CardHeader>
      <CardContent class="flex flex-col items-start gap-4">
        <p>Dummy content -- Vue 3 + Vite + shadcn-vue scaffold is wired up.</p>
        <Button>It works</Button>
      </CardContent>
    </Card>
  </div>
</template>
