<script setup lang="ts">
import { NConfigProvider, NCard, NButton, NSpace } from 'naive-ui'
</script>

<template>
  <n-config-provider>
    <n-space vertical style="max-width: 32rem; margin: 4rem auto;">
      <n-card title="Schedule">
        <p>Dummy content -- Vue 3 + Vite + Naive UI scaffold is wired up.</p>
        <n-button type="primary">It works</n-button>
      </n-card>
    </n-space>
  </n-config-provider>
</template>
