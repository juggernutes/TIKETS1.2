<template>
  <div class="login-wrap">
    <div class="login-panel">
      <section class="login-copy">
        <img :src="appAssets.brandLogo" alt="ROSARITO SG2" class="login-logo" />
        <span class="login-kicker">Portal corporativo</span>
        <h1>ROSARITO SG2</h1>
        <p>
          Accede al sistema con la identidad visual de la versión anterior,
          en un diseño más amplio y claro para operar mejor desde escritorio.
        </p>
      </section>

      <Card class="login-card">
        <template #content>
          <div class="login-brand">
            <span class="brand-chip">Acceso seguro</span>
            <h2>Iniciar sesión</h2>
            <p>Ingresa con tu usuario de red o correo corporativo.</p>
          </div>

          <form @submit.prevent="onLogin">
            <div class="field">
              <label for="cuenta">Usuario</label>
              <InputText id="cuenta" v-model="form.cuenta"
                autocomplete="username" placeholder="usuario@empresa.com"
                :disabled="cargando" class="w-full" />
            </div>

            <div class="field">
              <label for="password">Contraseña</label>
              <Password id="password" v-model="form.password"
                :feedback="false" toggleMask
                :disabled="cargando" inputClass="w-full"
                placeholder="••••••••" />
            </div>

            <Message v-if="error" severity="error" :closable="false" class="login-error">{{ error }}</Message>

            <Button type="submit" label="Ingresar" icon="pi pi-sign-in"
              :loading="cargando" class="w-full login-submit" />
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import Card      from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password  from 'primevue/password'
import Button    from 'primevue/button'
import Message   from 'primevue/message'
import { appAssets } from '../../config/appAssets'

const router   = useRouter()
const auth     = useAuthStore()
const cargando = ref(false)
const error    = ref('')

const form = reactive({ cuenta: '', password: '' })

async function onLogin() {
  error.value    = ''
  cargando.value = true
  try {
    const data = await auth.login(form.cuenta, form.password)
    router.push(data.debe_cambiar_password ? '/cambiar-password' : '/')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Error al iniciar sesión.'
  } finally {
    cargando.value = false
  }
}
</script>

<style scoped>
.login-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.login-panel {
  width: min(100%, 1180px);
  display: grid;
  grid-template-columns: minmax(320px, 1.1fr) minmax(420px, 520px);
  gap: 2rem;
  align-items: stretch;
}

.login-copy {
  padding: 2.5rem;
  border: 1px solid rgba(159, 176, 199, 0.14);
  border-radius: 30px;
  background:
    linear-gradient(135deg, rgba(197, 22, 46, 0.14), transparent 38%),
    linear-gradient(180deg, rgba(15, 26, 42, 0.92), rgba(10, 19, 33, 0.88));
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.22);
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.login-logo {
  width: min(280px, 100%);
  margin-bottom: 1.2rem;
  filter: drop-shadow(0 12px 24px rgba(0, 0, 0, 0.22));
}

.login-kicker {
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.24em;
  text-transform: uppercase;
  color: #9fb0c7;
}

.login-copy h1 {
  margin: 0.65rem 0 1rem;
  font-size: clamp(2.4rem, 4vw, 4rem);
  line-height: 0.96;
  letter-spacing: 0.04em;
  color: #ffffff;
}

.login-copy p {
  max-width: 32rem;
  margin: 0;
  color: #c6d3e3;
  font-size: 1rem;
  line-height: 1.65;
}

.login-card {
  width: 100%;
  max-width: 520px;
  align-self: center;
}

.login-brand {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 0.5rem 0 1.25rem;
  gap: 0.4rem;
}

.brand-chip {
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
}

.login-brand h2 {
  margin: 0;
  font-size: 1.95rem;
  font-weight: 700;
  color: #ffffff;
}

.login-brand p {
  margin: 0;
  color: #9fb0c7;
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

.login-error {
  margin-bottom: 1rem;
}

.login-submit {
  margin-top: 0.25rem;
}

@media (max-width: 980px) {
  .login-panel {
    grid-template-columns: 1fr;
  }

  .login-copy {
    padding: 2rem;
  }

  .login-card {
    max-width: none;
  }
}

@media (max-width: 640px) {
  .login-wrap {
    padding: 1rem;
  }

  .login-copy,
  .login-card {
    border-radius: 24px;
  }
}
</style>
