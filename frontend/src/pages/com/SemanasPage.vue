<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-calendar" /> Semanas</h1>
      <Button label="Nueva semana" icon="pi pi-plus" @click="abrirNueva" />
    </div>

    <Card>
      <template #content>
        <DataTable :value="semanas" :loading="cargando" stripedRows size="small"
          paginator :rows="30">
          <Column field="ID_Semana" header="#" style="width:60px" />
          <Column header="Semana / Año">
            <template #body="{ data }">Sem {{ data.Semana }} / {{ data.Anio }}</template>
          </Column>
          <Column header="Inicio">
            <template #body="{ data }">{{ formatFechaCorta(data.FechaInicio) }}</template>
          </Column>
          <Column header="Fin">
            <template #body="{ data }">{{ formatFechaCorta(data.FechaFin) }}</template>
          </Column>
          <Column field="DiasHabiles" header="Días hábiles" style="width:120px" />
          <Column field="Activo" header="Activo" style="width:80px">
            <template #body="{ data }">
              <Tag :value="data.Activo ? 'Sí' : 'No'" :severity="data.Activo ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column style="width:80px">
            <template #body="{ data }">
              <Button icon="pi pi-pencil" text rounded size="small"
                @click="abrirEditar(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="dlg" :header="editando ? 'Editar semana' : 'Nueva semana'"
      modal style="width:420px">
      <div class="form-grid">
        <div class="field">
          <label>Semana # *</label>
          <InputNumber v-model="form.Semana" :min="1" :max="53" class="w-full" />
        </div>
        <div class="field">
          <label>Año *</label>
          <InputNumber v-model="form.Anio" :min="2020" :max="2099" class="w-full" />
        </div>
        <div class="field">
          <label>Fecha inicio *</label>
          <DatePicker v-model="form.FechaInicio" dateFormat="dd/mm/yy" class="w-full" />
        </div>
        <div class="field">
          <label>Fecha fin *</label>
          <DatePicker v-model="form.FechaFin" dateFormat="dd/mm/yy" class="w-full" />
        </div>
        <div class="field span-2">
          <label>Meta mes inicio (portada) *</label>
          <Select v-model="form.ID_MetaMesInicio" :options="portadas"
            optionLabel="label" optionValue="ID_MetaMes"
            placeholder="Selecciona portada mensual..." class="w-full" />
        </div>
        <div class="field span-2">
          <label>Meta mes final (portada) — si la semana cruza meses</label>
          <Select v-model="form.ID_MetaMesFinal" :options="portadas"
            optionLabel="label" optionValue="ID_MetaMes"
            placeholder="Sin cruce de mes" showClear class="w-full" />
        </div>
        <div class="field">
          <label>Días en mes inicio *</label>
          <InputNumber v-model="form.DiasMesInicio" :min="1" :max="7" class="w-full" />
        </div>
        <div class="field">
          <label>Días en mes final</label>
          <InputNumber v-model="form.DiasMesFinal" :min="0" :max="6" class="w-full" />
        </div>
        <div class="field check-field span-2">
          <Checkbox v-model="form.Activo" binary inputId="activo" />
          <label for="activo">Activa</label>
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" text @click="dlg = false" />
        <Button label="Guardar" icon="pi pi-check" :loading="guardando" @click="guardar" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { comApi }  from '../../api/com'
import Card        from 'primevue/card'
import Button      from 'primevue/button'
import DataTable   from 'primevue/datatable'
import Column      from 'primevue/column'
import Tag         from 'primevue/tag'
import Dialog      from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import DatePicker  from 'primevue/datepicker'
import Checkbox    from 'primevue/checkbox'
import Select      from 'primevue/select'

const toast    = useToast()
const semanas  = ref([])
const portadas = ref([])
const cargando = ref(false)
const guardando = ref(false)
const dlg      = ref(false)
const editando = ref(false)
const semanaId = ref(null)

const form = reactive({
  Semana: null, Anio: null,
  FechaInicio: null, FechaFin: null,
  ID_MetaMesInicio: null, ID_MetaMesFinal: null,
  DiasMesInicio: 5, DiasMesFinal: null,
  Activo: true,
})

function formatFechaCorta(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'numeric' })
}

async function cargar() {
  cargando.value = true
  try {
    const { data } = await comApi.listarSemanas({ per_page: 100 })
    semanas.value = data.data ?? data
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar semanas', life: 3000 })
  } finally {
    cargando.value = false
  }
}

async function cargarPortadas() {
  try {
    const { data } = await comApi.listarPortadas({ per_page: 100 })
    const lista = data.data ?? data
    portadas.value = lista.map(p => ({
      ...p,
      label: `${p.Nombre} (${p.Mes}/${p.Anio})`,
    }))
  } catch { /* no crítico */ }
}

function abrirNueva() {
  editando.value          = false
  semanaId.value          = null
  form.Semana             = null
  form.Anio               = new Date().getFullYear()
  form.FechaInicio        = null
  form.FechaFin           = null
  form.ID_MetaMesInicio   = null
  form.ID_MetaMesFinal    = null
  form.DiasMesInicio      = 5
  form.DiasMesFinal       = null
  form.Activo             = true
  dlg.value               = true
}

function abrirEditar(s) {
  editando.value          = true
  semanaId.value          = s.ID_Semana
  form.Semana             = s.Semana
  form.Anio               = s.Anio
  form.FechaInicio        = s.FechaInicio ? new Date(s.FechaInicio) : null
  form.FechaFin           = s.FechaFin    ? new Date(s.FechaFin)    : null
  form.ID_MetaMesInicio   = s.ID_MetaMesInicio ?? null
  form.ID_MetaMesFinal    = s.ID_MetaMesFinal  ?? null
  form.DiasMesInicio      = s.DiasMesInicio ?? 5
  form.DiasMesFinal       = s.DiasMesFinal  ?? null
  form.Activo             = !!s.Activo
  dlg.value               = true
}

async function guardar() {
  guardando.value = true
  try {
    const payload = {
      ...form,
      FechaInicio: form.FechaInicio ? isoFecha(form.FechaInicio) : null,
      FechaFin:    form.FechaFin    ? isoFecha(form.FechaFin)    : null,
      DiasMesFinal: form.DiasMesFinal ?? 0,
    }
    if (editando.value) {
      await comApi.actualizarSemana(semanaId.value, payload)
    } else {
      await comApi.crearSemana(payload)
    }
    toast.add({ severity: 'success', summary: 'Semana guardada', life: 2500 })
    dlg.value = false
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error al guardar', life: 4000 })
  } finally {
    guardando.value = false
  }
}

function isoFecha(d) {
  return d instanceof Date
    ? d.toISOString().split('T')[0]
    : d
}

onMounted(() => { cargar(); cargarPortadas() })
</script>

<style scoped>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.page-title  { margin:0; font-size:1.4rem; display:flex; align-items:center; gap:0.5rem; }
.form-grid   { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; }
.field       { display:flex; flex-direction:column; gap:0.35rem; }
.field label { font-size:0.875rem; font-weight:500; }
.check-field { flex-direction:row; align-items:center; gap:0.5rem; margin-top:0.5rem; }
.span-2      { grid-column:span 2; }
.w-full      { width:100%; }
</style>
