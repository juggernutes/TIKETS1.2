<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-play-circle" /> Corridas de Comisión</h1>
      <Button label="Nueva corrida" icon="pi pi-plus" @click="abrirNueva" />
    </div>

    <Card>
      <template #content>
        <DataTable :value="corridas" :loading="cargando" stripedRows size="small"
          paginator :rows="20" :totalRecords="total" lazy @page="onPage">
          <Column field="ID_Corrida" header="#" style="width:60px" />
          <Column header="Semana">
            <template #body="{ data }">
              {{ data.semana ? `Sem ${data.semana.Semana}/${data.semana.Anio}` : '—' }}
            </template>
          </Column>
          <Column field="Estatus" header="Estatus" style="width:130px">
            <template #body="{ data }">
              <Tag :value="data.Estatus" :severity="severidad(data.Estatus)" />
            </template>
          </Column>
          <Column header="Creada">
            <template #body="{ data }">{{ formatFecha(data.FechaCreacion) }}</template>
          </Column>
          <Column header="Acciones" style="width:220px">
            <template #body="{ data }">
              <div class="btn-group">
                <Button v-if="data.Estatus === 'BORRADOR' || data.Estatus === 'EN_PROCESO'"
                  label="Calcular" icon="pi pi-calculator" size="small" severity="info"
                  :loading="calculando === data.ID_Corrida"
                  @click="confirmarCalculo(data)" />
                <Button v-if="data.Estatus !== 'PAGADO' && data.Estatus !== 'CANCELADO'"
                  icon="pi pi-pencil" text rounded size="small"
                  v-tooltip="'Cambiar estatus'"
                  @click="abrirEstatus(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog nueva corrida -->
    <Dialog v-model:visible="dlgNueva" header="Nueva corrida" modal style="width:400px">
      <div class="field">
        <label>Semana</label>
        <Select v-model="form.ID_Semana" :options="cats.semanas"
          optionLabel="label" optionValue="ID_Semana"
          placeholder="Selecciona semana..." class="w-full" />
      </div>
      <div class="field">
        <label>Observaciones</label>
        <Textarea v-model="form.Observaciones" rows="2" class="w-full" />
      </div>
      <template #footer>
        <Button label="Cancelar" text @click="dlgNueva = false" />
        <Button label="Crear" icon="pi pi-check" :loading="guardando" @click="crear" />
      </template>
    </Dialog>

    <!-- Dialog cambiar estatus -->
    <Dialog v-model:visible="dlgEstatus" header="Cambiar estatus" modal style="width:360px">
      <div class="field">
        <label>Nuevo estatus</label>
        <Select v-model="nuevoEstatus" :options="ESTATUS_OPT"
          optionLabel="label" optionValue="value"
          placeholder="Selecciona..." class="w-full" />
      </div>
      <template #footer>
        <Button label="Cancelar" text @click="dlgEstatus = false" />
        <Button label="Actualizar" icon="pi pi-check" :loading="guardando"
          @click="cambiarEstatus" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useCatalogosStore } from '../../stores/catalogos'
import { comApi } from '../../api/com'
import { formatFecha } from '../../utils/formato'
import Card      from 'primevue/card'
import Button    from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column    from 'primevue/column'
import Tag       from 'primevue/tag'
import Dialog    from 'primevue/dialog'
import Select    from 'primevue/select'
import Textarea  from 'primevue/textarea'

const toast   = useToast()
const confirm = useConfirm()
const cats    = useCatalogosStore()

const corridas    = ref([])
const total       = ref(0)
const cargando    = ref(false)
const calculando  = ref(null)
const guardando   = ref(false)
const pagina      = ref(1)
const dlgNueva    = ref(false)
const dlgEstatus  = ref(false)
const seleccionado = ref(null)
const nuevoEstatus = ref(null)

const form = reactive({ ID_Semana: null, Observaciones: '' })

const ESTATUS_OPT = [
  { label: 'Borrador',    value: 'BORRADOR' },
  { label: 'En proceso',  value: 'EN_PROCESO' },
  { label: 'Calculado',   value: 'CALCULADO' },
  { label: 'Aprobado',    value: 'APROBADO' },
  { label: 'Pagado',      value: 'PAGADO' },
  { label: 'Cancelado',   value: 'CANCELADO' },
]

async function cargar() {
  cargando.value = true
  try {
    const { data } = await comApi.listarCorridas({ page: pagina.value, per_page: 20 })
    corridas.value = data.data ?? data
    total.value    = data.total ?? corridas.value.length
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar corridas', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function onPage(e) { pagina.value = e.page + 1; cargar() }

function abrirNueva() {
  form.ID_Semana    = null
  form.Observaciones = ''
  dlgNueva.value   = true
}

async function crear() {
  if (!form.ID_Semana) {
    toast.add({ severity: 'warn', summary: 'Selecciona una semana', life: 2500 })
    return
  }
  guardando.value = true
  try {
    await comApi.crearCorrida({ ...form })
    toast.add({ severity: 'success', summary: 'Corrida creada', life: 2500 })
    dlgNueva.value = false
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error al crear', life: 4000 })
  } finally {
    guardando.value = false
  }
}

function confirmarCalculo(corrida) {
  confirm.require({
    message: `¿Ejecutar el motor de cálculo para la corrida #${corrida.ID_Corrida}?`,
    header:  'Calcular corrida',
    icon:    'pi pi-calculator',
    accept: () => ejecutarCalculo(corrida),
  })
}

async function ejecutarCalculo(corrida) {
  calculando.value = corrida.ID_Corrida
  try {
    const { data } = await comApi.calcularCorrida(corrida.ID_Corrida, {})
    toast.add({
      severity: 'success',
      summary:  `Cálculo completado`,
      detail:   `${data.procesados} registros procesados.${data.errores?.length ? ` ${data.errores.length} errores.` : ''}`,
      life: 5000,
    })
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error en cálculo', life: 5000 })
  } finally {
    calculando.value = null
  }
}

function abrirEstatus(corrida) {
  seleccionado.value = corrida
  nuevoEstatus.value = corrida.Estatus
  dlgEstatus.value   = true
}

async function cambiarEstatus() {
  guardando.value = true
  try {
    await comApi.cambiarEstatusCorrida(seleccionado.value.ID_Corrida, { Estatus: nuevoEstatus.value })
    toast.add({ severity: 'success', summary: 'Estatus actualizado', life: 2500 })
    dlgEstatus.value = false
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error', life: 4000 })
  } finally {
    guardando.value = false
  }
}

function severidad(e) {
  const m = { BORRADOR:'secondary', EN_PROCESO:'info', CALCULADO:'warn',
              APROBADO:'success', PAGADO:'success', CANCELADO:'danger' }
  return m[e] ?? 'secondary'
}

onMounted(cargar)
</script>

<style scoped>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.page-title  { margin:0; font-size:1.4rem; display:flex; align-items:center; gap:0.5rem; }
.field       { display:flex; flex-direction:column; gap:0.35rem; margin-bottom:0.75rem; }
.field label { font-size:0.875rem; font-weight:500; }
.w-full      { width:100%; }
.btn-group   { display:flex; gap:0.4rem; align-items:center; }
</style>
