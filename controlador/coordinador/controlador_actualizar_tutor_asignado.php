<?php
    require '../../modelo/modelo_coordinador.php';
    $MC = new Modelo_Coordinador();

    session_start();

    $semestre = $_SESSION['S_SEMESTRE'];

    $id_tutor = htmlspecialchars($_POST['tutor'],ENT_QUOTES,'UTF-8');
    $id_cor = htmlspecialchars($_POST['cor'],ENT_QUOTES,'UTF-8');
    $id_estu = htmlspecialchars($_POST['estu'],ENT_QUOTES,'UTF-8');

    $consulta = $MC->ActualizarTutorAsignado($id_tutor, $id_cor, $id_estu, $semestre);
    echo $consulta;
?>