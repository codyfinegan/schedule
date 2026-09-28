<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { AppHeader } from '@/components/app'
</script>

<template>
  <AppHeader />
  <div class="mx-auto my-16 flex max-w-lg flex-col gap-3">
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
