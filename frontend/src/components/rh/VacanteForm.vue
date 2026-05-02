<template>
  <form @submit.prevent="guardar">
    <div class="grid-2">
      <div class="field col-2">
        <label>Título del puesto *</label>
        <InputText v-model="form.Titulo" class="w-full" placeholder="Ej. Vendedor Tradicional TJ" />
      </div>

      <div class="field">
        <label>Puesto (catálogo) *</label>
        <Select v-model="form.ID_Puesto" :options="cats.puestos"
          optionLabel="Descripcion" optionValue="ID_Puesto"
          class="w-full" placeholder="Selecciona..." filter />
      </div>

      <div class="field">
        <label>Número de posiciones *</label>
        <InputNumber v-model="form.NumeroPosiciones" :min="1" :max="50"
          class="w-full" :useGrouping="false" />
      </div>

      <div class="field">
        <label>Detonante</label>
        <Select v-model="form.DetonanteTipo" :options="detonantes"
          optionLabel="label" optionValue="value"
          class="w-full" placeholder="Selecciona..." showClear />
      </div>

      <div v-if="form.DetonanteTipo === 'BAJA_EMPLEADO'" class="field">
        <label>No. empleado baja</label>
        <InputNumber v-model="form.DetonanteEmpleadoNumero" :min="1"
          class="w-full" :useGrouping="false" />
      </div>

      <div v-if="form.DetonanteTipo === 'CREACION_PUESTO'" class="field">
        <label>Puesto nuevo</label>
        <InputText v-model="form.DetonantePuestoNombre" class="w-full"
          placeholder="Nombre del puesto a crear..." />
      </div>

      <div class="field">
        <label>Área *</label>
        <Select v-model="form.ID_Area" :options="cats.areas"
          optionLabel="Nombre" optionValue="ID_Area"
          class="w-full" placeholder="Selecciona..." />
      </div>

      <div class="field">
        <label>Sucursal</label>
        <Select v-model="form.ID_Sucursal" :options="cats.sucursales"
          optionLabel="Nombre" optionValue="ID_Sucursal"
          class="w-full" placeholder="Todas" showClear />
      </div>

      <div class="field">
        <label>Salario mínimo</label>
        <InputNumber v-model="form.SalarioMin" mode="currency" currency="MXN"
          locale="es-MX" class="w-full" :min="0" />
      </div>

      <div class="field">
        <label>Salario máximo</label>
        <InputNumber v-model="form.SalarioMax" mode="currency" currency="MXN"
          locale="es-MX" class="w-full" :min="0" />
      </div>

      <div class="field col-2">
        <label>Descripción</label>
        <Textarea v-model="form.Descripcion" rows="3" class="w-full"
          placeholder="Responsabilidades y funciones del puesto..." />
      </div>

      <div class="field col-2">
        <label>Requisitos</label>
        <Textarea v-model="form.Requisitos" rows="2" class="w-full"
          placeholder="Escolaridad, experiencia, habilidades..." />
      </div>

      <div class="field col-2">
        <label>Comentario del detonante</label>
        <Textarea v-model="form.DetonanteComentario" rows="2" class="w-full"
          placeholder="Motivo de baja, justificacion del puesto o contexto operativo..." />
      </div>
    </div>

    <Message v-if="error" severity="error" :closable="false" class="mt-1">{{ error }}</Message>

    <div class="form-footer">
      <Button type="button" label="Cancelar" text @click="emit('cancelar')" />
      <Button type="submit" label="Crear vacante" icon="pi pi-save" :loading="guardando" />
    </div>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useCatalogosStore } from '../../stores/catalogos'
import { rhApi } from '../../api/rh'
import InputText   from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select      from 'primevue/select'
import Textarea    from 'primevue/textarea'
import Button      from 'primevue/button'
import Message     from 'primevue/message'

const emit = defineEmits(['guardado', 'cancelar'])
const cats = useCatalogosStore()

const guardando = ref(false)
const error     = ref('')
const detonantes = [
  { label: 'Baja de empleado', value: 'BAJA_EMPLEADO' },
  { label: 'Creacion de puesto', value: 'CREACION_PUESTO' },
  { label: 'Nueva posicion', value: 'NUEVA_POSICION' },
]

const form = reactive({
  Titulo:          '',
  ID_Puesto:       null,
  ID_Area:         null,
  ID_Sucursal:     null,
  NumeroPosiciones: 1,
  SalarioMin:      null,
  SalarioMax:      null,
  Descripcion:     '',
  Requisitos:      '',
  DetonanteTipo:   null,
  DetonanteEmpleadoNumero: null,
  DetonantePuestoNombre: '',
  DetonanteComentario: '',
})

async function guardar() {
  error.value = ''
  if (!form.Titulo || !form.ID_Puesto || !form.ID_Area) {
    error.value = 'Título, puesto y área son requeridos.'
    return
  }
  guardando.value = true
  try {
    await rhApi.crearVacante(form)
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
.grid-2     { display:grid; grid-template-columns:1fr 1fr; gap:0 1rem; }
.col-2      { grid-column: span 2; }
.field      { display:flex; flex-direction:column; gap:0.4rem; margin-bottom:0.85rem; }
.field label{ font-size:0.875rem; font-weight:500; }
.form-footer{ display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.5rem; }
.mt-1       { margin-top:0.5rem; }
</style>
