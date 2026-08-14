<script setup lang="ts">
import { ref } from 'vue'
import { api, ApiError } from '@/api/client'
import type { User } from '@/api/types'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Card from 'primevue/card'

const step = ref<'email' | 'code'>('email')
const email = ref('')
const code = ref('')
const error = ref('')
const info = ref('')
const busy = ref(false)

// Only ever navigate to a same-origin path from this -- it comes from a
// query param an attacker fully controls.
function safeRedirectTarget(): string {
  const redirect = new URLSearchParams(window.location.search).get('redirect')
  if (redirect && redirect.startsWith('/') && !redirect.startsWith('//')) {
    return redirect
  }
  return '/blocks'
}

async function requestCode() {
  error.value = ''
  busy.value = true
  try {
    await api.post('/auth/request-code', { email: email.value.trim().toLowerCase() })
    info.value = 'If that email is registered, a login code has been sent -- check your inbox.'
    step.value = 'code'
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Something went wrong.'
  } finally {
    busy.value = false
  }
}

async function verifyCode() {
  error.value = ''
  busy.value = true
  try {
    await api.post<{ user: User }>('/auth/verify-code', {
      email: email.value.trim().toLowerCase(),
      code: code.value.trim(),
    })
    window.location.href = safeRedirectTarget()
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Something went wrong.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="login">
    <Card>
      <template #title>Sign in</template>
      <template #content>
        <Message v-if="info" severity="info" :closable="false">{{ info }}</Message>
        <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

        <form v-if="step === 'email'" @submit.prevent="requestCode" class="form">
          <label for="email">Email</label>
          <InputText id="email" v-model="email" type="email" required autofocus />
          <Button type="submit" label="Send login code" :loading="busy" />
        </form>

        <form v-else @submit.prevent="verifyCode" class="form">
          <label for="code">6-digit code</label>
          <InputText id="code" v-model="code" inputmode="numeric" maxlength="6" required autofocus />
          <Button type="submit" label="Verify & sign in" :loading="busy" />
          <Button label="Use a different email" text size="small" @click="step = 'email'" />
        </form>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.login {
  display: flex;
  justify-content: center;
  padding-top: 3rem;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  min-width: 300px;
}
</style>
