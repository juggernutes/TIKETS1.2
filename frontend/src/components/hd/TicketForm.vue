<template>
  <form @submit.prevent="guardar">
    <div class="field">
      <label>Área *</label>
      <Select v-model="form.ID_Area_Origen" :options="cats.areas"
        optionLabel="Nombre" optionValue="ID_Area" class="w-full"
        placeholder="Selecciona el área..." @change="onAreaChange" />
    </div>

    <div class="field">
      <label>Empleado *</label>
      <Select
        v-model="form.Numero_Empleado"
        :options="empleadosArea"
        optionLabel="label"
        optionValue="Numero_Empleado"
        placeholder="Selecciona empleado..."
        class="w-full"
        :disabled="!form.ID_Area_Origen"
        filter
        filterPlaceholder="Buscar empleado..."
        showClear
      >
        <template #option="{ option }">
          <div class="emp-opcion">
            <span class="emp-nombre">{{ option.Nombre }}</span>
            <span class="emp-sub">{{ option.Numero_Empleado }} · {{ option.Area ?? '—' }}</span>
          </div>
        </template>
      </Select>
    </div>
    <div class="field">
      <label>Sistema *</label>
      <Select v-model="form.ID_Sistema" :options="cats.sistemas"
        optionLabel="Nombre" optionValue="ID_Sistema" class="w-full" placeholder="Selecciona..." />
    </div>

    <div class="field">
      <label>Descripción *</label>
      <Textarea v-model="form.Descripcion" rows="4" class="w-full"
        placeholder="Describe el problema..." />
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <div class="form-footer">
      <Button type="button" label="Cancelar" text @click="emit('cancelar')" />
      <Button type="submit" label="Guardar" icon="pi pi-save" :loading="guardando" />
    </div>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useCatalogosStore } from '../../stores/catalogos'
import { hdApi } from '../../api/hd'
import Select       from 'primevue/select'
import Textarea     from 'primevue/textarea'
import Button       from 'primevue/button'
import Message      from 'primevue/message'

const emit = defineEmits(['guardado', 'cancelar'])
const cats = useCatalogosStore()

const guardando          = ref(false)
const error              = ref('')
const empleadosArea      = ref([])

const form = reactive({
  Numero_Empleado: null,
  ID_Sistema: null,
  ID_Area_Origen: null,
  Descripcion: '',
})

async function cargarEmpleadosArea() {
  empleadosArea.value = []
  if (!form.ID_Area_Origen) return
  try {
    const { data } = await hdApi.buscarEmpleados('', form.ID_Area_Origen)
    empleadosArea.value = data.map(e => ({
      ...e,
      label: `${e.Numero_Empleado} - ${e.Nombre}`,
    }))
  } catch {
    empleadosArea.value = []
  }
}

async function onAreaChange() {
  form.Numero_Empleado = null
  await cargarEmpleadosArea()
}

async function guardar() {
  error.value = ''
  if (!form.ID_Area_Origen) {
    error.value = 'Selecciona el área.'
    return
  }
  if (!form.Numero_Empleado) {
    error.value = 'Selecciona un empleado.'
    return
  }
  guardando.value = true
  try {
    await hdApi.crearTicket(form)
    emit('guardado')
  } catch (e) {
    const msgs = e.response?.data?.errors
    error.value = msgs
      ? Object.values(msgs).flat().join(' ')
      : e.response?.data?.message ?? 'Error al guardar.'
  } finally {
    guardando.value = false
  }
}
</script>

<style scoped>
.field { display:flex; flex-direction:column; gap:0.4rem; margin-bottom:1rem; }
.field label { font-size:0.875rem; font-weight:500; }
.form-footer { display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.5rem; }
.w-full { width:100%; }
.emp-opcion { display:flex; flex-direction:column; line-height:1.3; }
.emp-nombre { font-size:0.9rem; font-weight:500; }
.emp-sub    { font-size:0.78rem; color:var(--p-text-muted-color); }
</style>
