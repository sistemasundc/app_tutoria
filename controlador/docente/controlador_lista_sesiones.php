<?php
session_start();
require '../../modelo/modelo_docente.php';
$docent = new Docente;

$id_sesion = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');

$consulta = $docent->ListaSesiones($id_sesion);

echo json_encode($consulta);
