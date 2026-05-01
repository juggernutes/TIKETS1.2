<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-ticket" /> Tickets de Soporte</h1>
      <Button label="Nuevo ticket" icon="pi pi-plus" @click="dlgNuevo = true" />
    </div>

    <!-- Filtros -->
    <Card class="mb-3">
      <template #content>
        <div class="filtros-grid">
          <Select v-model="filtros.id_estatus" :options="cats.estatusHd"
            optionLabel="Nombre" optionValue="ID_Estatus"
            placeholder="Estatus" showClear />
          <Select v-model="filtros.id_area_responsable" :options="cats.areas"
            optionLabel="Nombre" optionValue="ID_Area"
            placeholder="Área" showClear />
          <AutoComplete
            v-model="empleadoSel"
            :suggestions="empleadosSugeridos"
            optionLabel="Nombre"
            placeholder="Empleado..."
            :delay="300"
            @complete="buscarEmpleado"
            @item-select="e => filtros.numero_empleado = e.value.Numero_Empleado"
            @clear="filtros.numero_empleado = null"
            forceSelection
          />
          <DatePicker v-model="filtros.fecha_desde" placeholder="Desde" dateFormat="dd/mm/yy" showClear />
          <DatePicker v-model="filtros.fecha_hasta" placeholder="Hasta"  dateFormat="dd/mm/yy" showClear />
          <Button label="Buscar" icon="pi pi-search" @click="buscar" />
          <Button label="Limpiar" icon="pi pi-times" severity="secondary" text @click="limpiar" />
        </div>
      </template>
    </Card>

    <!-- Tabla -->
    <Card>
      <template #content>
        <DataTable :value="tickets" :loading="cargando"
          lazy :totalRecords="total"
          paginator :rows="20" @page="onPage"
          stripedRows size="small"
          scrollable scrollHeight="60vh">
          <Column field="ID_Ticket"  header="#"     style="width:65px" />
          <Column field="SerieFolio" header="Folio" style="width:140px" />
          <Column header="Empleado">
            <template #body="{ data }">
              {{ data.empleado?.Nombre ?? data.Numero_Empleado }}
            </template>
          </Column>
          <Column header="Sistema">
            <template #body="{ data }">{{ data.sistema?.Nombre }}</template>
          </Column>
          <Column header="Estatus" style="width:120px">
            <template #body="{ data }">
              <Tag :value="data.estatus?.Nombre" :severity="severidadEstatus(data.estatus?.Nombre)" />
            </template>
          </Column>
          <Column header="SLA" style="width:100px">
            <template #body="{ data }">
              <template v-if="data.FechaLimite">
                <Tag v-if="data.FueraDeSLA" value="Vencido" severity="danger"
                  v-tooltip="'Límite: ' + formatFecha(data.FechaLimite)" />
                <Tag v-else value="En tiempo" severity="success"
                  v-tooltip="'Límite: ' + formatFecha(data.FechaLimite)" />
              </template>
              <span v-else class="muted">—</span>
            </template>
          </Column>
          <Column header="Asignado a">
            <template #body="{ data }">{{ data.soporte?.Nombre ?? '—' }}</template>
          </Column>
          <Column header="Fecha reporte" style="width:140px">
            <template #body="{ data }">{{ formatFecha(data.FechaReporte) }}</template>
          </Column>
          <Column header="" style="width:60px">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded size="small"
                @click="verDetalle(data.ID_Ticket)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog nuevo ticket -->
    <Dialog v-model:visible="dlgNuevo" header="Nuevo Ticket" modal style="width:540px">
      <TicketForm @guardado="onTicketGuardado" @cancelar="dlgNuevo = false" />
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast }  from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { hdApi } from '../../api/hd'
import { formatFecha } from '../../utils/formato'
import TicketForm from '../../components/hd/TicketForm.vue'

import Card         from 'primevue/card'
import Button       from 'primevue/button'
import DataTable    from 'primevue/datatable'
import Column       from 'primevue/column'
import Select       from 'primevue/select'
import AutoComplete from 'primevue/autocomplete'
import DatePicker   from 'primevue/datepicker'
import Tag          from 'primevue/tag'
import Dialog       from 'primevue/dialog'

const router = useRouter()
const toast  = useToast()
const cats   = useCatalogosStore()

const tickets           = ref([])
const total             = ref(0)
const cargando          = ref(false)
const dlgNuevo          = ref(false)
const paginaActual      = ref(1)
const empleadoSel       = ref(null)
const empleadosSugeridos= ref([])

const filtros = reactive({
  id_estatus:          null,
  id_area_responsable: null,
  numero_empleado:     null,
  fecha_desde:         null,
  fecha_hasta:         null,
})

async function buscarEmpleado(event) {
  try {
    const { data } = await hdApi.buscarEmpleados(event.query)
    empleadosSugeridos.value = data
  } catch {
    empleadosSugeridos.value = []
  }
}

async function cargar() {
  cargando.value = true
  try {
    const params = {
      page: paginaActual.value,
      per_page: 20,
      ...Object.fromEntries(Object.entries(filtros).filter(([, v]) => v != null)),
    }
    const { data } = await hdApi.listarTickets(params)
    tickets.value = data.data
    total.value   = data.total
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los tickets.', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function buscar() {
  paginaActual.value = 1
  cargar()
}

function limpiar() {
  empleadoSel.value = null
  Object.assign(filtros, {
    id_estatus: null, id_area_responsable: null,
    numero_empleado: null, fecha_desde: null, fecha_hasta: null,
  })
  buscar()
}

function onPage(e) {
  paginaActual.value = e.page + 1
  cargar()
}

function verDetalle(id) {
  router.push(`/hd/tickets/${id}`)
}

function onTicketGuardado() {
  dlgNuevo.value = false
  cargar()
  toast.add({ severity: 'success', summary: 'Ticket creado', life: 3000 })
}

function severidadEstatus(nombre) {
  const map = {
    'Nuevo': 'info', 'Asignado': 'warn', 'En proceso': 'warn',
    'En espera': 'secondary', 'Resuelto': 'success', 'Cerrado': 'success', 'Cancelado': 'danger',
  }
  return map[nombre] ?? 'secondary'
}

onMounted(cargar)
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}
.page-title {
  margin: 0;
  font-size: 1.4rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.filtros-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: flex-end;
}
.mb-3  { margin-bottom: 1rem; }
.muted { color: var(--p-text-muted-color); }
</style>
