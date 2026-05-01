<template>
  <form @submit.prevent="guardar">

    <!-- Encabezado del pedido -->
    <div class="grid-2">
      <div class="field">
        <label>Unidad que pide *</label>
        <Select v-model="form.IdUnidadPedido" :options="vendedores"
          optionLabel="Nombre" optionValue="IdUnidad"
          class="w-full" placeholder="Selecciona ruta..."
          :loading="cargandoCats" filter />
      </div>

      <div class="field">
        <label>Supervisor *</label>
        <Select v-model="form.IdSupervisor" :options="supervisores"
          optionLabel="Nombre" optionValue="IdUnidad"
          class="w-full" placeholder="Selecciona supervisor..."
          :loading="cargandoCats" />
      </div>

      <div class="field">
        <label>Almacén *</label>
        <Select v-model="form.IdAlmacen" :options="almacenes"
          optionLabel="Nombre" optionValue="IdUnidad"
          class="w-full" placeholder="Selecciona almacén..."
          :loading="cargandoCats" />
      </div>

      <div class="field">
        <label>Día de entrega</label>
        <Select v-model="form.Dia"
          :options="[{l:'Lunes',v:'lu'},{l:'Martes',v:'ma'},{l:'Miércoles',v:'mi'},{l:'Jueves',v:'ju'},{l:'Viernes',v:'vi'},{l:'Sábado',v:'sa'}]"
          optionLabel="l" optionValue="v"
          class="w-full" placeholder="Opcional" showClear />
      </div>

      <div class="field">
        <label>Semana</label>
        <Select v-model="form.ID_Semana" :options="cats.semanas"
          optionLabel="label" optionValue="ID_Semana"
          class="w-full" placeholder="Opcional" showClear />
      </div>

      <div class="field">
        <label>Observaciones</label>
        <InputText v-model="form.ObserVen" class="w-full" placeholder="Nota del vendedor..." />
      </div>
    </div>

    <Divider />

    <!-- Detalle de artículos -->
    <div class="detalle-header">
      <h3 class="detalle-titulo">Artículos</h3>
      <Button icon="pi pi-plus" label="Agregar" size="small" outlined @click="agregarFila" />
    </div>

    <div v-for="(fila, i) in detalles" :key="i" class="fila-detalle">
      <Select v-model="fila.IdArticulo" :options="articulos"
        optionLabel="Nombre" optionValue="IdArticulo"
        placeholder="Artículo..." filter filterPlaceholder="Buscar..."
        :loading="cargandoArts" style="flex:2" />
      <InputNumber v-model="fila.CanPzPed" :min="1" placeholder="Piezas"
        :useGrouping="false" style="width:90px" />
      <InputNumber v-model="fila.VolPed" :min="0" :maxFractionDigits="3"
        placeholder="Volumen" style="width:110px" />
      <Button icon="pi pi-trash" text rounded severity="danger" size="small"
        @click="detalles.splice(i, 1)" :disabled="detalles.length === 1" />
    </div>

    <Message v-if="error" severity="error" :closable="false" class="mt-2">{{ error }}</Message>

    <div class="form-footer">
      <Button type="button" label="Cancelar" text @click="emit('cancelar')" />
      <Button type="submit" label="Crear pedido" icon="pi pi-save" :loading="guardando" />
    </div>
  </form>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useCatalogosStore } from '../../stores/catalogos'
import { pedApi } from '../../api/ped'
import client    from '../../api/client'
import InputText   from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select      from 'primevue/select'
import Button      from 'primevue/button'
import Message     from 'primevue/message'
import Divider     from 'primevue/divider'

const emit = defineEmits(['guardado', 'cancelar'])
const cats = useCatalogosStore()

const guardando    = ref(false)
const cargandoCats = ref(false)
const cargandoArts = ref(false)
const error        = ref('')

const vendedores  = ref([])
const supervisores= ref([])
const almacenes   = ref([])
const articulos   = ref([])

const form = reactive({
  IdUnidadPedido: null,
  IdSupervisor:   null,
  IdAlmacen:      null,
  IdEstado:       null,   // se asigna por defecto PENDIENTE en el backend
  Dia:            null,
  ID_Semana:      null,
  ObserVen:       '',
})

const detalles = reactive([{ IdArticulo: null, CanPzPed: 1, VolPed: 0 }])

function agregarFila() {
  detalles.push({ IdArticulo: null, CanPzPed: 1, VolPed: 0 })
}

async function cargarCatalogos() {
  cargandoCats.value = true
  cargandoArts.value = true
  try {
    const [resUnidades, resArts] = await Promise.all([
      client.get('/ped/unidades'),
      client.get('/catalogos/articulos'),
    ])
    const unidades = resUnidades.data?.data ?? resUnidades.data ?? []
    vendedores.value   = unidades.filter(u => u.TipoNombre === 'VENDEDOR')
    supervisores.value = unidades.filter(u => u.TipoNombre === 'SUPERVISOR')
    almacenes.value    = unidades.filter(u => u.TipoNombre === 'ALMACEN')
    articulos.value    = resArts.data?.data ?? resArts.data ?? []
  } catch {
    // silencioso — se mostrará vacío
  } finally {
    cargandoCats.value = false
    cargandoArts.value = false
  }
}

async function guardar() {
  error.value = ''
  const lineasValidas = detalles.filter(d => d.IdArticulo && d.CanPzPed > 0)

  if (!form.IdUnidadPedido || !form.IdSupervisor || !form.IdAlmacen) {
    error.value = 'Unidad, supervisor y almacén son requeridos.'
    return
  }
  if (lineasValidas.length === 0) {
    error.value = 'Agrega al menos un artículo válido.'
    return
  }

  // Obtener IdEstado PENDIENTE
  const estadoPendiente = await client.get('/ped/estados')
    .then(r => (r.data?.data ?? r.data ?? []).find(e => e.Nombre === 'PENDIENTE')?.IdEstado ?? null)
    .catch(() => null)

  guardando.value = true
  try {
    await pedApi.crearPedido({
      ...form,
      IdEstado: estadoPendiente,
      detalles: lineasValidas,
    })
    emit('guardado')
  } catch (e) {
    const msgs = e.response?.data?.errors
    error.value = msgs
      ? Object.values(msgs).flat().join(' ')
      : e.response?.data?.message ?? 'Error al guardar.'
  } finally {
    guardando.value = false
  }
}

onMounted(cargarCatalogos)
</script>

<style scoped>
.grid-2       { display:grid; grid-template-columns:1fr 1fr; gap:0 1rem; }
.field        { display:flex; flex-direction:column; gap:0.4rem; margin-bottom:0.85rem; }
.field label  { font-size:0.875rem; font-weight:500; }
.detalle-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.6rem; }
.detalle-titulo { margin:0; font-size:0.95rem; font-weight:600; }
.fila-detalle { display:flex; gap:0.5rem; align-items:center; margin-bottom:0.4rem; }
.form-footer  { display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1rem; }
.mt-2         { margin-top:0.75rem; }
</style>
