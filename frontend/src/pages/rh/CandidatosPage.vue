<template>
  <div>
    <PageHeader titulo="Candidatos" subtitulo="Recursos Humanos" icon="pi-users" />

    <!-- Filtros -->
    <Card class="mb-3">
      <template #content>
        <div class="filtros-row">
          <Select v-model="filtros.ID_Estatus" :options="cats.estatusCandidato"
            optionLabel="Nombre" optionValue="ID_EstatusCandidato"
            placeholder="Estatus" showClear class="filtro" @change="buscar" />
          <Select v-model="filtros.ID_Vacante_FK" :options="vacantes"
            optionLabel="Titulo" optionValue="ID_Vacante"
            placeholder="Vacante" showClear class="filtro" @change="buscar" />
          <InputText v-model="filtros.q" placeholder="Buscar nombre..."
            class="filtro" @keyup.enter="buscar" />
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
          <Column field="NombreCompleto" header="Nombre" />
          <Column field="Correo" header="Correo" />
          <Column field="Telefono" header="Teléfono" style="width:130px" />
          <Column header="Vacante">
            <template #body="{ data }">{{ data.vacante?.Titulo ?? '—' }}</template>
          </Column>
          <Column header="Fuente">
            <template #body="{ data }">{{ data.Fuente ?? '—' }}</template>
          </Column>
          <Column header="Estatus" style="width:140px">
            <template #body="{ data }">
              <EstatusTag :valor="data.estatus?.Nombre" modulo="rh-candidato" />
            </template>
          </Column>
          <Column header="Fecha" style="width:130px">
            <template #body="{ data }">{{ formatFecha(data.FechaPostulacion) }}</template>
          </Column>
          <Column style="width:60px">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded size="small"
                @click="$router.push(`/rh/candidatos/${data.ID_Candidato}`)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { rhApi } from '../../api/rh'
import { formatFecha } from '../../utils/formato'
import PageHeader from '../../components/shared/PageHeader.vue'
import EstatusTag from '../../components/shared/EstatusTag.vue'
import Card       from 'primevue/card'
import Button     from 'primevue/button'
import Select     from 'primevue/select'
import InputText  from 'primevue/inputtext'
import DataTable  from 'primevue/datatable'
import Column     from 'primevue/column'

const toast = useToast()
const cats  = useCatalogosStore()

const rows     = ref([])
const total    = ref(0)
const cargando = ref(false)
const pagina   = ref(1)
const perPage  = 15
const vacantes = ref([])

const filtros = ref({ ID_Estatus: null, ID_Vacante_FK: null, q: '' })

async function cargar() {
  cargando.value = true
  try {
    const params = { page: pagina.value, per_page: perPage }
    if (filtros.value.ID_Estatus)    params.id_estatus  = filtros.value.ID_Estatus
    if (filtros.value.ID_Vacante_FK) params.id_vacante  = filtros.value.ID_Vacante_FK
    if (filtros.value.q)             params.busqueda    = filtros.value.q
    const res = await rhApi.listarCandidatos(params)
    rows.value  = res.data.data ?? res.data
    total.value = res.data.total ?? rows.value.length
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar candidatos', life: 3000 })
  } finally {
    cargando.value = false
  }
}

async function cargarVacantes() {
  try {
    const res = await rhApi.listarVacantes({ per_page: 200 })
    vacantes.value = res.data.data ?? res.data
  } catch { /* no critical */ }
}

function buscar() { pagina.value = 1; cargar() }
function limpiar() { filtros.value = { ID_Estatus: null, ID_Vacante_FK: null, q: '' }; buscar() }
function onPage(e) { pagina.value = e.page + 1; cargar() }

onMounted(() => { cargar(); cargarVacantes() })
</script>

<style scoped>
.mb-3       { margin-bottom:1rem; }
.filtros-row{ display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; }
.filtro     { min-width:160px; }
</style>
