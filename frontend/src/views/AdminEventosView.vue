<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useAuthStore, API_URL } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'

const auth = useAuthStore()
const i18n = useI18nStore()
const t = (k) => i18n.t(k)

const eventos   = ref([])
const editando  = ref(false)
const msgRgpd   = ref(null)
const modalBorrar     = ref(false)
const eventoABorrar   = ref(null)

const form = reactive({
  id: null,
  titulo: '',
  fecha_evento: '',
  hora_inicio: '',
  aforo_max: 120,
  estado: 'confirmado',
  visible_publico: 1
})

const cargarEventos = async () => {
  const res = await fetch(`${API_URL}/eventos`, { credentials: 'include' })
  if (res.ok) eventos.value = await res.json()
}

const guardarEvento = async () => {
  const method = editando.value ? 'PUT' : 'POST'
  const url    = editando.value ? `${API_URL}/eventos/${form.id}` : `${API_URL}/eventos`
  const res = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(form)
  })
  if (res.ok) {
    resetForm()
    cargarEventos()
  }
}

const pedirConfirmacion = (id) => {
  eventoABorrar.value = id
  modalBorrar.value = true
}

const confirmarBorrado = async () => {
  const res = await fetch(`${API_URL}/eventos/${eventoABorrar.value}`, { method: 'DELETE', credentials: 'include' })
  if (res.ok) cargarEventos()
  modalBorrar.value = false
  eventoABorrar.value = null
}

const prepararEdicion = (ev) => {
  editando.value = true
  Object.assign(form, ev)
}

const resetForm = () => {
  editando.value = false
  Object.assign(form, { id: null, titulo: '', fecha_evento: '', hora_inicio: '', aforo_max: 120, estado: 'confirmado', visible_publico: 1 })
}

// ── PDF: abre directamente la descarga ────────────────────
const exportarPdf = (id_evento) => {
  window.open(`${API_URL}/pdf/aforo?evento=${id_evento}`, '_blank')
}

// ── RGPD ──────────────────────────────────────────────────
const limpiarRgpd = async () => {
  if (!confirm('¿Borrar todas las asistencias con más de 30 días?')) return
  const res = await fetch(`${API_URL}/rgpd/limpiar`, { method: 'DELETE', credentials: 'include' })
  const data = await res.json()
  msgRgpd.value = data.message || data.error || 'Hecho'
  setTimeout(() => msgRgpd.value = null, 4000)
}

onMounted(cargarEventos)
</script>

<template>
  <div class="min-h-screen bg-black text-white p-6 md:p-10">

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
      <h1 class="text-4xl font-black uppercase tracking-tighter">
        {{ t('admin_titulo') }}
      </h1>
      <!-- Herramientas RGPD -->
      <div class="flex gap-3 items-center flex-wrap">
        <button
          @click="limpiarRgpd"
          class="bg-red-900 hover:bg-red-700 border border-red-700 text-white font-bold uppercase text-xs px-4 py-2 transition-all"
        >
          {{ t('admin_rgpd') }}
        </button>
        <span v-if="msgRgpd" class="text-green-400 text-xs font-bold">{{ msgRgpd }}</span>
      </div>
    </div>

    <!-- Formulario nuevo/editar evento -->
    <div class="bg-zinc-900 border border-gray-800 p-6 mb-10">
      <h3 class="text-xl font-bold mb-5 text-pink-500 uppercase">
        {{ editando ? t('admin_editar') : t('admin_nuevo') }}
      </h3>
      <form @submit.prevent="guardarEvento" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input
          v-model="form.titulo"
          type="text"
          placeholder="Título del evento"
          class="bg-black p-3 border border-gray-700 text-white focus:border-pink-500 outline-none"
          required
        />
        <input
          v-model="form.fecha_evento"
          type="date"
          class="bg-black p-3 border border-gray-700 text-white focus:border-pink-500 outline-none"
          required
        />
        <input
          v-model="form.hora_inicio"
          type="time"
          class="bg-black p-3 border border-gray-700 text-white focus:border-pink-500 outline-none"
        />
        <input
          v-model="form.aforo_max"
          type="number"
          placeholder="Aforo máximo"
          class="bg-black p-3 border border-gray-700 text-white focus:border-pink-500 outline-none"
        />
        <select v-model="form.estado" class="bg-black p-3 border border-gray-700 text-white focus:border-pink-500 outline-none">
          <option value="pendiente">Pendiente</option>
          <option value="confirmado">Confirmado</option>
          <option value="cancelado">Cancelado</option>
        </select>
        <select v-model="form.visible_publico" class="bg-black p-3 border border-gray-700 text-white focus:border-pink-500 outline-none">
          <option :value="1">{{ t('admin_publico') }}</option>
          <option :value="0">{{ t('admin_oculto') }}</option>
        </select>
        <div class="md:col-span-2 flex gap-3">
          <button type="submit" class="bg-pink-600 hover:bg-pink-500 text-white font-black uppercase px-8 py-3 transition-all">
            {{ t('admin_guardar') }}
          </button>
          <button v-if="editando" @click="resetForm" type="button" class="bg-gray-700 hover:bg-gray-600 text-white font-bold uppercase px-6 py-3 transition-all">
            {{ t('admin_cancelar') }}
          </button>
        </div>
      </form>
    </div>

    <!-- Tabla de eventos -->
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="border-b-2 border-pink-600 text-pink-500 uppercase text-xs tracking-widest">
            <th class="p-3">{{ t('admin_fecha') }}</th>
            <th class="p-3">{{ t('admin_evento') }}</th>
            <th class="p-3">{{ t('admin_estado') }}</th>
            <th class="p-3">Vis.</th>
            <th class="p-3">{{ t('admin_acciones') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="ev in eventos"
            :key="ev.id"
            class="border-b border-gray-800 hover:bg-zinc-900 transition-colors"
          >
            <td class="p-3 text-gray-400 whitespace-nowrap">{{ ev.fecha_evento }}</td>
            <td class="p-3 font-bold text-white">{{ ev.titulo }}</td>
            <td class="p-3">
              <span
                :class="{
                  'text-green-400': ev.estado === 'confirmado',
                  'text-yellow-400': ev.estado === 'pendiente',
                  'text-red-400':   ev.estado === 'cancelado'
                }"
                class="font-bold uppercase text-xs"
              >
                {{ ev.estado }}
              </span>
            </td>
            <td class="p-3">
              <span :class="ev.visible_publico ? 'text-green-400' : 'text-gray-600'" class="text-xs font-bold">
                {{ ev.visible_publico ? '✓' : '✗' }}
              </span>
            </td>
            <td class="p-3">
              <div class="flex flex-wrap gap-2">
                <button @click="prepararEdicion(ev)" class="text-blue-400 hover:text-blue-300 font-bold text-xs uppercase underline">
                  {{ t('admin_editar') }}
                </button>
                <button @click="pedirConfirmacion(ev.id)" class="text-red-400 hover:text-red-300 font-bold text-xs uppercase underline">
                  {{ t('admin_borrar') }}
                </button>
                <button
                  @click="exportarPdf(ev.id)"
                  class="text-yellow-400 hover:text-yellow-300 font-bold text-xs uppercase underline"
                  title="Exportar listado de asistencia en PDF"
                >
                  📄 PDF
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal confirmación borrado -->
    <div v-if="modalBorrar" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
      <div class="bg-gray-900 border border-gray-700 w-full max-w-sm p-6 relative shadow-2xl">
        <h2 class="text-xl font-black uppercase text-white mb-2">¿Borrar evento?</h2>
        <p class="text-gray-400 text-sm mb-6">Esta acción no se puede deshacer.</p>
        <div class="flex gap-3">
          <button @click="confirmarBorrado" class="bg-red-600 hover:bg-red-500 text-white font-black uppercase px-6 py-2 transition-all">
            {{ t('admin_borrar') }}
          </button>
          <button @click="modalBorrar = false" class="bg-gray-700 hover:bg-gray-600 text-white font-bold uppercase px-6 py-2 transition-all">
            {{ t('admin_cancelar') }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
