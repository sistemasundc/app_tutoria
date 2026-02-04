<?php 
	include_once '../../modelo/modelo_horario.php';
	$horario  = new  Horario();

	session_start();

	$semestre = $_SESSION['S_SEMESTRE'];

	$tipo = htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8');
	$doce = htmlspecialchars($_POST['id_doce'],ENT_QUOTES,'UTF-8');

	$cursos = $horario->listar_alumnos_asignados($doce, $tipo, $semestre);

	echo json_encode($cursos);
 ?>