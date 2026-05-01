<template>
  <div v-if="calculo">
    <PageHeader :titulo="`Comisión — ${calculo.base?.empleado?.Nombre ?? calculo.ID_Calculo}`"
      subtitulo="Comisiones" icon="pi-chart-bar" :back="true">
      <EstatusTag :valor="calculo.Estatus" modulo="com" />
    </PageHeader>

    <div class="det-grid">
      <!-- Columna izquierda: desglose de indicadores -->
      <div class="col-left">
        <Card class="mb-3">
          <template #title>Información general</template>
          <template #content>
            <div class="info-grid">
              <span class="lbl">Vendedor</span>
              <span>{{ calculo.base?.empleado?.Nombre ?? '—' }}</span>
              <span class="lbl">Semana</span>
              <span>{{ calculo.base?.corrida?.semana
                ? `Sem ${calculo.base.corrida.semana.Semana}/${calculo.base.corrida.semana.Anio}`
                : '—' }}</span>
              <span class="lbl">Estatus</span>
              <span>{{ calculo.Estatus }}</span>
              <span class="lbl">Fecha cálculo</span>
              <span>{{ formatFecha(calculo.FechaCalculo) }}</span>
              <span class="lbl">Fecha aprobación</span>
              <span>{{ calculo.FechaAprobacion ? formatFecha(calculo.FechaAprobacion) : '—' }}</span>
            </div>
          </template>
        </Card>

        <Card class="mb-3">
          <template #title>Desglose de comisión</template>
          <template #content>
            <DataTable :value="calculo.base?.resultadosIndicador ?? []" showGridlines size="small">
              <Column field="indicador.Nombre" header="Indicador" />
              <Column field="sub_indicador.Descripcion" header="Sub-indicador" />
              <Column field="ValorReal" header="Valor real" style="width:120px;text-align:right">
                <template #body="{ data }">{{ data.ValorReal }}</template>
              </Column>
              <Column field="ValorMeta" header="Meta" style="width:100px;text-align:right" />
              <Column field="Porcentaje" header="%" style="width:80px;text-align:right">
                <template #body="{ data }">
                  <span :class="data.Porcentaje >= 100 ? 'verde' : 'rojo'">
                    {{ data.Porcentaje }}%
                  </span>
                </template>
              </Column>
              <Column header="Comisión" style="width:120px;text-align:right">
                <template #body="{ data }">{{ formatMoneda(data.MontoComision) }}</template>
              </Column>
            </DataTable>

            <div class="total-row">
              <span class="lbl">Total comisión</span>
              <span class="total-valor">{{ formatMoneda(calculo.MontoBruto) }}</span>
            </div>
          </template>
        </Card>

        <template v-if="calculo.Observaciones">
          <Card>
            <template #title>Observaciones</template>
            <template #content>
              <p class="texto">{{ calculo.Observaciones }}</p>
            </template>
          </Card>
        </template>
      </div>

      <!-- Columna derecha: acciones -->
      <div class="col-right">
        <Card>
          <template #title>Acciones</template>
          <template #content>
            <div class="acciones">
              <div class="field">
                <label>Cambiar estatus</label>
                <Select v-model="nuevoEstatus" :options="estatusOpciones"
                  optionLabel="Nombre" optionValue="val"
                  placeholder="Selecciona..." class="w-full" />
              </div>
              <template v-if="nuevoEstatus === 'RECHAZADO' || nuevoEstatus === 'CANCELADO'">
                <div class="field">
                  <label>Motivo</label>
                  <Textarea v-model="motivo" rows="2" class="w-full"
                    placeholder="Indica el motivo..." />
                </div>
              </template>
              <Button label="Actualizar" icon="pi pi-refresh"
                :loading="guardando" @click="cambiarEstatus" class="w-full" />

              <Divider />

              <template v-if="puedeAutorizar">
                <p class="txt-sm muted">Puedes aprobar esta comisión directamente.</p>
                <ConfirmBtn label="Aprobar comisión" icon="pi pi-check-circle"
                  severity="success" class="w-full"
                  mensaje="¿Confirmas la aprobación de esta comisión?"
                  header="Aprobar comisión"
                  @confirmado="autorizar" />
              </template>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
  <div v-else-if="cargando" class="center-spinner"><ProgressSpinner /></div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { comApi } from '../../api/com'
import { formatFecha, formatMoneda } from '../../utils/formato'
import PageHeader from '../../components/shared/PageHeader.vue'
import EstatusTag from '../../components/shared/EstatusTag.vue'
import ConfirmBtn from '../../components/shared/ConfirmBtn.vue'
import Card       from 'primevue/card'
import Button     from 'primevue/button'
import Select     from 'primevue/select'
import Textarea   from 'primevue/textarea'
import Divider    from 'primevue/divider'
import DataTable  from 'primevue/datatable'
import Column     from 'primevue/column'
import ProgressSpinner from 'primevue/progressspinner'

const route = useRoute()
const toast = useToast()

const calculo      = ref(null)
const cargando     = ref(false)
const nuevoEstatus = ref(null)
const motivo       = ref('')
const guardando    = ref(false)

const estatusOpciones = [
  { Nombre: 'Calculado', val: 'CALCULADO' },
  { Nombre: 'Aprobado',  val: 'APROBADO' },
  { Nombre: 'Rechazado', val: 'RECHAZADO' },
  { Nombre: 'Pagado',    val: 'PAGADO' },
  { Nombre: 'Cancelado', val: 'CANCELADO' },
]

const puedeAutorizar = computed(() =>
  calculo.value?.Estatus === 'CALCULADO'
)

async function cargar() {
  cargando.value = true
  try {
    const res = await comApi.verCalculo(route.params.id)
    calculo.value = res.data
    nuevoEstatus.value = calculo.value.Estatus
  } finally {
    cargando.value = false
  }
}

async function cambiarEstatus() {
  if (!nuevoEstatus.value) return
  guardando.value = true
  try {
    await comApi.cambiarEstatus(calculo.value.ID_Calculo, {
      estatus:       nuevoEstatus.value,
      Observaciones: motivo.value || undefined,
    })
    toast.add({ severity: 'success', summary: 'Estatus actualizado', life: 2500 })
    await cargar()
  } catch (err) {
    const msg = err.response?.data?.message ?? 'Error al cambiar estatus'
    toast.add({ severity: 'error', summary: msg, life: 4000 })
  } finally {
    guardando.value = false
  }
}

async function autorizar() {
  guardando.value = true
  try {
    await comApi.aprobar(calculo.value.ID_Calculo, {})
    toast.add({ severity: 'success', summary: 'Comisión autorizada', life: 2500 })
    await cargar()
  } catch (err) {
    const msg = err.response?.data?.message ?? 'Error al autorizar'
    toast.add({ severity: 'error', summary: msg, life: 4000 })
  } finally {
    guardando.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.det-grid { display:grid; grid-template-columns:1fr 340px; gap:1.25rem; }
.col-left,.col-right { display:flex; flex-direction:column; gap:1rem; }
.mb-3 { margin-bottom:1rem; }
.info-grid { display:grid; grid-template-columns:auto 1fr; gap:0.4rem 1rem; }
.lbl  { font-size:0.8rem; font-weight:600; color:var(--p-text-muted-color); white-space:nowrap; }
.mb-1 { margin-bottom:0.3rem; }
.texto{ margin:0; line-height:1.6; }
.acciones { display:flex; flex-direction:column; gap:0.8rem; }
.field    { display:flex; flex-direction:column; gap:0.35rem; }
.field label { font-size:0.875rem; font-weight:500; }
.w-full { width:100%; }
.txt-sm { font-size:0.82rem; }
.muted  { color:var(--p-text-muted-color); }
.verde  { color:var(--p-green-500, #22c55e); font-weight:600; }
.rojo   { color:var(--p-red-500, #ef4444); font-weight:600; }
.total-row { display:flex; justify-content:flex-end; align-items:center; gap:1rem;
  padding:0.75rem 0.5rem 0; border-top:1px solid var(--p-surface-200); margin-top:0.5rem; }
.total-valor { font-size:1.1rem; font-weight:700; }
.center-spinner { display:flex; justify-content:center; padding:4rem; }
@media(max-width:900px) { .det-grid { grid-template-columns:1fr; } }
</style>
