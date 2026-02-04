<?php 
	require '../../modelo/modelo_docente.php';

    $MD = new Docente();

    $id_asig = htmlspecialchars($_POST['asig'],ENT_QUOTES,'UTF-8');
    $id_tipo = htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8');

    $consulta = $MD->ChangeTipoAsig($id_asig, $id_tipo);

    echo $consulta;
 ?>