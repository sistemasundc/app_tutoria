<?php
session_start();
require '../../modelo/modelo_docente.php';
$docent = new Docente();

$consulta = $docent->TipoSession();
echo json_encode($consulta);
