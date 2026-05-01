<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-wallet" /> Comisiones</h1>
    </div>

    <!-- Filtros -->
    <Card class="mb-3">
      <template #content>
        <div class="filtros-grid">
          <Select v-model="filtros.id_corrida" :options="corridas"
            optionLabel="label" optionValue="value"
            placeholder="Corrida" showClear style="min-width:180px" />
          <Select v-model="filtros.estatus"
            :options="ESTATUS_OPTIONS" optionLabel="label" optionValue="value"
            placeholder="Estatus" showClear style="min-width:160px" />
          <Select v-model="filtros.canal"
            :options="[{label:'Tradicional',value:'TRADICIONAL'},{label:'Moderno',value:'MODERNO'}]"
            optionLabel="label" optionValue="value"
            placeholder="Canal" showClear />
          <Button label="Buscar" icon="pi pi-search" @click="cargar" />
        </div>
      </template>
    </Card>

    <!-- Totales rápidos -->
    <div class="totales-grid mb-3" v-if="tickets.length">
      <Card>
        <template #content>
          <div class="kpi"><span class="kpi-label">Comisión bruta</span><span class="kpi-valor">${{ fmt(totalBruto) }}</span></div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="kpi"><span class="kpi-label">Ajustes</span><span class="kpi-valor">${{ fmt(totalAjuste) }}</span></div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="kpi"><span class="kpi-label">Comisión neta</span><span class="kpi-valor text-primary">${{ fmt(totalNeto) }}</span></div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="kpi"><span class="kpi-label">Vendedores</span><span class="kpi-valor">{{ tickets.length }}</span></div>
        </template>
      </Card>
    </div>

    <!-- Tabla -->
    <Card>
      <template #content>
        <DataTable :value="tickets" :loading="cargando"
          lazy :totalRecords="total"
          paginator :rows="50" @page="onPage"
          stripedRows size="small"
          scrollable scrollHeight="55vh">
          <Column field="Numero_Empleado" header="Empleado #" style="width:110px" />
          <Column header="Nombre">
            <template #body="{ data }">{{ data.empleado?.Nombre ?? '—' }}</template>
          </Column>
          <Column field="Canal"  header="Canal"  style="width:110px" />
          <Column field="Puesto" header="Puesto" style="width:120px" />
          <Column header="Bruta" style="width:110px;text-align:right">
            <template #body="{ data }">${{ fmt(data.MontoBruto) }}</template>
          </Column>
          <Column header="Desc/Agr" style="width:100px;text-align:right">
            <template #body="{ data }">
              <span class="text-red">-${{ fmt(data.TotalDescuentos) }}</span>
              <span v-if="data.TotalAgregados > 0" class="text-green"> +${{ fmt(data.TotalAgregados) }}</span>
            </template>
          </Column>
          <Column header="Neta" style="width:110px;text-align:right;font-weight:700">
            <template #body="{ data }"><b>${{ fmt(data.MontoFinal) }}</b></template>
          </Column>
          <Column header="Estatus" style="width:130px">
            <template #body="{ data }">
              <Tag :value="data.Estatus" :severity="severidad(data.Estatus)" />
            </template>
          </Column>
          <Column header="" style="width:100px">
            <template #body="{ data }">
              <Button v-if="data.Estatus === 'CALCULADO'"
                label="Aprobar" icon="pi pi-check" size="small"
                severity="success" outlined
                @click="confirmarAprobar(data)" />
              <Button v-else
                icon="pi pi-eye" text rounded size="small"
                @click="router.push(`/com/calculos/${data.ID_Calculo}`)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog aprobar -->
    <Dialog v-model:visible="dlgAutorizar" header="Aprobar comisión" modal style="width:420px">
      <p>¿Confirmas la aprobación de la comisión de <b>{{ seleccionado?.base?.empleado?.Nombre }}</b>?</p>
      <p class="monto-resumen">Monto neto: <b>${{ fmt(seleccionado?.MontoFinal) }}</b></p>
      <div class="field mt-2">
        <label>Observaciones (opcional)</label>
        <Textarea v-model="obsAutorizar" rows="2" class="w-full" />
      </div>
      <template #footer>
        <Button label="Cancelar" text @click="dlgAutorizar = false" />
        <Button label="Autorizar" icon="pi pi-check" severity="success"
          :loading="autorizando" @click="ejecutarAutorizar" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast }  from 'primevue/usetoast'
import { useAuthStore } from '../../stores/auth'
import { comApi } from '../../api/com'
import Card       from 'primevue/card'
import Button     from 'primevue/button'
import DataTable  from 'primevue/datatable'
import Column     from 'primevue/column'
import Select     from 'primevue/select'
import Tag        from 'primevue/tag'
import Dialog     from 'primevue/dialog'
import Textarea   from 'primevue/textarea'

const router = useRouter()
const toast  = useToast()
const auth   = useAuthStore()

const tickets      = ref([])
const total        = ref(0)
const cargando     = ref(false)
const corridas     = ref([])
const dlgAutorizar = ref(false)
const seleccionado = ref(null)
const obsAutorizar = ref('')
const autorizando  = ref(false)
const paginaActual = ref(1)

const ESTATUS_OPTIONS = [
  { label: 'Calculado', value: 'CALCULADO' },
  { label: 'Aprobado',  value: 'APROBADO' },
  { label: 'Rechazado', value: 'RECHAZADO' },
  { label: 'Pagado',    value: 'PAGADO' },
  { label: 'Cancelado', value: 'CANCELADO' },
]

const filtros = reactive({ id_corrida: null, estatus: null, canal: null })

const totalBruto  = computed(() => tickets.value.reduce((s, r) => s + Number(r.MontoBruto      ?? 0), 0))
const totalAjuste = computed(() => tickets.value.reduce((s, r) => s + Number(r.TotalDescuentos ?? 0), 0))
const totalNeto   = computed(() => tickets.value.reduce((s, r) => s + Number(r.MontoFinal      ?? 0), 0))

async function cargarCorridas() {
  try {
    const { data } = await comApi.listarCorridas({ per_page: 100 })
    const lista = data.data ?? data
    corridas.value = lista.map(c => ({
      label: `Corrida #${c.ID_Corrida} — ${c.Estatus}`,
      value: c.ID_Corrida,
    }))
  } catch { /* no crítico */ }
}

async function cargar() {
  cargando.value = true
  try {
    const params = {
      page: paginaActual.value,
      per_page: 50,
      ...Object.fromEntries(Object.entries(filtros).filter(([, v]) => v != null)),
    }
    const { data } = await comApi.listarCalculos(params)
    tickets.value = data.data
    total.value   = data.total
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los cálculos.', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function onPage(e) { paginaActual.value = e.page + 1; cargar() }

function confirmarAprobar(row) {
  seleccionado.value = row
  obsAutorizar.value = ''
  dlgAutorizar.value = true
}

async function ejecutarAutorizar() {
  autorizando.value = true
  try {
    await comApi.aprobar(seleccionado.value.ID_Calculo, {
      ID_Usuario:    auth.usuario?.id,
      Observaciones: obsAutorizar.value || undefined,
    })
    toast.add({ severity: 'success', summary: 'Comisión aprobada', life: 3000 })
    dlgAutorizar.value = false
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message ?? 'No se pudo autorizar.', life: 4000 })
  } finally {
    autorizando.value = false
  }
}

function fmt(n) {
  return Number(n ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function severidad(estatus) {
  const map = {
    CALCULADO: 'info', APROBADO: 'success',
    RECHAZADO: 'danger', PAGADO: 'success', CANCELADO: 'secondary',
  }
  return map[estatus] ?? 'secondary'
}

onMounted(() => { cargarCorridas(); cargar() })
</script>

<style scoped>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.page-title  { margin:0; font-size:1.4rem; display:flex; align-items:center; gap:0.5rem; }
.filtros-grid{ display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; }
.totales-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; }
.kpi         { display:flex; flex-direction:column; gap:0.25rem; }
.kpi-label   { font-size:0.78rem; color:var(--p-text-muted-color); }
.kpi-valor   { font-size:1.4rem; font-weight:700; }
.mb-3        { margin-bottom:1rem; }
.mt-2        { margin-top:0.75rem; }
.monto-resumen { font-size:1.1rem; }
.text-primary{ color:var(--p-primary-500); }
</style>
