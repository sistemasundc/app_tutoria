<?php
    require '../../modelo/modelo_foro.php';
    $MF = new Foro();

    date_default_timezone_set('America/Lima');

	$fecha = date('Y-m-d H:i:s');

    $id_foro = htmlspecialchars($_POST['for'],ENT_QUOTES,'UTF-8');
    $id_usu = htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8');
    $respuesta = htmlspecialchars($_POST['res'],ENT_QUOTES,'UTF-8');
    
    $consulta = $MF->SubirForoRespuesta($id_foro, $respuesta, $fecha, $id_usu);

    echo $consulta;
?>