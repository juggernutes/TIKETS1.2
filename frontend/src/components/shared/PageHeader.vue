<template>
  <div class="page-header">
    <div class="page-header-left">
      <Button v-if="back" icon="pi pi-arrow-left" text rounded size="small"
        @click="router.back()" class="mr-1" />
      <div>
        <h1 class="page-title">
          <i v-if="icon" :class="`pi ${icon}`" />
          {{ titulo }}
        </h1>
        <p v-if="subtitulo" class="page-subtitle">{{ subtitulo }}</p>
      </div>
    </div>
    <div class="page-header-actions">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
const router = useRouter()
defineProps({ titulo: String, subtitulo: String, icon: String, back: Boolean })
</script>

<style scoped>
.page-header        { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:1.25rem; }
.page-header-left   { display:flex; align-items:center; gap:0.25rem; }
.page-header-actions{ display:flex; gap:0.5rem; align-items:center; }
.page-title         { margin:0; font-size:1.35rem; font-weight:700; display:flex; align-items:center; gap:0.5rem; }
.page-subtitle      { margin:0.2rem 0 0; font-size:0.85rem; color:var(--p-text-muted-color); }
.mr-1               { margin-right:0.25rem; }
</style>
