import { createApp, type Component } from 'vue'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import 'primeicons/primeicons.css'
import '../style.css'

// The PHP layout injects the page's already-fetched data here (see
// resources/views/layout.php + Http/Controllers/Pages/*) so the Vue island
// can seed its state from props instead of re-fetching it on mount.
function readInitialData(): Record<string, unknown> {
  const el = document.getElementById('page-data')
  if (!el?.textContent) return {}

  const payload = JSON.parse(el.textContent) as { initialData?: Record<string, unknown> }
  return payload.initialData ?? {}
}

export function mountPage(component: Component) {
  const app = createApp(component, readInitialData())

  app.use(PrimeVue, {
    theme: {
      preset: Aura,
      options: { darkModeSelector: '.dark' },
    },
  })

  app.mount('#app')
}
