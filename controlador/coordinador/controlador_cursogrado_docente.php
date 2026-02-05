<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require '../../modelo/modelo_coordinador.php';
$MU = new Modelo_Coordinador();

if (!isset($_POST['id_carga'], $_POST['id_doce'], $_POST['id_coodi'], $_POST['anio'])) {
  echo 0; exit;
}

$id_carga  = (int)$_POST['id_carga'];   // <-- en tu caso 5260 (id_cargalectiva)
$id_doce   = (int)$_POST['id_doce'];    // <-- 382
$id_coodi  = (int)$_POST['id_coodi'];   // <-- 43
$id_sem    = (int)$_POST['anio'];       // <-- 33

if ($id_carga <= 0 || $id_doce <= 0 || $id_coodi <= 0 || $id_sem <= 0) {
  echo 0; exit;
}

/**
 * Si ya existe al menos 1 asignación en tutoria_asignacion_tutoria para ese
 * docente + carga + semestre, devolvemos 555.
 * (Si prefieres “insertar faltantes” en vez de bloquear, me dices y lo ajusto.)
 */
$ya = $MU->ExisteTutoriaAsignacion($id_carga, $id_doce, $id_sem);
if ($ya > 0) {
  echo 555; exit;
}

/**
 * Inserta MASIVO: todos los alumnos de asignacion_estudiante
 * hacia tutoria_asignacion_tutoria
 */
$r = $MU->InsertarTutoriaAsignacionMasiva($id_carga, $id_doce, $id_coodi, $id_sem);

// ✅ NO lo conviertas a int, deja que salga el error si lo hay
echo $r;
