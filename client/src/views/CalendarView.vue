<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { api } from '@/api/client'
import type { Block, Paginated } from '@/api/types'
import Button from 'primevue/button'

// Server-rendered via CalendarPageController -- seeds the initial month's
// data so it doesn't need an immediate /api/blocks fetch on mount.
const props = defineProps<{ initialBlocks?: Block[] }>()

const blocks = ref<Block[]>(props.initialBlocks ?? [])
const viewYear = ref(new Date().getFullYear())
const viewMonth = ref(new Date().getMonth()) // 0-based

const weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

function dateKey(d: Date | string) {
  const date = typeof d === 'string' ? new Date(d) : d
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

const blocksByDate = computed(() => {
  const map = new Map<string, Block[]>()
  for (const block of blocks.value) {
    const key = dateKey(block.starts_at)
    if (!map.has(key)) map.set(key, [])
    map.get(key)!.push(block)
  }
  for (const list of map.values()) {
    list.sort((a, b) => a.starts_at.localeCompare(b.starts_at))
  }
  return map
})

interface DayCell {
  date: Date
  key: string
  inMonth: boolean
  isToday: boolean
  blocks: Block[]
}

const weeks = computed<DayCell[][]>(() => {
  const firstOfMonth = new Date(viewYear.value, viewMonth.value, 1)
  const gridStart = new Date(firstOfMonth)
  gridStart.setDate(gridStart.getDate() - firstOfMonth.getDay())

  const todayKey = dateKey(new Date())
  const days: DayCell[] = []
  for (let i = 0; i < 42; i++) {
    const date = new Date(gridStart)
    date.setDate(gridStart.getDate() + i)
    const key = dateKey(date)
    days.push({
      date,
      key,
      inMonth: date.getMonth() === viewMonth.value,
      isToday: key === todayKey,
      blocks: blocksByDate.value.get(key) ?? [],
    })
  }

  const rows: DayCell[][] = []
  for (let i = 0; i < days.length; i += 7) {
    rows.push(days.slice(i, i + 7))
  }
  // Drop trailing weeks that fall entirely outside the month.
  while (rows.length > 4 && rows[rows.length - 1].every((d) => !d.inMonth)) {
    rows.pop()
  }
  return rows
})

const monthLabel = computed(() =>
  new Date(viewYear.value, viewMonth.value, 1).toLocaleDateString(undefined, {
    month: 'long',
    year: 'numeric',
  }),
)

function shiftMonth(delta: number) {
  const d = new Date(viewYear.value, viewMonth.value + delta, 1)
  viewYear.value = d.getFullYear()
  viewMonth.value = d.getMonth()
}

function goToday() {
  const now = new Date()
  viewYear.value = now.getFullYear()
  viewMonth.value = now.getMonth()
}

async function load() {
  const [upcoming, past] = await Promise.all([
    api.get<Paginated<Block>>('/blocks?when=upcoming&per_page=200'),
    api.get<Paginated<Block>>('/blocks?when=past&per_page=200'),
  ])
  blocks.value = [...upcoming.data, ...past.data]
}

function formatTime(iso: string) {
  return new Date(iso).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
}

function printCalendar() {
  window.print()
}

watch([viewYear, viewMonth], load)
</script>

<template>
  <div class="calendar-page">
    <div class="toolbar">
      <h1>Calendar</h1>
      <div class="nav">
        <Button icon="pi pi-chevron-left" text @click="shiftMonth(-1)" aria-label="Previous month" />
        <span class="month-label">{{ monthLabel }}</span>
        <Button icon="pi pi-chevron-right" text @click="shiftMonth(1)" aria-label="Next month" />
        <Button label="Today" text size="small" @click="goToday" />
        <Button label="Print" icon="pi pi-print" outlined size="small" @click="printCalendar" />
      </div>
    </div>

    <div class="print-title">{{ monthLabel }}</div>

    <div class="month-grid">
      <div class="weekday-row">
        <div v-for="w in weekdayLabels" :key="w" class="weekday-cell">{{ w }}</div>
      </div>
      <div v-for="(week, i) in weeks" :key="i" class="week-row">
        <div
          v-for="day in week"
          :key="day.key"
          class="day-cell"
          :class="{ 'is-outside': !day.inMonth, 'is-today': day.isToday }"
        >
          <div class="day-number">{{ day.date.getDate() }}</div>
          <div class="day-blocks">
            <div v-for="block in day.blocks" :key="block.id" class="day-block" :class="{ 'is-past': block.is_past }">
              <span class="time">{{ formatTime(block.starts_at) }}</span>
              <span class="rsvp">{{ block.rsvp_count }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.calendar-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.nav {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.month-label {
  font-weight: 600;
  min-width: 10rem;
  text-align: center;
}

.print-title {
  display: none;
}

.month-grid {
  border: 1px solid var(--p-content-border-color);
  border-radius: 6px;
  overflow: hidden;
}

.weekday-row,
.week-row {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
}

.weekday-cell {
  padding: 0.5rem;
  font-size: 0.8rem;
  font-weight: 600;
  text-align: center;
  color: var(--p-text-muted-color);
  border-bottom: 1px solid var(--p-content-border-color);
}

.day-cell {
  min-height: 6rem;
  padding: 0.35rem;
  border-right: 1px solid var(--p-content-border-color);
  border-bottom: 1px solid var(--p-content-border-color);
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.day-cell:nth-child(7n) {
  border-right: none;
}

.day-cell.is-outside {
  background: var(--p-content-background);
  color: var(--p-text-muted-color);
  opacity: 0.5;
}

.day-cell.is-today .day-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  background: var(--p-primary-color);
  color: var(--p-primary-contrast-color);
}

.day-number {
  font-size: 0.85rem;
  font-weight: 600;
}

.day-blocks {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.day-block {
  display: flex;
  justify-content: space-between;
  gap: 0.25rem;
  font-size: 0.72rem;
  padding: 0.1rem 0.3rem;
  border-radius: 3px;
  background: var(--p-primary-50, var(--p-highlight-background));
  color: var(--p-primary-color);
}

.day-block.is-past {
  background: transparent;
  color: var(--p-text-muted-color);
  border: 1px solid var(--p-content-border-color);
}

@media print {
  .toolbar {
    display: none;
  }

  .print-title {
    display: block;
    font-size: 1.4rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 0.5rem;
  }

  .calendar-page {
    gap: 0.5rem;
  }

  .month-grid {
    page-break-inside: avoid;
  }

  .day-cell {
    min-height: 12vh;
  }

  .day-block {
    color: #000;
    background: transparent;
    border: 1px solid #ccc;
  }

  .day-block.is-past {
    border-style: dashed;
  }
}
</style>
