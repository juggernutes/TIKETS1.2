<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-flag" /> Metas Mensuales</h1>
      <Button label="Nueva portada" icon="pi pi-plus" @click="abrirNueva" />
    </div>

    <!-- Lista de portadas -->
    <Card class="mb-3">
      <template #content>
        <DataTable :value="portadas" :loading="cargando" stripedRows size="small"
          paginator :rows="20">
          <Column field="ID_MetaMes" header="#" style="width:60px" />
          <Column header="Mes">
            <template #body="{ data }">{{ data.Nombre }} ({{ data.Mes }}/{{ data.Anio }})</template>
          </Column>
          <Column field="Anio" header="Año" style="width:80px" />
          <Column field="DiasHabiles" header="Días hábiles" style="width:120px" />
          <Column header="Sucursal">
            <template #body="{ data }">{{ data.sucursal?.Nombre ?? '—' }}</template>
          </Column>
          <Column header="Acciones" style="width:200px">
            <template #body="{ data }">
              <div class="btn-group">
                <Button icon="pi pi-list" text rounded size="small"
                  v-tooltip="'Ver metas'" @click="abrirDetalle(data)" />
                <Button icon="pi pi-calendar-plus" text rounded size="small"
                  v-tooltip="'Calcular semanales'" @click="calcularSemanales(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog nueva portada -->
    <Dialog v-model:visible="dlgNueva" header="Nueva portada mensual" modal style="width:440px">
      <div class="form-grid">
        <div class="field span-2">
          <label>Nombre del mes *</label>
          <InputText v-model="formP.Nombre" placeholder="Ej: ENERO" maxlength="15" class="w-full" />
        </div>
        <div class="field">
          <label>Mes (1-12) *</label>
          <InputNumber v-model="formP.Mes" :min="1" :max="12" class="w-full" />
        </div>
        <div class="field">
          <label>Año *</label>
          <InputNumber v-model="formP.Anio" :min="2020" :max="2099" class="w-full" />
        </div>
        <div class="field span-2">
          <label>Días hábiles *</label>
          <InputNumber v-model="formP.DiasHabiles" :min="1" :max="31" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" text @click="dlgNueva = false" />
        <Button label="Crear" icon="pi pi-check" :loading="guardando" @click="crearPortada" />
      </template>
    </Dialog>

    <!-- Panel detalle de portada + metas -->
    <Dialog v-model:visible="dlgDetalle"
      :header="`Metas — ${portadaSel?.NombreMes ?? ''}`"
      modal style="width:760px">

      <div class="metas-acciones mb-2">
        <Button label="Agregar meta" icon="pi pi-plus" size="small" @click="agregarFila" />
        <Button label="Guardar todas" icon="pi pi-save" size="small" severity="success"
          :loading="guardandoMetas" @click="guardarMetas" />
      </div>

      <DataTable :value="filasMetas" editMode="cell" size="small"
        @cell-edit-complete="onCellEdit">
        <Column field="IdUnidad" header="ID Unidad" style="width:110px">
          <template #editor="{ data, field }">
            <InputNumber v-model="data[field]" class="w-full" :useGrouping="false" />
          </template>
        </Column>
        <Column field="IdLineaArticulo" header="ID Línea" style="width:110px">
          <template #editor="{ data, field }">
            <InputNumber v-model="data[field]" class="w-full" :useGrouping="false" />
          </template>
        </Column>
        <Column field="Meta" header="Meta" style="width:120px">
          <template #editor="{ data, field }">
            <InputNumber v-model="data[field]" class="w-full" />
          </template>
        </Column>
        <Column field="Porcentaje" header="%" style="width:90px">
          <template #editor="{ data, field }">
            <InputNumber v-model="data[field]" :minFractionDigits="2" class="w-full" />
          </template>
        </Column>
        <Column field="Mezcla" header="Mezcla" style="width:90px">
          <template #editor="{ data, field }">
            <InputNumber v-model="data[field]" :minFractionDigits="2" class="w-full" />
          </template>
        </Column>
        <Column style="width:50px">
          <template #body="{ index }">
            <Button icon="pi pi-trash" text rounded size="small" severity="danger"
              @click="filasMetas.splice(index, 1)" />
          </template>
        </Column>
      </DataTable>

      <template #footer>
        <Button label="Cerrar" text @click="dlgDetalle = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { comApi } from '../../api/com'
import Card        from 'primevue/card'
import Button      from 'primevue/button'
import DataTable   from 'primevue/datatable'
import Column      from 'primevue/column'
import Dialog      from 'primevue/dialog'
import Select      from 'primevue/select'
import InputText   from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'

const toast = useToast()
const cats  = useCatalogosStore()

const portadas      = ref([])
const cargando      = ref(false)
const guardando     = ref(false)
const guardandoMetas = ref(false)
const dlgNueva      = ref(false)
const dlgDetalle    = ref(false)
const portadaSel    = ref(null)
const filasMetas    = ref([])

const formP = reactive({
  Nombre: '', Mes: new Date().getMonth() + 1,
  Anio: new Date().getFullYear(), DiasHabiles: 22,
})

async function cargar() {
  cargando.value = true
  try {
    const { data } = await comApi.listarPortadas({ per_page: 100 })
    portadas.value = data.data ?? data
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar portadas', life: 3000 })
  } finally {
    cargando.value = false
  }
}

async function crearPortada() {
  guardando.value = true
  try {
    await comApi.crearPortada({ ...formP })
    toast.add({ severity: 'success', summary: 'Portada creada', life: 2500 })
    dlgNueva.value = false
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error', life: 4000 })
  } finally {
    guardando.value = false
  }
}

async function abrirDetalle(portada) {
  portadaSel.value = portada
  try {
    const { data } = await comApi.verPortada(portada.ID_Portada)
    filasMetas.value = data.contenido ?? []
  } catch {
    filasMetas.value = []
  }
  dlgDetalle.value = true
}

function agregarFila() {
  filasMetas.value.push({
    IdUnidad: null, IdLineaArticulo: null, Meta: 0, Porcentaje: 0, Mezcla: 0,
  })
}

function onCellEdit(e) {
  filasMetas.value[e.index][e.field] = e.newValue
}

async function guardarMetas() {
  guardandoMetas.value = true
  try {
    await comApi.storeMetas(portadaSel.value.ID_Portada, { metas: filasMetas.value })
    toast.add({ severity: 'success', summary: 'Metas guardadas', life: 2500 })
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error', life: 4000 })
  } finally {
    guardandoMetas.value = false
  }
}

async function calcularSemanales(portada) {
  try {
    await comApi.calcularMetasSemanales(portada.ID_Portada)
    toast.add({ severity: 'success', summary: 'Metas semanales calculadas', life: 2500 })
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error', life: 4000 })
  }
}

onMounted(cargar)
</script>

<style scoped>
.page-header  { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.page-title   { margin:0; font-size:1.4rem; display:flex; align-items:center; gap:0.5rem; }
.mb-3         { margin-bottom:1rem; }
.mb-2         { margin-bottom:0.75rem; }
.form-grid    { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; }
.field        { display:flex; flex-direction:column; gap:0.35rem; }
.field label  { font-size:0.875rem; font-weight:500; }
.span-2       { grid-column:span 2; }
.w-full       { width:100%; }
.btn-group    { display:flex; gap:0.3rem; }
.metas-acciones { display:flex; gap:0.5rem; }
</style>
