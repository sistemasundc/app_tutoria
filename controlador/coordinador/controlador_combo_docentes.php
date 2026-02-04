<?php
    require '../../modelo/modelo_coordinador.php';
    $MU = new Modelo_Coordinador();

    $consulta = $MU->listar_combo_docentes();

    if ($consulta != 0){
   		echo json_encode($consulta);
    }else {
    	echo "string";
    }
?>