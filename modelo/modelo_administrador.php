<?php
    class Modelo_Administrador{
        private $conexion;
        function __construct(){
            require_once 'modelo_conexion.php';
            $this->conexion = new conexion();
            $this->conexion->conectar();
        }

         

          function Extraer_contracena($usu_id){
               $sql = "SELECT id_usuario,clave FROM tutoria_usuario WHERE id_usuario='$usu_id'";
            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_array($consulta)) {
                   
                        $arreglo[] = $consulta_VU;
                    
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }


function listar_combo_niveles(){
  $sql = "SELECT id_tipo_asignacion, des_tipo FROM tutoria_tipo_asignacion WHERE id_tipo_asignacion <> '3'";
      $arreglo = array();
      if ($consulta = $this->conexion->conexion->query($sql)) {
        while ($consulta_VU = mysqli_fetch_array($consulta)) {
                        $arreglo[] = $consulta_VU;
        }
        return $arreglo;
        $this->conexion->cerrar();
      }
} 

function SemestreActual(){
 $sql = "SELECT max(id_semestre) as semestre FROM ficha_matricula";
      $arreglo = array();
      if ($consulta = $this->conexion->conexion->query($sql)) {
        while ($consulta_VU = mysqli_fetch_array($consulta)) {
                        $arreglo[] = $consulta_VU;
        }
        return $arreglo;
        $this->conexion->cerrar();
      }
}


function Cambiar_semetre($idesemtnew,$nombsemtnew,$semtA){
 $sql = "UPDATE semestres SET idsemestres='$idesemtnew',semestresnombre = '$nombsemtnew' WHERE idsemestres = '$semtA'";

            if ($consulta = $this->conexion->conexion->query($sql)) {
                return 1;
                
            }else{
                return 0;
            }
}

 
 


}
?>