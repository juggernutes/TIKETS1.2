<template>
  <main class="postulacion-page">
    <section v-if="vacante" class="postulacion-shell">
      <div class="vacante-panel">
        <span class="kicker">Postulación</span>
        <h1>{{ vacante.Titulo }}</h1>
        <div class="meta">
          <span>{{ vacante.area?.Nombre ?? 'Área no especificada' }}</span>
          <span>{{ vacante.sucursal?.Nombre ?? 'Sucursal abierta' }}</span>
          <span>{{ vacante.puesto?.Descripcion ?? 'Puesto por definir' }}</span>
        </div>
        <p v-if="vacante.Descripcion">{{ vacante.Descripcion }}</p>
        <div v-if="vacante.Requisitos" class="requisitos">
          <strong>Requisitos</strong>
          <p>{{ vacante.Requisitos }}</p>
        </div>
      </div>

      <form class="form-panel" @submit.prevent="enviar">
        <div class="grid-2">
          <div class="field">
            <label>Nombre *</label>
            <InputText v-model="form.Nombre" class="w-full" />
          </div>
          <div class="field">
            <label>Apellido paterno *</label>
            <InputText v-model="form.ApellidoPaterno" class="w-full" />
          </div>
          <div class="field">
            <label>Apellido materno</label>
            <InputText v-model="form.ApellidoMaterno" class="w-full" />
          </div>
          <div class="field">
            <label>Correo *</label>
            <InputText v-model="form.Correo" class="w-full" type="email" />
          </div>
          <div class="field">
            <label>Teléfono *</label>
            <InputText v-model="form.Telefono" class="w-full" />
          </div>
          <div class="field">
            <label>Teléfono alterno</label>
            <InputText v-model="form.TelefonoAlterno" class="w-full" />
          </div>
          <div class="field">
            <label>Escolaridad</label>
            <InputText v-model="form.Escolaridad" class="w-full" />
          </div>
          <div class="field">
            <label>Profesión</label>
            <InputText v-model="form.Profesion" class="w-full" />
          </div>
          <div class="field">
            <label>Pretensión salarial</label>
            <InputNumber v-model="form.PretensionSalarial" mode="currency" currency="MXN"
              locale="es-MX" class="w-full" :min="0" />
          </div>
          <div class="field">
            <label>LinkedIn / portafolio</label>
            <InputText v-model="form.LinkedIn_URL" class="w-full" />
          </div>
          <div class="field col-2">
            <label>Experiencia</label>
            <Textarea v-model="form.ExperienciaResumen" rows="4" class="w-full" />
          </div>
          <div class="field col-2">
            <label>CV</label>
            <input type="file" accept=".pdf,.doc,.docx" @change="onCv" />
          </div>
        </div>

        <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
        <Message v-if="exito" severity="success" :closable="false">{{ exito }}</Message>

        <Button type="submit" label="Enviar postulación" icon="pi pi-send"
          :loading="enviando" class="w-full" />
      </form>
    </section>
    <section v-else-if="cargando" class="estado">Cargando vacante...</section>
    <section v-else class="estado">Vacante no disponible.</section>
  </main>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { rhApi } from '../../api/rh'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import Message from 'primevue/message'

const route = useRoute()
const vacante = ref(null)
const cargando = ref(false)
const enviando = ref(false)
const error = ref('')
const exito = ref('')
const cv = ref(null)

const form = reactive({
  Nombre: '',
  ApellidoPaterno: '',
  ApellidoMaterno: '',
  Correo: '',
  Telefono: '',
  TelefonoAlterno: '',
  Escolaridad: '',
  Profesion: '',
  ExperienciaResumen: '',
  LinkedIn_URL: '',
  PretensionSalarial: null,
})

function onCv(event) {
  cv.value = event.target.files?.[0] ?? null
}

async function cargar() {
  cargando.value = true
  try {
    const { data } = await rhApi.verVacantePublica(route.params.id)
    vacante.value = data
  } catch {
    vacante.value = null
  } finally {
    cargando.value = false
  }
}

async function enviar() {
  error.value = ''
  exito.value = ''
  if (!form.Nombre || !form.ApellidoPaterno || !form.Correo || !form.Telefono) {
    error.value = 'Nombre, apellido paterno, correo y teléfono son requeridos.'
    return
  }

  const data = new FormData()
  Object.entries(form).forEach(([key, value]) => {
    if (value !== null && value !== '') data.append(key, value)
  })
  if (cv.value) data.append('cv', cv.value)

  enviando.value = true
  try {
    await rhApi.postularVacante(route.params.id, data)
    exito.value = 'Postulación recibida. Recursos Humanos revisará tu información.'
  } catch (e) {
    const msgs = e.response?.data?.errors
    error.value = msgs
      ? Object.values(msgs).flat().join(' ')
      : e.response?.data?.message ?? 'No se pudo enviar la postulación.'
  } finally {
    enviando.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.postulacion-page {
  min-height: 100vh;
  background: #0b1422;
  color: #edf3fb;
  padding: 2rem;
}
.postulacion-shell {
  width: min(1180px, 100%);
  margin: 0 auto;
  display: grid;
  grid-template-columns: minmax(0, 0.9fr) minmax(420px, 1.1fr);
  gap: 1.5rem;
  align-items: start;
}
.vacante-panel,
.form-panel {
  border: 1px solid rgba(159, 176, 199, 0.18);
  background: rgba(15, 26, 42, 0.92);
  border-radius: 8px;
  padding: 1.5rem;
}
.kicker {
  color: #9fb0c7;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-weight: 700;
}
h1 {
  margin: 0.5rem 0 1rem;
  font-size: 2rem;
}
.meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.meta span {
  border: 1px solid rgba(159, 176, 199, 0.22);
  border-radius: 999px;
  padding: 0.35rem 0.65rem;
  color: #dce5f0;
  font-size: 0.82rem;
}
.requisitos {
  margin-top: 1rem;
}
.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem;
}
.col-2 {
  grid-column: span 2;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.field label {
  font-size: 0.86rem;
  font-weight: 600;
  color: #dce5f0;
}
.w-full {
  width: 100%;
}
.estado {
  text-align: center;
  padding: 4rem;
  color: #dce5f0;
}
@media (max-width: 900px) {
  .postulacion-shell,
  .grid-2 {
    grid-template-columns: 1fr;
  }
  .col-2 {
    grid-column: auto;
  }
}
</style>
