<?php
    require '../../modelo/modelo_foro.php';
    $MF = new Foro();

    date_default_timezone_set('America/Lima');

	$fecha = date('Y-m-d');
	$hora = date('H:i:s');

    $titulo = htmlspecialchars($_POST['titu'],ENT_QUOTES,'UTF-8');
    $descripcion = htmlspecialchars($_POST['des'],ENT_QUOTES,'UTF-8');
    $id_doce = htmlspecialchars($_POST['doce'],ENT_QUOTES,'UTF-8');
    $semestre = htmlspecialchars($_POST['anio'],ENT_QUOTES,'UTF-8');

    $id_carga = htmlspecialchars($_POST['car'],ENT_QUOTES,'UTF-8');
    $array_carga = explode(",", $id_carga);

    $id_foro = $MF->SubirForo($titulo, $descripcion, $fecha, $id_doce, $semestre);

    if (!empty($id_foro)){

        for ($i=0; $i < count($array_carga); $i++) { 
            $consulta = $MF->PermisoForo($array_carga[$i], $id_foro);
        }

        if ($consulta == 1){
        	echo 1;
        }else {
        	echo 0;
        }
    }else {
    	echo 0;
    }
?>