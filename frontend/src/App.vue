<script setup>
import { onMounted } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useI18nStore } from './stores/i18n'

const authStore = useAuthStore()
const i18nStore = useI18nStore()
const router    = useRouter()
const t = (k) => i18nStore.t(k)

const idiomas = ['es', 'eu', 'en']
const flagLabels = { es: 'ES', eu: 'EU', en: 'EN' }

onMounted(() => { authStore.checkSession() })

const cerrarSesion = async () => {
  await authStore.logout()
  router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-black text-white flex flex-col font-sans selection:bg-pink-600 selection:text-white">

    <header class="w-full bg-zinc-950 border-b-2 border-pink-600 sticky top-0 z-40 shadow-2xl">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center p-4 gap-4">

        <router-link to="/" class="flex flex-col items-center md:items-start group">
          <span class="text-3xl font-black tracking-tighter uppercase text-white group-hover:text-pink-500 transition-colors leading-none">Lakobra</span>
          <span class="text-xs font-bold tracking-widest text-pink-600 uppercase">Kolektiboa / Bilbao</span>
        </router-link>

        <nav class="flex flex-wrap justify-center gap-4 md:gap-6 text-sm font-bold uppercase tracking-widest text-gray-300">
          <router-link to="/eventos" class="hover:text-white hover:underline decoration-pink-600 decoration-2 underline-offset-8 transition-all">
            {{ t('nav_agenda') }}
          </router-link>
          <router-link to="/solicitud-artista" class="hover:text-white hover:underline decoration-pink-600 decoration-2 underline-offset-8 transition-all">
            {{ t('nav_artistas') }}
          </router-link>
          <router-link
            v-if="authStore.isAuthenticated"
            to="/txandas"
            class="hover:text-white hover:underline decoration-pink-600 decoration-2 underline-offset-8 transition-all"
          >
            {{ t('nav_txandas') }}
          </router-link>

          <template v-if="authStore.user?.rol === 'admin'">
            <router-link to="/admin/eventos" class="text-pink-500 border border-pink-500 px-3 py-1 hover:bg-pink-600 hover:text-white transition-all">
              {{ t('nav_eventos') }}
            </router-link>
          </template>

          <template v-if="authStore.user?.rol === 'admin' || authStore.user?.rol === 'txandalari'">
            <router-link to="/escanear" class="text-green-400 border border-green-500 px-3 py-1 hover:bg-green-600 hover:text-white transition-all">
              {{ t('nav_escanear') }}
            </router-link>
          </template>
        </nav>

        <div class="flex items-center gap-3">

          <!-- Selector de idioma -->
          <div class="flex gap-1">
            <button
              v-for="lang in idiomas"
              :key="lang"
              @click="i18nStore.cambiarIdioma(lang)"
              :class="i18nStore.idioma === lang
                ? 'bg-pink-600 text-white'
                : 'bg-zinc-800 text-gray-400 hover:text-white hover:bg-zinc-700'"
              class="text-xs font-black px-2 py-1 uppercase transition-all"
            >
              {{ flagLabels[lang] }}
            </button>
          </div>

          <!-- Auth -->
          <template v-if="!authStore.isAuthenticated">
            <router-link to="/auth" class="bg-white text-black font-black px-5 py-2 uppercase tracking-wide hover:bg-pink-600 hover:text-white transition-all">
              {{ t('nav_socios') }}
            </router-link>
          </template>

          <template v-else>
            <div class="flex items-center gap-3 bg-zinc-900 px-4 py-2 border border-gray-800">
              <router-link to="/dashboard" class="text-sm font-bold uppercase text-white hover:text-pink-500">
                {{ authStore.user?.nombre }}
              </router-link>
              <button @click="cerrarSesion" class="text-xs bg-red-600 text-white font-bold uppercase px-2 py-1 hover:bg-red-500 transition-all">
                {{ t('nav_salir') }}
              </button>
            </div>
          </template>

        </div>
      </div>
    </header>

    <main class="grow flex flex-col">
      <RouterView />
    </main>

    <footer class="bg-zinc-950 border-t border-gray-900 py-12 px-6 mt-auto">
      <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left text-gray-500">
        <div>
          <h4 class="text-white font-black uppercase mb-4 tracking-widest text-lg">Lakobra Kolektiboa</h4>
          <p class="text-sm leading-relaxed">Proyecto de sala de conciertos en Bilbao.<br>Autogestionada, asamblearia y underground.</p>
        </div>
        <div>
          <h4 class="text-white font-black uppercase mb-4 tracking-widest text-lg">Contacto</h4>
          <p class="text-sm">Bilbao, Bizkaia<br>Circuito no comercial</p>
        </div>
        <div>
          <h4 class="text-white font-black uppercase mb-4 tracking-widest text-lg">Legal</h4>
          <p class="text-sm">Condiciones generales</p>
          <p class="text-sm">Política de privacidad</p>
        </div>
      </div>
      <div class="text-center text-xs mt-12 text-gray-700 font-bold uppercase tracking-widest">
        © 2026 Lakobra Kolektiboa. Todos los derechos reservados.
      </div>
    </footer>

  </div>
</template>
