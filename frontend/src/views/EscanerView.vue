<script setup>
import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore, API_URL } from '../stores/auth'

const authStore = useAuthStore()
const router    = useRouter()


const escaner    = ref(null)   
const activo     = ref(false)
const procesando = ref(false) 
const resultado = ref(null)

const estadoClase = {
  ok:             'bg-green-500  text-white',
  ya_entro:       'bg-yellow-400 text-black',
  aforo_completo: 'bg-red-600    text-white',
  sin_evento:     'bg-red-600    text-white',
  error:          'bg-red-600    text-white',
}
const estadoEmoji = {
  ok:             '✅',
  ya_entro:       '⚠️',
  aforo_completo: '🚫',
  sin_evento:     '🚫',
  error:          '❌',
}


function iniciar() {
  if (activo.value) return
  activo.value  = true
  resultado.value = null

  
  escaner.value = new Html5QrcodeScanner(
    'reader',
    {
      fps: 10,
      qrbox: { width: 280, height: 280 },
      rememberLastUsedCamera: true,
      showTorchButtonIfSupported: true,  
    },
     false
  )

  escaner.value.render(onScanExito, onScanError)
}


function detener() {
  escaner.value?.clear().catch(() => {})
  escaner.value = null
  activo.value  = false
}


async function onScanExito(token) {
  if (procesando.value) return   // ignora frames extra mientras procesa
  procesando.value = true
  resultado.value  = null

  try {
    const res = await fetch(`${API_URL}/validar/qr`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ token })
    })
    resultado.value = await res.json()
  } catch {
    resultado.value = { estado: 'error', mensaje: 'Error de conexión con el servidor' }
  }

 
  setTimeout(() => { procesando.value = false }, 3000)
}


function onScanError() { /* frames sin QR — no hacer nada */ }


function limpiar() {
  resultado.value  = null
  procesando.value = false
}

onUnmounted(detener)
</script>

<template>
  <div class="min-h-screen bg-black text-white flex flex-col items-center py-10 px-4">

    <h1 class="text-4xl font-black uppercase mb-1 tracking-tighter">
      Escáner <span class="text-pink-600">QR</span>
    </h1>
    <p class="text-gray-400 text-sm uppercase tracking-widest mb-8">
      Control de acceso — Lakobra
    </p>

    
    <transition name="slide">
      <div
        v-if="resultado"
        :class="estadoClase[resultado.estado] || 'bg-gray-700 text-white'"
        class="w-full max-w-sm rounded-2xl p-8 mb-6 text-center shadow-2xl"
      >
        <div class="text-6xl mb-3">{{ estadoEmoji[resultado.estado] || '❓' }}</div>
        <div class="text-2xl font-black uppercase">{{ resultado.mensaje }}</div>
        <div v-if="resultado.nombre" class="text-xl font-bold mt-2">{{ resultado.nombre }}</div>
        <div v-if="resultado.evento"  class="text-sm mt-1 opacity-75">{{ resultado.evento }}</div>

        <button
          @click="limpiar"
          class="mt-6 bg-black/30 hover:bg-black/50 px-6 py-2 rounded-full font-bold uppercase text-sm"
        >
          Siguiente 
        </button>
      </div>
    </transition>

    
    <div class="w-full max-w-sm">
      <div id="reader" class="rounded-xl overflow-hidden border-2 border-gray-700"></div>
    </div>

    
    <div class="flex gap-4 mt-6">
      <button
        v-if="!activo"
        @click="iniciar"
        class="bg-pink-600 hover:bg-pink-500 text-white font-black uppercase px-8 py-3 rounded-full tracking-widest"
      >
         Iniciar cámara
      </button>
      <button
        v-else
        @click="detener"
        class="bg-gray-700 hover:bg-gray-600 text-white font-bold uppercase px-8 py-3 rounded-full"
      >
        ✖ Detener
      </button>
    </div>

    
    <p v-if="procesando && !resultado" class="mt-4 text-pink-400 animate-pulse font-bold uppercase text-sm">
      Procesando…
    </p>

  
    <button
      @click="router.push('/dashboard')"
      class="mt-10 text-gray-500 hover:text-white text-sm underline"
    >
       Volver al panel
    </button>

  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all .3s ease; }
.slide-enter-from, .slide-leave-to       { opacity: 0; transform: translateY(-12px); }
</style>
