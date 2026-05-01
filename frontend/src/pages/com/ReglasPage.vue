<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-sliders-h" /> Reglas de Comisión</h1>
      <Button label="Nueva regla" icon="pi pi-plus" @click="abrirNueva" />
    </div>

    <!-- Filtros -->
    <Card class="mb-3">
      <template #content>
        <div class="filtros-row">
          <Select v-model="filtros.id_indicador" :options="cats.indicadores"
            optionLabel="Nombre" optionValue="ID_Indicador"
            placeholder="Indicador" showClear style="min-width:160px" />
          <Select v-model="filtros.tce"
            :options="TCE_OPT" optionLabel="label" optionValue="value"
            placeholder="TCE" showClear />
          <Select v-model="filtros.canal"
            :options="[{label:'Tradicional',value:'TRADICIONAL'},{label:'Moderno',value:'MODERNO'}]"
            optionLabel="label" optionValue="value"
            placeholder="Canal" showClear />
          <Button label="Buscar" icon="pi pi-search" @click="cargar" />
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable :value="reglas" :loading="cargando" stripedRows size="small"
          paginator :rows="30">
          <Column header="Indicador">
            <template #body="{ data }">{{ data.indicador?.Nombre ?? data.Clave_Indicador }}</template>
          </Column>
          <Column header="Sub-indicador">
            <template #body="{ data }">{{ data.sub_indicador?.Clave ?? '—' }}</template>
          </Column>
          <Column field="TCE"    header="TCE"    style="width:70px" />
          <Column field="Puesto" header="Puesto" style="width:100px" />
          <Column field="Canal"  header="Canal"  style="width:110px" />
          <Column header="% Mín" style="width:90px;text-align:right">
            <template #body="{ data }">{{ (data.PorcentajeMinimo * 100).toFixed(0) }}%</template>
          </Column>
          <Column header="% Máx" style="width:90px;text-align:right">
            <template #body="{ data }">{{ (data.PorcentajeMaximo * 100).toFixed(0) }}%</template>
          </Column>
          <Column header="Monto" style="width:110px;text-align:right">
            <template #body="{ data }">{{ data.Monto ? formatMoneda(data.Monto) : '—' }}</template>
          </Column>
          <Column header="Factor" style="width:90px;text-align:right">
            <template #body="{ data }">{{ data.Factor ?? '—' }}</template>
          </Column>
          <Column field="Activo" header="Activo" style="width:70px">
            <template #body="{ data }">
              <Tag :value="data.Activo ? 'Sí' : 'No'" :severity="data.Activo ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column style="width:80px">
            <template #body="{ data }">
              <div class="btn-group">
                <Button icon="pi pi-pencil" text rounded size="small"
                  @click="abrirEditar(data)" />
                <Button icon="pi pi-trash" text rounded size="small" severity="danger"
                  @click="confirmarEliminar(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog nueva/editar regla -->
    <Dialog v-model:visible="dlg" :header="editando ? 'Editar regla' : 'Nueva regla'"
      modal style="width:540px">
      <div class="form-grid">
        <div class="field">
          <label>Indicador</label>
          <Select v-model="form.ID_Indicador" :options="cats.indicadores"
            optionLabel="Nombre" optionValue="ID_Indicador"
            placeholder="Selecciona..." class="w-full" />
        </div>
        <div class="field">
          <label>TCE (opcional)</label>
          <Select v-model="form.TCE" :options="TCE_OPT"
            optionLabel="label" optionValue="value"
            placeholder="Todos" showClear class="w-full" />
        </div>
        <div class="field">
          <label>Canal (opcional)</label>
          <Select v-model="form.Canal"
            :options="[{label:'Tradicional',value:'TRADICIONAL'},{label:'Moderno',value:'MODERNO'}]"
            optionLabel="label" optionValue="value"
            placeholder="Todos" showClear class="w-full" />
        </div>
        <div class="field">
          <label>Puesto (opcional)</label>
          <Select v-model="form.Puesto"
            :options="[{label:'Vendedor',value:'VENDEDOR'},{label:'Supervisor',value:'SUPERVISOR'}]"
            optionLabel="label" optionValue="value"
            placeholder="Todos" showClear class="w-full" />
        </div>
        <div class="field">
          <label>% Mínimo (0-1)</label>
          <InputNumber v-model="form.PorcentajeMinimo" :minFractionDigits="4" class="w-full" />
        </div>
        <div class="field">
          <label>% Máximo (0-1)</label>
          <InputNumber v-model="form.PorcentajeMaximo" :minFractionDigits="4" class="w-full" />
        </div>
        <div class="field">
          <label>Monto $ (para reglas de pago)</label>
          <InputNumber v-model="form.Monto" :minFractionDigits="2" class="w-full" />
        </div>
        <div class="field">
          <label>Factor (para devoluciones)</label>
          <InputNumber v-model="form.Factor" :minFractionDigits="4" class="w-full" />
        </div>
        <div class="field check-field span-2">
          <Checkbox v-model="form.Activo" binary inputId="rActivo" />
          <label for="rActivo">Activa</label>
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
import { formatMoneda } from '../../utils/formato'
import Card        from 'primevue/card'
import Button      from 'primevue/button'
import DataTable   from 'primevue/datatable'
import Column      from 'primevue/column'
import Tag         from 'primevue/tag'
import Dialog      from 'primevue/dialog'
import Select      from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import Checkbox    from 'primevue/checkbox'

const toast   = useToast()
const confirm = useConfirm()
const cats    = useCatalogosStore()

const reglas   = ref([])
const cargando = ref(false)
const guardando = ref(false)
const dlg       = ref(false)
const editando  = ref(false)
const reglaId   = ref(null)

const filtros = reactive({ id_indicador: null, tce: null, canal: null })

const form = reactive({
  ID_Indicador: null, ID_SubIndicador: null,
  TCE: null, Canal: null, Puesto: null,
  PorcentajeMinimo: 0, PorcentajeMaximo: 1,
  Monto: null, Factor: null, Activo: true,
})

const TCE_OPT = [
  { label: 'VT', value: 'VT' }, { label: 'VM', value: 'VM' },
  { label: 'XT', value: 'XT' }, { label: 'XM', value: 'XM' },
]

async function cargar() {
  cargando.value = true
  try {
    const params = Object.fromEntries(
      Object.entries(filtros).filter(([, v]) => v != null)
    )
    const { data } = await comApi.listarReglas({ ...params, per_page: 200 })
    reglas.value = data.data ?? data
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar reglas', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function abrirNueva() {
  editando.value = false
  reglaId.value  = null
  Object.assign(form, {
    ID_Indicador: null, ID_SubIndicador: null,
    TCE: null, Canal: null, Puesto: null,
    PorcentajeMinimo: 0, PorcentajeMaximo: 1,
    Monto: null, Factor: null, Activo: true,
  })
  dlg.value = true
}

function abrirEditar(regla) {
  editando.value = true
  reglaId.value  = regla.ID_Regla
  Object.assign(form, {
    ID_Indicador:     regla.ID_Indicador,
    ID_SubIndicador:  regla.ID_SubIndicador ?? null,
    TCE:              regla.TCE   ?? null,
    Canal:            regla.Canal ?? null,
    Puesto:           regla.Puesto ?? null,
    PorcentajeMinimo: Number(regla.PorcentajeMinimo),
    PorcentajeMaximo: Number(regla.PorcentajeMaximo),
    Monto:            regla.Monto  ? Number(regla.Monto)  : null,
    Factor:           regla.Factor ? Number(regla.Factor) : null,
    Activo:           !!regla.Activo,
  })
  dlg.value = true
}

async function guardar() {
  guardando.value = true
  try {
    if (editando.value) {
      await comApi.actualizarRegla(reglaId.value, { ...form })
    } else {
      await comApi.crearRegla({ ...form })
    }
    toast.add({ severity: 'success', summary: 'Regla guardada', life: 2500 })
    dlg.value = false
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error al guardar', life: 4000 })
  } finally {
    guardando.value = false
  }
}

function confirmarEliminar(regla) {
  confirm.require({
    message: '¿Eliminar esta regla de comisión?',
    header:  'Confirmar eliminación',
    icon:    'pi pi-trash',
    acceptClass: 'p-button-danger',
    accept: () => eliminar(regla),
  })
}

async function eliminar(regla) {
  try {
    await comApi.eliminarRegla(regla.ID_Regla)
    toast.add({ severity: 'success', summary: 'Regla eliminada', life: 2500 })
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
.check-field { flex-direction:row; align-items:center; gap:0.5rem; }
.span-2      { grid-column:span 2; }
.w-full      { width:100%; }
.btn-group   { display:flex; gap:0.3rem; }
</style>
