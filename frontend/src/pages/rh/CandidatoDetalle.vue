<template>
  <div v-if="candidato">
    <PageHeader :titulo="candidato.NombreCompleto"
      subtitulo="Candidato — RH" icon="pi-user" :back="true">
      <EstatusTag :valor="candidato.estatus?.Nombre" modulo="rh-candidato" />
    </PageHeader>

    <div class="det-grid">
      <!-- Columna izquierda -->
      <div class="col-left">
        <!-- Datos generales -->
        <Card class="mb-3">
          <template #title>Datos del candidato</template>
          <template #content>
            <div class="info-grid">
              <span class="lbl">Correo</span>
              <span>{{ candidato.Correo ?? '—' }}</span>
              <span class="lbl">Teléfono</span>
              <span>{{ candidato.Telefono ?? '—' }}</span>
              <span class="lbl">Vacante</span>
              <span>{{ candidato.vacante?.Titulo ?? '—' }}</span>
              <span class="lbl">Fuente</span>
              <span>{{ candidato.Fuente ?? '—' }}</span>
              <span class="lbl">Postulación</span>
              <span>{{ formatFecha(candidato.FechaPostulacion) }}</span>
            </div>
            <template v-if="candidato.Observaciones">
              <Divider />
              <p class="lbl mb-1">Observaciones</p>
              <p class="texto">{{ candidato.Observaciones }}</p>
            </template>
          </template>
        </Card>

        <AdjuntosPanel
          modulo="rh"
          entidad="candidato_cv"
          :idRef="candidato.ID_Candidato"
          titulo="CV y documentos"
          accept=".pdf,.doc,.docx"
        />

        <!-- Entrevistas -->
        <Card class="mb-3">
          <template #title>
            <div class="row-between">
              <span>Entrevistas</span>
              <Button label="Agendar" icon="pi pi-plus" size="small" text @click="dlgEntrevista = true" />
            </div>
          </template>
          <template #content>
            <div v-for="e in entrevistas" :key="e.ID_Entrevista" class="entrevista-item">
              <div class="row-between">
                <span class="fw">{{ e.TipoEntrevista ?? 'Entrevista' }}</span>
                <Tag :value="e.Resultado ?? 'Pendiente'"
                  :severity="e.Resultado === 'APROBADO' ? 'success' : e.Resultado === 'RECHAZADO' ? 'danger' : 'info'" />
              </div>
              <span class="txt-sm muted">{{ formatFecha(e.FechaEntrevista) }}</span>
              <p v-if="e.Comentarios" class="texto mt-xs">{{ e.Comentarios }}</p>
            </div>
            <p v-if="!entrevistas.length" class="sin-items">Sin entrevistas agendadas.</p>
          </template>
        </Card>

        <!-- Oferta laboral -->
        <Card v-if="ofertaActual">
          <template #title>Oferta Laboral</template>
          <template #content>
            <div class="info-grid">
              <span class="lbl">Salario ofertado</span>
              <span>{{ formatMoneda(ofertaActual.SalarioOfertado) }}</span>
              <span class="lbl">Fecha ingreso</span>
              <span>{{ ofertaActual.FechaIngreso ? formatFecha(ofertaActual.FechaIngreso) : '—' }}</span>
              <span class="lbl">Estatus</span>
              <span>{{ ofertaActual.Estatus ?? 'ENVIADA' }}</span>
            </div>
          </template>
        </Card>
      </div>

      <!-- Columna derecha: acciones -->
      <div class="col-right">
        <Card class="mb-3">
          <template #title>Cambiar estatus</template>
          <template #content>
            <div class="acciones">
              <div class="field">
                <Select v-model="nuevoEstatusId" :options="cats.estatusCandidato"
                  optionLabel="Nombre" optionValue="ID_EstatusCandidato"
                  placeholder="Selecciona estatus..." class="w-full" />
              </div>
              <div class="field">
                <Textarea v-model="motivoEstatus" rows="2" class="w-full"
                  placeholder="Motivo / comentario (opcional)..." />
              </div>
              <Button label="Actualizar" icon="pi pi-refresh" :loading="guardandoEstatus"
                @click="cambiarEstatus" class="w-full" />
            </div>
          </template>
        </Card>

        <Card v-if="puedeOferta">
          <template #title>Enviar oferta</template>
          <template #content>
            <div class="acciones">
              <div class="field">
                <label>Salario ofertado</label>
                <InputNumber v-model="oferta.SalarioOfertado" mode="currency" currency="MXN"
                  locale="es-MX" class="w-full" />
              </div>
              <div class="field">
                <label>Fecha de ingreso</label>
                <DatePicker v-model="oferta.FechaIngreso" dateFormat="dd/mm/yy" class="w-full" />
              </div>
              <div class="field">
                <label>Fecha de vencimiento</label>
                <DatePicker v-model="oferta.FechaVencimiento" dateFormat="dd/mm/yy" class="w-full" />
              </div>
              <Button label="Enviar oferta" icon="pi pi-send" :loading="enviandoOferta"
                @click="enviarOferta" class="w-full" />
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Dialog agendar entrevista -->
    <Dialog v-model:visible="dlgEntrevista" header="Agendar entrevista" modal style="width:440px">
      <div class="acciones p-1">
        <div class="field">
          <label>Tipo de entrevista *</label>
          <Select v-model="nuevaEntrevista.TipoEntrevista"
            :options="tiposEntrevista" optionLabel="label" optionValue="value"
            placeholder="Selecciona tipo..." class="w-full" />
        </div>
        <div class="field">
          <label>Fecha y hora *</label>
          <DatePicker v-model="nuevaEntrevista.FechaEntrevista" showTime hourFormat="24"
            dateFormat="dd/mm/yy" class="w-full" />
        </div>
        <div class="field">
          <label>Entrevistador</label>
          <Select v-model="nuevaEntrevista.ID_UsuarioEntrevistador"
            :options="cats.usuarios" optionLabel="Nombre" optionValue="ID_Usuario"
            placeholder="(opcional)" showClear class="w-full" />
        </div>
        <div class="field">
          <label>Comentarios</label>
          <Textarea v-model="nuevaEntrevista.Comentarios" rows="2" class="w-full" />
        </div>
        <div class="row-between mt-1">
          <Button label="Cancelar" severity="secondary" text @click="dlgEntrevista = false" />
          <Button label="Agendar" icon="pi pi-calendar-plus" :loading="agendando"
            @click="agendarEntrevista" />
        </div>
      </div>
    </Dialog>
  </div>
  <div v-else-if="cargando" class="center-spinner"><ProgressSpinner /></div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { rhApi } from '../../api/rh'
import { formatFecha, formatMoneda } from '../../utils/formato'
import PageHeader from '../../components/shared/PageHeader.vue'
import EstatusTag from '../../components/shared/EstatusTag.vue'
import AdjuntosPanel from '../../components/shared/AdjuntosPanel.vue'
import Card        from 'primevue/card'
import Button      from 'primevue/button'
import Select      from 'primevue/select'
import Textarea    from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import DatePicker  from 'primevue/datepicker'
import Divider     from 'primevue/divider'
import Dialog      from 'primevue/dialog'
import Tag         from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'

const route = useRoute()
const toast = useToast()
const cats  = useCatalogosStore()

const candidato        = ref(null)
const entrevistas      = ref([])
const cargando         = ref(false)
const nuevoEstatusId   = ref(null)
const motivoEstatus    = ref('')
const guardandoEstatus = ref(false)
const dlgEntrevista    = ref(false)
const agendando        = ref(false)
const enviandoOferta   = ref(false)

const entrevistaVacia = () => ({
  FechaEntrevista: null, TipoEntrevista: null,
  ID_UsuarioEntrevistador: null, Comentarios: '',
})
const nuevaEntrevista = ref(entrevistaVacia())
const oferta = ref({ SalarioOfertado: null, FechaIngreso: null, FechaVencimiento: null })

const ofertaActual = computed(() => candidato.value?.ofertas?.[0] ?? null)

const tiposEntrevista = [
  { label: 'Presencial',  value: 'PRESENCIAL' },
  { label: 'Virtual',     value: 'VIRTUAL' },
  { label: 'Telefónica',  value: 'TELEFONICA' },
  { label: 'Técnica',     value: 'TECNICA' },
  { label: 'RH',          value: 'RH' },
  { label: 'Panel',       value: 'PANEL' },
]

const puedeOferta = computed(() =>
  candidato.value && ['SELECCIONADO', 'ENTREVISTADO'].includes(candidato.value.estatus?.Nombre)
)

async function cargar() {
  cargando.value = true
  try {
    const res = await rhApi.verCandidato(route.params.id)
    candidato.value = res.data
    nuevoEstatusId.value = candidato.value.ID_EstatusCandidato
    entrevistas.value = candidato.value.entrevistas ?? []
  } finally {
    cargando.value = false
  }
}

async function cambiarEstatus() {
  if (!nuevoEstatusId.value) return
  guardandoEstatus.value = true
  try {
    await rhApi.cambiarEstatusCandidato(candidato.value.ID_Candidato, {
      ID_EstatusCandidato: nuevoEstatusId.value,
      Comentario: motivoEstatus.value || undefined,
    })
    toast.add({ severity: 'success', summary: 'Estatus actualizado', life: 2500 })
    await cargar()
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cambiar estatus', life: 3000 })
  } finally {
    guardandoEstatus.value = false
  }
}

async function agendarEntrevista() {
  agendando.value = true
  try {
    await rhApi.agendarEntrevista({
      ID_Candidato: candidato.value.ID_Candidato,
      ID_Vacante:   candidato.value.ID_Vacante,
      ...nuevaEntrevista.value,
    })
    toast.add({ severity: 'success', summary: 'Entrevista agendada', life: 2500 })
    dlgEntrevista.value = false
    nuevaEntrevista.value = entrevistaVacia()
    await cargar()
  } catch {
    toast.add({ severity: 'error', summary: 'Error al agendar', life: 3000 })
  } finally {
    agendando.value = false
  }
}

async function enviarOferta() {
  enviandoOferta.value = true
  try {
    await rhApi.enviarOferta({
      ID_Candidato: candidato.value.ID_Candidato,
      ID_Vacante:   candidato.value.ID_Vacante,
      ...oferta.value,
    })
    toast.add({ severity: 'success', summary: 'Oferta enviada', life: 2500 })
    await cargar()
  } catch {
    toast.add({ severity: 'error', summary: 'Error al enviar oferta', life: 3000 })
  } finally {
    enviandoOferta.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.det-grid { display:grid; grid-template-columns:1fr 360px; gap:1.25rem; }
.col-left,.col-right { display:flex; flex-direction:column; gap:1rem; }
.mb-3 { margin-bottom:1rem; }
.mt-1 { margin-top:0.5rem; }
.mt-xs{ margin-top:0.25rem; }
.info-grid { display:grid; grid-template-columns:auto 1fr; gap:0.4rem 1rem; }
.lbl  { font-size:0.8rem; font-weight:600; color:var(--p-text-muted-color); white-space:nowrap; }
.mb-1 { margin-bottom:0.3rem; }
.texto{ margin:0; line-height:1.6; }
.fw   { font-weight:600; }
.muted{ color:var(--p-text-muted-color); }
.txt-sm { font-size:0.85rem; }
.acciones { display:flex; flex-direction:column; gap:0.8rem; }
.field    { display:flex; flex-direction:column; gap:0.35rem; }
.field label { font-size:0.875rem; font-weight:500; }
.w-full { width:100%; }
.row-between { display:flex; justify-content:space-between; align-items:center; }
.entrevista-item { display:flex; flex-direction:column; gap:0.2rem;
  padding:0.65rem 0.8rem; border-radius:8px; background:var(--p-surface-100); margin-bottom:0.5rem; }
.cv-link { display:inline-flex; align-items:center; gap:0.4rem; color:var(--p-primary-color); font-size:0.9rem; }
.sin-items { text-align:center; color:var(--p-text-muted-color); padding:1rem 0; }
.center-spinner { display:flex; justify-content:center; padding:4rem; }
.p-1 { padding:0.25rem; }
@media(max-width:900px) { .det-grid { grid-template-columns:1fr; } }
</style>
