<?php
   require '../../modelo/modelo_docente.php';

    $MD = new Docente();

    date_default_timezone_set('America/Lima');

	$fecha = date('Y-m-d');
	$hora = date('H:i:s');

    $id_der = htmlspecialchars($_POST['der'],ENT_QUOTES,'UTF-8');

    $motivo_referido = htmlspecialchars($_POST['motivo'],ENT_QUOTES,'UTF-8');
    $result_referido = htmlspecialchars($_POST['result'],ENT_QUOTES,'UTF-8');
    $area = htmlspecialchars($_POST['area'],ENT_QUOTES,'UTF-8');
    $id_estu = htmlspecialchars($_POST['estu'],ENT_QUOTES,'UTF-8');
    $id_doce = htmlspecialchars($_POST['doce'],ENT_QUOTES,'UTF-8');
    $id_asig = htmlspecialchars($_POST['asig'],ENT_QUOTES,'UTF-8');

    $consulta = $MD->DerivarAlumno($fecha, $hora, $motivo_referido, $area, $id_estu, $id_doce);

    if ($consulta == 1){
       $consulta = $MD->UpdateDerivadoApoyo($id_der, $result_referido); 
    }
    echo $consulta;
?>