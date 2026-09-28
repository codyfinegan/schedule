<script setup lang="ts">
import { nextTick } from "vue"
import { useDark } from "@vueuse/core"
import { Button } from "@/components/ui/button"
import SunIcon from "@/components/icons/SunIcon.vue"
import MoonIcon from "@/components/icons/MoonIcon.vue"

// Toggles the `.dark` class on <html> (which the CSS variables in style.css
// key off) and persists the choice in localStorage.
const isDark = useDark({ initialValue: "dark" })

// Crossfades the whole page between themes via the View Transitions API (the
// animation itself is styled in style.css). Falls back to an instant switch
// where unsupported or when the user prefers reduced motion.
function toggle() {
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches
    if (!("startViewTransition" in document) || reduceMotion) {
        isDark.value = !isDark.value
        return
    }
    document.startViewTransition(async () => {
        isDark.value = !isDark.value
        await nextTick()
    })
}
</script>

<template>
    <Button 
        variant="ghost" 
        size="icon" 
        class="rounded-full"
        :aria-label="isDark ? 'Switch to light theme' : 'Switch to dark theme'" 
        @click="toggle"
        >
        <MoonIcon v-if="isDark" />
        <SunIcon v-else />
    </Button>
</template>
