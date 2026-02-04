<?php 
  
  if(!isset($_SESSION['S_IDUSUARIO']) && !isset($_SESSION['S_USER']) && !isset($_SESSION['S_ROL'])){
    header("Location: ../index.php"); // Redirecciona a index.php
    echo "Hellooooooooooooooooo";
    echo '<meta http-equiv="refresh" content="1">';
    exit();
  } 
 ?>