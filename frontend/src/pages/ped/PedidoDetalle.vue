<template>
  <div v-if="pedido">
    <PageHeader :titulo="`Pedido - ${pedido.FolioPedido ?? '#' + pedido.IdPedido}`"
      subtitulo="Pedidos" icon="pi-box" :back="true">
      <EstatusTag :valor="pedido.estado?.Nombre" modulo="ped" />
    </PageHeader>

    <div class="det-grid">
      <div class="col-left">
        <Card class="mb-3">
          <template #title>Información</template>
          <template #content>
            <div class="info-grid">
              <span class="lbl">Unidad</span>
              <span>{{ pedido.unidadPedido?.Nombre ?? pedido.IdUnidadPedido }}</span>
              <span class="lbl">Supervisor</span>
              <span>{{ pedido.supervisor?.Nombre ?? '-' }}</span>
              <span class="lbl">Almacén</span>
              <span>{{ pedido.almacen?.Nombre ?? '-' }}</span>
              <span class="lbl">Estado</span>
              <span>{{ pedido.estado?.Nombre ?? '-' }}</span>
              <span class="lbl">Fecha pedido</span>
              <span>{{ formatFecha(pedido.FechaPedido ?? pedido.created_at) }}</span>
              <span class="lbl">Peso pedido</span>
              <span>{{ numero(pedido.PedVolPed) }} kg</span>
              <span class="lbl">Peso autorizado</span>
              <span>{{ numero(pedido.PedVolApr) }} kg</span>
              <span class="lbl">Peso surtido</span>
              <span>{{ numero(pedido.PedVolSur) }} kg</span>
            </div>
          </template>
        </Card>

        <Card>
          <template #title>Artículos</template>
          <template #content>
            <DataTable :value="lineas" showGridlines size="small">
              <Column header="Artículo">
                <template #body="{ data }">
                  <div class="articulo-copy">
                    <strong>{{ data.articulo?.Nombre ?? data.IdArticulo }}</strong>
                    <span>{{ data.IdArticulo }}</span>
                  </div>
                </template>
              </Column>
              <Column header="Pedido" style="width:100px;text-align:right">
                <template #body="{ data }">{{ data.CanPzPed }}</template>
              </Column>
              <Column header="Autorizado" style="width:135px;text-align:right">
                <template #body="{ data }">
                  <InputNumber v-if="puedeAutorizar" v-model="data.CanPzAprEdit"
                    :min="0" :useGrouping="false" inputClass="text-right" @input="recalcular(data)" />
                  <span v-else>{{ data.CanPzApr ?? '-' }}</span>
                </template>
              </Column>
              <Column header="Surtido" style="width:135px;text-align:right">
                <template #body="{ data }">
                  <InputNumber v-if="puedeSurtir" v-model="data.CanPzSurEdit"
                    :min="0" :useGrouping="false" inputClass="text-right" @input="recalcular(data)" />
                  <span v-else>{{ data.CanPzSur ?? '-' }}</span>
                </template>
              </Column>
              <Column header="Peso/u" style="width:95px;text-align:right">
                <template #body="{ data }">{{ pesoUnitario(data).toFixed(3) }}</template>
              </Column>
              <Column header="Vol. etapa" style="width:110px;text-align:right">
                <template #body="{ data }">{{ volumenEtapa(data).toFixed(3) }}</template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>

      <div class="col-right">
        <Card>
          <template #title>Acciones</template>
          <template #content>
            <div class="acciones">
              <Message v-if="!puedeAutorizar && !puedeSurtir && !puedeCancelar" severity="info" :closable="false">
                Este pedido no tiene una acción pendiente para tu bandeja.
              </Message>

              <div v-if="puedeAutorizar || puedeSurtir" class="resumen-etapa">
                <span>Total de etapa</span>
                <strong>{{ totalEtapa.toFixed(3) }} kg</strong>
              </div>

              <div class="field">
                <label>Comentario</label>
                <Textarea v-model="comentario" rows="3" class="w-full" placeholder="Comentario opcional..." />
              </div>

              <Button v-if="puedeAutorizar" label="Autorizar pedido" icon="pi pi-check"
                :loading="guardando" @click="autorizar" class="w-full" />
              <Button v-if="puedeSurtir" label="Surtir pedido" icon="pi pi-box"
                :loading="guardando" @click="surtir" class="w-full" />
              <Button v-if="puedeCancelar" label="Cancelar pedido" icon="pi pi-times" severity="danger" outlined
                :loading="cancelando" @click="cancelar" class="w-full" />
            </div>
          </template>
        </Card>

        <AdjuntosPanel
          modulo="ped"
          entidad="pedido"
          :idRef="pedido.IdPedido"
          titulo="Evidencias"
        />
      </div>
    </div>
  </div>
  <div v-else-if="cargando" class="center-spinner"><ProgressSpinner /></div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../stores/auth'
import { pedApi } from '../../api/ped'
import { formatFecha } from '../../utils/formato'
import PageHeader from '../../components/shared/PageHeader.vue'
import EstatusTag from '../../components/shared/EstatusTag.vue'
import AdjuntosPanel from '../../components/shared/AdjuntosPanel.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ProgressSpinner from 'primevue/progressspinner'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'

const route = useRoute()
const toast = useToast()
const auth = useAuthStore()

const pedido = ref(null)
const lineas = ref([])
const cargando = ref(false)
const guardando = ref(false)
const cancelando = ref(false)
const comentario = ref('')

const estadoNormalizado = computed(() => String(pedido.value?.estado?.Nombre ?? '').toUpperCase())
const puedeAutorizar = computed(() => auth.puede('ped.pedidos.autorizar') && ['CAPTURADO', 'PENDIENTE'].includes(estadoNormalizado.value))
const puedeSurtir = computed(() => auth.puede('ped.pedidos.surtir') && estadoNormalizado.value === 'AUTORIZADO')
const puedeCancelar = computed(() => auth.puede('ped.pedidos.cancelar') && !['CANCELADO', 'SURTIDO'].includes(estadoNormalizado.value))
const totalEtapa = computed(() => lineas.value.reduce((sum, l) => sum + volumenEtapa(l), 0))

function pesoUnitario(linea) {
  return Number(linea.articulo?.Peso ?? 0)
}

function volumenEtapa(linea) {
  if (puedeAutorizar.value) return Number(linea.VolAprEdit ?? 0)
  if (puedeSurtir.value) return Number(linea.VolSurEdit ?? 0)
  return Number(linea.VolSur ?? linea.VolApr ?? linea.VolPed ?? 0)
}

function numero(valor) {
  return Number(valor ?? 0).toFixed(3)
}

function prepararLineas(detalles) {
  lineas.value = (detalles ?? []).map(det => {
    const peso = Number(det.articulo?.Peso ?? 0)
    const canApr = Number(det.CanPzApr ?? det.CanPzPed ?? 0)
    const canSur = Number(det.CanPzSur ?? det.CanPzApr ?? det.CanPzPed ?? 0)
    return {
      ...det,
      CanPzAprEdit: canApr,
      VolAprEdit: Number((canApr * peso).toFixed(3)),
      CanPzSurEdit: canSur,
      VolSurEdit: Number((canSur * peso).toFixed(3)),
    }
  })
}

function recalcular(linea) {
  const peso = pesoUnitario(linea)
  if (puedeAutorizar.value) {
    linea.VolAprEdit = Number((Number(linea.CanPzAprEdit ?? 0) * peso).toFixed(3))
  }
  if (puedeSurtir.value) {
    linea.VolSurEdit = Number((Number(linea.CanPzSurEdit ?? 0) * peso).toFixed(3))
  }
}

async function cargar() {
  cargando.value = true
  try {
    const res = await pedApi.verPedido(route.params.id)
    pedido.value = res.data
    prepararLineas(res.data.detalles)
  } finally {
    cargando.value = false
  }
}

async function autorizar() {
  guardando.value = true
  try {
    await pedApi.autorizar(pedido.value.IdPedido, {
      ObserSup: comentario.value || undefined,
      detalles: lineas.value.map(l => ({
        IdArticulo: l.IdArticulo,
        CanPzApr: Number(l.CanPzAprEdit ?? 0),
        VolApr: Number(l.VolAprEdit ?? 0),
      })),
    })
    toast.add({ severity: 'success', summary: 'Pedido autorizado', life: 2500 })
    await cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error al autorizar', life: 3500 })
  } finally {
    guardando.value = false
  }
}

async function surtir() {
  guardando.value = true
  try {
    await pedApi.surtir(pedido.value.IdPedido, {
      ObserAlm: comentario.value || undefined,
      detalles: lineas.value.map(l => ({
        IdArticulo: l.IdArticulo,
        CanPzSur: Number(l.CanPzSurEdit ?? 0),
        VolSur: Number(l.VolSurEdit ?? 0),
      })),
    })
    toast.add({ severity: 'success', summary: 'Pedido surtido', life: 2500 })
    await cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error al surtir', life: 3500 })
  } finally {
    guardando.value = false
  }
}

async function cancelar() {
  cancelando.value = true
  try {
    await pedApi.cancelar(pedido.value.IdPedido, { Notas: comentario.value || undefined })
    toast.add({ severity: 'success', summary: 'Pedido cancelado', life: 2500 })
    await cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error al cancelar', life: 3500 })
  } finally {
    cancelando.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.det-grid { display:grid; grid-template-columns:1fr 340px; gap:1.25rem; }
.col-left,.col-right { display:flex; flex-direction:column; gap:1rem; }
.mb-3 { margin-bottom:1rem; }
.info-grid { display:grid; grid-template-columns:auto 1fr; gap:0.45rem 1rem; }
.lbl { font-size:0.8rem; font-weight:700; color:var(--app-muted); white-space:nowrap; }
.acciones { display:flex; flex-direction:column; gap:0.8rem; }
.field { display:flex; flex-direction:column; gap:0.35rem; }
.field label { font-size:0.875rem; font-weight:600; }
.w-full { width:100%; }
.center-spinner { display:flex; justify-content:center; padding:4rem; }
.articulo-copy { display:flex; flex-direction:column; gap:0.1rem; }
.articulo-copy span { color:var(--app-muted); font-size:0.78rem; }
.resumen-etapa {
  display:flex;
  justify-content:space-between;
  gap:1rem;
  padding:0.75rem;
  border-radius:12px;
  background:rgba(255,255,255,0.05);
}
.resumen-etapa span { color:var(--app-muted); }
@media(max-width:900px) { .det-grid { grid-template-columns:1fr; } }
</style>
