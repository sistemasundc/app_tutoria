<?php
    require '../../modelo/modelo_foro.php';
    $MF = new Foro();

    $iddoce = htmlspecialchars($_POST['doce'],ENT_QUOTES,'UTF-8');
    $semestre = htmlspecialchars($_POST['anio'],ENT_QUOTES,'UTF-8');

    $consulta = $MF->CargaAsignadoDocente($iddoce, $semestre);
    echo json_encode($consulta);
?>