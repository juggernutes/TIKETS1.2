import { defineStore } from 'pinia'
import { ref } from 'vue'
import { catalogosApi } from '../api/catalogos'

export const useCatalogosStore = defineStore('catalogos', () => {
  const areas               = ref([])
  const sucursales          = ref([])
  const puestos             = ref([])
  const sistemas            = ref([])
  const tiposError          = ref([])
  const erroresHd           = ref([])
  const estatusHd           = ref([])
  const solucionesHd        = ref([])
  const proveedores         = ref([])
  const estatusCandidato    = ref([])
  const fuentesReclutamiento= ref([])
  const semanas             = ref([])
  const indicadores         = ref([])
  const subIndicadores      = ref([])
  const usuarios            = ref([])

  const cargado = ref(false)

  async function cargar() {
    if (cargado.value) return
    const [
      resAreas, resSucursales, resPuestos, resSistemas,
      resTipoError, resErrores, resEstatusHd, resSoluciones, resProveedores,
      resEstatusCand, resFuentes, resSemanas, resIndicadores, resSubIndicadores, resUsuarios,
    ] = await Promise.all([
      catalogosApi.areas(),
      catalogosApi.sucursales(),
      catalogosApi.puestos(),
      catalogosApi.sistemas(),
      catalogosApi.tipoError(),
      catalogosApi.erroresHd(),
      catalogosApi.estatusHd(),
      catalogosApi.solucionesHd(),
      catalogosApi.proveedores(),
      catalogosApi.estatusCandidato(),
      catalogosApi.fuentesReclutamiento(),
      catalogosApi.semanas(),
      catalogosApi.indicadores(),
      catalogosApi.subIndicadores(),
      catalogosApi.usuarios(),
    ])

    areas.value                = resAreas.data
    sucursales.value           = resSucursales.data
    puestos.value              = resPuestos.data
    sistemas.value             = resSistemas.data
    tiposError.value           = resTipoError.data
    erroresHd.value            = resErrores.data
    estatusHd.value            = resEstatusHd.data
    solucionesHd.value         = resSoluciones.data
    proveedores.value          = resProveedores.data
    estatusCandidato.value     = resEstatusCand.data
    fuentesReclutamiento.value = resFuentes.data
    semanas.value              = resSemanas.data.map(s => ({
      ...s,
      label: `Sem ${s.Semana}/${s.Anio}`,
    }))
    indicadores.value          = resIndicadores.data
    subIndicadores.value       = resSubIndicadores.data
    usuarios.value             = resUsuarios.data
    cargado.value              = true
  }

  return {
    areas, sucursales, puestos, sistemas, tiposError, erroresHd,
    estatusHd, solucionesHd, proveedores, estatusCandidato,
    fuentesReclutamiento, semanas, indicadores, subIndicadores, usuarios,
    cargado, cargar,
  }
})
