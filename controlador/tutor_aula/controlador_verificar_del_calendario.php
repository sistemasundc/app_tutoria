<?php
    require '../../modelo/modelo_docente.php';
    $docent = new Docente();

    $id_sesion = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');

    $consulta = $docent->VerificarDelCalendario($id_sesion);
    echo $consulta;
?>
