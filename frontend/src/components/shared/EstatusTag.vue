<template>
  <Tag :value="label ?? valor" :severity="sev" />
</template>

<script setup>
import { computed } from 'vue'
import Tag from 'primevue/tag'

const props = defineProps({
  valor:  { type: String, default: '' },
  modulo: { type: String, default: 'hd' }, // hd | rh-vacante | rh-candidato | ped | com
  label:  { type: String, default: null },
})

const MAPA = {
  hd: {
    'Nuevo':'info','Asignado':'warn','En proceso':'warn',
    'En espera':'secondary','Resuelto':'success','Cerrado':'success','Cancelado':'danger',
  },
  'rh-vacante': {
    ABIERTA:'success', PAUSADA:'warn', CERRADA:'secondary', CANCELADA:'danger',
  },
  'rh-candidato': {
    NUEVO:'info', EN_REVISION:'warn', CITADO:'warn', ENTREVISTADO:'warn',
    SELECCIONADO:'success', OFERTA_ENVIADA:'success', CONTRATADO:'success',
    RECHAZADO:'danger', DESCARTADO:'secondary',
  },
  ped: {
    PENDIENTE:'info', AUTORIZADO:'warn', EN_PROCESO:'warn', SURTIDO:'success', CANCELADO:'danger',
  },
  com: {
    CALCULADO:'info', APROBADO:'success', RECHAZADO:'danger',
    PAGADO:'success', CANCELADO:'secondary',
  },
  'com-corrida': {
    BORRADOR:'secondary', EN_PROCESO:'warn', CALCULADO:'info',
    APROBADO:'success', PAGADO:'success', CANCELADO:'danger',
  },
}

const sev = computed(() => MAPA[props.modulo]?.[props.valor] ?? 'secondary')
</script>
