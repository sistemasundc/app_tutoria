
<?php
    require '../../modelo/modelo_alumno.php';
    $MU = new Alumno();

    $id_estu = htmlspecialchars($_POST['estu'],ENT_QUOTES,'UTF-8');
    $fecha = htmlspecialchars($_POST['fecha'],ENT_QUOTES,'UTF-8');

    $consulta = $MU->listar_sessiones_tutoria($id_estu, $fecha);
    
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