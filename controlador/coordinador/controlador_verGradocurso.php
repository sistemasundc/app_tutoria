<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require '../../modelo/modelo_coordinador.php';
$docent = new Modelo_Coordinador();

$iddocente = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
$semestre  = isset($_POST['anio']) ? (int)$_POST['anio'] : 0;

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;

$data = [];

if ($iddocente > 0 && $semestre > 0) {
  $consulta = $docent->Ver_CargosAsignados($iddocente, $semestre);

  // ✅ si el modelo devuelve array plano
  if (is_array($consulta)) {
    $data = $consulta;

    // ✅ si el modelo devuelve {data: [...]}
    if (isset($consulta['data']) && is_array($consulta['data'])) {
      $data = $consulta['data'];
    }
  }
}

echo json_encode([
  "draw" => $draw,
  "recordsTotal" => count($data),
  "recordsFiltered" => count($data),
  "data" => $data
]);
