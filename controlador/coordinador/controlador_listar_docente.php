<?php
   session_start(); // <--  activo

   require '../../modelo/modelo_docente.php';
   $docent = new Docente;
   
   $semestre = htmlspecialchars($_POST['anio'], ENT_QUOTES, 'UTF-8');
   $id_coordinador = $_SESSION['S_IDUSUARIO']; // guardado
   
   $consulta = $docent->listar_docente($semestre, $id_coordinador);
   
   if ($consulta) {
       echo json_encode($consulta);
   } else {
       echo '{
           "sEcho": 1,
           "iTotalRecords": "0",
           "iTotalDisplayRecords": "0",
           "aaData": []
       }';
   }
   
?>