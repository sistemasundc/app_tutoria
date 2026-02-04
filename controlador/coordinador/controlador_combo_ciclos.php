<?php
session_start();
require '../../modelo/modelo_coordinador.php';
$MU = new Modelo_Coordinador();

$iddoce = htmlspecialchars($_POST['doce'], ENT_QUOTES, 'UTF-8');
$semestre = htmlspecialchars($_POST['anio'], ENT_QUOTES, 'UTF-8');

$consulta = $MU->listar_combo_ciclos($iddoce, $semestre);
echo json_encode($consulta);
