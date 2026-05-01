<template>
  <div class="cp-wrap">
    <Card class="cp-card">
      <template #content>
        <div class="cp-header">
          <span class="cp-chip">Seguridad</span>
          <h2>Actualizar contraseña</h2>
          <p v-if="forzado" class="cp-aviso">
            <i class="pi pi-exclamation-triangle" />
            Tu cuenta requiere que cambies tu contraseña antes de continuar.
          </p>
          <p v-else>Ingresa tu contraseña actual y elige una nueva.</p>
        </div>

        <form @submit.prevent="onSubmit">
          <div class="field">
            <label for="actual">Contraseña actual</label>
            <Password id="actual" v-model="form.password_actual"
              :feedback="false" toggleMask :disabled="cargando"
              inputClass="w-full" placeholder="••••••••" />
          </div>

          <div class="field">
            <label for="nueva">Nueva contraseña</label>
            <Password id="nueva" v-model="form.password_nuevo"
              :feedback="false" toggleMask :disabled="cargando"
              inputClass="w-full" placeholder="••••••••" />
            <small class="cp-hint">
              Mínimo 8 caracteres, 1 mayúscula, 1 número y 1 carácter especial.
            </small>
          </div>

          <div class="field">
            <label for="confirmar">Confirmar nueva contraseña</label>
            <Password id="confirmar" v-model="form.password_nuevo_confirmation"
              :feedback="false" toggleMask :disabled="cargando"
              inputClass="w-full" placeholder="••••••••" />
          </div>

          <Message v-if="error" severity="error" :closable="false" class="mb-3">{{ error }}</Message>
          <Message v-if="exito" severity="success" :closable="false" class="mb-3">{{ exito }}</Message>

          <div class="cp-actions">
            <Button type="submit" label="Actualizar contraseña"
              icon="pi pi-lock" :loading="cargando" class="w-full" />
            <Button v-if="!forzado" type="button" label="Cancelar"
              severity="secondary" text class="w-full"
              @click="router.back()" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter }    from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import Card    from 'primevue/card'
import Password from 'primevue/password'
import Button  from 'primevue/button'
import Message from 'primevue/message'

const router  = useRouter()
const auth    = useAuthStore()
const forzado = computed(() => auth.debeCambiarPassword)

const cargando = ref(false)
const error    = ref('')
const exito    = ref('')

const REGEX_PASSWORD = /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/

const form = reactive({
  password_actual:              '',
  password_nuevo:               '',
  password_nuevo_confirmation:  '',
})

async function onSubmit() {
  error.value = ''
  exito.value = ''

  if (!form.password_actual) {
    error.value = 'Ingresa tu contraseña actual.'
    return
  }
  if (!REGEX_PASSWORD.test(form.password_nuevo)) {
    error.value = 'La nueva contraseña debe tener mínimo 8 caracteres, una mayúscula, un número y un carácter especial.'
    return
  }
  if (form.password_nuevo !== form.password_nuevo_confirmation) {
    error.value = 'Las contraseñas nuevas no coinciden.'
    return
  }

  cargando.value = true
  try {
    await auth.actualizarPassword({
      password_actual:             form.password_actual,
      password_nuevo:              form.password_nuevo,
      password_nuevo_confirmation: form.password_nuevo_confirmation,
    })
    exito.value = 'Contraseña actualizada correctamente.'
    setTimeout(() => router.push('/'), 1200)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Error al actualizar la contraseña.'
  } finally {
    cargando.value = false
  }
}
</script>

<style scoped>
.cp-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.cp-card {
  width: min(100%, 480px);
}

.cp-header {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  padding: 0.5rem 0 1.5rem;
}

.cp-chip {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.7rem;
  border-radius: 999px;
  background: rgba(197, 22, 46, 0.14);
  color: #ffb2bc;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  width: fit-content;
}

.cp-header h2 {
  margin: 0;
  font-size: 1.65rem;
  font-weight: 700;
  color: #ffffff;
}

.cp-header p {
  margin: 0;
  color: #9fb0c7;
  font-size: 0.95rem;
}

.cp-aviso {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: rgba(234, 179, 8, 0.12);
  color: #fde047;
  font-size: 0.9rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  margin-bottom: 1.1rem;
}

.field label {
  font-size: 0.88rem;
  font-weight: 600;
  color: #dce5f0;
}

.cp-hint {
  color: #9fb0c7;
  font-size: 0.78rem;
  line-height: 1.4;
}

.cp-actions {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  margin-top: 0.5rem;
}
</style>
