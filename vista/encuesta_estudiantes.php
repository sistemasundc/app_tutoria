<?php
header('Content-Type: application/json; charset=utf-8');

/*===================  SEGURIDAD  ===================*/
const API_TOKEN = 'undc2025Segura!'; // Debe coincidir con Apps Script
if (!isset($_SERVER['HTTP_X_API_KEY']) || $_SERVER['HTTP_X_API_KEY'] !== API_TOKEN) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

/*===================  CONEXIÓN BD  ===================*/
$host = "localhost";
$user = "usr_sivireno";
$pass = "S1v1r3n0@";
$db   = "dbsivireno";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

/*===================  INPUT JSON  ===================*/
$raw = file_get_contents('php://input');
if ($raw === '' || $raw === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Cuerpo vacío']);
    exit;
}

error_log('[encuesta_estudiantes] payload: ' . $raw);

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

/*===================  MAPEO / NORMALIZACIÓN  ===================*/
function likert_to_int($v) {
    if ($v === null || $v === '') return null;

    // Si ya viene numérico (1..5) o "1".."5"
    if (is_numeric($v)) {
        $n = (int)$v;
        if ($n >= 1 && $n <= 5) return $n;
    }

    // Normaliza texto
    $s = mb_strtolower(trim((string)$v), 'UTF-8');

    // Escala del Form:
    // Muy Satisfecho, Satisfecho, Moderadamente Satisfecho, Poco Satisfecho, Insatisfecho
    if (strpos($s, 'muy satisfecho') !== false) return 5;
    if ($s === 'satisfecho') return 4;
    if (strpos($s, 'moderadamente satisfecho') !== false) return 3;
    if (strpos($s, 'poco satisfecho') !== false) return 2;
    if (strpos($s, 'insatisfecho') !== false) return 1;

    // Fallback por sinónimos
    if (strpos($s, 'excelente') !== false || strpos($s, 'muy bueno') !== false || strpos($s, 'totalmente de acuerdo') !== false) return 5;
    if ($s === 'bueno' || $s === 'de acuerdo') return 4;
    if (strpos($s, 'regular') !== false || strpos($s, 'neutral') !== false) return 3;
    if (strpos($s, 'malo') !== false || strpos($s, 'en desacuerdo') !== false) return 2;
    if (strpos($s, 'muy malo') !== false || strpos($s, 'totalmente en desacuerdo') !== false) return 1;

    return null;
}

function to_str_or_null($v) {
    if ($v === null) return null;
    $s = trim((string)$v);
    return ($s === '') ? null : $s;
}

/*===================  CAMPOS ESPERADOS  ===================*/
// Vienen del Apps Script
$sheet_row     = isset($data['sheet_row']) ? (int)$data['sheet_row'] : null;
$timestamp_utc = $data['timestamp_utc'] ?? null; // "YYYY-mm-dd HH:ii:ss" (UTC)
$email         = to_str_or_null($data['email'] ?? null);

$q1 = likert_to_int($data['q1'] ?? null);
$q2 = likert_to_int($data['q2'] ?? null);
$q3 = likert_to_int($data['q3'] ?? null);
$q4 = likert_to_int($data['q4'] ?? null);
$q5 = likert_to_int($data['q5'] ?? null);
$q6 = likert_to_int($data['q6'] ?? null);

// texto abierto
$q7_texto = to_str_or_null($data['q7_texto'] ?? null);

// Fecha/hora a DATETIME MySQL
$ts = null;
if (!empty($timestamp_utc)) {
    $t = strtotime($timestamp_utc);
    if ($t !== false) $ts = date('Y-m-d H:i:s', $t);
}

/*===================  INSERT / UPDATE (UPSERT)  ===================*/
try {
    if (!$sheet_row) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'sheet_row es requerido']);
        exit;
    }

    // OBLIGATORIO PARA UPSERT:
    // ALTER TABLE tutoria_encuesta_estudiantes ADD UNIQUE KEY uk_sheet_row (sheet_row);

    $sql = "INSERT INTO tutoria_encuesta_estudiantes
              (sheet_row, timestamp_utc, email, q1, q2, q3, q4, q5, q6, q7_texto)
            VALUES
              (:sheet_row, :timestamp_utc, :email, :q1, :q2, :q3, :q4, :q5, :q6, :q7_texto)
            ON DUPLICATE KEY UPDATE
              timestamp_utc = VALUES(timestamp_utc),
              email         = VALUES(email),
              q1            = VALUES(q1),
              q2            = VALUES(q2),
              q3            = VALUES(q3),
              q4            = VALUES(q4),
              q5            = VALUES(q5),
              q6            = VALUES(q6),
              q7_texto      = VALUES(q7_texto)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':sheet_row'     => $sheet_row,
        ':timestamp_utc' => $ts,
        ':email'         => $email,
        ':q1'            => $q1,
        ':q2'            => $q2,
        ':q3'            => $q3,
        ':q4'            => $q4,
        ':q5'            => $q5,
        ':q6'            => $q6,
        ':q7_texto'      => $q7_texto,
    ]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('[encuesta_estudiantes][ERROR] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
