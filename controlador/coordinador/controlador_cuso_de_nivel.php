<?php
    require '../../modelo/modelo_coordinador.php';

    $docent = new Modelo_Coordinador();
    $idschool = htmlspecialchars($_POST['idescuela'],ENT_QUOTES,'UTF-8');
    $idciclo = htmlspecialchars($_POST['ciclo'],ENT_QUOTES,'UTF-8');
   
    $consulta = $docent->Traer_curso($idschool, $idciclo);

 
    echo json_encode($consulta);
    /*
    if(!empty($consulta))
     foreach ($consulta as  $value) {

     	 $cursos = $docent->Extraer_Cursos_Estado_Pendiente($cursos, $value['idcurso']);
     	# code...
     }
     */

?>