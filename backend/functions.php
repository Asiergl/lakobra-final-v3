<?php
// ============================================================
//  functions.php  —  Funciones de la API Lakobra
// ============================================================

// ============================================================
//  AUTENTICACION
// ============================================================

function login($mysqli, $input) {
    if (empty($input['email']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(["error" => "Email y contrasena son obligatorios"]);
        return;
    }

    $stmt = $mysqli->prepare("SELECT id, nombre, password, rol, qr_token FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $input['email']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($input['password'], $row['password'])) {
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['nombre']   = $row['nombre'];
            $_SESSION['rol']      = $row['rol'];
            $_SESSION['qr_token'] = $row['qr_token'];

            echo json_encode([
                "message" => "Login exitoso",
                "rol"     => $row['rol'],
                "nombre"  => $row['nombre']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Contrasena incorrecta"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Usuario no encontrado"]);
    }
}

function register($mysqli, $input) {
    if (empty($input['nombre']) || empty($input['dni']) || empty($input['email']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(["error" => "Rellena todos los campos obligatorios (Nombre, DNI, Email, Password)"]);
        return;
    }

    $check = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ? OR dni = ?");
    $check->bind_param("ss", $input['email'], $input['dni']);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        http_response_code(409);
        echo json_encode(["error" => "El correo o el DNI ya estan registrados"]);
        return;
    }
    $check->close();

    $hash      = password_hash($input['password'], PASSWORD_BCRYPT);
    $direccion = isset($input['direccion']) ? $input['direccion'] : null;
    $qr_token  = bin2hex(random_bytes(16));

    $stmt = $mysqli->prepare("INSERT INTO usuarios (nombre, dni, email, password, qr_token, direccion, rol) VALUES (?, ?, ?, ?, ?, ?, 'socio')");
    $stmt->bind_param("ssssss", $input['nombre'], $input['dni'], $input['email'], $hash, $qr_token, $direccion);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["message" => "Registrado correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al registrar en la base de datos"]);
    }
}

function logout() {
    session_destroy();
    echo json_encode(["message" => "Sesion cerrada"]);
}

// ============================================================
//  EVENTOS
// ============================================================

function getEventos($mysqli) {
    $isAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
    $query = $isAdmin
        ? "SELECT * FROM eventos ORDER BY fecha_evento DESC"
        : "SELECT * FROM eventos WHERE visible_publico = 1 ORDER BY fecha_evento DESC";

    $result  = $mysqli->query($query);
    $eventos = [];
    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }
    echo json_encode($eventos);
}

function createEvento($mysqli, $input) {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["error" => "Acceso denegado. Solo administradores."]);
        return;
    }
    if (empty($input['titulo']) || empty($input['fecha_evento'])) {
        http_response_code(400);
        echo json_encode(["error" => "El titulo y la fecha son obligatorios"]);
        return;
    }

    $hora    = $input['hora_inicio']    ?? null;
    $aforo   = $input['aforo_max']      ?? 120;
    $estado  = $input['estado']         ?? 'pendiente';
    $visible = $input['visible_publico']?? 0;

    $stmt = $mysqli->prepare("INSERT INTO eventos (titulo, fecha_evento, hora_inicio, aforo_max, estado, visible_publico) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisi", $input['titulo'], $input['fecha_evento'], $hora, $aforo, $estado, $visible);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["message" => "Evento creado", "id" => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al guardar el evento"]);
    }
}

function updateEvento($mysqli, $id, $input) {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["error" => "Acceso denegado."]);
        return;
    }
    $stmt = $mysqli->prepare("UPDATE eventos SET titulo=?, fecha_evento=?, hora_inicio=?, aforo_max=?, estado=?, visible_publico=? WHERE id=?");
    $stmt->bind_param("sssisii", $input['titulo'], $input['fecha_evento'], $input['hora_inicio'], $input['aforo_max'], $input['estado'], $input['visible_publico'], $id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Evento actualizado"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al actualizar"]);
    }
}

function deleteEvento($mysqli, $id) {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["error" => "Acceso denegado."]);
        return;
    }
    $stmt = $mysqli->prepare("DELETE FROM eventos WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Evento eliminado"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al eliminar"]);
    }
}


function createArtista($mysqli, $input) {
    if (empty($input['nombre_artista']) || empty($input['email_contacto'])) {
        http_response_code(400);
        echo json_encode(["error" => "Nombre del artista y email son obligatorios"]);
        return;
    }

    $desc   = $input['descripcion'] ?? null;
    $estado = 'pendiente';

    $stmt = $mysqli->prepare("INSERT INTO solicitudes_artistas (nombre_artista, email_contacto, descripcion, estado) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $input['nombre_artista'], $input['email_contacto'], $desc, $estado);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["message" => "Solicitud enviada. Gracias!"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al enviar la solicitud"]);
    }
}

function validarPorDni($mysqli, $input) {
    // Solo admin o txandalari pueden validar entradas
    if (!isset($_SESSION['rol']) ||
        ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'txandalari')) {
        http_response_code(403);
        echo json_encode(["error" => "Acceso denegado"]);
        return;
    }

    if (empty($input['dni'])) {
        http_response_code(400);
        echo json_encode(["error" => "DNI requerido"]);
        return;
    }

    $stmt = $mysqli->prepare("SELECT id, nombre, qr_token FROM usuarios WHERE dni = ?");
    $stmt->bind_param("s", $input['dni']);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if (!$usuario) {
        echo json_encode(["estado" => "error", "mensaje" => "DNI no encontrado. El socio no esta registrado."]);
        return;
    }

    // Si el usuario no tiene token QR, generarlo ahora
    if (empty($usuario['qr_token'])) {
        $nuevo_token = bin2hex(random_bytes(16));
        $upd = $mysqli->prepare("UPDATE usuarios SET qr_token = ? WHERE id = ?");
        $upd->bind_param("si", $nuevo_token, $usuario['id']);
        $upd->execute();
        $usuario['qr_token'] = $nuevo_token;
    }

    $resEvento = $mysqli->query(
        "SELECT id, titulo, aforo_max FROM eventos
         WHERE fecha_evento = CURDATE() AND estado = 'confirmado'
         LIMIT 1"
    );
    $evento = $resEvento->fetch_assoc();

    if (!$evento) {
        echo json_encode(["estado" => "sin_evento", "mensaje" => "No hay evento activo hoy"]);
        return;
    }

    $stmtDup = $mysqli->prepare("SELECT id FROM asistencias WHERE id_evento = ? AND id_usuario = ?");
    $stmtDup->bind_param("ii", $evento['id'], $usuario['id']);
    $stmtDup->execute();
    $stmtDup->store_result();

    if ($stmtDup->num_rows > 0) {
        echo json_encode([
            "estado"  => "ya_entro",
            "mensaje" => "Esta persona ya entro",
            "nombre"  => $usuario['nombre']
        ]);
        return;
    }

    $stmtCount = $mysqli->prepare("SELECT COUNT(*) as total FROM asistencias WHERE id_evento = ?");
    $stmtCount->bind_param("i", $evento['id']);
    $stmtCount->execute();
    $total = $stmtCount->get_result()->fetch_assoc()['total'];
    if ($total >= $evento['aforo_max']) {
        echo json_encode(["estado" => "aforo_completo", "mensaje" => "Aforo completo"]);
        return;
    }

    $stmtIns = $mysqli->prepare("INSERT INTO asistencias (id_evento, id_usuario) VALUES (?, ?)");
    $stmtIns->bind_param("ii", $evento['id'], $usuario['id']);
    $stmtIns->execute();

    echo json_encode([
        "estado"  => "ok",
        "mensaje" => "Acceso permitido",
        "nombre"  => $usuario['nombre'],
        "evento"  => $evento['titulo']
    ]);
}



function validarQR($mysqli, $input) {
    
    if (!isset($_SESSION['rol']) ||
        ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'txandalari')) {
        echo json_encode(["estado" => "error", "mensaje" => "No autorizado"]);
        return;
    }

    if (empty($input['token'])) {
        echo json_encode(["estado" => "error", "mensaje" => "Token requerido"]);
        return;
    }
    $token = trim($input['token']);

    $stmt = $mysqli->prepare("SELECT id, nombre FROM usuarios WHERE qr_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if (!$usuario) {
        echo json_encode(["estado" => "error", "mensaje" => "Token invalido"]);
        return;
    }


    $resEvento = $mysqli->query(
        "SELECT id, titulo, aforo_max FROM eventos
         WHERE fecha_evento = CURDATE() AND estado = 'confirmado'
         LIMIT 1"
    );
    $evento = $resEvento->fetch_assoc();

    if (!$evento) {
        echo json_encode(["estado" => "sin_evento", "mensaje" => "No hay evento activo hoy"]);
        return;
    }


    $stmtDup = $mysqli->prepare("SELECT id FROM asistencias WHERE id_evento = ? AND id_usuario = ?");
    $stmtDup->bind_param("ii", $evento['id'], $usuario['id']);
    $stmtDup->execute();
    $stmtDup->store_result();

    if ($stmtDup->num_rows > 0) {
        echo json_encode([
            "estado"  => "ya_entro",
            "mensaje" => "Esta persona ya entro",
            "nombre"  => $usuario['nombre']
        ]);
        return;
    }

    $stmtCount = $mysqli->prepare("SELECT COUNT(*) as total FROM asistencias WHERE id_evento = ?");
    $stmtCount->bind_param("i", $evento['id']);
    $stmtCount->execute();
    $total = $stmtCount->get_result()->fetch_assoc()['total'];

   
    if ($total >= $evento['aforo_max']) {
        echo json_encode(["estado" => "aforo_completo", "mensaje" => "Aforo completo"]);
        return;
    }

   
    $stmtIns = $mysqli->prepare("INSERT INTO asistencias (id_evento, id_usuario) VALUES (?, ?)");
    $stmtIns->bind_param("ii", $evento['id'], $usuario['id']);
    $stmtIns->execute();

   
    echo json_encode([
        "estado"  => "ok",
        "mensaje" => "Acceso permitido",
        "nombre"  => $usuario['nombre'],
        "evento"  => $evento['titulo']
    ]);
}


//  SPRINT 5 — no esta hecho


function generarPdfAforo($mysqli) {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["error" => "Solo administradores"]);
        return;
    }

    // Obtener id_evento del querystring
    $id_evento = isset($_GET['evento']) ? (int)$_GET['evento'] : 0;

    // Si no se pasa evento, usar el evento activo del dia
    if ($id_evento === 0) {
        $res = $mysqli->query(
            "SELECT * FROM eventos WHERE fecha_evento = CURDATE() AND estado = 'confirmado' LIMIT 1"
        );
        $evento = $res ? $res->fetch_assoc() : null;
    } else {
        $stmt = $mysqli->prepare("SELECT * FROM eventos WHERE id = ?");
        $stmt->bind_param("i", $id_evento);
        $stmt->execute();
        $evento = $stmt->get_result()->fetch_assoc();
    }

    if (!$evento) {
        http_response_code(404);
        echo json_encode(["error" => "Evento no encontrado"]);
        return;
    }

    // Obtener asistentes
    $stmt2 = $mysqli->prepare(
        "SELECT u.nombre, u.dni, u.direccion, a.fecha_hora_entrada
         FROM asistencias a
         JOIN usuarios u ON u.id = a.id_usuario
         WHERE a.id_evento = ?
         ORDER BY a.fecha_hora_entrada ASC"
    );
    $stmt2->bind_param("i", $evento['id']);
    $stmt2->execute();
    $asistentes = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    // ── Intentar usar FPDF si está disponible ──────────────
    $fpdf_paths = [
        __DIR__ . '/fpdf/fpdf.php',
        __DIR__ . '/../fpdf/fpdf.php',
        __DIR__ . '/libs/fpdf/fpdf.php',
    ];
    $fpdf_found = false;
    foreach ($fpdf_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $fpdf_found = true;
            break;
        }
    }

    if ($fpdf_found) {
        // ── PDF con FPDF ────────────────────────────────────
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="aforo_' . $evento['fecha_evento'] . '.pdf"');

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 12, 'LAKOBRA KOLEKTIBOA - LISTADO DE AFORO', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Evento: ' . $evento['titulo'], 0, 1);
        $pdf->Cell(0, 8, 'Fecha: '  . $evento['fecha_evento'], 0, 1);
        $pdf->Cell(0, 8, 'Aforo maximo: ' . $evento['aforo_max'], 0, 1);
        $pdf->Cell(0, 8, 'Total asistentes: ' . count($asistentes), 0, 1);
        $pdf->Cell(0, 8, 'Generado: ' . date('d/m/Y H:i'), 0, 1);
        $pdf->Ln(5);

        // Cabecera tabla
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(220, 20, 60);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(8,  8, 'N',         1, 0, 'C', true);
        $pdf->Cell(60, 8, 'NOMBRE',    1, 0, 'L', true);
        $pdf->Cell(30, 8, 'DNI',       1, 0, 'C', true);
        $pdf->Cell(60, 8, 'ENTRADA',   1, 0, 'C', true);
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        foreach ($asistentes as $i => $a) {
            $fill = ($i % 2 === 0);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell(8,  7, ($i + 1),                    1, 0, 'C', $fill);
            $pdf->Cell(60, 7, $a['nombre'],                1, 0, 'L', $fill);
            $pdf->Cell(30, 7, $a['dni'],                   1, 0, 'C', $fill);
            $pdf->Cell(60, 7, $a['fecha_hora_entrada'],    1, 0, 'C', $fill);
            $pdf->Ln();
        }

        $pdf->Output('D', 'aforo_' . $evento['fecha_evento'] . '.pdf');
        exit();

    } else {
        // ── Fallback: CSV descargable si no hay FPDF ───────
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="aforo_' . $evento['fecha_evento'] . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        fputcsv($out, ['LAKOBRA KOLEKTIBOA - LISTADO DE AFORO'], ';');
        fputcsv($out, ['Evento:', $evento['titulo']], ';');
        fputcsv($out, ['Fecha:', $evento['fecha_evento']], ';');
        fputcsv($out, ['Aforo max:', $evento['aforo_max']], ';');
        fputcsv($out, ['Total:', count($asistentes)], ';');
        fputcsv($out, ['Generado:', date('d/m/Y H:i')], ';');
        fputcsv($out, [], ';');
        fputcsv($out, ['N', 'NOMBRE', 'DNI', 'DIRECCION', 'HORA ENTRADA'], ';');
        foreach ($asistentes as $i => $a) {
            fputcsv($out, [
                $i + 1,
                $a['nombre'],
                $a['dni'],
                $a['direccion'] ?? '',
                $a['fecha_hora_entrada']
            ], ';');
        }
        fclose($out);
        exit();
    }
}



// ============================================================
//  INSCRIPCION A EVENTOS
// ============================================================

function inscribirseEvento($mysqli, $id_evento) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Debes iniciar sesion para inscribirte"]);
        return;
    }
    $id_usuario = $_SESSION['user_id'];

    $stmt = $mysqli->prepare("SELECT id, titulo, aforo_max FROM eventos WHERE id = ? AND estado = 'confirmado'");
    $stmt->bind_param("i", $id_evento);
    $stmt->execute();
    $evento = $stmt->get_result()->fetch_assoc();

    if (!$evento) {
        http_response_code(404);
        echo json_encode(["error" => "Evento no encontrado o no disponible"]);
        return;
    }

    $dup = $mysqli->prepare("SELECT id FROM asistencias WHERE id_evento = ? AND id_usuario = ?");
    $dup->bind_param("ii", $id_evento, $id_usuario);
    $dup->execute();
    $dup->store_result();
    if ($dup->num_rows > 0) {
        echo json_encode(["estado" => "ya_inscrito", "mensaje" => "Ya estas inscrito en este evento"]);
        return;
    }

    $cnt = $mysqli->prepare("SELECT COUNT(*) as total FROM asistencias WHERE id_evento = ?");
    $cnt->bind_param("i", $id_evento);
    $cnt->execute();
    $total = $cnt->get_result()->fetch_assoc()['total'];
    if ($total >= $evento['aforo_max']) {
        echo json_encode(["estado" => "aforo_completo", "mensaje" => "El aforo del evento esta completo"]);
        return;
    }

    $ins = $mysqli->prepare("INSERT INTO asistencias (id_evento, id_usuario) VALUES (?, ?)");
    $ins->bind_param("ii", $id_evento, $id_usuario);
    if ($ins->execute()) {
        echo json_encode(["estado" => "ok", "mensaje" => "Inscripcion realizada! Nos vemos en " . $evento['titulo']]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al inscribirse"]);
    }
}

function cancelarInscripcion($mysqli, $id_evento) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "No autorizado"]);
        return;
    }
    $id_usuario = $_SESSION['user_id'];

    $stmt = $mysqli->prepare("DELETE FROM asistencias WHERE id_evento = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_evento, $id_usuario);
    if ($stmt->execute() && $mysqli->affected_rows > 0) {
        echo json_encode(["estado" => "ok", "mensaje" => "Inscripcion cancelada"]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "No estabas inscrito en este evento"]);
    }
}

function misInscripciones($mysqli) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "No autorizado"]);
        return;
    }
    $id_usuario = $_SESSION['user_id'];

    $stmt = $mysqli->prepare(
        "SELECT e.id, e.titulo, e.fecha_evento, e.hora_inicio, e.aforo_max,
                a.fecha_hora_entrada
         FROM asistencias a
         JOIN eventos e ON e.id = a.id_evento
         WHERE a.id_usuario = ?
         ORDER BY e.fecha_evento DESC"
    );
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($rows);
}

// ============================================================
//  RGPD — limpieza de asistencias con mas de 30 dias
// ============================================================

function limpiarRGPD($mysqli) {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["error" => "Solo administradores"]);
        return;
    }

    $result = $mysqli->query(
        "DELETE FROM asistencias WHERE fecha_hora_entrada < NOW() - INTERVAL 30 DAY"
    );

    if ($result) {
        echo json_encode([
            "message"    => "Limpieza RGPD completada",
            "eliminadas" => $mysqli->affected_rows
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error en la limpieza RGPD"]);
    }
}

// ============================================================
//  TXANDAS (TURNOS)
// ============================================================

function getTxandas($mysqli) {
    // Devuelve todos los turnos con nombre del usuario y evento
    $res = $mysqli->query(
        "SELECT t.id, t.id_evento, t.id_usuario, t.puesto, u.nombre,
                e.titulo as evento_titulo, e.fecha_evento
         FROM turnos t
         JOIN usuarios u ON u.id = t.id_usuario
         JOIN eventos  e ON e.id = t.id_evento
         WHERE e.estado = 'confirmado'
         ORDER BY e.fecha_evento ASC, t.puesto ASC"
    );
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

function getMisTxandas($mysqli) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "No autorizado"]);
        return;
    }
    $id_usuario = $_SESSION['user_id'];
    $stmt = $mysqli->prepare(
        "SELECT t.*, e.titulo, e.fecha_evento FROM turnos t
         JOIN eventos e ON e.id = t.id_evento
         WHERE t.id_usuario = ?
         ORDER BY e.fecha_evento ASC"
    );
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function apuntarseTxanda($mysqli, $id_evento, $input) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "No autorizado"]);
        return;
    }
    $id_usuario = $_SESSION['user_id'];
    $puesto     = $input['puesto'] ?? '';

    $puestos_validos = ['barra', 'puerta', 'limpieza', 'otros'];
    if (!in_array($puesto, $puestos_validos)) {
        http_response_code(400);
        echo json_encode(["error" => "Puesto no valido"]);
        return;
    }

    // Verificar que el evento existe y esta confirmado
    $ev = $mysqli->prepare("SELECT id FROM eventos WHERE id = ? AND estado = 'confirmado'");
    $ev->bind_param("i", $id_evento);
    $ev->execute();
    $ev->store_result();
    if ($ev->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Evento no disponible"]);
        return;
    }

    // Comprobar duplicado (mismo evento + usuario + puesto)
    $dup = $mysqli->prepare("SELECT id FROM turnos WHERE id_evento = ? AND id_usuario = ? AND puesto = ?");
    $dup->bind_param("iis", $id_evento, $id_usuario, $puesto);
    $dup->execute();
    $dup->store_result();
    if ($dup->num_rows > 0) {
        echo json_encode(["estado" => "ya_apuntado", "mensaje" => "Ya estas apuntado en este puesto"]);
        return;
    }

    $ins = $mysqli->prepare("INSERT INTO turnos (id_evento, id_usuario, puesto) VALUES (?, ?, ?)");
    $ins->bind_param("iis", $id_evento, $id_usuario, $puesto);
    if ($ins->execute()) {
        echo json_encode(["estado" => "ok", "mensaje" => "Apuntado correctamente al turno de " . $puesto]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al apuntarse"]);
    }
}

function desapuntarseTxanda($mysqli, $id_evento, $input) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "No autorizado"]);
        return;
    }
    $id_usuario = $_SESSION['user_id'];
    $puesto     = $input['puesto'] ?? '';

    $stmt = $mysqli->prepare(
        "DELETE FROM turnos WHERE id_evento = ? AND id_usuario = ? AND puesto = ?"
    );
    $stmt->bind_param("iis", $id_evento, $id_usuario, $puesto);
    if ($stmt->execute() && $mysqli->affected_rows > 0) {
        echo json_encode(["estado" => "ok", "mensaje" => "Desapuntado del turno"]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "No estabas apuntado en este turno"]);
    }
}
?>