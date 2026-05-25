<script setup>
// ============================================================
//  TxandasView.vue  —  Gestión de turnos (Txandas)
//  Accesible para: admin, txandalari, socio
//  - Socios ven los turnos disponibles y se apuntan
//  - Admin/txandalari pueden gestionar todos los turnos
// ============================================================
import { ref, onMounted, computed } from 'vue'
import { useAuthStore, API_URL } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'

const auth = useAuthStore()
const i18n = useI18nStore()
const t = (k) => i18n.t(k)

const eventos      = ref([])
const turnos       = ref([])   // todos los turnos con info de quien está
const misTurnos    = ref([])   // IDs de turnos en los que está el usuario
const cargando     = ref(true)
const procesando   = ref(null) // id del turno que está procesando

const puestos = ['barra', 'puerta', 'limpieza', 'otros']

// ── Cargar eventos confirmados ─────────────────────────────
const cargarEventos = async () => {
  const res = await fetch(`${API_URL}/eventos`, { credentials: 'include' })
  if (res.ok) {
    const data = await res.json()
    // Solo eventos confirmados (pasados y futuros)
    eventos.value = data.filter(e => e.estado === 'confirmado')
  }
}

// ── Cargar todos los turnos ────────────────────────────────
const cargarTurnos = async () => {
  const res = await fetch(`${API_URL}/txandas`, { credentials: 'include' })
  if (res.ok) turnos.value = await res.json()
}

// ── Cargar mis turnos ──────────────────────────────────────
const cargarMisTurnos = async () => {
  const res = await fetch(`${API_URL}/txandas/mis`, { credentials: 'include' })
  if (res.ok) {
    const data = await res.json()
    misTurnos.value = data.map(t => `${t.id_evento}-${t.puesto}`)
  }
}

// ── Apuntarse o desapuntarse de un turno ──────────────────
const toggleTurno = async (id_evento, puesto) => {
  const key = `${id_evento}-${puesto}`
  procesando.value = key
  const estaApuntado = misTurnos.value.includes(key)

  try {
    const res = await fetch(`${API_URL}/txandas/${id_evento}`, {
      method: estaApuntado ? 'DELETE' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ puesto })
    })
    const data = await res.json()
    if (data.estado === 'ok') {
      if (estaApuntado) {
        misTurnos.value = misTurnos.value.filter(k => k !== key)
      } else {
        misTurnos.value.push(key)
      }
      await cargarTurnos()
    } else {
      alert(data.mensaje || data.error || 'Error')
    }
  } catch {
    alert('Error de conexión')
  } finally {
    procesando.value = null
  }
}

// ── Obtener personas apuntadas a un puesto ─────────────────
const getApuntados = (id_evento, puesto) => {
  return turnos.value.filter(t => t.id_evento == id_evento && t.puesto === puesto)
}

const formatFecha = (f) => {
  const d = new Date(f + 'T00:00')
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(async () => {
  await Promise.all([cargarEventos(), cargarTurnos(), cargarMisTurnos()])
  cargando.value = false
})
</script>

<template>
  <div class="min-h-screen bg-black text-white py-12 px-4">
    <div class="max-w-5xl mx-auto">

      <h1 class="text-5xl font-black uppercase mb-2 tracking-tighter">
        {{ t('txandas_titulo') }}
      </h1>
      <p class="text-gray-400 mb-10 uppercase tracking-widest text-sm">
        {{ t('txandas_desc') }}
      </p>

      <div v-if="cargando" class="text-pink-500 animate-pulse text-center py-20 font-bold uppercase tracking-widest">
        {{ t('cargando') }}
      </div>

      <div v-else-if="eventos.length === 0" class="text-gray-500 text-center py-20 border border-dashed border-gray-700">
        {{ t('txandas_sin_turnos') }}
      </div>

      <!-- Un bloque por evento -->
      <div v-else class="flex flex-col gap-8">
        <div
          v-for="ev in eventos"
          :key="ev.id"
          class="border border-gray-800 bg-zinc-900/60"
        >
          <!-- Cabecera evento -->
          <div class="bg-zinc-900 border-b border-gray-800 px-6 py-4 flex justify-between items-center">
            <div>
              <h2 class="text-2xl font-black uppercase text-white">{{ ev.titulo }}</h2>
              <p class="text-pink-600 text-sm font-bold uppercase tracking-widest mt-1">
                {{ formatFecha(ev.fecha_evento) }}
                {{ ev.hora_inicio ? '· ' + ev.hora_inicio.substring(0,5) + 'h' : '' }}
              </p>
            </div>
          </div>

          <!-- Grid de puestos -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-800">
            <div
              v-for="puesto in puestos"
              :key="puesto"
              class="p-5 flex flex-col gap-3"
            >
              <!-- Nombre del puesto -->
              <div class="flex items-center justify-between">
                <span class="text-pink-500 font-black uppercase text-sm tracking-widest">
                  {{ puesto }}
                </span>
                <span class="text-xs text-gray-500 font-bold">
                  {{ getApuntados(ev.id, puesto).length }} persona(s)
                </span>
              </div>

              <!-- Lista de apuntados -->
              <ul class="text-sm text-gray-300 space-y-1 min-h-[40px]">
                <li
                  v-for="turno in getApuntados(ev.id, puesto)"
                  :key="turno.id"
                  class="flex items-center gap-2"
                >
                  <span class="text-green-400 text-xs">●</span>
                  {{ turno.nombre }}
                </li>
                <li v-if="getApuntados(ev.id, puesto).length === 0" class="text-gray-600 italic text-xs">
                  Sin nadie aún
                </li>
              </ul>

              <!-- Botón apuntarse/desapuntarse -->
              <button
                v-if="auth.isAuthenticated"
                @click="toggleTurno(ev.id, puesto)"
                :disabled="procesando === `${ev.id}-${puesto}`"
                :class="misTurnos.includes(`${ev.id}-${puesto}`)
                  ? 'bg-pink-700 hover:bg-red-700 text-white'
                  : 'bg-transparent border border-gray-600 text-gray-300 hover:border-white hover:text-white'"
                class="w-full py-2 text-xs font-black uppercase transition-all disabled:opacity-50"
              >
                <span v-if="procesando === `${ev.id}-${puesto}`">...</span>
                <span v-else-if="misTurnos.includes(`${ev.id}-${puesto}`)">{{ t('txandas_desapuntarse') }}</span>
                <span v-else>{{ t('txandas_apuntarse') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
