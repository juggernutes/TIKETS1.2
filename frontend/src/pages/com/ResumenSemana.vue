<template>
  <div>
    <PageHeader titulo="Resumen de Semana" subtitulo="Comisiones" icon="pi-chart-pie" />

    <!-- Selector de semana -->
    <Card class="mb-3">
      <template #content>
        <div class="filtros-row">
          <Select v-model="semanaId" :options="cats.semanas"
            optionLabel="label" optionValue="ID_Semana"
            placeholder="Selecciona semana..." class="filtro-semana" />
          <Button label="Cargar" icon="pi pi-sync" :loading="cargando" @click="cargar" />
        </div>
      </template>
    </Card>

    <template v-if="resumen">
      <!-- KPI cards -->
      <div class="kpi-grid mb-3">
        <Card class="kpi-card">
          <template #content>
            <p class="kpi-label">Empleados en corridas</p>
            <p class="kpi-valor">{{ resumen.total_empleados ?? 0 }}</p>
          </template>
        </Card>
        <Card class="kpi-card">
          <template #content>
            <p class="kpi-label">Corridas totales</p>
            <p class="kpi-valor">{{ resumen.corridas?.length ?? 0 }}</p>
          </template>
        </Card>
        <Card class="kpi-card warn">
          <template #content>
            <p class="kpi-label">Calculadas</p>
            <p class="kpi-valor">{{ resumen.corridas?.filter(c => c.Estatus === 'CALCULADO').length ?? 0 }}</p>
          </template>
        </Card>
        <Card class="kpi-card success">
          <template #content>
            <p class="kpi-label">Aprobadas / Pagadas</p>
            <p class="kpi-valor">{{ resumen.corridas?.filter(c => ['APROBADO','PAGADO'].includes(c.Estatus)).length ?? 0 }}</p>
          </template>
        </Card>
      </div>

      <!-- Tabla de corridas -->
      <Card>
        <template #title>Corridas de la semana</template>
        <template #content>
          <DataTable :value="resumen.corridas ?? []" rowHover stripedRows size="small">
            <Column field="ID_Corrida" header="#" style="width:70px" />
            <Column header="Estatus" style="width:130px">
              <template #body="{ data }">
                <EstatusTag :valor="data.Estatus" modulo="com-corrida" />
              </template>
            </Column>
            <Column header="Fecha" style="width:160px">
              <template #body="{ data }">{{ formatFecha(data.FechaCreacion) }}</template>
            </Column>
            <Column style="width:60px">
              <template #body="{ data }">
                <Button icon="pi pi-eye" text rounded size="small"
                  @click="$router.push(`/com/corridas`)" />
              </template>
            </Column>
          </DataTable>
          <p v-if="!resumen.corridas?.length" class="sin-items">Sin corridas en esta semana.</p>
        </template>
      </Card>
    </template>

    <div v-else-if="!cargando" class="empty-state">
      <i class="pi pi-chart-bar empty-icon" />
      <p>Selecciona una semana para ver el resumen.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { comApi } from '../../api/com'
import { formatMoneda, formatFecha } from '../../utils/formato'
import PageHeader from '../../components/shared/PageHeader.vue'
import EstatusTag from '../../components/shared/EstatusTag.vue'
import Card      from 'primevue/card'
import Button    from 'primevue/button'
import Select    from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column    from 'primevue/column'

const toast   = useToast()
const cats    = useCatalogosStore()

const semanaId = ref(null)
const resumen  = ref(null)
const cargando = ref(false)

async function cargar() {
  if (!semanaId.value) return
  cargando.value = true
  try {
    const res = await comApi.resumenSemana(semanaId.value)
    resumen.value = res.data
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar resumen', life: 3000 })
  } finally {
    cargando.value = false
  }
}

onMounted(() => {
  if (cats.semanas.length) {
    semanaId.value = cats.semanas[0].ID_Semana  // index 0 = most recent (sorted desc)
  }
})
</script>

<style scoped>
.mb-3        { margin-bottom:1rem; }
.filtros-row { display:flex; gap:0.75rem; align-items:center; }
.filtro-semana { min-width:240px; }

.kpi-grid    { display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem; }
.kpi-card    { text-align:center; }
.kpi-card.warn    { border-top:3px solid var(--p-amber-400, #f59e0b); }
.kpi-card.success { border-top:3px solid var(--p-green-500, #22c55e); }
.kpi-label   { margin:0 0 0.3rem; font-size:0.82rem; color:var(--p-text-muted-color); font-weight:500; }
.kpi-valor   { margin:0; font-size:1.6rem; font-weight:700; }

.empty-state { display:flex; flex-direction:column; align-items:center; justify-content:center;
  padding:4rem; gap:0.75rem; color:var(--p-text-muted-color); }
.empty-icon  { font-size:3rem; }
.sin-items   { text-align:center; color:var(--p-text-muted-color); padding:1rem 0; }

@media(max-width:800px) { .kpi-grid { grid-template-columns:repeat(2, 1fr); } }
</style>
