<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore, API_URL } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'

const authStore = useAuthStore()
const i18n      = useI18nStore()
const t         = (k) => i18n.t(k)
const router    = useRouter()

const eventos              = ref([])
const cargando             = ref(true)
const inscritos            = ref(new Set())
const cargandoInscripcion  = ref(null)

const cargarEventos = async () => {
  try {
    const res = await fetch(`${API_URL}/eventos`, { credentials: 'include' })
    if (res.ok) eventos.value = await res.json()
  } catch { /* silencioso */ } finally { cargando.value = false }
}

const cargarMisInscripciones = async () => {
  if (!authStore.isAuthenticated) return
  try {
    const res = await fetch(`${API_URL}/inscripciones/mis`, { credentials: 'include' })
    if (res.ok) {
      const data = await res.json()
      inscritos.value = new Set(data.map(i => i.id))
    }
  } catch { /* silencioso */ }
}

const toggleInscripcion = async (evento) => {
  if (!authStore.isAuthenticated) { router.push('/auth'); return }
  cargandoInscripcion.value = evento.id
  const yaInscrito = inscritos.value.has(evento.id)
  try {
    const res = await fetch(`${API_URL}/inscripciones/${evento.id}`, {
      method: yaInscrito ? 'DELETE' : 'POST',
      credentials: 'include'
    })
    const data = await res.json()
    if (data.estado === 'ok') {
      if (yaInscrito) inscritos.value.delete(evento.id)
      else inscritos.value.add(evento.id)
      inscritos.value = new Set(inscritos.value)
    } else {
      alert(data.mensaje || data.error || 'Error')
    }
  } catch { alert('Error de conexión') }
  finally { cargandoInscripcion.value = null }
}

const formatFecha = (f) => {
  const d = new Date(f + 'T00:00')
  return {
    dia: d.toLocaleDateString('es-ES', { weekday: 'long' }),
    num: d.toLocaleDateString('es-ES', { day: '2-digit' }),
    mes: d.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' }),
  }
}

onMounted(async () => {
  await cargarEventos()
  await cargarMisInscripciones()
})
</script>

<template>
  <div class="bg-black min-h-screen py-16 px-4">
    <div class="max-w-5xl mx-auto">
      <h1 class="text-5xl md:text-7xl font-black mb-12 text-white uppercase tracking-tighter border-b-4 border-pink-600 inline-block pb-2">
        {{ t('agenda_titulo') }} <span class="text-pink-600"></span>
      </h1>

      <div v-if="!authStore.isAuthenticated" class="mb-8 border border-pink-600/40 bg-pink-950/20 px-6 py-4 text-pink-300 text-sm font-bold uppercase tracking-wider">
        ⚡ <router-link to="/auth" class="underline hover:text-pink-400">{{ t('auth_login') }}</router-link>
        &nbsp;— {{ t('agenda_login_aviso') }}
      </div>

      <div v-if="cargando" class="text-center py-20">
        <span class="text-pink-600 text-xl font-bold uppercase tracking-widest animate-pulse">{{ t('agenda_cargando') }}</span>
      </div>

      <div v-else-if="eventos.length === 0" class="text-center py-20 border border-dashed border-gray-700 bg-zinc-900">
        <p class="text-gray-400 text-2xl uppercase font-bold tracking-widest">{{ t('agenda_vacia') }}</p>
        <p class="text-gray-600 mt-2">{{ t('agenda_pronto') }}</p>
      </div>

      <div class="flex flex-col gap-6">
        <div
          v-for="ev in eventos"
          :key="ev.id"
          class="flex flex-col md:flex-row bg-zinc-900/80 border border-gray-800 hover:border-pink-600 transition-colors group relative overflow-hidden"
        >
          <div class="absolute left-0 top-0 bottom-0 w-1 bg-pink-600 transform -translate-x-full group-hover:translate-x-0 transition-transform"></div>

          <!-- Fecha -->
          <div class="bg-black p-6 md:w-56 flex flex-col justify-center items-center border-b md:border-b-0 md:border-r border-gray-800">
            <span class="text-pink-600 font-black uppercase tracking-widest text-sm mb-2 capitalize">{{ formatFecha(ev.fecha_evento).dia }}</span>
            <span class="text-6xl font-black text-white leading-none">{{ formatFecha(ev.fecha_evento).num }}</span>
            <span class="text-gray-400 font-bold uppercase mt-2 text-center text-sm capitalize">{{ formatFecha(ev.fecha_evento).mes }}</span>
          </div>

          <!-- Info -->
          <div class="p-8 flex-1 flex flex-col justify-center">
            <h2 class="text-4xl font-black text-white uppercase group-hover:text-pink-500 transition-colors break-words">{{ ev.titulo }}</h2>
            <div class="mt-6 grid grid-cols-2 gap-4 text-sm font-bold uppercase text-gray-400 tracking-wider">
              <div>
                <span class="text-pink-600 block text-xs">{{ t('agenda_apertura') }}</span>
                {{ ev.hora_inicio ? ev.hora_inicio.substring(0,5) + ' H' : t('por_confirmar') }}
              </div>
              <div>
                <span class="text-pink-600 block text-xs">{{ t('agenda_sala') }}</span>
                Lakobra ({{ t('aforo') }} {{ ev.aforo_max }})
              </div>
            </div>
          </div>

          <!-- Botón inscripción -->
          <div class="p-6 md:w-56 flex flex-col justify-center bg-black/40">
            <button
              v-if="authStore.isAuthenticated"
              @click="toggleInscripcion(ev)"
              :disabled="cargandoInscripcion === ev.id"
              :class="inscritos.has(ev.id)
                ? 'bg-pink-600 text-white border-pink-600 hover:bg-red-700 hover:border-red-700'
                : 'bg-transparent text-white border-white hover:bg-white hover:text-black'"
              class="w-full border-2 font-black uppercase py-3 transition-all disabled:opacity-50"
            >
              <span v-if="cargandoInscripcion === ev.id">...</span>
              <span v-else-if="inscritos.has(ev.id)">{{ t('agenda_inscrito') }}</span>
              <span v-else>{{ t('agenda_inscribirse') }}</span>
            </button>
            <button
              v-else
              @click="router.push('/auth')"
              class="w-full border-2 border-gray-600 text-gray-300 font-bold uppercase py-3 hover:border-white hover:text-white transition-colors"
            >
              {{ t('agenda_inscribirse') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
