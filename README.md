# LaKobra Kolektiboa — Aplicación Web

Plataforma web para la gestión de una sala de conciertos autogestionada en Bilbao. Permite a los socios consultar la agenda, inscribirse a eventos, gestionar sus turnos de voluntariado (txandas) y acceder mediante QR. Los administradores disponen de un panel completo para gestionar eventos, exportar aforos en PDF y cumplir con la RGPD.

---

## Tecnologías

**Frontend**
- Vue 3 (Composition API con `<script setup>`)
- Vue Router 4
- Pinia (gestión de estado)
- Tailwind CSS 4
- Vite 7
- html5-qrcode (escáner QR)

**Backend**
- PHP (router artesanal sin framework)
- MySQL / MariaDB
- Apache con mod_rewrite (XAMPP)

---

## Estructura del proyecto

```
lakobra/
├── backend/
│   ├── .htaccess        # Rewrite rules → index.php?url=...
│   ├── db.php           # Conexión a la base de datos
│   ├── index.php        # Router principal de la API
│   └── functions.php    # Lógica de negocio (auth, eventos, txandas...)
└── frontend/
    ├── src/
    │   ├── components/
    │   │   └── AuthModal.vue
    │   ├── router/
    │   │   └── index.js
    │   ├── stores/
    │   │   ├── auth.js    # Sesión + constante API_URL
    │   │   └── i18n.js    # Traducciones ES / EU / EN
    │   ├── views/
    │   │   ├── HomeView.vue
    │   │   ├── EventosView.vue
    │   │   ├── DashboardView.vue
    │   │   ├── AdminEventosView.vue
    │   │   ├── ArtistasView.vue
    │   │   ├── EscanerView.vue
    │   │   └── TxandasView.vue
    │   ├── App.vue
    │   └── main.js
    ├── vite.config.js
    └── package.json
```

---

## Instalación

### Requisitos previos
- XAMPP (Apache + MySQL) con PHP 8+
- Node.js 20+ o 22+

### 1. Clonar / descomprimir el proyecto

Coloca la carpeta del proyecto dentro de `htdocs` de XAMPP y **renómbrala a `lakobra`**:

```
C:/xampp/htdocs/lakobra/
```

> El nombre de la carpeta es importante: el proxy de Vite apunta exactamente a esa ruta.

### 2. Crear la base de datos

1. Abre phpMyAdmin (`http://localhost/phpmyadmin`)
2. Crea una base de datos llamada `lakobra`
3. Importa el fichero SQL del proyecto (si lo tienes) o crea las tablas manualmente

### 3. Configurar la conexión a la base de datos

Edita `backend/db.php` si tus credenciales de MySQL son distintas a las de XAMPP por defecto:

```php
$host = 'localhost';
$user = 'root';
$pass = '';          // En XAMPP suele estar vacía
$db   = 'lakobra';
```

### 4. Instalar dependencias del frontend

```bash
cd frontend
npm install
```

### 5. Arrancar el servidor de desarrollo

Con XAMPP corriendo (Apache activado):

```bash
cd frontend
npm run dev
```

Abre el navegador en `http://localhost:5173`

---

## Roles de usuario

| Rol | Acceso |
|---|---|
| (sin sesión) | Home, Agenda pública, Solicitud de artista |
| `socio` | Dashboard, inscripción a eventos, txandas |
| `txandalari` | Todo lo anterior + escáner QR |
| `admin` | Todo lo anterior + gestión de eventos, exportar PDF, limpieza RGPD |

---

## Rutas de la aplicación

| Ruta | Vista | Protección |
|---|---|---|
| `/` | Home | Pública |
| `/eventos` | Agenda de conciertos | Pública |
| `/solicitud-artista` | Formulario para artistas | Pública |
| `/auth` | Login / Registro | Pública |
| `/dashboard` | Panel del socio + QR | Requiere sesión |
| `/txandas` | Gestión de turnos | Requiere sesión |
| `/admin/eventos` | Panel de administración | Requiere rol `admin` |
| `/escanear` | Escáner QR de entrada | Requiere rol `admin` o `txandalari` |

---

## Endpoints de la API

La API escucha en `/api/...` (el proxy de Vite redirige al backend PHP).

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/auth/login` | Iniciar sesión |
| POST | `/api/auth/register` | Registrar nuevo socio |
| POST | `/api/auth/logout` | Cerrar sesión |
| GET | `/api/users/me` | Datos de la sesión activa |
| GET | `/api/eventos` | Listar eventos |
| POST | `/api/eventos` | Crear evento (admin) |
| PUT | `/api/eventos/:id` | Editar evento (admin) |
| DELETE | `/api/eventos/:id` | Borrar evento (admin) |
| POST | `/api/artistas` | Enviar solicitud de artista |
| GET | `/api/inscripciones/mis` | Mis inscripciones |
| POST | `/api/inscripciones/:id` | Inscribirse a un evento |
| DELETE | `/api/inscripciones/:id` | Cancelar inscripción |
| GET | `/api/txandas` | Listar todos los turnos |
| GET | `/api/txandas/mis` | Mis turnos |
| POST | `/api/txandas/:id` | Apuntarse a un turno |
| DELETE | `/api/txandas/:id` | Desapuntarse de un turno |
| POST | `/api/validar/dni` | Validar entrada por DNI |
| POST | `/api/validar/qr` | Validar entrada por QR |
| GET | `/api/pdf/aforo?evento=:id` | Exportar PDF de aforo (admin) |
| DELETE | `/api/rgpd/limpiar` | Limpiar asistencias >30 días (admin) |

---

## Internacionalización

La aplicación soporta tres idiomas gestionados desde `src/stores/i18n.js` sin dependencias externas. El idioma elegido se persiste en `localStorage`.

| Código | Idioma |
|---|---|
| `es` | Castellano (por defecto) |
| `eu` | Euskera |
| `en` | Inglés |

---

## Scripts disponibles

```bash
npm run dev       # Servidor de desarrollo con hot-reload
npm run build     # Build de producción en /dist
npm run preview   # Previsualizar el build de producción
npm run lint      # Pasar ESLint con auto-fix
npm run format    # Formatear con Prettier
```
