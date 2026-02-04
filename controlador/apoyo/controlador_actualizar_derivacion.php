<?php
    require '../../modelo/modelo_apoyo.php';
    $MA = new Apoyo();

    $id_der = htmlspecialchars($_POST['ider'],ENT_QUOTES,'UTF-8');
    $resultado = htmlspecialchars($_POST['res'],ENT_QUOTES,'UTF-8');
    $obserbacion = htmlspecialchars($_POST['obs'],ENT_QUOTES,'UTF-8');
    $id_asig = htmlspecialchars($_POST['idsg'],ENT_QUOTES,'UTF-8');

    date_default_timezone_set('America/Lima');

    $fecha = date('Y-m-d');

    $consulta = $MA->ActualizarDerivacion($id_der, $resultado, $obserbacion, $fecha, $id_asig);

    echo $consulta;
?>