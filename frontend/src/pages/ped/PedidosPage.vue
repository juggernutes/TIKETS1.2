<template>
  <div>
    <PageHeader titulo="Pedidos" subtitulo="Área de Ventas" icon="pi-box">
      <Button label="Nuevo pedido" icon="pi pi-plus" @click="dlgNuevo = true" />
    </PageHeader>

    <!-- Filtros -->
    <Card class="mb-3">
      <template #content>
        <div class="filtros-row">
          <Select v-model="filtros.id_estado" :options="estadoOpciones"
            optionLabel="Nombre" optionValue="val"
            placeholder="Estado" showClear class="filtro" @change="buscar" />
          <Select v-model="filtros.ID_Semana" :options="cats.semanas"
            optionLabel="label" optionValue="ID_Semana"
            placeholder="Semana" showClear class="filtro" @change="buscar" />
          <InputText v-model="filtros.folio" placeholder="Folio..." class="filtro" @keyup.enter="buscar" />
          <Button icon="pi pi-search" @click="buscar" />
          <Button icon="pi pi-times" severity="secondary" outlined @click="limpiar" v-tooltip="'Limpiar'" />
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable :value="rows" :loading="cargando" lazy
          :totalRecords="total" :rows="perPage" :first="(pagina-1)*perPage"
          @page="onPage" paginator paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
          rowHover stripedRows>
          <Column field="FolioPedido" header="Folio" style="width:130px" />
          <Column header="Unidad">
            <template #body="{ data }">{{ data.unidadPedido?.Nombre ?? data.IdUnidadPedido }}</template>
          </Column>
          <Column header="Semana">
            <template #body="{ data }">{{ data.semana ? `Sem ${data.semana.Semana}/${data.semana.Anio}` : '—' }}</template>
          </Column>
          <Column header="Registros" style="width:90px;text-align:center">
            <template #body="{ data }">{{ data.Registros ?? 0 }}</template>
          </Column>
          <Column header="Estado" style="width:130px">
            <template #body="{ data }">
              <EstatusTag :valor="data.estado?.Nombre" modulo="ped" />
            </template>
          </Column>
          <Column header="Fecha" style="width:130px">
            <template #body="{ data }">{{ formatFecha(data.created_at) }}</template>
          </Column>
          <Column style="width:60px">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded size="small"
                @click="$router.push(`/ped/pedidos/${data.IdPedido}`)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog nuevo pedido -->
    <Dialog v-model:visible="dlgNuevo" header="Nuevo Pedido" modal style="width:680px">
      <PedidoForm @guardado="onGuardado" @cancelar="dlgNuevo = false" />
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { pedApi } from '../../api/ped'
import { formatFecha } from '../../utils/formato'
import PageHeader from '../../components/shared/PageHeader.vue'
import EstatusTag from '../../components/shared/EstatusTag.vue'
import PedidoForm from '../../components/ped/PedidoForm.vue'
import Card       from 'primevue/card'
import Button     from 'primevue/button'
import Select     from 'primevue/select'
import InputText  from 'primevue/inputtext'
import DataTable  from 'primevue/datatable'
import Column     from 'primevue/column'
import Dialog     from 'primevue/dialog'

const toast = useToast()
const cats  = useCatalogosStore()

const rows     = ref([])
const total    = ref(0)
const cargando = ref(false)
const pagina   = ref(1)
const perPage  = 15
const dlgNuevo = ref(false)

const filtros = ref({ id_estado: null, ID_Semana: null, folio: '' })

const estadoOpciones = [
  { Nombre: 'Pendiente',   val: 'PENDIENTE' },
  { Nombre: 'Autorizado',  val: 'AUTORIZADO' },
  { Nombre: 'En proceso',  val: 'EN_PROCESO' },
  { Nombre: 'Surtido',     val: 'SURTIDO' },
  { Nombre: 'Cancelado',   val: 'CANCELADO' },
]

async function cargar() {
  cargando.value = true
  try {
    const params = { page: pagina.value, per_page: perPage }
    if (filtros.value.id_estado) params.id_estado  = filtros.value.id_estado
    if (filtros.value.ID_Semana) params.ID_Semana  = filtros.value.ID_Semana
    if (filtros.value.folio)     params.folio      = filtros.value.folio
    const res = await pedApi.listarPedidos(params)
    rows.value  = res.data.data ?? res.data
    total.value = res.data.total ?? rows.value.length
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar pedidos', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function buscar() { pagina.value = 1; cargar() }
function limpiar() { filtros.value = { id_estado: null, ID_Semana: null, folio: '' }; buscar() }
function onPage(e) { pagina.value = e.page + 1; cargar() }
function onGuardado() { dlgNuevo.value = false; cargar() }

onMounted(cargar)
</script>

<style scoped>
.mb-3       { margin-bottom:1rem; }
.filtros-row{ display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; }
.filtro     { min-width:160px; }
</style>
