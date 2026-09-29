<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { isAxiosError } from 'axios'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { AppHeader } from '@/components/app'
import { api } from '@/api'

const me = ref<unknown>(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/me')
    me.value = data
  } catch (err) {
    me.value = isAxiosError(err)
      ? { error: err.message, status: err.response?.status, data: err.response?.data }
      : { error: String(err) }
  }
})
</script>

<template>
  <AppHeader />
  <div class="mx-auto my-16 flex max-w-lg flex-col gap-3">
    <Card>
      <CardHeader>
        <CardTitle>/me</CardTitle>
      </CardHeader>
      <CardContent>
        <pre class="overflow-x-auto text-sm">{{ me === null ? 'Loading…' : JSON.stringify(me, null, 2) }}</pre>
      </CardContent>
    </Card>
  </div>
</template>
