<?php
// ============================================================
//  index.php  —  Router principal de la API Lakobra
// ============================================================


header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

$base = __DIR__;
require_once $base . '/db.php';
require_once $base . '/functions.php';

$url    = isset($_GET['url']) ? $_GET['url'] : '';
$method = $_SERVER['REQUEST_METHOD'];

$parts    = explode('/', trim($url, '/'));
$resource = isset($parts[0]) ? $parts[0] : '';
$param    = isset($parts[1]) ? $parts[1] : null;

if ($resource === 'auth') {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if     ($param === 'login')    { login($mysqli, $input); }
        elseif ($param === 'register') { register($mysqli, $input); }
        elseif ($param === 'logout')   { logout(); }
        else   { http_response_code(404); echo json_encode(["error" => "Accion de auth no valida"]); }
    }
}

elseif ($resource === 'users' && $param === 'me') {
    if ($method === 'GET') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            exit(json_encode(["authenticated" => false, "error" => "No autorizado"]));
        }
        echo json_encode([
            "authenticated" => true,
            "id"       => $_SESSION['user_id'],
            "nombre"   => $_SESSION['nombre'],
            "rol"      => $_SESSION['rol'],
            "qr_token" => $_SESSION['qr_token']
        ]);
    }
}

elseif ($resource === 'eventos') {
    if      ($method === 'GET')                   { getEventos($mysqli); }
    elseif  ($method === 'POST')                  { $input = json_decode(file_get_contents('php://input'), true); createEvento($mysqli, $input); }
    elseif  ($method === 'PUT'    && $param)      { $input = json_decode(file_get_contents('php://input'), true); updateEvento($mysqli, $param, $input); }
    elseif  ($method === 'DELETE' && $param)      { deleteEvento($mysqli, $param); }
    else    { http_response_code(405); echo json_encode(["error" => "Metodo no permitido"]); }
}

elseif ($resource === 'artistas') {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        createArtista($mysqli, $input);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Metodo no permitido"]);
    }
}

elseif ($resource === 'validar' && $param === 'dni') {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        validarPorDni($mysqli, $input);
    }
}

elseif ($resource === 'validar' && $param === 'qr') {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        validarQR($mysqli, $input);
    }
}

// ── Inscripciones ──────────────────────────────────────────────────────────
// GET  /inscripciones/mis       → mis inscripciones (usuario logado)
// POST /inscripciones/{id}      → inscribirse a evento
// DELETE /inscripciones/{id}    → cancelar inscripcion
elseif ($resource === 'inscripciones') {
    if ($param === 'mis' && $method === 'GET') {
        misInscripciones($mysqli);
    } elseif ($param && $method === 'POST') {
        inscribirseEvento($mysqli, (int)$param);
    } elseif ($param && $method === 'DELETE') {
        cancelarInscripcion($mysqli, (int)$param);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Metodo no permitido"]);
    }
}

// ── Txandas ────────────────────────────────────────────────────────────────
// GET  /txandas       → todos los turnos con nombres
// GET  /txandas/mis   → mis turnos
// POST /txandas/{id}  → apuntarse (body: {puesto})
// DELETE /txandas/{id}→ desapuntarse (body: {puesto})
elseif ($resource === 'txandas') {
    if ($method === 'GET' && $param === 'mis') {
        getMisTxandas($mysqli);
    } elseif ($method === 'GET' && !$param) {
        getTxandas($mysqli);
    } elseif ($method === 'POST' && $param) {
        $input = json_decode(file_get_contents('php://input'), true);
        apuntarseTxanda($mysqli, (int)$param, $input);
    } elseif ($method === 'DELETE' && $param) {
        $input = json_decode(file_get_contents('php://input'), true);
        desapuntarseTxanda($mysqli, (int)$param, $input);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Metodo no permitido"]);
    }
}

// ── PDF aforo (Sprint 5) ───────────────────────────────────────────────────
// GET /pdf/aforo?evento={id}
elseif ($resource === 'pdf' && $param === 'aforo') {
    if ($method === 'GET') {
        generarPdfAforo($mysqli);
    }
}

// ── RGPD ───────────────────────────────────────────────────────────────────
elseif ($resource === 'rgpd' && $param === 'limpiar') {
    if ($method === 'DELETE') {
        limpiarRGPD($mysqli);
    }
}

else {
    http_response_code(404);
    echo json_encode(["error" => "Ruta no encontrada"]);
}
?>
