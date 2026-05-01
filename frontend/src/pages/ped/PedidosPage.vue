<template>
  <div>
    <PageHeader titulo="Pedidos" subtitulo="Área de Ventas" icon="pi-box">
      <Button v-if="auth.puede('ped.pedidos.crear')" label="Nuevo pedido" icon="pi pi-plus" @click="dlgNuevo = true" />
    </PageHeader>

    <div v-if="bandejas.length > 1" class="bandejas-row">
      <Button
        v-for="item in bandejas"
        :key="item.key"
        :label="item.label"
        :icon="item.icon"
        :outlined="bandeja !== item.key"
        size="small"
        @click="cambiarBandeja(item.key)"
      />
    </div>

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
            <template #body="{ data }">{{ unidadPedido(data)?.Nombre ?? data.IdUnidadPedido }}</template>
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
import { computed, ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../stores/auth'
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
const auth  = useAuthStore()
const cats  = useCatalogosStore()

const rows     = ref([])
const total    = ref(0)
const cargando = ref(false)
const pagina   = ref(1)
const perPage  = 15
const dlgNuevo = ref(false)
const bandeja  = ref('todos')

const filtros = ref({ id_estado: null, ID_Semana: null, folio: '' })

const bandejas = computed(() => [
  auth.puede('ped.pedidos.ver') && { key: 'todos', label: 'Todos', icon: 'pi pi-list' },
  auth.puede('ped.pedidos.ver_por_autorizar') && { key: 'autorizar', label: 'Por autorizar', icon: 'pi pi-check-circle' },
  auth.puede('ped.pedidos.ver_por_surtir') && { key: 'surtir', label: 'Por surtir', icon: 'pi pi-box' },
].filter(Boolean))

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
    const res = await cargarBandeja(params)
    rows.value  = res.data.data ?? res.data
    total.value = res.data.total ?? rows.value.length
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar pedidos', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function cargarBandeja(params) {
  if (bandeja.value === 'autorizar') return pedApi.porAutorizar(params)
  if (bandeja.value === 'surtir') return pedApi.porSurtir(params)
  return pedApi.listarPedidos(params)
}

function unidadPedido(pedido) {
  return pedido?.unidadPedido ?? pedido?.unidad_pedido ?? null
}

function cambiarBandeja(key) {
  bandeja.value = key
  buscar()
}

function buscar() { pagina.value = 1; cargar() }
function limpiar() { filtros.value = { id_estado: null, ID_Semana: null, folio: '' }; buscar() }
function onPage(e) { pagina.value = e.page + 1; cargar() }
function onGuardado() { dlgNuevo.value = false; cargar() }

onMounted(() => {
  if (!bandejas.value.some(item => item.key === bandeja.value)) {
    bandeja.value = bandejas.value[0]?.key ?? 'todos'
  }
  cargar()
})
</script>

<style scoped>
.mb-3       { margin-bottom:1rem; }
.bandejas-row { display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:1rem; }
.filtros-row{ display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; }
.filtro     { min-width:160px; }
</style>
