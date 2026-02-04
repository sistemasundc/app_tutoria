<?php
    require '../../modelo/modelo_apoyo.php';
    $MA = new Apoyo();

    $id_estu = htmlspecialchars($_POST['estu'],ENT_QUOTES,'UTF-8');
    $ider = htmlspecialchars($_POST['ider'],ENT_QUOTES,'UTF-8');

    $consulta = $MA->VerDetalleDerivacion($id_estu, $ider);

    echo json_encode($consulta);
?>