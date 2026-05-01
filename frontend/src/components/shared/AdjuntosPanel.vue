<template>
  <Card class="adjuntos-card">
    <template #title>{{ titulo }}</template>
    <template #content>
      <div class="adjuntos">
        <div class="upload-row">
          <input
            ref="fileInput"
            type="file"
            class="file-input"
            :accept="accept"
            @change="onArchivo"
          />
          <Button
            icon="pi pi-paperclip"
            label="Adjuntar"
            size="small"
            :loading="subiendo"
            :disabled="!archivo"
            @click="subir"
          />
          <Button
            v-if="archivo"
            icon="pi pi-times"
            text
            rounded
            size="small"
            severity="secondary"
            @click="limpiarArchivo"
          />
        </div>

        <span v-if="archivo" class="archivo-seleccionado">{{ archivo.name }}</span>

        <div v-if="imagenes.length" class="imagenes-grid">
          <button
            v-for="img in imagenes"
            :key="img.ID_Adjunto"
            type="button"
            class="thumb"
            @click="descargar(img)"
          >
            <img :src="previews[img.ID_Adjunto]" :alt="img.NombreArchivo" />
          </button>
        </div>

        <DataTable
          :value="adjuntos"
          :loading="cargando"
          size="small"
          class="adjuntos-tabla"
          emptyMessage="Sin adjuntos"
        >
          <Column header="Archivo">
            <template #body="{ data }">
              <div class="archivo-copy">
                <span>{{ data.NombreArchivo }}</span>
                <small>{{ data.Extension?.toUpperCase() || tipoDesdeMime(data.MimeType) }} · {{ formatoBytes(data.TamanoBytes) }}</small>
              </div>
            </template>
          </Column>
          <Column style="width:92px">
            <template #body="{ data }">
              <div class="acciones">
                <Button icon="pi pi-download" text rounded size="small" @click="descargar(data)" />
                <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="eliminar(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { adjuntosApi } from '../../api/adjuntos'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const props = defineProps({
  modulo: { type: String, required: true },
  entidad: { type: String, required: true },
  idRef: { type: [Number, String], required: true },
  titulo: { type: String, default: 'Adjuntos' },
  accept: { type: String, default: 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt' },
})

const toast = useToast()
const adjuntos = ref([])
const previews = ref({})
const archivo = ref(null)
const fileInput = ref(null)
const cargando = ref(false)
const subiendo = ref(false)

const imagenes = computed(() => adjuntos.value.filter(a => String(a.MimeType ?? '').startsWith('image/')))

function revocarPreviews() {
  Object.values(previews.value).forEach(url => URL.revokeObjectURL(url))
  previews.value = {}
}

async function cargarPreviews() {
  revocarPreviews()
  const nuevas = {}
  for (const img of imagenes.value) {
    try {
      const res = await adjuntosApi.descargar(img.ID_Adjunto)
      nuevas[img.ID_Adjunto] = URL.createObjectURL(res.data)
    } catch {}
  }
  previews.value = nuevas
}

async function cargar() {
  if (!props.idRef) return
  cargando.value = true
  try {
    const { data } = await adjuntosApi.listar({
      modulo: props.modulo,
      entidad: props.entidad,
      id_ref: props.idRef,
    })
    adjuntos.value = data ?? []
    await cargarPreviews()
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar adjuntos', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function onArchivo(event) {
  archivo.value = event.target.files?.[0] ?? null
}

function limpiarArchivo() {
  archivo.value = null
  if (fileInput.value) fileInput.value.value = ''
}

async function subir() {
  if (!archivo.value) return
  subiendo.value = true
  try {
    const form = new FormData()
    form.append('Modulo', props.modulo)
    form.append('Entidad', props.entidad)
    form.append('ID_Referencia', props.idRef)
    form.append('archivo', archivo.value)
    await adjuntosApi.subir(form)
    limpiarArchivo()
    toast.add({ severity: 'success', summary: 'Archivo adjuntado', life: 2200 })
    await cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'No se pudo adjuntar', life: 3500 })
  } finally {
    subiendo.value = false
  }
}

async function descargar(adjunto) {
  try {
    const res = await adjuntosApi.descargar(adjunto.ID_Adjunto)
    const url = URL.createObjectURL(res.data)
    const link = document.createElement('a')
    link.href = url
    link.download = adjunto.NombreArchivo
    link.click()
    URL.revokeObjectURL(url)
  } catch {
    toast.add({ severity: 'error', summary: 'No se pudo descargar', life: 3000 })
  }
}

async function eliminar(adjunto) {
  try {
    await adjuntosApi.eliminar(adjunto.ID_Adjunto)
    toast.add({ severity: 'success', summary: 'Adjunto eliminado', life: 2200 })
    await cargar()
  } catch {
    toast.add({ severity: 'error', summary: 'No se pudo eliminar', life: 3000 })
  }
}

function formatoBytes(bytes) {
  const valor = Number(bytes ?? 0)
  if (valor < 1024) return `${valor} B`
  if (valor < 1024 * 1024) return `${(valor / 1024).toFixed(1)} KB`
  return `${(valor / 1024 / 1024).toFixed(1)} MB`
}

function tipoDesdeMime(mime) {
  return String(mime ?? '').split('/').pop()?.toUpperCase() || 'ARCHIVO'
}

watch(() => props.idRef, cargar, { immediate: true })
onBeforeUnmount(revocarPreviews)
</script>

<style scoped>
.adjuntos {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.upload-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto auto;
  gap: 0.5rem;
  align-items: center;
}

.file-input {
  min-width: 0;
  width: 100%;
  font-size: 0.85rem;
}

.archivo-seleccionado {
  color: var(--app-muted);
  font-size: 0.82rem;
  word-break: break-word;
}

.imagenes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
  gap: 0.5rem;
}

.thumb {
  aspect-ratio: 1;
  padding: 0;
  border: 1px solid rgba(148, 163, 184, 0.35);
  border-radius: 8px;
  background: rgba(15, 23, 42, 0.04);
  overflow: hidden;
  cursor: pointer;
}

.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.archivo-copy {
  display: flex;
  flex-direction: column;
  gap: 0.12rem;
  min-width: 0;
}

.archivo-copy span {
  overflow-wrap: anywhere;
}

.archivo-copy small {
  color: var(--app-muted);
}

.acciones {
  display: flex;
  justify-content: flex-end;
  gap: 0.15rem;
}

@media (max-width: 640px) {
  .upload-row {
    grid-template-columns: 1fr;
  }
}
</style>
