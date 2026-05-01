<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-users" /> Asignación Empleado-Ruta</h1>
      <Button label="Asignar empleado" icon="pi pi-plus" @click="abrirNueva" />
    </div>

    <!-- Filtros -->
    <Card class="mb-3">
      <template #content>
        <div class="filtros-row">
          <Select v-model="filtros.id_semana" :options="cats.semanas"
            optionLabel="label" optionValue="ID_Semana"
            placeholder="Semana" showClear style="min-width:180px" />
          <Button label="Buscar" icon="pi pi-search" @click="cargar" />
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable :value="bases" :loading="cargando" stripedRows size="small"
          paginator :rows="30" :totalRecords="total" lazy @page="onPage">
          <Column header="Empleado #">
            <template #body="{ data }">{{ data.Numero_Empleado }}</template>
          </Column>
          <Column header="Nombre">
            <template #body="{ data }">{{ data.empleado?.Nombre ?? '—' }}</template>
          </Column>
          <Column header="Semana">
            <template #body="{ data }">
              {{ data.semana ? `Sem ${data.semana.Semana}/${data.semana.Anio}` : '—' }}
            </template>
          </Column>
          <Column header="Unidad" style="width:100px">
            <template #body="{ data }">{{ data.unidad?.Nombre ?? data.IdUnidad }}</template>
          </Column>
          <Column style="width:60px">
            <template #body="{ data }">
              <Button icon="pi pi-trash" text rounded size="small" severity="danger"
                @click="confirmarEliminar(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog nueva asignación -->
    <Dialog v-model:visible="dlg" header="Asignar empleado a ruta" modal style="width:480px">
      <div class="form-grid">
        <div class="field span-2">
          <label>Semana</label>
          <Select v-model="form.ID_Semana" :options="cats.semanas"
            optionLabel="label" optionValue="ID_Semana"
            placeholder="Selecciona semana..." class="w-full" />
        </div>
        <div class="field">
          <label>Número de empleado *</label>
          <InputNumber v-model="form.Numero_Empleado" class="w-full" :useGrouping="false" />
        </div>
        <div class="field">
          <label>ID Unidad operacional *</label>
          <InputNumber v-model="form.IdUnidad" class="w-full" :useGrouping="false" />
        </div>
        <div class="field span-2">
          <label>Observaciones</label>
          <InputText v-model="form.Observaciones" class="w-full" placeholder="(opcional)" />
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
import { useToast }   from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useCatalogosStore } from '../../stores/catalogos'
import { comApi }  from '../../api/com'
import Card        from 'primevue/card'
import Button      from 'primevue/button'
import DataTable   from 'primevue/datatable'
import Column      from 'primevue/column'
import Dialog      from 'primevue/dialog'
import Select      from 'primevue/select'
import InputText   from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'

const toast   = useToast()
const confirm = useConfirm()
const cats    = useCatalogosStore()

const bases    = ref([])
const total    = ref(0)
const cargando = ref(false)
const guardando = ref(false)
const pagina   = ref(1)
const dlg      = ref(false)

const filtros = reactive({ id_semana: null })

const form = reactive({
  ID_Semana: null, IdUnidad: null,
  Numero_Empleado: null, Observaciones: '',
})


async function cargar() {
  cargando.value = true
  try {
    const params = {
      page: pagina.value, per_page: 30,
      ...Object.fromEntries(Object.entries(filtros).filter(([, v]) => v != null)),
    }
    const { data } = await comApi.listarBaseEmpleados(params)
    bases.value = data.data ?? data
    total.value = data.total ?? bases.value.length
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar asignaciones', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function onPage(e) { pagina.value = e.page + 1; cargar() }

function abrirNueva() {
  Object.assign(form, {
    ID_Semana: null, IdUnidad: null,
    Numero_Empleado: null, Observaciones: '',
  })
  dlg.value = true
}

async function guardar() {
  guardando.value = true
  try {
    await comApi.crearBase({ ...form })
    toast.add({ severity: 'success', summary: 'Asignación guardada', life: 2500 })
    dlg.value = false
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error al guardar', life: 4000 })
  } finally {
    guardando.value = false
  }
}

function confirmarEliminar(base) {
  confirm.require({
    message: `¿Eliminar la asignación del empleado #${base.Numero_Empleado}?`,
    header:  'Confirmar eliminación',
    icon:    'pi pi-trash',
    acceptClass: 'p-button-danger',
    accept: () => eliminar(base),
  })
}

async function eliminar(base) {
  try {
    await comApi.eliminarBase(base.ID_Comision)
    toast.add({ severity: 'success', summary: 'Asignación eliminada', life: 2500 })
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error', life: 4000 })
  }
}

onMounted(cargar)
</script>

<style scoped>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.page-title  { margin:0; font-size:1.4rem; display:flex; align-items:center; gap:0.5rem; }
.mb-3        { margin-bottom:1rem; }
.filtros-row { display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; }
.form-grid   { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; }
.field       { display:flex; flex-direction:column; gap:0.35rem; }
.field label { font-size:0.875rem; font-weight:500; }
.span-2      { grid-column:span 2; }
.w-full      { width:100%; }
</style>
