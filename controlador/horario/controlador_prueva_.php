<?php
    require '../../modelo/modelo_horario.php';

    $horario = new Horario();
    $numer=1;

    $selecionado = htmlspecialchars($_POST['horarios'],ENT_QUOTES,'UTF-8');
    $id_doce = htmlspecialchars($_POST['id_doce'],ENT_QUOTES,'UTF-8');

    $data =(isset($_POST['horarios']))? json_decode($_POST['horarios'],true): array("error"=>"no se pudo completar el registro");


    if (!empty($data)) {

        foreach ($data as $value) {
         	$tdId  = substr($value['idtd'] , -2);

         	 $resp = $horario->Registar_horario($value['hora'],$value['dia'], $id_doce, $value['id_estu'], $value['tipo']);

        }

        echo $resp;
    }else {
        echo 500;
    }
?>