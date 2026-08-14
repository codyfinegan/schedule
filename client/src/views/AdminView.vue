<script setup lang="ts">
import { ref } from 'vue'
import { api } from '@/api/client'
import type { User, Block, RecurringSchedule, Paginated } from '@/api/types'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Card from 'primevue/card'

// Server-rendered via AdminPageController -- seeds all three tabs so they
// don't need immediate /api fetches on mount.
const props = defineProps<{
  initialUsers?: User[]
  initialBlocks?: Block[]
  initialSchedules?: RecurringSchedule[]
}>()

// -- Users --
const users = ref<User[]>(props.initialUsers ?? [])
async function loadUsers() {
  const { data } = await api.get<{ data: User[] }>('/admin/users')
  users.value = data
}
async function setApproved(user: User, approved: boolean) {
  const { data } = await api.patch<{ data: User }>(`/admin/users/${user.id}`, { is_approved: approved })
  Object.assign(user, data)
}
async function setAdmin(user: User, isAdmin: boolean) {
  const { data } = await api.patch<{ data: User }>(`/admin/users/${user.id}`, { is_admin: isAdmin })
  Object.assign(user, data)
}

// -- Manual blocks --
const blocks = ref<Block[]>(props.initialBlocks ?? [])
async function loadBlocks() {
  const { data } = await api.get<Paginated<Block>>('/blocks?when=upcoming&per_page=100')
  blocks.value = data
}
const newBlock = ref<{ starts_at: Date | null; ends_at: Date | null; botc_app_code: string }>({
  starts_at: null,
  ends_at: null,
  botc_app_code: '',
})
async function createBlock() {
  if (!newBlock.value.starts_at || !newBlock.value.ends_at) return
  await api.post('/blocks', {
    starts_at: newBlock.value.starts_at.toISOString(),
    ends_at: newBlock.value.ends_at.toISOString(),
    botc_app_code: newBlock.value.botc_app_code || null,
  })
  newBlock.value = { starts_at: null, ends_at: null, botc_app_code: '' }
  await loadBlocks()
}
async function deleteBlock(block: Block) {
  await api.delete(`/blocks/${block.id}`)
  await loadBlocks()
}

// -- Recurring schedules --
const schedules = ref<RecurringSchedule[]>(props.initialSchedules ?? [])
async function loadSchedules() {
  const { data } = await api.get<{ data: RecurringSchedule[] }>('/recurring-schedules')
  schedules.value = data
}
const dayOptions = [
  { label: 'Sunday', value: 0 },
  { label: 'Monday', value: 1 },
  { label: 'Tuesday', value: 2 },
  { label: 'Wednesday', value: 3 },
  { label: 'Thursday', value: 4 },
  { label: 'Friday', value: 5 },
  { label: 'Saturday', value: 6 },
]
const newSchedule = ref<{ day_of_week: number; start_time: string; duration_minutes: number }>({
  day_of_week: 3,
  start_time: '19:00',
  duration_minutes: 180,
})
async function createSchedule() {
  await api.post('/recurring-schedules', newSchedule.value)
  await loadSchedules()
  await loadBlocks()
}
async function toggleScheduleActive(schedule: RecurringSchedule) {
  await api.patch(`/recurring-schedules/${schedule.id}`, { is_active: !schedule.is_active })
  await loadSchedules()
  await loadBlocks()
}
async function deleteSchedule(schedule: RecurringSchedule) {
  await api.delete(`/recurring-schedules/${schedule.id}`)
  await loadSchedules()
}

</script>

<template>
  <div class="admin">
    <h1>Admin</h1>
    <Tabs value="0">
      <TabList>
        <Tab value="0">Users</Tab>
        <Tab value="1">Manual blocks</Tab>
        <Tab value="2">Recurring schedules</Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="0">
          <DataTable :value="users" data-key="id">
            <Column field="email" header="Email" />
            <Column field="display_name" header="Name" />
            <Column header="Approved">
              <template #body="{ data }">
                <Checkbox :model-value="data.is_approved" binary @update:model-value="(v) => setApproved(data, v)" />
              </template>
            </Column>
            <Column header="Admin">
              <template #body="{ data }">
                <Checkbox :model-value="data.is_admin" binary @update:model-value="(v) => setAdmin(data, v)" />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <TabPanel value="1">
          <Card class="form-card">
            <template #title>New block</template>
            <template #content>
              <div class="form-row">
                <DatePicker v-model="newBlock.starts_at" show-time hour-format="24" placeholder="Starts at" />
                <DatePicker v-model="newBlock.ends_at" show-time hour-format="24" placeholder="Ends at" />
                <InputText v-model="newBlock.botc_app_code" placeholder="botc.app code (optional)" />
                <Button label="Create" @click="createBlock" />
              </div>
            </template>
          </Card>

          <DataTable :value="blocks" data-key="id">
            <Column field="starts_at" header="Starts" />
            <Column field="ends_at" header="Ends" />
            <Column field="botc_app_code" header="botc.app" />
            <Column field="rsvp_count" header="RSVPs" />
            <Column header="">
              <template #body="{ data }">
                <Button label="Delete" severity="danger" text size="small" @click="deleteBlock(data)" />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <TabPanel value="2">
          <Card class="form-card">
            <template #title>New recurring schedule</template>
            <template #content>
              <div class="form-row">
                <Select v-model="newSchedule.day_of_week" :options="dayOptions" option-label="label" option-value="value" />
                <InputText v-model="newSchedule.start_time" placeholder="HH:MM" />
                <InputNumber v-model="newSchedule.duration_minutes" suffix=" min" :min="15" />
                <Button label="Create" @click="createSchedule" />
              </div>
            </template>
          </Card>

          <DataTable :value="schedules" data-key="id">
            <Column header="Day">
              <template #body="{ data }">{{ dayOptions[data.day_of_week].label }}</template>
            </Column>
            <Column field="start_time" header="Start" />
            <Column field="duration_minutes" header="Duration (min)" />
            <Column header="Active">
              <template #body="{ data }">
                <Checkbox :model-value="data.is_active" binary @update:model-value="() => toggleScheduleActive(data)" />
              </template>
            </Column>
            <Column header="">
              <template #body="{ data }">
                <Button label="Delete" severity="danger" text size="small" @click="deleteSchedule(data)" />
              </template>
            </Column>
          </DataTable>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>

<style scoped>
.form-card {
  margin-bottom: 1rem;
}

.form-row {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  align-items: center;
}
</style>
