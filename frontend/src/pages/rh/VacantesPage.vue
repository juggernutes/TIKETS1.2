<template>
  <div>
    <PageHeader titulo="Vacantes" subtitulo="Recursos Humanos" icon="pi-briefcase">
      <Button label="Nueva vacante" icon="pi pi-plus" @click="dlgNueva = true" />
    </PageHeader>

    <!-- Filtros -->
    <Card class="mb-3">
      <template #content>
        <div class="filtros-row">
          <Select v-model="filtros.estatus" :options="[{ Nombre:'Todos', val:null }, ...estatusOpciones]"
            optionLabel="Nombre" optionValue="val"
            placeholder="Estatus" class="filtro" @change="buscar" />
          <Select v-model="filtros.id_area" :options="cats.areas"
            optionLabel="Nombre" optionValue="ID_Area"
            placeholder="Área" showClear class="filtro" @change="buscar" />
          <Select v-model="filtros.id_sucursal" :options="cats.sucursales"
            optionLabel="Nombre" optionValue="ID_Sucursal"
            placeholder="Sucursal" showClear class="filtro" @change="buscar" />
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
          <Column field="Folio" header="Folio" style="width:110px" />
          <Column field="Titulo" header="Título" />
          <Column header="Área">
            <template #body="{ data }">{{ data.area?.Nombre }}</template>
          </Column>
          <Column header="Sucursal">
            <template #body="{ data }">{{ data.sucursal?.Nombre }}</template>
          </Column>
          <Column field="NumeroPosiciones" header="Posiciones" style="width:100px;text-align:center" />
          <Column header="Estatus" style="width:130px">
            <template #body="{ data }">
              <EstatusTag :valor="data.Estatus" modulo="rh-vacante" />
            </template>
          </Column>
          <Column header="Fecha" style="width:130px">
            <template #body="{ data }">{{ formatFecha(data.FechaCreacion) }}</template>
          </Column>
          <Column style="width:60px">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded size="small"
                @click="$router.push(`/rh/vacantes/${data.ID_Vacante}`)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog nueva vacante -->
    <Dialog v-model:visible="dlgNueva" header="Nueva Vacante" modal style="width:540px">
      <VacanteForm @guardado="onGuardado" @cancelar="dlgNueva = false" />
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { rhApi } from '../../api/rh'
import { formatFecha } from '../../utils/formato'
import PageHeader  from '../../components/shared/PageHeader.vue'
import EstatusTag  from '../../components/shared/EstatusTag.vue'
import VacanteForm from '../../components/rh/VacanteForm.vue'
import Card        from 'primevue/card'
import Button      from 'primevue/button'
import Select      from 'primevue/select'
import DataTable   from 'primevue/datatable'
import Column      from 'primevue/column'
import Dialog      from 'primevue/dialog'

const toast = useToast()
const cats  = useCatalogosStore()

const rows     = ref([])
const total    = ref(0)
const cargando = ref(false)
const pagina   = ref(1)
const perPage  = 15
const dlgNueva = ref(false)

const filtros = ref({ estatus: null, id_area: null, id_sucursal: null })

const estatusOpciones = [
  { Nombre: 'Abierta',    val: 'ABIERTA' },
  { Nombre: 'Pausada',    val: 'PAUSADA' },
  { Nombre: 'Cerrada',    val: 'CERRADA' },
  { Nombre: 'Cancelada',  val: 'CANCELADA' },
]

async function cargar() {
  cargando.value = true
  try {
    const res = await rhApi.listarVacantes({
      page: pagina.value,
      per_page: perPage,
      ...Object.fromEntries(Object.entries(filtros.value).filter(([, v]) => v !== null)),
    })
    rows.value  = res.data.data ?? res.data
    total.value = res.data.total ?? rows.value.length
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar vacantes', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function buscar() { pagina.value = 1; cargar() }
function limpiar() { filtros.value = { estatus: null, id_area: null, id_sucursal: null }; buscar() }
function onPage(e) { pagina.value = e.page + 1; cargar() }
function onGuardado() { dlgNueva.value = false; cargar() }

onMounted(cargar)
</script>

<style scoped>
.mb-3       { margin-bottom:1rem; }
.filtros-row{ display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; }
.filtro     { min-width:160px; }
</style>
