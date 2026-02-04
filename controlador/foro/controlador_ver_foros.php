<?php 
	require '../../modelo/modelo_foro.php';
    $MF = new Foro();

    date_default_timezone_set('America/Lima');

	$fecha = date('Y-m-d');
	$hora = date('H:i:s');

    $iddoce = htmlspecialchars($_POST['doce'],ENT_QUOTES,'UTF-8');
    $semestre = htmlspecialchars($_POST['anio'],ENT_QUOTES,'UTF-8');
    $search = htmlspecialchars($_POST['fil'],ENT_QUOTES,'UTF-8');
    $pagina = htmlspecialchars($_POST['pag'],ENT_QUOTES,'UTF-8');

    $consulta = $MF->VerForosDoce($iddoce, $semestre, $fecha, $search, $pagina);

    if (!empty($consulta)) {
    	echo json_encode($consulta);
    }else {
    	echo 0;
    }
 ?>