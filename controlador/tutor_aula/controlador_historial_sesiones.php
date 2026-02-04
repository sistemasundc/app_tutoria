<?php
session_start();
require '../..//modelo/modelo_docente.php';
$MU = new Docente();

$id_doc      = (int)($_POST['doce'] ?? 0);
$id_semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);

$consulta = $MU->listar_historial_sesiones_TA($id_doc, $id_semestre);

if ($consulta) {
  echo json_encode($consulta);
} else {
  echo '{
    "sEcho": 1,
    "iTotalRecords": "0",
    "iTotalDisplayRecords": "0",
    "aaData": []
  }';
}
