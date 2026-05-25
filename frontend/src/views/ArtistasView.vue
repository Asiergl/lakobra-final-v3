<script setup>
import { reactive, ref } from 'vue';
import { API_URL } from '../stores/auth';
import Swal from 'sweetalert2';
const cargando = ref(false);

const form = reactive({
    nombre_artista: '',
    email_contacto: '',
    descripcion: ''
});

const enviarSolicitud = async () => {
    cargando.value = true;
    try {
        const res = await fetch(`${API_URL}/artistas`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(form)
        });
        if (res.ok) {
            Swal.fire({
                title: '¡Recibido!',
                text: 'Revisaremos tu propuesta y te contactaremos por email.',
                icon: 'success',
                background: '#111',
                color: '#fff',
                confirmButtonColor: '#db2777'
            });
            form.nombre_artista = ''; form.email_contacto = ''; form.descripcion = '';
        }
    } catch (e) {
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    } finally {
        cargando.value = false;
    }
};
</script>

<template>
    <div class="max-w-2xl mx-auto my-20 p-12 bg-gray-900 rounded-3xl border border-gray-800 shadow-2xl">
        <h2 class="text-4xl font-black mb-2 uppercase text-pink-500 italic">¿Quieres tocar?</h2>
        <p class="text-gray-400 mb-8">Envíanos tu propuesta artística para la próxima asamblea.</p>
        
        <form @submit.prevent="enviarSolicitud" class="flex flex-col gap-5">
            <div class="flex flex-col gap-2">
                <label class="text-xs font-bold uppercase tracking-widest text-gray-500">Nombre Banda / DJ</label>
                <input v-model="form.nombre_artista" type="text" placeholder="Ej: Kobra Elorrieta" required class="bg-black border border-gray-800 p-4 rounded-xl focus:border-pink-500 outline-none transition-all" />
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-xs font-bold uppercase tracking-widest text-gray-500">Email de contacto</label>
                <input v-model="form.email_contacto" type="email" placeholder="contacto@banda.com" required class="bg-black border border-gray-800 p-4 rounded-xl focus:border-pink-500 outline-none transition-all" />
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-xs font-bold uppercase tracking-widest text-gray-500">Descripción del show</label>
                <textarea v-model="form.descripcion" placeholder="Explica tu propuesta..." rows="4" class="bg-black border border-gray-800 p-4 rounded-xl focus:border-pink-500 outline-none transition-all resize-none"></textarea>
            </div>

            <button type="submit" :disabled="cargando" class="bg-pink-600 py-4 mt-4 font-black uppercase tracking-widest rounded-xl hover:bg-pink-500 transition-all disabled:opacity-50">
                {{ cargando ? 'Enviando...' : 'Enviar Propuesta' }}
            </button>
        </form>
    </div>
</template>