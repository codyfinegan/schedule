<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { ThemeToggle } from "@/components/app/header"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

</script>

<template>
  <header
    data-slot="header"
    :class="
      cn(
        'flex h-(--header-height) shrink-0 items-center gap-2 border-b transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-(--header-height)',
        props.class,
      )
    ">
    <div class="flex w-full items-center gap-1 px-4 lg:gap-2 lg:px-6">
      <h1 class="font-medium text-lg">
        <a href="/">
        BOTC
        <small>Game Scheduler</small>
        </a>
      </h1>
      <div class="ml-auto flex items-center gap-2">
        <ThemeToggle />
      </div>
    </div>
  </header>
</template>
