<template>
  <div v-if="vacante">
    <PageHeader :titulo="`Vacante — ${vacante.Folio ?? '#' + vacante.ID_Vacante}`"
      subtitulo="Recursos Humanos" icon="pi-briefcase" :back="true">
      <EstatusTag :valor="vacante.Estatus" modulo="rh-vacante" />
    </PageHeader>

    <div class="det-grid">
      <!-- Columna izquierda: info + candidatos -->
      <div class="col-left">
        <Card class="mb-3">
          <template #title>Información</template>
          <template #content>
            <div class="info-grid">
              <span class="lbl">Título</span>
              <span>{{ vacante.Titulo }}</span>
              <span class="lbl">Área</span>
              <span>{{ vacante.area?.Nombre }}</span>
              <span class="lbl">Sucursal</span>
              <span>{{ vacante.sucursal?.Nombre }}</span>
              <span class="lbl">Puesto</span>
              <span>{{ vacante.puesto?.Descripcion ?? '—' }}</span>
              <span class="lbl">Posiciones</span>
              <span>{{ vacante.NumeroPosiciones }}</span>
              <span class="lbl">Rango salarial</span>
              <span>{{ vacante.SalarioMin || vacante.SalarioMax
                ? `${vacante.SalarioMin ? formatMoneda(vacante.SalarioMin) : '?'} – ${vacante.SalarioMax ? formatMoneda(vacante.SalarioMax) : '?'}`
                : '—' }}</span>
              <span class="lbl">Creada</span>
              <span>{{ formatFecha(vacante.FechaCreacion) }}</span>
              <span class="lbl">Cierre</span>
              <span>{{ vacante.FechaCierre ? formatFecha(vacante.FechaCierre) : '—' }}</span>
            </div>
            <template v-if="vacante.Descripcion">
              <Divider />
              <p class="lbl mb-1">Descripción</p>
              <p class="texto">{{ vacante.Descripcion }}</p>
            </template>
            <template v-if="vacante.Requisitos">
              <Divider />
              <p class="lbl mb-1">Requisitos</p>
              <p class="texto">{{ vacante.Requisitos }}</p>
            </template>
          </template>
        </Card>

        <!-- Candidatos de esta vacante (cargados con show()) -->
        <Card>
          <template #title>
            <div class="row-between">
              <span>Candidatos</span>
              <Badge :value="vacante.candidatos?.length ?? 0" />
            </div>
          </template>
          <template #content>
            <DataTable :value="vacante.candidatos ?? []" rowHover stripedRows size="small">
              <Column field="NombreCompleto" header="Nombre" />
              <Column header="Estatus" style="width:140px">
                <template #body="{ data }">
                  <EstatusTag :valor="data.estatus?.Nombre" modulo="rh-candidato" />
                </template>
              </Column>
              <Column style="width:60px">
                <template #body="{ data }">
                  <Button icon="pi pi-eye" text rounded size="small"
                    @click="$router.push(`/rh/candidatos/${data.ID_Candidato}`)" />
                </template>
              </Column>
            </DataTable>
            <p v-if="!vacante.candidatos?.length" class="sin-items">Sin candidatos registrados.</p>
          </template>
        </Card>
      </div>

      <!-- Columna derecha: acciones -->
      <div class="col-right">
        <Card>
          <template #title>Acciones</template>
          <template #content>
            <div class="acciones">
              <div class="field">
                <label>Cambiar estatus</label>
                <div class="row-gap">
                  <Select v-model="nuevoEstatus" :options="estatusOpciones"
                    optionLabel="Nombre" optionValue="val"
                    placeholder="Estatus..." class="flex-1" />
                  <Button icon="pi pi-refresh" :loading="guardando"
                    @click="cambiarEstatus" v-tooltip="'Actualizar'" />
                </div>
              </div>
              <template v-if="esCierre">
                <div class="field">
                  <label>Motivo de cierre</label>
                  <Textarea v-model="motivoCierre" rows="3" class="w-full"
                    placeholder="Describe el motivo..." />
                </div>
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
import { rhApi } from '../../api/rh'
import { formatFecha, formatMoneda } from '../../utils/formato'
import PageHeader from '../../components/shared/PageHeader.vue'
import EstatusTag from '../../components/shared/EstatusTag.vue'
import Card     from 'primevue/card'
import Button   from 'primevue/button'
import Select   from 'primevue/select'
import Textarea from 'primevue/textarea'
import Divider  from 'primevue/divider'
import Badge    from 'primevue/badge'
import DataTable from 'primevue/datatable'
import Column   from 'primevue/column'
import ProgressSpinner from 'primevue/progressspinner'

const route = useRoute()
const toast = useToast()

const vacante      = ref(null)
const cargando     = ref(false)
const nuevoEstatus = ref(null)
const motivoCierre  = ref('')
const guardando     = ref(false)

const estatusOpciones = [
  { Nombre: 'Abierta',   val: 'ABIERTA' },
  { Nombre: 'Pausada',   val: 'PAUSADA' },
  { Nombre: 'Cerrada',   val: 'CERRADA' },
  { Nombre: 'Cancelada', val: 'CANCELADA' },
]

const esCierre = computed(() => ['CERRADA', 'CANCELADA'].includes(nuevoEstatus.value))

async function cargar() {
  cargando.value = true
  try {
    const res = await rhApi.verVacante(route.params.id)
    vacante.value = res.data
    nuevoEstatus.value = vacante.value.Estatus
  } finally {
    cargando.value = false
  }
}

async function cambiarEstatus() {
  if (!nuevoEstatus.value) return
  guardando.value = true
  try {
    await rhApi.cambiarEstatusVacante(vacante.value.ID_Vacante, {
      Estatus:    nuevoEstatus.value,
      Comentario: motivoCierre.value || undefined,
    })
    toast.add({ severity: 'success', summary: 'Estatus actualizado', life: 2500 })
    await cargar()
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cambiar estatus', life: 3000 })
  } finally {
    guardando.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.det-grid { display:grid; grid-template-columns:1fr 380px; gap:1.25rem; }
.col-left,.col-right { display:flex; flex-direction:column; gap:1rem; }
.mb-3 { margin-bottom:1rem; }
.info-grid { display:grid; grid-template-columns:auto 1fr; gap:0.4rem 1rem; }
.lbl      { font-size:0.8rem; font-weight:600; color:var(--p-text-muted-color); white-space:nowrap; }
.mb-1     { margin-bottom:0.3rem; }
.texto    { margin:0; line-height:1.6; }
.acciones { display:flex; flex-direction:column; gap:0.8rem; }
.field    { display:flex; flex-direction:column; gap:0.35rem; }
.field label { font-size:0.875rem; font-weight:500; }
.w-full   { width:100%; }
.flex-1   { flex:1; }
.row-gap  { display:flex; align-items:center; gap:0.5rem; }
.row-between { display:flex; justify-content:space-between; align-items:center; }
.sin-items { text-align:center; color:var(--p-text-muted-color); padding:1rem 0; }
.center-spinner { display:flex; justify-content:center; padding:4rem; }
@media(max-width:900px) { .det-grid { grid-template-columns:1fr; } }
</style>
