import { createApp }   from 'vue'
import { createPinia } from 'pinia'
import PrimeVue        from 'primevue/config'
import Aura            from '@primevue/themes/aura'
import ToastService    from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import 'primeicons/primeicons.css'

import router from './router'
import App    from './App.vue'
import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: { darkModeSelector: '.dark' },
  },
  locale: {
    startsWith:       'Empieza con',
    contains:         'Contiene',
    notContains:      'No contiene',
    endsWith:         'Termina con',
    equals:           'Es igual a',
    notEquals:        'No es igual a',
    noFilter:         'Sin filtro',
    lt:               'Menor que',
    lte:              'Menor o igual que',
    gt:               'Mayor que',
    gte:              'Mayor o igual que',
    dateIs:           'La fecha es',
    dateIsNot:        'La fecha no es',
    dateBefore:       'La fecha es anterior',
    dateAfter:        'La fecha es posterior',
    clear:            'Limpiar',
    apply:            'Aplicar',
    matchAll:         'Cumple todo',
    matchAny:         'Cumple alguno',
    addRule:          'Agregar regla',
    removeRule:       'Eliminar regla',
    accept:           'Sí',
    reject:           'No',
    choose:           'Seleccionar',
    upload:           'Subir',
    cancel:           'Cancelar',
    dayNames:         ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],
    dayNamesShort:    ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'],
    dayNamesMin:      ['D','L','M','X','J','V','S'],
    monthNames:       ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
    monthNamesShort:  ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
    today:            'Hoy',
    weekHeader:       'Sem',
    firstDayOfWeek:   1,
    dateFormat:       'dd/mm/yy',
    weak:             'Débil',
    medium:           'Media',
    strong:           'Fuerte',
    passwordPrompt:   'Ingresa una contraseña',
    emptyMessage:     'Sin resultados',
    emptyFilterMessage:'Sin resultados',
  },
})
app.use(ToastService)
app.use(ConfirmationService)

app.mount('#app')
