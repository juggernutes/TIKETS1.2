<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-file-check" /> Checklist DOC</h1>
    </div>

    <Card class="mb-3">
      <template #content>
        <div class="filtros-row">
          <Select v-model="filtros.id_semana" :options="cats.semanas"
            optionLabel="label" optionValue="ID_Semana"
            placeholder="Semana" showClear style="min-width:180px" />
          <Button label="Buscar" icon="pi pi-search" @click="cargar" />
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable :value="bases" :loading="cargando" stripedRows size="small"
          paginator :rows="30">
          <Column header="Empleado">
            <template #body="{ data }">
              {{ data.Numero_Empleado }} - {{ data.empleado?.Nombre ?? '-' }}
            </template>
          </Column>
          <Column field="Ruta" header="Ruta" style="width:90px" />
          <Column field="Canal" header="Canal" style="width:110px" />
          <Column header="DOC capturado" style="width:140px">
            <template #body="{ data }">
              <Tag
                :value="data.resultados_doc_count ? 'Capturado' : 'Pendiente'"
                :severity="data.resultados_doc_count ? 'success' : 'warn'" />
            </template>
          </Column>
          <Column style="width:120px">
            <template #body="{ data }">
              <Button icon="pi pi-pencil" label="Capturar" size="small" text
                @click="abrirCaptura(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="dlg"
      :header="`DOC - ${baseSel?.empleado?.Nombre ?? ''}`"
      modal style="width:520px">
      <div class="doc-grid">
        <div v-for="concepto in CONCEPTOS_DOC" :key="concepto.clave" class="doc-row">
          <label class="doc-label">{{ concepto.clave }} - {{ concepto.label }}</label>
          <Checkbox v-model="formDoc[concepto.clave]" binary />
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" text @click="dlg = false" />
        <Button label="Guardar checklist" icon="pi pi-save" severity="success"
          :loading="guardando" @click="guardar" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { comApi } from '../../api/com'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'

const toast = useToast()
const cats = useCatalogosStore()

const bases = ref([])
const cargando = ref(false)
const guardando = ref(false)
const dlg = ref(false)
const baseSel = ref(null)

const filtros = reactive({ id_semana: null })

const CONCEPTOS_DOC = [
  { clave: 'CHK', label: 'Check list de unidades' },
  { clave: 'SPH', label: 'Smartphone / impresora / licencia' },
  { clave: 'ACO', label: 'Acompanamiento supervisor' },
  { clave: 'LIQ', label: 'Liquidacion perfecta' },
  { clave: 'MES', label: 'Mesa de control' },
  { clave: 'REP', label: 'Reporte liq. mayor $600' },
  { clave: 'PRO', label: 'Check list promotoria' },
  { clave: 'MER', label: 'Mercadeo tiendas' },
]

const formDoc = reactive(Object.fromEntries(CONCEPTOS_DOC.map(c => [c.clave, false])))

function subIdDoc(clave) {
  const indicador = cats.indicadores.find(i => i.Clave === 'DOC')
  if (!indicador) return null
  return cats.subIndicadores.find(s =>
    s.ID_Indicador === indicador.ID_Indicador && s.Clave === clave
  )?.ID_SubIndicador ?? null
}

async function cargar() {
  cargando.value = true
  try {
    const params = Object.fromEntries(
      Object.entries(filtros).filter(([, v]) => v != null)
    )
    const { data } = await comApi.listarBaseCalculo({ ...params, per_page: 100 })
    bases.value = data.data ?? data
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function abrirCaptura(base) {
  baseSel.value = base
  for (const c of CONCEPTOS_DOC) {
    formDoc[c.clave] = Boolean(
      base.resultados_doc?.find(d => d.sub_indicador?.Clave === c.clave)?.Cumplido
    )
  }
  dlg.value = true
}

async function guardar() {
  guardando.value = true
  try {
    const conceptos = CONCEPTOS_DOC
      .map(c => ({
        ID_SubIndicador: subIdDoc(c.clave),
        Cumplido: Boolean(formDoc[c.clave]),
      }))
      .filter(c => c.ID_SubIndicador)

    await comApi.storeResultadoDoc(baseSel.value.ID_Base, { conceptos })
    toast.add({ severity: 'success', summary: 'Checklist DOC guardado', life: 2500 })
    dlg.value = false
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error al guardar', life: 4000 })
  } finally {
    guardando.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.page-title { margin:0; font-size:1.4rem; display:flex; align-items:center; gap:0.5rem; }
.mb-3 { margin-bottom:1rem; }
.filtros-row { display:flex; gap:0.75rem; align-items:flex-end; }
.doc-grid { display:flex; flex-direction:column; gap:0.5rem; }
.doc-row { display:grid; grid-template-columns:1fr 48px; align-items:center; gap:0.75rem; }
.doc-label { font-size:0.875rem; font-weight:500; }
</style>
