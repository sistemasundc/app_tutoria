
<?php
    class Files{
        private $conexion;
        function __construct(){
            require_once 'modelo_conexion.php';
            $this->conexion = new conexion();
            $this->conexion->conectar();
        }

      function listar_Grados_Docente($idprofe) {
          $sql = "SELECT a.id_estudiante as id_estu, e.nom_estu as nombre
                  FROM tutoria_asignacion_tutoria as a
                    INNER JOIN estudiante as e ON e.id_estu = a.id_estudiante
                  WHERE a.id_docente = '$idprofe' /* AND a.tipo_asignacion_id = '2' */";

          $arreglo = array();
          if ($consulta = $this->conexion->conexion->query($sql)) {
            while ($consulta_VU = mysqli_fetch_array($consulta)) {
              $arreglo[] = $consulta_VU;
            }

            return $arreglo;
            $this->conexion->cerrar();
        }
      }

      function Registra_Files_Grado($nombreArchivo,$extension,$fecha,$gradosId){
       $sql = "INSERT INTO tutoria_files_tuto (nombrearchivo, extension, fechaCreate, ciclo_file, folders_id) VALUES ('$nombreArchivo','$extension','$fecha','$gradosId', '1')";

                  if ($consulta = $this->conexion->conexion->query($sql)) {
                 return 1;    
                  }else{
                      return 0;
                  }

      }

        //nombrearchivo    extension   fechaCreate ciclo_file  folders_id  id_doce id_estudiante   tipo 
      function Registra_Files_Grado_Foders($iddoce, $idestu, $nombreArchivo,$extension,$fecha,$idforder){
          if ($idestu == '1' OR $idestu == '2'){
            $ver = $idestu;
            $idestu = '0';
          }else {
            $ver = '0';
          }

          $sql = "INSERT INTO tutoria_files (nombrearchivo, extension, fechaCreate, folders_id, id_doce, id_estudiante, tipo) VALUES ('$nombreArchivo','$extension','$fecha','$idforder', '$iddoce', '$idestu', '$ver')";

          if ($consulta = $this->conexion->conexion->query($sql)) {
            return 1;    
          }else{
            return 0;
          }
      }



    function listar_Archivos(){

      $sql  ="SELECT idfile, nombrearchivo, fechaCreate, ciclo_file from tutoria_files
              ORDER BY fechaCreate DESC";        
            $arreglo = array();
           if ($consulta = $this->conexion->conexion->query($sql)) {
           while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
                   $arreglo['data'][]=$consulta_VU;
          }
          return $arreglo;
          $this->conexion->cerrar();
          }
    }

  function Extraer_Nombre_file($idArchivo){
   
    $sql  = "select  nombrearchivo from tutoria_files where idfile='$idArchivo' ";     
            $arreglo = array();
           if ($consulta = $this->conexion->conexion->query($sql)) {
           while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
                   $arreglo[]=$consulta_VU;
          }
          return $arreglo;
          $this->conexion->cerrar();
          }

  } 

  function Quitar_file($idArchivo){

     $sql=   "DELETE FROM tutoria_files WHERE idfile = '$idArchivo'";

      if ($consulta = $this->conexion->conexion->query($sql)) {
        return 1;
        
      }else{
        return 0;
      }
  } 


    }
?>


