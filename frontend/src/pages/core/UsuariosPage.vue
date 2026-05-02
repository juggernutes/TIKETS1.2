<template>
  <div>
    <PageHeader titulo="Usuarios" subtitulo="Cuentas del portal y reset de contraseña" icon="pi-user-edit" />

    <Card class="mb-3">
      <template #content>
        <div class="filtros-grid">
          <InputText v-model="filtros.buscar" placeholder="Nombre o correo..." @keyup.enter="cargar" />
          <Select v-model="filtros.area" :options="cats.areas" optionLabel="Nombre" optionValue="ID_Area" placeholder="Área" showClear />
          <Select v-model="filtros.activo" :options="opcionesActivo" optionLabel="label" optionValue="value" placeholder="Estatus" showClear />
          <Button label="Buscar" icon="pi pi-search" @click="buscar" />
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable :value="usuarios" :loading="cargando" lazy :totalRecords="total"
          paginator :rows="perPage" @page="onPage" stripedRows size="small" scrollable scrollHeight="60vh">
          <Column field="nombre" header="Nombre" />
          <Column field="cuenta" header="Cuenta" />
          <Column field="email" header="Correo" />
          <Column field="rol" header="Rol" />
          <Column field="area" header="Área" />
          <Column header="Contraseña" style="width:150px">
            <template #body="{ data }">
              <Tag :value="data.debe_cambiar_password ? 'Cambio pendiente' : 'Actualizada'"
                :severity="data.debe_cambiar_password ? 'warn' : 'success'" />
            </template>
          </Column>
          <Column header="" style="width:90px">
            <template #body="{ data }">
              <Button icon="pi pi-key" text rounded size="small"
                v-tooltip="'Resetear contraseña'"
                :disabled="!data.id_login"
                @click="resetear(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="dlgReset" header="Contraseña temporal" modal style="width:440px">
      <div v-if="resetInfo" class="reset-box">
        <span class="lbl">Cuenta</span>
        <strong>{{ resetInfo.cuenta }}</strong>
        <span class="lbl">Contraseña temporal</span>
        <div class="password-row">
          <code>{{ resetInfo.password_temporal }}</code>
          <Button icon="pi pi-copy" text rounded
            v-tooltip="'Copiar contraseña'"
            @click="copiarPassword" />
        </div>
        <p>El usuario deberá cambiarla al iniciar sesión.</p>
      </div>
      <template #footer>
        <Button label="Cerrar" icon="pi pi-check" @click="dlgReset = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { useCatalogosStore } from '../../stores/catalogos'
import { coreApi } from '../../api/core'
import PageHeader from '../../components/shared/PageHeader.vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'

const cats = useCatalogosStore()
const confirm = useConfirm()
const toast = useToast()

const usuarios = ref([])
const total = ref(0)
const cargando = ref(false)
const pagina = ref(1)
const perPage = 20
const dlgReset = ref(false)
const resetInfo = ref(null)

const filtros = reactive({ buscar: '', area: null, activo: null })
const opcionesActivo = [
  { label: 'Activos', value: 1 },
  { label: 'Inactivos', value: 0 },
]

async function cargar() {
  cargando.value = true
  try {
    const params = {
      page: pagina.value,
      per_page: perPage,
      ...(filtros.buscar && { buscar: filtros.buscar }),
      ...(filtros.area && { area: filtros.area }),
      ...(filtros.activo !== null && { activo: filtros.activo }),
    }
    const { data } = await coreApi.usuarios(params)
    usuarios.value = data.data
    total.value = data.total
  } finally {
    cargando.value = false
  }
}

function buscar() {
  pagina.value = 1
  cargar()
}

function onPage(e) {
  pagina.value = e.page + 1
  cargar()
}

function resetear(usuario) {
  confirm.require({
    message: `¿Resetear contraseña de ${usuario.nombre}?`,
    header: 'Confirmar reset',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Resetear',
    rejectLabel: 'Cancelar',
    accept: async () => {
      try {
        const { data } = await coreApi.resetPasswordUsuario(usuario.id_usuario)
        resetInfo.value = data.data
        dlgReset.value = true
        await cargar()
      } catch {
        toast.add({ severity: 'error', summary: 'No se pudo resetear', life: 3000 })
      }
    },
  })
}

async function copiarPassword() {
  if (!resetInfo.value?.password_temporal) return
  try {
    await navigator.clipboard.writeText(resetInfo.value.password_temporal)
    toast.add({ severity: 'success', summary: 'Contraseña copiada', life: 2000 })
  } catch {
    toast.add({ severity: 'warn', summary: 'No se pudo copiar', detail: resetInfo.value.password_temporal, life: 5000 })
  }
}

onMounted(async () => {
  await cats.cargar()
  cargar()
})
</script>

<style scoped>
.mb-3 {
  margin-bottom: 1rem;
}
.filtros-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr auto;
  gap: 0.75rem;
  align-items: end;
}
.reset-box {
  display: grid;
  gap: 0.5rem;
}
.lbl {
  color: var(--p-text-muted-color);
  font-weight: 600;
}
code {
  padding: 0.6rem 0.75rem;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  font-size: 1rem;
}
.password-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.5rem;
  align-items: center;
}
.password-row code {
  min-width: 0;
  overflow-wrap: anywhere;
}
@media (max-width: 900px) {
  .filtros-grid {
    grid-template-columns: 1fr;
  }
}
</style>
