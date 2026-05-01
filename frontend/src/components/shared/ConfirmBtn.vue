<template>
  <Button v-bind="$attrs" @click="confirmar" />
</template>

<script setup>
import { useConfirm } from 'primevue/useconfirm'
import Button from 'primevue/button'

const confirm = useConfirm()
const props   = defineProps({
  mensaje:  { type: String, default: '¿Estás seguro?' },
  header:   { type: String, default: 'Confirmación' },
  severity: { type: String, default: 'warn' },
})
const emit = defineEmits(['confirmado'])

function confirmar() {
  confirm.require({
    message:       props.mensaje,
    header:        props.header,
    icon:          'pi pi-exclamation-triangle',
    rejectLabel:   'Cancelar',
    acceptLabel:   'Confirmar',
    acceptClass:   props.severity === 'danger' ? 'p-button-danger' : '',
    accept:        () => emit('confirmado'),
  })
}
</script>
