<?php
    require '../../modelo/modelo_docente.php';
    $docent = new Docente();

    $id_doce = htmlspecialchars($_POST['id_docente'],ENT_QUOTES,'UTF-8');
    $id_estu = htmlspecialchars($_POST['id_estudiante'],ENT_QUOTES,'UTF-8');
    $inicio = htmlspecialchars($_POST['inicio'],ENT_QUOTES,'UTF-8');
    $final = htmlspecialchars($_POST['final'],ENT_QUOTES,'UTF-8');
    $tema = htmlspecialchars($_POST['tema'],ENT_QUOTES,'UTF-8'); 
    $compromiso = htmlspecialchars($_POST['compromiso'],ENT_QUOTES,'UTF-8');
    $tipo_session = htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8');
    $obser = htmlspecialchars($_POST['obs'],ENT_QUOTES,'UTF-8');
    $reu_otros = htmlspecialchars($_POST['reu_otro'],ENT_QUOTES,'UTF-8');
    $fecha = htmlspecialchars($_POST['fecha'],ENT_QUOTES,'UTF-8');

    $array_asig = htmlspecialchars($_POST['array_asig'],ENT_QUOTES,'UTF-8');
    $array_asignacion = explode(",", $array_asig);

    $id_registro = $docent->RegistrarSessionTutoria($id_doce, $inicio, $final, $tema, $compromiso, $tipo_session, $obser, $reu_otros, $fecha);

    if ($id_registro != 0){
        for ($i=0; $i < count($array_asignacion); $i++) { 
            $consulta = $docent->RegistrarDetalleSessionTutoria($array_asignacion[$i], $id_registro);
        }
    }
    
    echo $consulta;
?>