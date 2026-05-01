<template>
  <div>
    <div class="page-header">
      <h1 class="page-title"><i class="pi pi-chart-line" /> Carga de Resultados</h1>
    </div>

    <!-- Filtros / selector de base -->
    <Card class="mb-3">
      <template #content>
        <div class="filtros-row">
          <Select v-model="filtros.id_semana" :options="cats.semanas"
            optionLabel="label" optionValue="ID_Semana"
            placeholder="Semana" showClear style="min-width:180px" />
          <Select v-model="filtros.canal"
            :options="[{label:'Tradicional',value:'TRADICIONAL'},{label:'Moderno',value:'MODERNO'}]"
            optionLabel="label" optionValue="value"
            placeholder="Canal" showClear />
          <Button label="Buscar" icon="pi pi-search" @click="cargar" />
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable :value="bases" :loading="cargando" stripedRows size="small"
          paginator :rows="30" :totalRecords="total" lazy @page="onPage">
          <Column header="Empleado">
            <template #body="{ data }">
              {{ data.Numero_Empleado }} — {{ data.empleado?.Nombre ?? '—' }}
            </template>
          </Column>
          <Column field="Ruta"   header="Ruta"   style="width:90px" />
          <Column field="Canal"  header="Canal"  style="width:110px" />
          <Column field="TCE"    header="TCE"    style="width:70px" />
          <Column header="Resultados" style="width:160px">
            <template #body="{ data }">
              <Tag :value="`${data.resultados_indicador_count ?? 0} capturados`"
                :severity="data.resultados_indicador_count > 0 ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column style="width:180px">
            <template #body="{ data }">
              <div class="btn-group">
                <Button label="Ventas" icon="pi pi-chart-bar" size="small"
                  @click="abrirVentas(data)" />
                <Button label="Dev." icon="pi pi-arrow-down-left" size="small" severity="warn"
                  @click="abrirDevolucion(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog carga de ventas (VOL, COB, EFE, EFI, NSE) -->
    <Dialog v-model:visible="dlgVentas"
      :header="`Ventas — ${baseSel?.empleado?.Nombre ?? ''}`"
      modal style="width:600px">

      <div v-for="ind in INDICADORES_VENTAS" :key="ind.clave" class="indicador-bloque">
        <h4 class="ind-titulo">{{ ind.label }}</h4>

        <div v-if="ind.clave === 'EFE' || ind.clave === 'EFI'" class="ind-campos">
          <div class="field">
            <label>Clientes visitados</label>
            <InputNumber v-model="formVentas[ind.clave].ClientesVisitados" class="w-full" />
          </div>
          <div class="field" v-if="ind.clave === 'EFE'">
            <label>Clientes con compra</label>
            <InputNumber v-model="formVentas[ind.clave].ClientesConCompra" class="w-full" />
          </div>
          <div class="field" v-if="ind.clave === 'EFI'">
            <label>Clientes activos</label>
            <InputNumber v-model="formVentas[ind.clave].ClientesActivos" class="w-full" />
          </div>
        </div>

        <div v-else-if="!ind.subs" class="ind-campos">
          <div class="field">
            <label>Valor real</label>
            <InputNumber v-model="formVentas[ind.clave].ValorReal" :minFractionDigits="2" class="w-full" />
          </div>
          <div class="field">
            <label>Meta</label>
            <InputNumber v-model="formVentas[ind.clave].Meta" :minFractionDigits="2" class="w-full" />
          </div>
        </div>

        <div v-else class="subs-grid">
          <div v-for="sub in ind.subs" :key="sub.clave" class="sub-bloque">
            <span class="sub-label">{{ sub.label }}</span>
            <InputNumber v-model="formVentas[ind.clave][sub.clave]"
              :minFractionDigits="2" placeholder="Valor" class="sub-input" />
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" text @click="dlgVentas = false" />
        <Button label="Guardar ventas" icon="pi pi-save" severity="success"
          :loading="guardando" @click="guardarVentas" />
      </template>
    </Dialog>

    <!-- Dialog devoluciones (DF1 / DAU) -->
    <Dialog v-model:visible="dlgDev"
      :header="`Devoluciones — ${baseSel?.empleado?.Nombre ?? ''}`"
      modal style="width:380px">
      <div class="form-grid">
        <div class="field">
          <label>DF1 — Devolución F1 ($)</label>
          <InputNumber v-model="formDev.DF1" :minFractionDigits="2" class="w-full" />
        </div>
        <div class="field">
          <label>DAU — Devolución autoservicio ($)</label>
          <InputNumber v-model="formDev.DAU" :minFractionDigits="2" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" text @click="dlgDev = false" />
        <Button label="Guardar devoluciones" icon="pi pi-save" severity="warn"
          :loading="guardando" @click="guardarDevolucion" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { comApi } from '../../api/com'
import Card        from 'primevue/card'
import Button      from 'primevue/button'
import DataTable   from 'primevue/datatable'
import Column      from 'primevue/column'
import Tag         from 'primevue/tag'
import Dialog      from 'primevue/dialog'
import Select      from 'primevue/select'
import InputNumber from 'primevue/inputnumber'

const toast = useToast()
const cats  = useCatalogosStore()

const bases    = ref([])
const total    = ref(0)
const cargando = ref(false)
const guardando = ref(false)
const pagina   = ref(1)
const baseSel  = ref(null)
const dlgVentas = ref(false)
const dlgDev    = ref(false)

const filtros = reactive({ id_semana: null, canal: null })

const INDICADORES_VENTAS = [
  { clave: 'VOL', label: 'VOL — Volumen', subs: [
    { clave: 'EMB', label: 'EMB' }, { clave: 'CF',  label: 'CF' },
    { clave: 'QSO', label: 'QSO' }, { clave: 'MTK', label: 'MTK' },
  ]},
  { clave: 'COB', label: 'COB — Cobertura', subs: [
    { clave: 'BOL',  label: 'BOL'  }, { clave: 'CHOR', label: 'CHOR' },
    { clave: 'JAM',  label: 'JAM'  }, { clave: 'LOM',  label: 'LOM'  },
    { clave: 'MTK',  label: 'MTK'  }, { clave: 'QSO',  label: 'QSO'  },
    { clave: 'SAL',  label: 'SAL'  }, { clave: 'TOC',  label: 'TOC'  },
  ]},
  { clave: 'EFE', label: 'EFE — Efectividad' },
  { clave: 'EFI', label: 'EFI — Eficiencia' },
  { clave: 'NSE', label: 'NSE — Nivel de servicio' },
]

// Estado del form de ventas indexado por clave de indicador
const formVentas = reactive({
  VOL: { EMB:0, CF:0, QSO:0, MTK:0 },
  COB: { BOL:0, CHOR:0, JAM:0, LOM:0, MTK:0, QSO:0, SAL:0, TOC:0 },
  EFE: { ClientesVisitados:0, ClientesConCompra:0 },
  EFI: { ClientesVisitados:0, ClientesActivos:0 },
  NSE: { ValorReal:0, Meta:0 },
})

const formDev = reactive({ DF1: 0, DAU: 0 })

function subId(indicadorClave, subClave) {
  const indicador = cats.indicadores.find(i => i.Clave === indicadorClave)
  if (!indicador) return null
  return cats.subIndicadores.find(s =>
    s.ID_Indicador === indicador.ID_Indicador && s.Clave === subClave
  )?.ID_SubIndicador ?? null
}

async function cargar() {
  cargando.value = true
  try {
    const params = {
      page: pagina.value, per_page: 30,
      ...Object.fromEntries(Object.entries(filtros).filter(([, v]) => v != null)),
    }
    const { data } = await comApi.listarBaseCalculo(params)
    bases.value = data.data ?? data
    total.value = data.total ?? bases.value.length
  } catch {
    toast.add({ severity: 'error', summary: 'Error al cargar', life: 3000 })
  } finally {
    cargando.value = false
  }
}

function onPage(e) { pagina.value = e.page + 1; cargar() }

function abrirVentas(base) {
  baseSel.value = base
  // Resetear form
  Object.assign(formVentas.VOL, { EMB:0, CF:0, QSO:0, MTK:0 })
  Object.assign(formVentas.COB, { BOL:0, CHOR:0, JAM:0, LOM:0, MTK:0, QSO:0, SAL:0, TOC:0 })
  Object.assign(formVentas.EFE, { ClientesVisitados:0, ClientesConCompra:0 })
  Object.assign(formVentas.EFI, { ClientesVisitados:0, ClientesActivos:0 })
  Object.assign(formVentas.NSE, { ValorReal:0, Meta:0 })
  dlgVentas.value = true
}

function abrirDevolucion(base) {
  baseSel.value = base
  formDev.DF1   = 0
  formDev.DAU   = 0
  dlgDev.value  = true
}

async function guardarVentas() {
  guardando.value = true
  try {
    // Construir array de resultados para el bulk
    const resultados = []
    const idBase = baseSel.value.ID_Base

    // VOL sub-indicadores
    for (const [clave, valor] of Object.entries(formVentas.VOL)) {
      const idSub = subId('VOL', clave)
      if (idSub) {
        resultados.push({
          ID_Base: idBase,
          clave_indicador: 'VOL',
          ID_SubIndicador: idSub,
          ValorReal: valor || 0,
          Meta: 0,
        })
      }
    }

    // COB sub-indicadores
    for (const [clave, valor] of Object.entries(formVentas.COB)) {
      const idSub = subId('COB', clave)
      if (idSub) {
        resultados.push({
          ID_Base: idBase,
          clave_indicador: 'COB',
          ID_SubIndicador: idSub,
          ValorReal: valor || 0,
          Meta: 0,
        })
      }
    }

    // EFE
    resultados.push({
      ID_Base: idBase, clave_indicador: 'EFE', ValorReal: 0,
      ClientesVisitados: formVentas.EFE.ClientesVisitados,
      ClientesConCompra: formVentas.EFE.ClientesConCompra,
    })

    // EFI
    resultados.push({
      ID_Base: idBase, clave_indicador: 'EFI', ValorReal: 0,
      ClientesVisitados: formVentas.EFI.ClientesVisitados,
      ClientesActivos:   formVentas.EFI.ClientesActivos,
    })

    // NSE
    resultados.push({
      ID_Base: idBase, clave_indicador: 'NSE',
      ValorReal: formVentas.NSE.ValorReal, Meta: formVentas.NSE.Meta,
    })

    await comApi.storeResultadoBulk({ resultados })
    toast.add({ severity: 'success', summary: 'Resultados guardados', life: 2500 })
    dlgVentas.value = false
    cargar()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message ?? 'Error al guardar', life: 4000 })
  } finally {
    guardando.value = false
  }
}

async function guardarDevolucion() {
  guardando.value = true
  try {
    await Promise.all([
      comApi.storeDevolucion(baseSel.value.ID_Base, {
        clave_indicador: 'DF1',
        ValorReal: formDev.DF1 || 0,
      }),
      comApi.storeDevolucion(baseSel.value.ID_Base, {
        clave_indicador: 'DAU',
        ValorReal: formDev.DAU || 0,
      }),
    ])
    toast.add({ severity: 'success', summary: 'Devoluciones guardadas', life: 2500 })
    dlgDev.value = false
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
.page-header   { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.page-title    { margin:0; font-size:1.4rem; display:flex; align-items:center; gap:0.5rem; }
.mb-3          { margin-bottom:1rem; }
.filtros-row   { display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; }
.btn-group     { display:flex; gap:0.4rem; }
.indicador-bloque { border:1px solid var(--p-surface-200); border-radius:6px; padding:0.75rem; margin-bottom:0.75rem; }
.ind-titulo    { margin:0 0 0.6rem; font-size:0.9rem; font-weight:600; }
.ind-campos    { display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; }
.subs-grid     { display:grid; grid-template-columns:repeat(4, 1fr); gap:0.5rem; }
.sub-bloque    { display:flex; flex-direction:column; gap:0.25rem; }
.sub-label     { font-size:0.75rem; font-weight:600; color:var(--p-text-muted-color); }
.sub-input     { width:100%; }
.form-grid     { display:flex; flex-direction:column; gap:0.75rem; }
.field         { display:flex; flex-direction:column; gap:0.35rem; }
.field label   { font-size:0.875rem; font-weight:500; }
.w-full        { width:100%; }
</style>
