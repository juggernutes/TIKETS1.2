<template>
  <div>
    <PageHeader
      :titulo="empleado ? empleado.Nombre : 'Empleado'"
      subtitulo="Detalle de empleado y equipos asignados"
      icon="pi-id-card"
      :back="true"
    />

    <div v-if="empleado" class="det-grid">
      <Card>
        <template #title>Datos del empleado</template>
        <template #content>
          <div class="info-grid">
            <span class="lbl">Número</span>
            <span>{{ empleado.Numero_Empleado }}</span>
            <span class="lbl">Correo</span>
            <a v-if="empleado.Correo" :href="`mailto:${empleado.Correo}`">{{ empleado.Correo }}</a>
            <span v-else>—</span>
            <span class="lbl">Teléfono</span>
            <span>{{ empleado.Telefono ?? '—' }}</span>
            <span class="lbl">Extensión</span>
            <span>{{ empleado.Extension ?? '—' }}</span>
            <span class="lbl">Área</span>
            <span>{{ empleado.area?.Nombre ?? '—' }}</span>
            <span class="lbl">Sucursal</span>
            <span>{{ empleado.sucursal?.Nombre ?? '—' }}</span>
            <span class="lbl">Puesto</span>
            <span>{{ empleado.puesto?.Descripcion ?? '—' }}</span>
            <span class="lbl">AnyDesk</span>
            <span>{{ empleado.UsuarioAnyDesk ?? '—' }}</span>
            <span class="lbl">Estatus</span>
            <Tag :value="empleado.Activo ? 'Activo' : 'Inactivo'" :severity="empleado.Activo ? 'success' : 'secondary'" />
          </div>
        </template>
      </Card>

      <Card>
        <template #title>Usuario relacionado</template>
        <template #content>
          <div class="info-grid">
            <span class="lbl">Usuario</span>
            <span>{{ empleado.usuario_relacion?.usuario?.Nombre ?? '—' }}</span>
            <span class="lbl">Rol</span>
            <span>{{ empleado.usuario_relacion?.usuario?.rol?.Nombre ?? '—' }}</span>
            <span class="lbl">Email</span>
            <span>{{ empleado.usuario_relacion?.usuario?.Email ?? '—' }}</span>
          </div>
        </template>
      </Card>
    </div>

    <Card v-if="empleado" class="mt-3">
      <template #title>Equipos asignados</template>
      <template #content>
        <DataTable :value="equipos" :loading="cargando" size="small" stripedRows>
          <Column header="Tipo">
            <template #body="{ data }">{{ data.equipo?.tipo?.Nombre ?? '—' }}</template>
          </Column>
          <Column header="Marca / Modelo">
            <template #body="{ data }">
              {{ [data.equipo?.Marca, data.equipo?.Modelo].filter(Boolean).join(' ') || '—' }}
            </template>
          </Column>
          <Column header="Serie">
            <template #body="{ data }">{{ data.equipo?.NumeroSerie ?? '—' }}</template>
          </Column>
          <Column header="Activo fijo">
            <template #body="{ data }">{{ data.equipo?.NuActvoFijo ?? '—' }}</template>
          </Column>
          <Column header="IP">
            <template #body="{ data }">{{ data.equipo?.IPDireccion ?? '—' }}</template>
          </Column>
          <Column header="Asignación">
            <template #body="{ data }">{{ formatFecha(data.FechaAsignacion) }}</template>
          </Column>
          <Column header="Estatus" style="width:110px">
            <template #body="{ data }">
              <Tag :value="data.Activo ? 'Asignado' : 'Devuelto'" :severity="data.Activo ? 'success' : 'secondary'" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <div v-else-if="cargando" class="center-spinner">
      <ProgressSpinner />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { coreApi } from '../../api/core'
import { formatFecha } from '../../utils/formato'
import PageHeader from '../../components/shared/PageHeader.vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'

const route = useRoute()
const empleado = ref(null)
const equipos = ref([])
const cargando = ref(false)

async function cargar() {
  cargando.value = true
  try {
    const { data } = await coreApi.empleado(route.params.numero)
    empleado.value = data
    equipos.value = data.equipos_asignados ?? []
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.det-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}
.info-grid {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.5rem 1rem;
}
.lbl {
  color: var(--p-text-muted-color);
  font-weight: 600;
  white-space: nowrap;
}
.mt-3 {
  margin-top: 1rem;
}
.center-spinner {
  display: flex;
  justify-content: center;
  padding: 4rem;
}
a {
  color: #93c5fd;
  text-decoration: none;
}
@media (max-width: 900px) {
  .det-grid {
    grid-template-columns: 1fr;
  }
}
</style>
