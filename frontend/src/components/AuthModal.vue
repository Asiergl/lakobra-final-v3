<script setup>
import { ref, reactive } from 'vue';

const emit = defineEmits(['close', 'login-success']);

const isLoginMode = ref(true);
const errorMsg = ref('');
const successMsg = ref('');
const isLoading = ref(false);

// Datos adaptados a la base de datos de Lakobra
const form = reactive({
    nombre: '',
    dni: '',
    email: '',
    password: '',
    direccion: '' 
});

// Asegúrate de que esta es la ruta donde está tu PHP
const API_URL = 'http://localhost/backend';

const toggleMode = () => {
    isLoginMode.value = !isLoginMode.value;
    errorMsg.value = '';
    successMsg.value = '';
    // Limpiamos el formulario al cambiar
    form.nombre = '';
    form.dni = '';
    form.email = '';
    form.password = '';
    form.direccion = ''; // <-- También limpiamos la dirección
};

const handleSubmit = async () => {
    errorMsg.value = '';
    successMsg.value = '';
    isLoading.value = true;

    // Dependiendo del modo, enviamos a login o register
    const endpoint = isLoginMode.value ? '/auth/login' : '/auth/register';

    try {
        const response = await fetch(`${API_URL}${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(form)
        });

        const data = await response.json();

        if (response.ok) {
            if (isLoginMode.value) {
                // Si es login, cerramos el modal y avisamos a la App
                emit('login-success', data);
                emit('close');
            } else {
                // Si es registro, mostramos éxito y pasamos al modo login
                successMsg.value = '¡Socio registrado con éxito! Ahora inicia sesión.';
                setTimeout(() => {
                    isLoginMode.value = true;
                }, 2000);
            }
        } else {
            errorMsg.value = data.error || 'Ocurrió un error en el servidor';
        }
    } catch (error) {
        errorMsg.value = 'Error de conexión. ¿Está encendido el Backend?';
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-md p-6 relative shadow-2xl">
            
            <button @click="$emit('close')" class="absolute top-4 right-4 text-gray-400 hover:text-white text-2xl font-bold cursor-pointer">
                &times;
            </button>

            <h2 class="text-3xl font-bold text-white mb-6 text-center">
                {{ isLoginMode ? 'Acceder a Lakobra' : 'Nuevo Socio' }}
            </h2>

            <form @submit.prevent="handleSubmit" class="space-y-4">
                
                <div v-if="!isLoginMode" class="space-y-4">
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-2">Nombre completo</label>
                        <input v-model="form.nombre" type="text" required 
                            class="w-full bg-gray-50 text-black border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-2">DNI</label>
                        <input v-model="form.dni" type="text" required 
                            class="w-full bg-gray-50 text-black border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-2">Dirección</label>
                        <input v-model="form.direccion" type="text" placeholder="Ej: Calle Principal 123"
                            class="w-full bg-gray-50 text-black border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors" />
                    </div>
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-bold mb-2">Email</label>
                    <input v-model="form.email" type="email" required 
                        class="w-full bg-gray-50 text-black border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors" />
                </div>
                <div>
                    <label class="block text-gray-300 text-sm font-bold mb-2">Contraseña</label>
                    <input v-model="form.password" type="password" required 
                        class="w-full bg-gray-50 text-black border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors" />
                </div>

                <div v-if="errorMsg" class="bg-red-500/20 border border-red-500/50 text-red-400 p-3 rounded text-sm text-center font-medium">
                    {{ errorMsg }}
                </div>
                <div v-if="successMsg" class="bg-green-500/20 border border-green-500/50 text-green-400 p-3 rounded text-sm text-center font-medium">
                    {{ successMsg }}
                </div>

                <button type="submit" :disabled="isLoading" 
                    class="w-full bg-white text-black font-bold py-3 rounded-lg hover:bg-gray-200 transition-colors mt-6 cursor-pointer disabled:opacity-50">
                    {{ isLoading ? 'Conectando...' : (isLoginMode ? 'Entrar' : 'Registrarse') }}
                </button>
            </form>

            <div class="mt-6 text-center text-gray-400 text-sm">
                <p v-if="isLoginMode">
                    ¿No eres socio? 
                    <button @click="toggleMode" type="button" class="text-white font-bold hover:underline cursor-pointer">Solicita acceso</button>
                </p>
                <p v-else>
                    ¿Ya tienes cuenta? 
                    <button @click="toggleMode" type="button" class="text-white font-bold hover:underline cursor-pointer">Inicia sesión</button>
                </p>
            </div>
            
        </div>
    </div>
</template>