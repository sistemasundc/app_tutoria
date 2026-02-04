<?php
require '../../modelo/modelo_horario.php';
$horario = new Horario();

$id_doce = htmlspecialchars($_POST['doce'], ENT_QUOTES, 'UTF-8');
$rol = htmlspecialchars($_POST['rol_usuario'], ENT_QUOTES, 'UTF-8');

$consulta = $horario->CargarHorario($id_doce, $rol);
echo json_encode($consulta);