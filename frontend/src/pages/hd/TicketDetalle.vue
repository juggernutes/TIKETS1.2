<template>
  <div v-if="ticket">
    <PageHeader :titulo="`Ticket — ${ticket.SerieFolio ?? '#' + ticket.ID_Ticket}`"
      subtitulo="Help Desk" icon="pi-ticket" :back="true">
      <div class="header-tags">
        <Tag v-if="ticket.FueraDeSLA" value="SLA Vencido" severity="danger" icon="pi pi-clock" />
        <EstatusTag :valor="ticket.estatus?.Nombre" modulo="hd" />
      </div>
    </PageHeader>

    <div class="det-grid">
      <!-- Columna izquierda -->
      <div class="col-left">
        <Card class="mb-3">
          <template #title>Información</template>
          <template #content>
            <div class="info-grid">
              <span class="lbl">Empleado</span>
              <span>{{ ticket.empleado?.Nombre ?? ticket.Numero_Empleado }}</span>
              <span class="lbl">Sistema</span>
              <span>{{ ticket.sistema?.Nombre }}</span>
              <span class="lbl">Tipo de error</span>
              <span>{{ ticket.error?.Descripcion }}</span>
              <span class="lbl">Área origen</span>
              <span>{{ ticket.areaOrigen?.Nombre }}</span>
              <span class="lbl">Área responsable</span>
              <span>{{ ticket.areaResponsable?.Nombre }}</span>
              <span class="lbl">Agente asignado</span>
              <span>{{ ticket.soporte?.Nombre ?? '—' }}</span>
              <span class="lbl">Proveedor</span>
              <span>{{ ticket.proveedor?.Nombre ?? '—' }}</span>
              <span class="lbl">Prioridad</span>
              <span>
                <Tag v-if="ticket.Prioridad" :value="ticket.Prioridad"
                  :severity="severidadPrioridad(ticket.Prioridad)" />
                <span v-else>—</span>
              </span>
              <template v-if="ticket.FechaLimite">
                <span class="lbl">Fecha límite SLA</span>
                <span :class="{ 'text-danger': ticket.FueraDeSLA }">
                  {{ formatFecha(ticket.FechaLimite) }}
                  <i v-if="ticket.FueraDeSLA" class="pi pi-exclamation-triangle ml-1" />
                </span>
              </template>
              <span class="lbl">Fecha reporte</span>
              <span>{{ formatFecha(ticket.FechaReporte) }}</span>
              <span class="lbl">Fecha asignación</span>
              <span>{{ ticket.FechaAsignacion ? formatFecha(ticket.FechaAsignacion) : '—' }}</span>
              <span class="lbl">Fecha solución</span>
              <span>{{ ticket.FechaSolucion ? formatFecha(ticket.FechaSolucion) : '—' }}</span>
            </div>
            <Divider />
            <p class="lbl mb-1">Descripción</p>
            <p class="texto">{{ ticket.Descripcion }}</p>
            <template v-if="ticket.DetalleSolucion">
              <Divider />
              <p class="lbl mb-1">Detalle de solución</p>
              <p class="texto">{{ ticket.DetalleSolucion }}</p>
            </template>
          </template>
        </Card>

        <!-- Encuesta: mostrar si ya existe -->
        <Card v-if="ticket.encuesta">
          <template #title>Satisfacción del usuario</template>
          <template #content>
            <Rating :modelValue="ticket.encuesta.Calificacion" readonly :cancel="false" />
            <p v-if="ticket.encuesta.Comentarios" class="texto mt-1">
              {{ ticket.encuesta.Comentarios }}
            </p>
          </template>
        </Card>

        <!-- Encuesta: registrar si el ticket está resuelto/cerrado y no tiene encuesta -->
        <Card v-else-if="ticketCerrado">
          <template #title>Calificar atención</template>
          <template #content>
            <p class="lbl mb-1">¿Cómo calificarías la atención recibida?</p>
            <Rating v-model="encuesta.Calificacion" :cancel="false" class="mb-2" />
            <Textarea v-model="encuesta.Comentarios" rows="3" class="w-full"
              placeholder="Comentarios opcionales..." />
            <div class="form-footer mt-1">
              <Button label="Enviar calificación" icon="pi pi-send" size="small"
                :disabled="!encuesta.Calificacion"
                :loading="enviandoEncuesta" @click="enviarEncuesta" />
            </div>
          </template>
        </Card>
      </div>

      <!-- Columna derecha -->
      <div class="col-right">
        <Card class="mb-3">
          <template #title>Acciones</template>
          <template #content>
            <div class="acciones">
              <div class="field">
                <label>Asignar agente</label>
                <div class="row-gap">
                  <Select v-model="agenteId" :options="agentesDelArea"
                    optionLabel="Nombre" optionValue="ID_Usuario"
                    placeholder="Selecciona un agente..." class="flex-1"
                    filter filterPlaceholder="Buscar..." showClear />
                  <Button icon="pi pi-check" :loading="guardandoAgente"
                    @click="asignarAgente" v-tooltip="'Asignar'" />
                </div>
              </div>

              <Divider />

              <div class="field">
                <label>Problema</label>
                <Select v-model="form.ID_Error" :options="cats.erroresHd"
                  optionLabel="Descripcion" optionValue="ID_Error"
                  placeholder="Selecciona el problema..." class="w-full"
                  filter filterPlaceholder="Buscar..." />
              </div>

              <div class="field">
                <label>Prioridad</label>
                <Select v-model="form.Prioridad" :options="prioridadOpciones"
                  optionLabel="label" optionValue="value"
                  placeholder="Selecciona prioridad..." class="w-full" />
              </div>

              <div class="field">
                <label>Área responsable</label>
                <Select v-model="form.ID_Area_Responsable" :options="cats.areas"
                  optionLabel="Nombre" optionValue="ID_Area"
                  placeholder="Selecciona el área..." class="w-full" />
              </div>

              <div class="field">
                <label>Cambiar estatus</label>
                <div class="row-gap">
                  <Select v-model="nuevoEstatus" :options="cats.estatusHd"
                    optionLabel="Nombre" optionValue="ID_Estatus"
                    placeholder="Estatus..." class="flex-1" />
                  <Button icon="pi pi-refresh" :loading="guardandoEstatus"
                    @click="cambiarEstatus" v-tooltip="'Actualizar'" />
                </div>
              </div>

              <div class="field">
                <label>Categoría de solución</label>
                <Select v-model="form.ID_Solucion" :options="cats.solucionesHd"
                  optionLabel="Descripcion" optionValue="ID_Solucion"
                  placeholder="Selecciona..." class="w-full" />
              </div>

              <div class="field">
                <label>Solución / seguimiento</label>
                <Textarea v-model="form.DetalleSolucion" rows="4" class="w-full"
                  placeholder="Captura el avance o la solución aplicada..." />
              </div>

              <div class="row-gap actions-row">
                <Button label="Guardar seguimiento" icon="pi pi-save"
                  :loading="guardandoEstatus" @click="cambiarEstatus" />
                <Button label="Cerrar ticket" icon="pi pi-lock" severity="success"
                  :disabled="ticketCerrado"
                  :loading="guardandoEstatus" @click="cerrarTicket" />
              </div>

              <Divider />

              <div class="field">
                <label>Proveedor</label>
                <Select v-model="proveedorId" :options="cats.proveedores"
                  optionLabel="Nombre" optionValue="ID_Proveedor"
                  placeholder="Selecciona proveedor..." class="w-full"
                  filter filterPlaceholder="Buscar..." />
              </div>

              <div class="field">
                <label>Seguimiento proveedor</label>
                <Textarea v-model="seguimientoProveedor" rows="3" class="w-full"
                  placeholder="Describe qué se envía o qué queda pendiente con el proveedor..." />
              </div>

              <Button label="Enviar a proveedor" icon="pi pi-send"
                severity="warn" :disabled="!proveedorId"
                :loading="enviandoProveedor" @click="enviarProveedor" />
            </div>
          </template>
        </Card>

        <!-- Comentarios -->
        <Card>
          <template #title>
            <div class="row-between">
              <span>Comentarios</span>
              <Badge :value="comentarios.length" />
            </div>
          </template>
          <template #content>
            <div class="comentarios-scroll">
              <div v-for="c in comentarios" :key="c.ID_Comentario"
                class="comentario" :class="{ interno: c.EsInterno }">
                <div class="comentario-head">
                  <span class="comentario-autor">
                    <i class="pi pi-user" /> {{ c.usuario?.Nombre ?? '—' }}
                  </span>
                  <span class="comentario-fecha">
                    <Tag v-if="c.EsInterno" value="Interno" severity="warn" class="mr-1" />
                    {{ formatFecha(c.Fecha) }}
                  </span>
                </div>
                <p class="comentario-texto">{{ c.Mensaje }}</p>
              </div>
              <p v-if="!comentarios.length" class="sin-items">Sin comentarios.</p>
            </div>

            <Divider />
            <div class="field">
              <Textarea v-model="nuevoComentario" rows="3" class="w-full"
                placeholder="Escribe un comentario..." />
            </div>
            <div class="row-between mt-1">
              <div class="row-gap">
                <Checkbox v-model="esInterno" :binary="true" inputId="interno" />
                <label for="interno" class="txt-sm">Nota interna</label>
              </div>
              <Button label="Enviar" icon="pi pi-send" size="small"
                :disabled="!nuevoComentario.trim()"
                :loading="enviandoComentario" @click="enviarComentario" />
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
  <div v-else-if="cargando" class="center-spinner">
    <ProgressSpinner />
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { useAuthStore }      from '../../stores/auth'
import { hdApi } from '../../api/hd'
import { formatFecha } from '../../utils/formato'
import PageHeader from '../../components/shared/PageHeader.vue'
import EstatusTag from '../../components/shared/EstatusTag.vue'
import Card            from 'primevue/card'
import Button          from 'primevue/button'
import Select          from 'primevue/select'
import Textarea        from 'primevue/textarea'
import Checkbox        from 'primevue/checkbox'
import Divider         from 'primevue/divider'
import Badge           from 'primevue/badge'
import Tag             from 'primevue/tag'
import Rating          from 'primevue/rating'
import ProgressSpinner from 'primevue/progressspinner'

const route = useRoute()
const toast = useToast()
const cats  = useCatalogosStore()
const auth  = useAuthStore()

const ticket             = ref(null)
const comentarios        = ref([])
const cargando           = ref(false)
const agenteId           = ref(null)
const nuevoEstatus       = ref(null)
const nuevoComentario    = ref('')
const esInterno          = ref(false)
const proveedorId        = ref(null)
const seguimientoProveedor = ref('')
const guardandoAgente    = ref(false)
const guardandoEstatus   = ref(false)
const enviandoProveedor  = ref(false)
const enviandoComentario = ref(false)
const enviandoEncuesta   = ref(false)
const form               = ref({
  ID_Error: null,
  Prioridad: null,
  ID_Area_Responsable: null,
  ID_Solucion: null,
  DetalleSolucion: '',
})
const encuesta           = reactive({ Calificacion: null, Comentarios: '' })

const prioridadOpciones = [
  { label: 'Crítica', value: 'CRITICA' },
  { label: 'Alta',    value: 'ALTA' },
  { label: 'Media',   value: 'MEDIA' },
  { label: 'Baja',    value: 'BAJA' },
]

const ticketCerrado = computed(() =>
  ['Resuelto', 'Cerrado'].includes(ticket.value?.estatus?.Nombre)
)

const estatusCerradoId = computed(() =>
  cats.estatusHd.find(e => e.Nombre === 'Cerrado')?.ID_Estatus
)

const agentesDelArea = computed(() => {
  if (!ticket.value?.ID_Area_Responsable) return cats.usuarios
  return cats.usuarios.filter(u => u.ID_Area === ticket.value.ID_Area_Responsable)
})

function severidadPrioridad(p) {
  return { CRITICA: 'danger', ALTA: 'warn', MEDIA: 'info', BAJA: 'secondary' }[p] ?? 'secondary'
}

async function cargar() {
  cargando.value = true
  try {
    const [t, c] = await Promise.all([
      hdApi.verTicket(route.params.id),
      hdApi.listarComentarios(route.params.id),
    ])
    ticket.value      = t.data
    comentarios.value = Array.isArray(c.data) ? c.data : c.data.data ?? []
    agenteId.value    = ticket.value.ID_Soporte
    nuevoEstatus.value= ticket.value.ID_Estatus
    proveedorId.value = ticket.value.ID_Proveedor
    seguimientoProveedor.value = ticket.value.SeguimientoProveedor ?? ''
    form.value = {
      ID_Error: ticket.value.ID_Error,
      Prioridad: ticket.value.Prioridad ?? 'MEDIA',
      ID_Area_Responsable: ticket.value.ID_Area_Responsable,
      ID_Solucion: ticket.value.ID_Solucion,
      DetalleSolucion: ticket.value.DetalleSolucion ?? '',
    }
  } finally {
    cargando.value = false
  }
}

async function asignarAgente() {
  if (!agenteId.value) return
  guardandoAgente.value = true
  try {
    await hdApi.asignarAgente(ticket.value.ID_Ticket, { ID_Soporte: agenteId.value })
    toast.add({ severity: 'success', summary: 'Agente asignado', life: 2500 })
    await cargar()
  } catch { toast.add({ severity: 'error', summary: 'Error al asignar agente', life: 3000 }) }
  finally  { guardandoAgente.value = false }
}

async function cambiarEstatus() {
  if (!nuevoEstatus.value) return
  guardandoEstatus.value = true
  try {
    await hdApi.actualizarEstatus(ticket.value.ID_Ticket, {
      ID_Estatus:      nuevoEstatus.value,
      ID_Error:        form.value.ID_Error || undefined,
      Prioridad:       form.value.Prioridad || undefined,
      ID_Area_Responsable: form.value.ID_Area_Responsable || undefined,
      ID_Solucion:     form.value.ID_Solucion || undefined,
      DetalleSolucion: form.value.DetalleSolucion || undefined,
    })
    toast.add({ severity: 'success', summary: 'Estatus actualizado', life: 2500 })
    await cargar()
  } catch { toast.add({ severity: 'error', summary: 'Error al cambiar estatus', life: 3000 }) }
  finally  { guardandoEstatus.value = false }
}

async function cerrarTicket() {
  if (!estatusCerradoId.value) return
  nuevoEstatus.value = estatusCerradoId.value
  await cambiarEstatus()
}

async function enviarProveedor() {
  if (!proveedorId.value) return
  enviandoProveedor.value = true
  try {
    await hdApi.enviarProveedor(ticket.value.ID_Ticket, {
      ID_Proveedor: proveedorId.value,
      SeguimientoProveedor: seguimientoProveedor.value || undefined,
    })
    toast.add({ severity: 'success', summary: 'Ticket enviado a proveedor', life: 2500 })
    await cargar()
  } catch {
    toast.add({ severity: 'error', summary: 'Error al enviar a proveedor', life: 3000 })
  } finally {
    enviandoProveedor.value = false
  }
}

async function enviarComentario() {
  enviandoComentario.value = true
  try {
    await hdApi.agregarComentario(ticket.value.ID_Ticket, {
      ID_Usuario: auth.usuario?.id,
      Mensaje:    nuevoComentario.value,
      EsInterno:  esInterno.value,
    })
    nuevoComentario.value = ''
    esInterno.value       = false
    const res = await hdApi.listarComentarios(ticket.value.ID_Ticket)
    comentarios.value = Array.isArray(res.data) ? res.data : res.data.data ?? []
  } catch { toast.add({ severity: 'error', summary: 'Error al enviar', life: 3000 }) }
  finally  { enviandoComentario.value = false }
}

async function enviarEncuesta() {
  enviandoEncuesta.value = true
  try {
    await hdApi.registrarEncuesta(ticket.value.ID_Ticket, {
      Calificacion: encuesta.Calificacion,
      Comentarios:  encuesta.Comentarios || undefined,
      ID_Usuario:   auth.usuario?.id,
    })
    toast.add({ severity: 'success', summary: '¡Gracias por tu calificación!', life: 3000 })
    await cargar()
  } catch (e) {
    toast.add({
      severity: 'error',
      summary: e.response?.data?.message ?? 'Error al enviar la encuesta',
      life: 3000,
    })
  } finally {
    enviandoEncuesta.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.det-grid { display:grid; grid-template-columns:1fr 1.3fr; gap:1.25rem; }
.col-left,.col-right { display:flex; flex-direction:column; gap:1rem; }
.mb-3 { margin-bottom:1rem; }
.mb-2 { margin-bottom:0.6rem; }
.mt-1 { margin-top:0.5rem; }
.mr-1 { margin-right:0.25rem; }
.ml-1 { margin-left:0.25rem; }

.header-tags { display:flex; align-items:center; gap:0.5rem; }

.info-grid { display:grid; grid-template-columns:auto 1fr; gap:0.4rem 1rem; }
.lbl       { font-size:0.8rem; font-weight:600; color:var(--p-text-muted-color); white-space:nowrap; }
.mb-1      { margin-bottom:0.3rem; }
.texto     { margin:0; line-height:1.6; }
.text-danger { color: var(--p-red-500, #ef4444); font-weight:600; }

.acciones  { display:flex; flex-direction:column; gap:0.8rem; }
.field     { display:flex; flex-direction:column; gap:0.35rem; }
.field label { font-size:0.875rem; font-weight:500; }
.w-full    { width:100%; }
.flex-1    { flex:1; }
.row-gap   { display:flex; align-items:center; gap:0.5rem; }
.row-between { display:flex; justify-content:space-between; align-items:center; }
.txt-sm    { font-size:0.85rem; cursor:pointer; }
.form-footer { display:flex; justify-content:flex-end; }

.comentarios-scroll { display:flex; flex-direction:column; gap:0.75rem;
                      max-height:340px; overflow-y:auto; padding-right:0.25rem; }
.comentario        { padding:0.7rem 0.85rem; border-radius:8px; background:var(--p-surface-100); }
.comentario.interno{ background:var(--p-amber-50,#fffbeb); border-left:3px solid var(--p-amber-400,#f59e0b); }
.comentario-head   { display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem; }
.comentario-autor  { font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:0.3rem; }
.comentario-fecha  { font-size:0.78rem; color:var(--p-text-muted-color); display:flex; align-items:center; gap:0.25rem; }
.comentario-texto  { margin:0; font-size:0.9rem; line-height:1.5; }
.sin-items         { text-align:center; color:var(--p-text-muted-color); padding:1rem 0; }
.center-spinner    { display:flex; justify-content:center; padding:4rem; }

@media(max-width:900px) { .det-grid { grid-template-columns:1fr; } }
</style>
