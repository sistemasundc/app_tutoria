<?php
    require '../../modelo/modelo_apoyo.php';
    $MU = new Apoyo();

    $id_apoyo = htmlspecialchars($_POST['apoyo'],ENT_QUOTES,'UTF-8');
    $estado = htmlspecialchars($_POST['est'],ENT_QUOTES,'UTF-8');
    $semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);
    $consulta = $MU->ListarAlumnosReferidos($id_apoyo, $estado, $semestre);

    if($consulta){
        echo json_encode($consulta);
    }else{
        echo '{
		    "sEcho": 1,
		    "iTotalRecords": "0",
		    "iTotalDisplayRecords": "0",
		    "aaData": []
		}';
    }

?>