<?php
    require '../../modelo/modelo_coordinador.php';
    $MU = new Modelo_Coordinador();
    $consulta = $MU->listar_combo_niveles();
    echo json_encode($consulta);
?>