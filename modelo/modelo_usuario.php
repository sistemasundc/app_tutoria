<?php
    class Modelo_Usuario{
        private $conexion;
        function __construct(){
            require_once 'modelo_conexion.php';
            $this->conexion = new conexion();
            $this->conexion->conectar();
        }

    
        function VerificarUsuario($correo,$contra){
          $sql="SELECT u.id_usuario, u.nombres, r.nombre, u.estado, u.id_car, c.nom_car as escuela, u.clave 
                FROM tutoria_usuario as u
                  INNER JOIN tutoria_rol as r on r.id_rol = u.rol_id
                  INNER JOIN carrera as c ON c.id_car = u.id_car
                WHERE cor_inst='$correo'";

          $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_array($consulta)) {
                    if(password_verify($contra, $consulta_VU["clave"]))
                    {
                        $arreglo[] = $consulta_VU;
                    }
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }
        function VerificarDocente($correo,$contra) {
          $sql= "SELECT id_doce, nom_doce, CONCAT('DOCENTE') as rol, estadousu_doce FROM docente WHERE email_doce='$correo'";

          $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_array($consulta)) {
                    
                        $arreglo[] = $consulta_VU;
                   
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }

        function VerificarEstudiante($correo,$contra) {
          $sql= "SELECT id_estu, nom_estu, CONCAT('ALUMNO') as rol, pass_estu as clave  FROM estudiante WHERE email_estu='$correo'";

          $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_array($consulta)) {
                    
                    $arreglo[] = $consulta_VU;
                    
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }
        function listar_usuario(){
           $sql=  "SELECT
				usuario.id_usuario,
				usuario.username,
				usuario.apaterno,
				usuario.amaterno,
				rol.nombre AS nombre_rol,
				escuelaprofesional.nombre AS descripcion_escuela,
				usuario.estado
			FROM
				tutoria_usuario
			INNER JOIN
				tutoria_rol ON usuario.rol_id = rol.id_rol
			WHERE usuario.rol_id=2";

            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

                    $arreglo["data"][]=$consulta_VU;

                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }



        function listar_combo_rol(){
             $sql = "SELECT * FROM tutoria_rol";
            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_array($consulta)) {
                        $arreglo[] = $consulta_VU;
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }
		

		function Modificar_Estatus_Usuario($idusuario,$estatus){
       $sql = "UPDATE tutoria_usuario SET estado = '$estatus' WHERE id_usuario = '$idusuario'";
			if ($consulta = $this->conexion->conexion->query($sql)) {
				return 1;
				
			}else{
				return 0;
			}
        }



      function CambiarContra_Usuario($usuid,$contranew,$newfoto){
             $sql = "UPDATE tutoria_usuario SET clave = '$contranew',foto='$newfoto' WHERE id_usuario = '$usuid'";
            if ($consulta = $this->conexion->conexion->query($sql)) {      
              return 1;
              
            }else{
              return 0;
            }
      }

      function CambiarContra_Usuario_sinfoto($usuid,$contranew,$fotoActual){
             $sql = "UPDATE tutoria_usuario SET clave = '$contranew',foto='$fotoActual' WHERE id_usuario = '$usuid'";
            if ($consulta = $this->conexion->conexion->query($sql)) {      
              return 1;
              
            }else{
              return 0;
            }
      }
        

        function Registrar_Usuario($usuario,$contra,$sexo,$rol,$usuapell){
             $sql = "INSERT INTO tutoria_usuario(usu_nombre,usu_contrasena,usu_sexo,rol_id,usu_apellido,usu_foto) VALUES ('$usuario','$contra','$sexo','$rol','$usuapell','imagenes/images.png')";
     
            if ($consulta = $this->conexion->conexion->query($sql)) {

           return 1;
                
            }else{
                return 0;
            }
     }
     


      function Datos_Usuario_eliminar( $idusuario){
            $sql=   "DELETE FROM tutoria_usuario WHERE usu_id = '$idusuario'";

      if ($consulta = $this->conexion->conexion->query($sql)) {
        return 1;
        
      }else{
        return 0;
      }
        }

         function Modificar_Datos_Usuario( $idusuario,$nombre,$apellido,$sexo,$rol){
             $sql = "UPDATE tutoria_usuario SET usu_nombre='$nombre', usu_sexo = '$sexo',rol_id = '$rol',usu_apellido='$apellido' WHERE usu_id = '$idusuario'";
            if ($consulta = $this->conexion->conexion->query($sql)) {
                return 1; 
            }else{
                return 0;
            }
        }

        function Extraer_contracena($usu_id, $rol_usu){


                //AQUI ME QUEDE VALIANDO EL ADMINISTRADOR LA SESION
                if($rol_usu==='ADMINISTRADOR'){

                     $sql = "SELECT id_usuario,clave,foto, CONCAT('ADMINISTRADOR') FROM tutoria_usuario WHERE id_usuario='$usu_id'";
                        $arreglo = array();
                        if ($consulta = $this->conexion->conexion->query($sql)) {
                            while ($consulta_VU = mysqli_fetch_array($consulta)) {
                               
                                    $arreglo[] = $consulta_VU;
                            }
                            return $arreglo;
                            $this->conexion->cerrar();
                            
                        }
                }

              
              //AQUI ME QUEDE VALIANDO EL CORDINADO LA SESION
                if($rol_usu==='COORDINADOR'){

                     $sql = "SELECT id_usuario,clave,foto, CONCAT('COODINADOR') FROM tutoria_usuario WHERE id_usuario='$usu_id'";
                        $arreglo = array();
                        if ($consulta = $this->conexion->conexion->query($sql)) {
                            while ($consulta_VU = mysqli_fetch_array($consulta)) {
                               
                                    $arreglo[] = $consulta_VU;
                            }
                            return $arreglo;
                            $this->conexion->cerrar();
                            
                        }
                }

                //AQUI ME QUEDE VALIANDO EL APOYO LA SESION
                if($rol_usu==='APOYO'){

                     $sql = "SELECT id_usuario,clave,foto, CONCAT('APOYO') FROM tutoria_usuario WHERE id_usuario='$usu_id'";
                        $arreglo = array();
                        if ($consulta = $this->conexion->conexion->query($sql)) {
                            while ($consulta_VU = mysqli_fetch_array($consulta)) {
                               
                                    $arreglo[] = $consulta_VU;
                            }
                            return $arreglo;
                            $this->conexion->cerrar();
                            
                        }
                }
                 //AQUI  LA SESION DOCENTE
                if($rol_usu==='DOCENTE'){

                     $sql = "SELECT id_doce, nom_doce, CONCAT('DOCENTE') as rol, estadousu_doce, fot_doce as foto FROM docente WHERE id_doce='$usu_id'";
                        $arreglo = array();
                        if ($consulta = $this->conexion->conexion->query($sql)) {
                            while ($consulta_VU = mysqli_fetch_array($consulta)) {
                               
                                    $arreglo[] = $consulta_VU;
                            }
                            return $arreglo;
                            $this->conexion->cerrar();
                        }
                }
                //AQUI  LA SESION TUTOR DE AULA
                if($rol_usu==='TUTOR DE AULA'){
                    $sql = "SELECT id_doce, pass_doce AS clave, fot_doce as foto FROM docente WHERE id_doce='$usu_id'";
                    $arreglo = array();
                    if ($consulta = $this->conexion->conexion->query($sql)) {
                        while ($consulta_VU = mysqli_fetch_array($consulta)) {
                            $arreglo[] = $consulta_VU;
                        }
                        return $arreglo;
                        $this->conexion->cerrar();
                    }
                }
                if($rol_usu==='DOCENTE'){
                    $sql = "SELECT id_doce, pass_doce AS clave, fot_doce as foto FROM docente WHERE id_doce='$usu_id'";
                    $arreglo = array();
                    if ($consulta = $this->conexion->conexion->query($sql)) {
                        while ($consulta_VU = mysqli_fetch_array($consulta)) {
                            $arreglo[] = $consulta_VU;
                        }
                        return $arreglo;
                        $this->conexion->cerrar();
                    }
                }
                if($rol_usu==='ALUMNO'){

                     $sql = "SELECT id_estu, nom_estu, CONCAT('ALUMNO') as rol, pass_estu as clave, fot_estu as  foto FROM estudiante WHERE id_estu='$usu_id'";
                        $arreglo = array();
                        if ($consulta = $this->conexion->conexion->query($sql)) {
                            while ($consulta_VU = mysqli_fetch_array($consulta)) {
                               
                                    $arreglo[] = $consulta_VU;
                            }
                            return $arreglo;
                            $this->conexion->cerrar();
                        }
                }
                    
        }
        //UNIFICADO 
        /*function Extraer_contracena($usu_id, $rol_usu) {
            $arreglo = array();
            $sql = "";
        
            if ($rol_usu === 'ADMINISTRADOR' || $rol_usu === 'COORDINADOR' || $rol_usu === 'APOYO') {
                $sql = "SELECT id_usuario, clave, foto FROM tutoria_usuario WHERE id_usuario = '$usu_id'";
            } elseif ($rol_usu === 'DOCENTE' || $rol_usu === 'TUTOR DE AULA') {
                $sql = "SELECT id_doce AS id_usuario, pass_doce AS clave, fot_doce AS foto FROM docente WHERE id_doce = '$usu_id'";
            } elseif ($rol_usu === 'ALUMNO') {
                $sql = "SELECT id_estu AS id_usuario, pass_estu AS clave, fot_estu AS foto FROM estudiante WHERE id_estu = '$usu_id'";
            } else {
                return [];
            }
        
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($fila = mysqli_fetch_assoc($consulta)) {
                    $arreglo[] = $fila;
                }
            }
        
            return $arreglo;
        }*/
        

    }
?>