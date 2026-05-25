<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18nStore } from '../stores/i18n'
import { API_URL } from '../stores/auth'

const i18n   = useI18nStore()
const t      = (k) => i18n.t(k)
const router = useRouter()

const eventosDestacados = ref([])

const formatFecha = (f) => {
  const d = new Date(f + 'T00:00')
  return {
    dia: d.toLocaleDateString('es-ES', { weekday: 'short' }),
    num: d.toLocaleDateString('es-ES', { day: '2-digit' }),
    mes: d.toLocaleDateString('es-ES', { month: 'short' }).toUpperCase(),
  }
}

onMounted(async () => {
  try {
    const res = await fetch(`${API_URL}/eventos`)
    if (res.ok) {
      const todos = await res.json()
      eventosDestacados.value = todos
        .filter(e => e.estado === 'confirmado' && e.visible_publico)
        .slice(0, 3)
    }
  } catch { /* silencioso */ }
})
</script>

<template>
  <div class="bg-black min-h-screen text-white">

    <!-- ── HERO ── -->
    <section class="relative min-h-[90vh] flex flex-col justify-center items-center text-center px-6 border-b-4 border-pink-600 overflow-hidden">
      <!-- Fondo decorativo -->
      <div class="absolute inset-0 pointer-events-none opacity-10">
        <div class="absolute top-0 left-0 w-96 h-96 bg-pink-600 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-pink-800 rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>
      </div>

      <div class="relative z-10 max-w-4xl">
        <p class="text-pink-500 font-black uppercase tracking-[0.3em] text-sm mb-4">
          Bilbao · Underground · Autogestión
        </p>
        <h1 class="text-[clamp(4rem,15vw,10rem)] font-black leading-none tracking-tighter uppercase mb-4">
          LAKOBRA
        </h1>
        <p class="text-gray-300 text-xl md:text-2xl font-bold mb-12 uppercase tracking-widest">
          {{ t('home_hero_sub') }}
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            @click="router.push('/eventos')"
            class="bg-pink-600 hover:bg-pink-500 text-white font-black uppercase px-10 py-4 text-lg tracking-widest transition-all hover:scale-105"
          >
            {{ t('home_ver_agenda') }}
          </button>
          <button
            @click="router.push('/solicitud-artista')"
            class="border-2 border-white hover:border-pink-500 hover:text-pink-500 text-white font-black uppercase px-10 py-4 text-lg tracking-widest transition-all"
          >
            {{ t('home_tocar') }}
          </button>
        </div>
      </div>
    </section>

    <!-- ── QUÉ ES ── -->
    <section class="py-24 px-6 border-b border-gray-900">
      <div class="max-w-5xl mx-auto grid md:grid-cols-2 gap-16 items-center">
        <div>
          <h2 class="text-5xl font-black uppercase tracking-tighter mb-6">
            {{ t('home_que_es') }}
          </h2>
          <p class="text-gray-400 text-lg leading-relaxed">
            {{ t('home_desc') }}
          </p>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-zinc-900 border border-gray-800 p-6 text-center">
            <span class="text-4xl font-black text-pink-600 block">🎸</span>
            <span class="text-sm font-bold uppercase text-gray-300 mt-2 block">Música en vivo</span>
          </div>
          <div class="bg-zinc-900 border border-gray-800 p-6 text-center">
            <span class="text-4xl font-black text-pink-600 block">🤝</span>
            <span class="text-sm font-bold uppercase text-gray-300 mt-2 block">Autogestión</span>
          </div>
          <div class="bg-zinc-900 border border-gray-800 p-6 text-center">
            <span class="text-4xl font-black text-pink-600 block">🏴</span>
            <span class="text-sm font-bold uppercase text-gray-300 mt-2 block">Asamblea</span>
          </div>
          <div class="bg-zinc-900 border border-gray-800 p-6 text-center">
            <span class="text-4xl font-black text-pink-600 block">📍</span>
            <span class="text-sm font-bold uppercase text-gray-300 mt-2 block">Bilbao</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ── PRÓXIMOS CONCIERTOS ── -->
    <section class="py-24 px-6 border-b border-gray-900">
      <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-end mb-12">
          <h2 class="text-5xl font-black uppercase tracking-tighter">
            {{ t('home_proximos') }}
          </h2>
          <button
            @click="router.push('/eventos')"
            class="text-pink-500 font-black uppercase text-sm hover:text-pink-400 underline decoration-pink-600"
          >
            {{ t('home_ver_todo') }}
          </button>
        </div>

        <div v-if="eventosDestacados.length === 0" class="text-gray-600 text-center py-12 border border-dashed border-gray-800 uppercase font-bold tracking-widest">
          {{ t('agenda_vacia') }}
        </div>

        <div class="flex flex-col gap-4">
          <div
            v-for="ev in eventosDestacados"
            :key="ev.id"
            class="flex gap-6 items-center bg-zinc-900/60 border border-gray-800 hover:border-pink-600 transition-colors p-5 group cursor-pointer"
            @click="router.push('/eventos')"
          >
            <!-- Fecha -->
            <div class="text-center bg-black p-4 min-w-[70px] border border-gray-800">
              <span class="text-pink-600 text-xs font-black uppercase block">{{ formatFecha(ev.fecha_evento).dia }}</span>
              <span class="text-3xl font-black text-white block leading-none">{{ formatFecha(ev.fecha_evento).num }}</span>
              <span class="text-gray-500 text-xs font-bold uppercase block">{{ formatFecha(ev.fecha_evento).mes }}</span>
            </div>
            <!-- Info -->
            <div class="flex-1">
              <h3 class="text-2xl font-black uppercase group-hover:text-pink-500 transition-colors">{{ ev.titulo }}</h3>
              <p class="text-gray-500 text-sm uppercase font-bold mt-1">
                {{ ev.hora_inicio ? ev.hora_inicio.substring(0,5) + 'h' : t('por_confirmar') }}
                · {{ t('aforo') }} {{ ev.aforo_max }}
              </p>
            </div>
            <span class="text-pink-600 font-black text-2xl">→</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ── CTA ARTISTAS ── -->
    <section class="py-24 px-6">
      <div class="max-w-3xl mx-auto text-center">
        <h2 class="text-5xl font-black uppercase tracking-tighter mb-6">
          {{ t('home_tocar') }}
        </h2>
        <p class="text-gray-400 text-lg mb-10">
          ¿Tienes un grupo o proyecto musical? Mándanos tu propuesta y la valoramos en asamblea.
        </p>
        <button
          @click="router.push('/solicitud-artista')"
          class="bg-white text-black font-black uppercase px-12 py-4 text-lg tracking-widest hover:bg-pink-600 hover:text-white transition-all"
        >
          {{ t('home_tocar') }} →
        </button>
      </div>
    </section>

  </div>
</template>
