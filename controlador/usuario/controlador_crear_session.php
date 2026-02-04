<?php
	//Creando las sesiones de usurio
	$IDUSUARIO = $_POST['idusuario'];
	$USER = $_POST['user'];
	$ROL = $_POST['rol'];
	$SCHOOL = $_POST['school'];

	session_start();
	$_SESSION['S_IDUSUARIO']=$IDUSUARIO;
	$_SESSION['S_USER']=$USER;
	$_SESSION['S_ROL']=$ROL;
	$_SESSION['S_SCHOOL']=$SCHOOL;

	$_SESSION['S_SCHOOLNAME'] = $_POST['schoolname'];
	$_SESSION['S_CICLO'] = $_POST['ciclo'];

	$_SESSION['S_SEMESTRE'] = 30;
	$_SESSION['S_SEMESTRE_FECHA'] = "2024-2";

	if (
		empty($_SESSION['S_IDUSUARIO']) &&
		empty($_SESSION['S_USER']) &&
		empty($_SESSION['S_ROL'])
	 ){
		session_destroy();
		header('Location: ../../Login/index.php');
	}
?>