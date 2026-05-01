<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-clock" /> Acuerdos de Nivel de Servicio (SLA)</h1>
      <Button label="Nuevo SLA" icon="pi pi-plus" @click="abrirNuevo" />
    </div>

    <Card>
      <template #content>
        <DataTable :value="slas" :loading="cargando" stripedRows size="small">
          <Column field="Nombre"        header="Nombre"       />
          <Column field="Prioridad"     header="Prioridad">
            <template #body="{ data }">
              <Tag :value="data.Prioridad" :severity="severidadPrioridad(data.Prioridad)" />
            </template>
          </Column>
          <Column field="NombreArea"    header="Área"         >
            <template #body="{ data }">{{ data.NombreArea ?? 'General' }}</template>
          </Column>
          <Column field="HorasRespuesta"  header="Respuesta (h)"  style="width:130px;text-align:center" />
          <Column field="HorasResolucion" header="Resolución (h)" style="width:130px;text-align:center" />
          <Column field="Activo"        header="Activo"       style="width:80px">
            <template #body="{ data }">
              <Tag :value="data.Activo ? 'Sí' : 'No'" :severity="data.Activo ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column header="" style="width:90px">
            <template #body="{ data }">
              <div class="row-gap">
                <Button icon="pi pi-pencil" text rounded size="small"
                  @click="abrirEditar(data)" v-tooltip="'Editar'" />
                <Button icon="pi pi-trash" text rounded size="small" severity="danger"
                  @click="confirmarEliminar(data)" v-tooltip="'Eliminar'" />
              </div>
            </template>
          </Column>
          <template #empty>
            <p class="sin-items">Sin SLAs registrados.</p>
          </template>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog crear / editar -->
    <Dialog v-model:visible="dlgVisible" :header="editando ? 'Editar SLA' : 'Nuevo SLA'"
      modal style="width:460px">
      <form @submit.prevent="guardar" class="sla-form">
        <div class="field">
          <label>Nombre *</label>
          <InputText v-model="form.Nombre" class="w-full" maxlength="100" />
        </div>
        <div class="field">
          <label>Prioridad *</label>
          <Select v-model="form.Prioridad" :options="prioridadOpciones"
            optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div class="field">
          <label>Área (opcional)</label>
          <Select v-model="form.ID_Area" :options="cats.areas"
            optionLabel="Nombre" optionValue="ID_Area"
            class="w-full" placeholder="General (todas las áreas)" showClear />
        </div>
        <div class="dos-col">
          <div class="field">
            <label>Horas de respuesta *</label>
            <InputNumber v-model="form.HorasRespuesta" :min="1" :useGrouping="false" class="w-full" />
          </div>
          <div class="field">
            <label>Horas de resolución *</label>
            <InputNumber v-model="form.HorasResolucion" :min="1" :useGrouping="false" class="w-full" />
          </div>
        </div>
        <div class="field-check">
          <Checkbox v-model="form.Activo" :binary="true" inputId="activo" />
          <label for="activo">Activo</label>
        </div>

        <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

        <div class="form-footer">
          <Button type="button" label="Cancelar" text @click="dlgVisible = false" />
          <Button type="submit" :label="editando ? 'Guardar cambios' : 'Crear'" icon="pi pi-save" :loading="guardando" />
        </div>
      </form>
    </Dialog>

    <!-- Confirm eliminar -->
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast }   from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useCatalogosStore } from '../../stores/catalogos'
import { hdApi } from '../../api/hd'

import Card         from 'primevue/card'
import Button       from 'primevue/button'
import DataTable    from 'primevue/datatable'
import Column       from 'primevue/column'
import Tag          from 'primevue/tag'
import Dialog       from 'primevue/dialog'
import InputText    from 'primevue/inputtext'
import InputNumber  from 'primevue/inputnumber'
import Select       from 'primevue/select'
import Checkbox     from 'primevue/checkbox'
import Message      from 'primevue/message'

const toast   = useToast()
const confirm = useConfirm()
const cats    = useCatalogosStore()

const slas      = ref([])
const cargando  = ref(false)
const dlgVisible= ref(false)
const editando  = ref(false)
const guardando = ref(false)
const error     = ref('')
const editId    = ref(null)

const prioridadOpciones = [
  { label: 'Crítica', value: 'CRITICA' },
  { label: 'Alta',    value: 'ALTA' },
  { label: 'Media',   value: 'MEDIA' },
  { label: 'Baja',    value: 'BAJA' },
]

const formVacio = () => ({
  Nombre: '', Prioridad: 'MEDIA', ID_Area: null,
  HorasRespuesta: 8, HorasResolucion: 24, Activo: true,
})
const form = reactive(formVacio())

async function cargar() {
  cargando.value = true
  try {
    const { data } = await hdApi.listarSla()
    slas.value = data
  } finally {
    cargando.value = false
  }
}

function abrirNuevo() {
  editando.value = false
  editId.value   = null
  error.value    = ''
  Object.assign(form, formVacio())
  dlgVisible.value = true
}

function abrirEditar(sla) {
  editando.value = true
  editId.value   = sla.ID_SLA
  error.value    = ''
  Object.assign(form, {
    Nombre:          sla.Nombre,
    Prioridad:       sla.Prioridad,
    ID_Area:         sla.ID_Area ?? null,
    HorasRespuesta:  sla.HorasRespuesta,
    HorasResolucion: sla.HorasResolucion,
    Activo:          !!sla.Activo,
  })
  dlgVisible.value = true
}

async function guardar() {
  error.value = ''
  if (form.HorasResolucion < form.HorasRespuesta) {
    error.value = 'Las horas de resolución deben ser ≥ horas de respuesta.'
    return
  }
  guardando.value = true
  try {
    if (editando.value) {
      await hdApi.editarSla(editId.value, form)
      toast.add({ severity: 'success', summary: 'SLA actualizado', life: 2500 })
    } else {
      await hdApi.crearSla(form)
      toast.add({ severity: 'success', summary: 'SLA creado', life: 2500 })
    }
    dlgVisible.value = false
    await cargar()
  } catch (e) {
    const msgs = e.response?.data?.errors
    error.value = msgs
      ? Object.values(msgs).flat().join(' ')
      : e.response?.data?.message ?? 'Error al guardar.'
  } finally {
    guardando.value = false
  }
}

function confirmarEliminar(sla) {
  confirm.require({
    message: `¿Eliminar el SLA "${sla.Nombre}"?`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptSeverity: 'danger',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    accept: async () => {
      try {
        await hdApi.eliminarSla(sla.ID_SLA)
        toast.add({ severity: 'success', summary: 'SLA eliminado', life: 2500 })
        await cargar()
      } catch (e) {
        toast.add({
          severity: 'warn',
          summary: e.response?.data?.message ?? 'No se pudo eliminar',
          life: 3500,
        })
        await cargar()
      }
    },
  })
}

function severidadPrioridad(p) {
  return { CRITICA: 'danger', ALTA: 'warn', MEDIA: 'info', BAJA: 'secondary' }[p] ?? 'secondary'
}

onMounted(cargar)
</script>

<style scoped>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.page-title  { margin:0; font-size:1.4rem; display:flex; align-items:center; gap:0.5rem; }
.sin-items   { text-align:center; color:var(--p-text-muted-color); padding:1rem 0; }
.row-gap     { display:flex; gap:0.25rem; }

.sla-form    { display:flex; flex-direction:column; gap:0.1rem; }
.field       { display:flex; flex-direction:column; gap:0.4rem; margin-bottom:0.9rem; }
.field label { font-size:0.875rem; font-weight:500; }
.dos-col     { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; }
.field-check { display:flex; align-items:center; gap:0.5rem; margin-bottom:0.9rem; font-size:0.875rem; cursor:pointer; }
.form-footer { display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.5rem; }
.w-full      { width:100%; }
</style>
