<script setup>
import { ref, reactive } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const isLoginMode = ref(true)
const errorMsg = ref('')
const successMsg = ref('')
const errores = reactive({
  nombre: '',
  dni: '',
  email: '',
  password: ''
})

const form = reactive({
  email: '',
  password: '',
  nombre: '',
  dni: '',
  direccion: ''
})

const toggleMode = () => {
  isLoginMode.value = !isLoginMode.value
  errorMsg.value = ''
  successMsg.value = ''
  errores.nombre = ''
  errores.dni = ''
  errores.email = ''
  errores.password = ''
}

function validarFormulario() {
  let valido = true
  errores.nombre = ''
  errores.dni = ''
  errores.email = ''
  errores.password = ''

  if (form.nombre.trim().length < 3) {
    errores.nombre = 'El nombre debe tener al menos 3 caracteres.'
    valido = false
  }

  // DNI español: 8 números seguidos de 1 letra
  const regexDNI = /^\d{8}[A-Za-z]$/
  if (!regexDNI.test(form.dni.trim())) {
    errores.dni = 'El DNI debe tener 8 números y 1 letra (ej: 12345678A).'
    valido = false
  }

  // Email básico
  const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!regexEmail.test(form.email.trim())) {
    errores.email = 'Introduce un correo electrónico válido.'
    valido = false
  }

  if (form.password.length < 8) {
    errores.password = 'La contraseña debe tener al menos 8 caracteres.'
    valido = false
  }

  return valido
}

const handleSubmit = async () => {
  errorMsg.value = ''
  successMsg.value = ''

  // Si estamos en registro, validamos antes de enviar
  if (!isLoginMode.value) {
    if (!validarFormulario()) return
  }
  
  try {
    if (isLoginMode.value) {
      await authStore.login(form.email, form.password)
      router.push('/dashboard')
    } else {
      await authStore.register(form)
      successMsg.value = 'Registro exitoso. Ahora puedes iniciar sesión.'
      isLoginMode.value = true
    }
  } catch (error) {
    errorMsg.value = error.message
  }
}
</script>

<template>
  <div class="flex justify-center items-center min-h-[80vh] bg-white text-black">
    <div class="w-full max-w-md p-8 border-2 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
      <h2 class="text-3xl font-black mb-6 uppercase">{{ isLoginMode ? 'Entrar' : 'Unirse a Lakobra' }}</h2>
      
      <p v-if="errorMsg" class="bg-black text-white p-3 mb-4 font-bold">{{ errorMsg }}</p>
      <p v-if="successMsg" class="border-2 border-black p-3 mb-4 font-bold">{{ successMsg }}</p>

      <form @submit.prevent="handleSubmit" class="flex flex-col gap-4">
        <template v-if="!isLoginMode">
          <div>
            <input v-model="form.nombre" type="text" placeholder="Nombre completo *" class="border-2 border-black p-3 outline-none focus:bg-gray-100 w-full" :class="{ 'border-red-600': errores.nombre }" />
            <p v-if="errores.nombre" class="text-red-600 text-sm mt-1">{{ errores.nombre }}</p>
          </div>
          <div>
            <input v-model="form.dni" type="text" placeholder="DNI * (ej: 12345678A)" class="border-2 border-black p-3 outline-none focus:bg-gray-100 w-full" :class="{ 'border-red-600': errores.dni }" />
            <p v-if="errores.dni" class="text-red-600 text-sm mt-1">{{ errores.dni }}</p>
          </div>
          <input v-model="form.direccion" type="text" placeholder="Dirección" class="border-2 border-black p-3 outline-none focus:bg-gray-100 w-full" />
        </template>
        
        <div>
          <input v-model="form.email" type="text" placeholder="Correo electrónico *" class="border-2 border-black p-3 outline-none focus:bg-gray-100 w-full" :class="{ 'border-red-600': errores.email }" />
          <p v-if="errores.email" class="text-red-600 text-sm mt-1">{{ errores.email }}</p>
        </div>
        <div>
          <input v-model="form.password" type="password" placeholder="Contraseña *" class="border-2 border-black p-3 outline-none focus:bg-gray-100 w-full" :class="{ 'border-red-600': errores.password }" />
          <p v-if="errores.password" class="text-red-600 text-sm mt-1">{{ errores.password }}</p>
        </div>

        <button type="submit" class="bg-black text-white font-bold py-3 px-4 uppercase hover:bg-white hover:text-black border-2 border-black transition-colors mt-2">
          {{ isLoginMode ? 'Iniciar Sesión' : 'Registrarse' }}
        </button>
      </form>

      <div class="mt-6 text-center border-t-2 border-black pt-4">
        <button @click="toggleMode" class="font-bold underline hover:text-gray-600">
          {{ isLoginMode ? '¿No tienes cuenta? Regístrate' : '¿Ya tienes cuenta? Inicia sesión' }}
        </button>
      </div>
    </div>
  </div>
</template>