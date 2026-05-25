<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore, API_URL } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'

const authStore = useAuthStore()
const i18n      = useI18nStore()
const t         = (k) => i18n.t(k)

// ─── Mis inscripciones ──────────────────────────────────────
const misInscripciones = ref([])

async function cargarMisInscripciones() {
  try {
    const res = await fetch(`${API_URL}/inscripciones/mis`, { credentials: 'include' })
    if (res.ok) misInscripciones.value = await res.json()
  } catch { /* silencioso */ }
}

async function cancelarInscripcion(id_evento) {
  if (!confirm('¿Cancelar inscripción?')) return
  try {
    const res = await fetch(`${API_URL}/inscripciones/${id_evento}`, {
      method: 'DELETE', credentials: 'include'
    })
    if (res.ok) cargarMisInscripciones()
  } catch { /* silencioso */ }
}

const formatFecha = (f) => {
  const d = new Date(f + 'T00:00')
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(cargarMisInscripciones)

// ─── SPRINT 3: Validación manual por DNI ───────────────────
const dniManual     = ref('')
const resultadoDni  = ref(null)
const cargandoDni   = ref(false)

async function validarPorDni() {
  if (!dniManual.value.trim()) return
  resultadoDni.value = null
  cargandoDni.value  = true
  try {
    const res = await fetch(`${API_URL}/validar/dni`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ dni: dniManual.value.trim().toUpperCase() })
    })
    resultadoDni.value = await res.json()
    dniManual.value = ''
  } catch {
    resultadoDni.value = { estado: 'error', mensaje: 'Error de conexión' }
  } finally {
    cargandoDni.value = false
  }
}

// ─── SPRINT 4: Escáner QR ───────────────────────────────────
const escaner        = ref(null)
const resultadoQR    = ref(null)
const escanerActivo  = ref(false)
let   html5QrScanner = null

async function iniciarEscaner() {
  const { Html5QrcodeScanner } = await import('html5-qrcode')
  html5QrScanner = new Html5QrcodeScanner('reader', {
    fps: 10,
    qrbox: { width: 250, height: 250 },
    rememberLastUsedCamera: true
  })
  html5QrScanner.render(onScanSuccess, onScanFailure)
  escanerActivo.value = true
}

function detenerEscaner() {
  if (html5QrScanner) {
    html5QrScanner.clear().catch(() => {})
    html5QrScanner = null
  }
  escanerActivo.value = false
}

async function onScanSuccess(decodedText) {
  detenerEscaner()
  try {
    const res = await fetch(`${API_URL}/validar/qr`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ token: decodedText })
    })
    resultadoQR.value = await res.json()
  } catch {
    resultadoQR.value = { estado: 'error', mensaje: 'Error de conexión' }
  }
}

function onScanFailure() { /* silencioso */ }

const colorEstado = (estado) => ({
  'ok':         'bg-green-600 text-white',
  'ya_entro':   'bg-yellow-500 text-black',
  'aforo':      'bg-red-600 text-white',
  'sin_evento': 'bg-red-600 text-white',
  'error':      'bg-red-600 text-white',
}[estado] || 'bg-gray-700 text-white')

onUnmounted(() => { if (html5QrScanner) html5QrScanner.clear().catch(() => {}) })
</script>

<template>
  <div class="min-h-screen bg-black text-white p-6 md:p-10">
    <div class="max-w-3xl mx-auto flex flex-col gap-8">

      <!-- Cabecera -->
      <div class="border-b-2 border-pink-600 pb-6">
        <h1 class="text-4xl font-black uppercase tracking-tighter">{{ t('dash_panel') }}</h1>
        <p class="text-pink-500 font-bold mt-1">
          {{ t('dash_hola') }}, {{ authStore.user?.nombre }}
          <span class="ml-2 text-xs bg-zinc-800 px-2 py-1 uppercase text-gray-400">{{ authStore.user?.rol }}</span>
        </p>
      </div>

      <!-- QR del socio -->
      <div class="bg-zinc-900 border border-gray-800 p-8 flex flex-col md:flex-row gap-8 items-center">
        <div class="text-center">
          <img
            v-if="authStore.user?.qr_token"
            :src="`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${authStore.user.qr_token}`"
            alt="QR personal"
            class="border-4 border-white w-48 h-48"
          />
          <div v-else class="w-48 h-48 bg-zinc-800 border border-dashed border-gray-600 flex items-center justify-center text-gray-500 text-sm">
            Sin QR
          </div>
        </div>
        <div>
          <h2 class="text-2xl font-black uppercase mb-2">{{ t('dash_qr_titulo') }}</h2>
          <p class="text-gray-400 text-sm leading-relaxed">{{ t('dash_qr_desc') }}</p>
          <p class="text-xs text-gray-600 mt-4 font-mono break-all">{{ authStore.user?.qr_token }}</p>
        </div>
      </div>

      <!-- Mis inscripciones -->
      <div class="bg-zinc-900 border border-gray-800 p-6">
        <h3 class="text-xl font-black uppercase mb-4 text-pink-500">{{ t('dash_mis_inscripciones') }}</h3>
        <div v-if="misInscripciones.length === 0" class="text-gray-500 text-sm">
          {{ t('dash_sin_inscripciones') }}
          <router-link to="/eventos" class="underline text-white font-bold ml-1">{{ t('dash_ver_agenda') }}</router-link>
        </div>
        <ul v-else class="divide-y divide-gray-800">
          <li v-for="ev in misInscripciones" :key="ev.id" class="py-3 flex justify-between items-center gap-4">
            <div>
              <p class="font-bold">{{ ev.titulo }}</p>
              <p class="text-sm text-gray-500">
                {{ formatFecha(ev.fecha_evento) }}
                {{ ev.hora_inicio ? '· ' + ev.hora_inicio.substring(0,5) + 'h' : '' }}
              </p>
            </div>
            <button
              @click="cancelarInscripcion(ev.id)"
              class="text-xs border border-red-500 text-red-400 px-3 py-1 hover:bg-red-600 hover:text-white transition-colors font-bold uppercase shrink-0"
            >
              {{ t('dash_cancelar') }}
            </button>
          </li>
        </ul>
      </div>

      <!-- ── Controles portero (admin / txandalari) ── -->
      <template v-if="authStore.user?.rol === 'admin' || authStore.user?.rol === 'txandalari'">

        <!-- Validación por DNI -->
        <div class="bg-zinc-900 border border-gray-800 p-6">
          <h3 class="text-xl font-black uppercase mb-4 text-pink-500">Validar por DNI</h3>
          <div class="flex gap-3">
            <input
              v-model="dniManual"
              type="text"
              placeholder="Introduce el DNI"
              @keyup.enter="validarPorDni"
              class="bg-black border border-gray-700 text-white p-3 flex-1 focus:border-pink-500 outline-none uppercase font-mono"
              maxlength="9"
            />
            <button
              @click="validarPorDni"
              :disabled="cargandoDni"
              class="bg-pink-600 hover:bg-pink-500 text-white font-black uppercase px-6 py-3 transition-all disabled:opacity-50"
            >
              {{ cargandoDni ? '...' : 'Validar' }}
            </button>
          </div>
          <!-- Resultado -->
          <div
            v-if="resultadoDni"
            :class="colorEstado(resultadoDni.estado)"
            class="mt-4 p-4 font-bold text-center rounded"
          >
            <p class="text-lg">{{ resultadoDni.nombre }}</p>
            <p>{{ resultadoDni.mensaje }}</p>
            <p v-if="resultadoDni.aforo" class="text-sm mt-1">Aforo: {{ resultadoDni.aforo }}</p>
          </div>
        </div>

        <!-- Escáner QR -->
        <div class="bg-zinc-900 border border-gray-800 p-6">
          <h3 class="text-xl font-black uppercase mb-4 text-pink-500">Escáner QR</h3>
          <div class="flex gap-3 mb-4">
            <button
              v-if="!escanerActivo"
              @click="iniciarEscaner"
              class="bg-green-700 hover:bg-green-600 text-white font-black uppercase px-6 py-3 transition-all"
            >
              📷 Iniciar escáner
            </button>
            <button
              v-else
              @click="detenerEscaner"
              class="bg-red-700 hover:bg-red-600 text-white font-black uppercase px-6 py-3 transition-all"
            >
              ⏹ Detener
            </button>
            <button
              v-if="resultadoQR"
              @click="resultadoQR = null; iniciarEscaner()"
              class="border border-gray-600 text-gray-300 hover:border-white font-bold uppercase px-4 py-3 transition-all"
            >
              Nuevo escaneo
            </button>
          </div>
          <!-- Área del escáner -->
          <div id="reader" class="w-full max-w-sm mx-auto"></div>
          <!-- Resultado QR -->
          <div
            v-if="resultadoQR"
            :class="colorEstado(resultadoQR.estado)"
            class="mt-4 p-6 font-bold text-center text-xl rounded"
          >
            <p class="text-2xl mb-2">
              {{ resultadoQR.estado === 'ok' ? '✅' : resultadoQR.estado === 'ya_entro' ? '⚠️' : '🚫' }}
            </p>
            <p>{{ resultadoQR.nombre || '' }}</p>
            <p class="text-sm mt-1">{{ resultadoQR.mensaje }}</p>
            <p v-if="resultadoQR.aforo" class="text-sm">Aforo: {{ resultadoQR.aforo }}</p>
          </div>
        </div>

      </template>

    </div>
  </div>
</template>
