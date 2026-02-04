<?php
header('Content-Type: application/json; charset=utf-8');

/*===================  SEGURIDAD  ===================*/
const API_TOKEN = 'undc2025Segura!';
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
    echo json_encode(['ok' => false, 'error' => 'Error de conexión: '.$e->getMessage()]);
    exit;
}

/*===================  INPUT JSON  ===================*/
$raw = file_get_contents('php://input');
if ($raw === '' || $raw === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Cuerpo vacío']);
    exit;
}
error_log('[encuesta_docentes] payload: '.$raw);

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

/*===================  HELPERS  ===================*/
function likert_to_int($v) {
    if ($v === null || $v === '') return null;

    if (is_numeric($v)) {
        $n = (int)$v;
        if ($n >= 1 && $n <= 5) return $n;
    }

    $s = mb_strtolower(trim((string)$v), 'UTF-8');

    if (strpos($s, 'muy satisfecho') !== false) return 5;
    if ($s === 'satisfecho') return 4;
    if (strpos($s, 'moderadamente satisfecho') !== false) return 3;
    if (strpos($s, 'poco satisfecho') !== false) return 2;
    if (strpos($s, 'insatisfecho') !== false) return 1;

    // sinónimos
    if (strpos($s, 'excelente') !== false || strpos($s, 'muy bueno') !== false || strpos($s, 'totalmente de acuerdo') !== false) return 5;
    if ($s === 'bueno' || $s === 'de acuerdo') return 4;
    if (strpos($s, 'regular') !== false || strpos($s, 'neutral') !== false) return 3;
    if (strpos($s, 'malo') !== false) return 2;
    if (strpos($s, 'muy malo') !== false) return 1;

    return null;
}

function clean_text($v) {
    if ($v === null) return null;
    $s = trim((string)$v);
    return ($s === '') ? null : $s;
}

function to_mysql_datetime_from_utc($timestamp_utc) {
    if (empty($timestamp_utc)) return null;
    $t = strtotime($timestamp_utc);
    if ($t === false) return null;
    return date('Y-m-d H:i:s', $t);
}

/*===================  CAMPOS ESPERADOS  ===================*/
$sheet_row     = isset($data['sheet_row']) ? (int)$data['sheet_row'] : null;
$timestamp_utc = $data['timestamp_utc'] ?? null;
$email         = clean_text($data['email'] ?? null);
$id_semestre   = isset($data['id_semestre']) ? (int)$data['id_semestre'] : null;

$q1 = likert_to_int($data['q1'] ?? null);
$q2 = likert_to_int($data['q2'] ?? null);
$q3 = likert_to_int($data['q3'] ?? null);
$q4 = likert_to_int($data['q4'] ?? null);
$q5 = likert_to_int($data['q5'] ?? null);
$q6 = likert_to_int($data['q6'] ?? null);

$q8_texto  = clean_text($data['q8_texto'] ?? null);
$q10_texto = clean_text($data['q10_texto'] ?? null);

$ts = to_mysql_datetime_from_utc($timestamp_utc);

/*===================  VALIDACIONES  ===================*/
if ($id_semestre === null || $id_semestre <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Falta id_semestre o es inválido']);
    exit;
}
if ($email === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Falta email']);
    exit;
}
if ($sheet_row === null || $sheet_row <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Falta sheet_row o es inválido']);
    exit;
}

/*===================  INSERT  ===================*/
try {
    $sql = "INSERT INTO tutoria_encuesta_docentes
              (sheet_row, timestamp_utc, email, id_semestre, q1, q2, q3, q4, q5, q6, q8_texto, q10_texto)
            VALUES
              (:sheet_row, :timestamp_utc, :email, :id_semestre, :q1, :q2, :q3, :q4, :q5, :q6, :q8_texto, :q10_texto)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':sheet_row'     => $sheet_row,
        ':timestamp_utc' => $ts,
        ':email'         => $email,
        ':id_semestre'   => $id_semestre,
        ':q1'            => $q1,
        ':q2'            => $q2,
        ':q3'            => $q3,
        ':q4'            => $q4,
        ':q5'            => $q5,
        ':q6'            => $q6,
        ':q8_texto'      => $q8_texto,
        ':q10_texto'     => $q10_texto,
    ]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('[encuesta_docentes][ERROR] '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
