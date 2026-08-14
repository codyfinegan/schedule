<script setup lang="ts">
import { ref, watch } from 'vue'
import { api } from '@/api/client'
import type { Block, Paginated } from '@/api/types'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import DataView from 'primevue/dataview'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

// Server-rendered via BlocksPageController -- seeds the "Upcoming" tab so
// it doesn't need an immediate /api/blocks fetch on mount.
const props = defineProps<{ initialUpcoming?: Block[] }>()

const upcoming = ref<Block[]>(props.initialUpcoming ?? [])
const past = ref<Block[]>([])
const loading = ref(false);

async function load(when: 'upcoming' | 'past') {
  loading.value = true
  try {
    const { data } = await api.get<Paginated<Block>>(`/blocks?when=${when}`)
    if (when === 'upcoming') {
      upcoming.value = data
    } else {
      past.value = data
    }
  } finally {
    loading.value = false
  }
}

async function toggleRsvp(block: Block) {
  const path = `/blocks/${block.id}/rsvp`
  const { data } = block.viewer_has_rsvped
    ? await api.delete<{ data: Block }>(path)
    : await api.post<{ data: Block }>(path)

  const idx = upcoming.value.findIndex((b) => b.id === block.id)
  if (idx !== -1) upcoming.value[idx] = data
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleString(undefined, {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const activeIndex = ref('0')
watch(activeIndex, (i) => {
  if (i === '1' && past.value.length === 0) load('past')
})
</script>

<template>
  <div>
    <h1>Games</h1>
    <Tabs v-model:value="activeIndex">
      <TabList>
        <Tab value="0">Upcoming</Tab>
        <Tab value="1">Past</Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="0">
          <DataView :value="upcoming" data-key="id">
            <template #list="{ items }">
              <div v-for="block in items as Block[]" :key="block.id" class="block-row">
                <div>
                  <div class="when">{{ formatDate(block.starts_at) }}</div>
                  <div class="meta">
                    <Tag v-if="block.botc_app_code" :value="`botc.app: ${block.botc_app_code}`" severity="info" />
                    <span class="rsvp-count">{{ block.rsvp_count }} RSVP'd</span>
                  </div>
                </div>
                <Button
                  :label="block.viewer_has_rsvped ? 'Cancel RSVP' : 'RSVP'"
                  :severity="block.viewer_has_rsvped ? 'secondary' : 'primary'"
                  @click="toggleRsvp(block)"
                />
              </div>
            </template>
            <template #empty>No upcoming games scheduled.</template>
          </DataView>
        </TabPanel>
        <TabPanel value="1">
          <DataView :value="past" data-key="id">
            <template #list="{ items }">
              <div v-for="block in items as Block[]" :key="block.id" class="block-row">
                <div>
                  <div class="when">{{ formatDate(block.starts_at) }}</div>
                  <div class="meta">{{ block.rsvp_count }} attended</div>
                </div>
              </div>
            </template>
            <template #empty>No past games yet.</template>
          </DataView>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>

<style scoped>
.block-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--p-content-border-color);
}

.when {
  font-weight: 600;
}

.meta {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  color: var(--p-text-muted-color);
  font-size: 0.9rem;
}
</style>
