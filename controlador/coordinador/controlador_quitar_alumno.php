<?php
// controlador/coordinador/controlador_quitar_alumno.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

header('Content-Type: text/plain; charset=utf-8');

require '../../modelo/modelo_coordinador.php';

if (!isset($_POST['idasig'])) {
    echo 'ERROR: NO_ID';
    exit;
}

$id_asig = (int)$_POST['idasig'];

$docent   = new Modelo_Coordinador();
$result   = $docent->Quitar_Matricula($id_asig);

// IMPORTANTE: esto es EXACTAMENTE lo que verá el JS
echo $result;
