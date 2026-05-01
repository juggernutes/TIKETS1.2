<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-users" /> Empleados</h1>
    </div>

    <!-- Filtros -->
    <Card class="mb-3">
      <template #content>
        <div class="filtros-grid">
          <InputText v-model="filtros.buscar" placeholder="Nombre, correo o número..."
            @keyup.enter="cargar" class="w-full" />
          <Select v-model="filtros.area" :options="cats.areas"
            optionLabel="Nombre" optionValue="ID_Area"
            placeholder="Área" showClear />
          <Select v-model="filtros.sucursal" :options="cats.sucursales"
            optionLabel="Nombre" optionValue="ID_Sucursal"
            placeholder="Sucursal" showClear />
          <Select v-model="filtros.activo" :options="opcionesActivo"
            optionLabel="label" optionValue="value"
            placeholder="Estatus" showClear />
          <Button label="Buscar" icon="pi pi-search" @click="cargar" />
        </div>
      </template>
    </Card>

    <!-- Tabla -->
    <Card>
      <template #content>
        <DataTable :value="empleados" :loading="cargando"
          lazy :totalRecords="total"
          paginator :rows="perPage" @page="onPage"
          stripedRows size="small"
          selectionMode="single"
          @row-click="verEmpleado"
          scrollable scrollHeight="60vh">

          <Column field="numero_empleado" header="N° Empleado" style="width:120px" />
          <Column field="nombre"          header="Nombre" />
          <Column field="correo"          header="Correo">
            <template #body="{ data }">
              <a :href="`mailto:${data.correo}`" class="correo-link">{{ data.correo ?? '—' }}</a>
            </template>
          </Column>
          <Column field="area"     header="Área" />
          <Column field="sucursal" header="Sucursal" />
          <Column field="puesto"   header="Puesto" />
          <Column field="extension" header="Ext." style="width:80px">
            <template #body="{ data }">{{ data.extension ?? '—' }}</template>
          </Column>
          <Column header="Estatus" style="width:100px">
            <template #body="{ data }">
              <Tag :value="data.activo ? 'Activo' : 'Inactivo'"
                :severity="data.activo ? 'success' : 'secondary'" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCatalogosStore } from '../../stores/catalogos'
import { coreApi } from '../../api/core'

import Card      from 'primevue/card'
import InputText from 'primevue/inputtext'
import Select    from 'primevue/select'
import Button    from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column    from 'primevue/column'
import Tag       from 'primevue/tag'

const cats = useCatalogosStore()
const router = useRouter()

const empleados = ref([])
const cargando  = ref(false)
const total     = ref(0)
const perPage   = 20
const pagina    = ref(1)

const filtros = reactive({
  buscar:   '',
  area:     null,
  sucursal: null,
  activo:   null,
})

const opcionesActivo = [
  { label: 'Activos',   value: 1 },
  { label: 'Inactivos', value: 0 },
]

async function cargar() {
  cargando.value = true
  try {
    const params = {
      page:     pagina.value,
      per_page: perPage,
      ...(filtros.buscar   && { buscar:   filtros.buscar }),
      ...(filtros.area     && { area:     filtros.area }),
      ...(filtros.sucursal && { sucursal: filtros.sucursal }),
      ...(filtros.activo !== null && { activo: filtros.activo }),
    }
    const { data } = await coreApi.empleados(params)
    empleados.value = data.data
    total.value     = data.total
  } finally {
    cargando.value = false
  }
}

function onPage(e) {
  pagina.value = e.page + 1
  cargar()
}

function verEmpleado(event) {
  router.push(`/core/empleados/${event.data.numero_empleado}`)
}

onMounted(async () => {
  await cats.cargar()
  cargar()
})
</script>

<style scoped>
.filtros-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr auto;
  gap: 0.75rem;
  align-items: end;
}

.correo-link {
  color: #93c5fd;
  text-decoration: none;
}
.correo-link:hover {
  text-decoration: underline;
}

@media (max-width: 900px) {
  .filtros-grid {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
