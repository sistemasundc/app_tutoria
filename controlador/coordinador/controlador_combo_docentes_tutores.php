<?php
    require '../../modelo/modelo_coordinador.php';
    $MU = new Modelo_Coordinador();

    session_start();

    $semestre = $_SESSION['S_SEMESTRE'];

    $ciclo = htmlspecialchars($_POST['ciclo'],ENT_QUOTES,'UTF-8');
    $id_estu = htmlspecialchars($_POST['id_estu'],ENT_QUOTES,'UTF-8');

    $id_car = $MU->verificarIdCarrera($id_estu, $semestre);

    if ($id_car) {
	    $consulta = $MU->listar_combo_docentes_tutores($ciclo, $id_car, $semestre);
    	echo json_encode($consulta);
    }
?>