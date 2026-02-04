<?php 
session_start();

// Verificar si viene el rol seleccionado
if (!isset($_POST['rol_seleccionado'])) {
    header('Location: ../index.php');
    exit();
}

// Decodificar el rol seleccionado
$rol_data = json_decode(base64_decode($_POST['rol_seleccionado']), true);

// Validaciones adicionales
if (!$rol_data || !isset($rol_data['id_general']) || !isset($rol_data['rol'])) {
    echo "<script>alert('Error al procesar el rol seleccionado.'); window.location='../index.php';</script>";
    exit();
}

// Mapa de roles
$mapaRoles = [
    1 => 'COORDINADOR GENERAL DE TUTORIA',
    2 => 'TUTOR DE CURSO',
    3 => 'ALUMNO',
    4 => 'DIRECTOR DE DEPARTAMENTO ACADEMICO',
    5 => 'APOYO',
    6 => 'TUTOR DE AULA',
    7 => 'DIRECCION DE ESCUELA',
    8 => 'SUPERVISIÓN',
    9 => 'VICEPRESIDENCA ACADEMICA',
    10 => 'COMITÉ - SUPERVISIÓN',
    11 =>'DEPARTAMENTO ESTUDIOS GENERALES'
];

// Asignar sesiones
$_SESSION['S_IDUSUARIO']   = $rol_data['id_general'];
$_SESSION['S_USER']        = trim($rol_data['nombre']);
$_SESSION['S_ROL_ID']      = $rol_data['id_rol']; 
$_SESSION['S_ROL']         = is_numeric($rol_data['rol']) && isset($mapaRoles[(int)$rol_data['rol']]) 
                             ? $mapaRoles[(int)$rol_data['rol']] 
                             : strtoupper(trim($rol_data['rol']));
$_SESSION['S_ORIGEN']      = $rol_data['fuente'] ?? 'desconocido';
$_SESSION['S_SEMESTRE']    = 32;

$_SESSION['S_SCHOOL']      = $rol_data['id_car'] ?? '-';
$_SESSION['S_SCHOOLNAME']  = $rol_data['escuela'] ?? '-';
$_SESSION['S_SEMESTRE_FECHA'] = '2025-I'; 

if ($rol_data['rol'] === 'ALUMNO') {
    $_SESSION['S_IDESTU'] = $rol_data['id_general'];
    $_SESSION['S_EMAIL']  = $rol_data['correo'];
}
// unset si ya no se necesita
unset($_SESSION['ROLES_MULTIPLES']);

// Redirigir
header("Location: ../../vista/index.php");
exit();
?>
