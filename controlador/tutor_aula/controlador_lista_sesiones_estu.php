<?php 
    require '../../modelo/modelo_docente.php';
    $docent = new Docente;

    $id_sesion = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $id_estu = htmlspecialchars($_POST['es'],ENT_QUOTES,'UTF-8');

    $consulta = $docent->ListaSesionesEstu($id_sesion, $id_estu);

    echo json_encode($consulta);
?>