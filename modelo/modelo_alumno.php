<?php

    class Alumno{

        private $conexion;
        function __construct(){
            require_once 'modelo_conexion.php';
            $this->conexion = new conexion();
            $this->conexion->conectar();
        }
    
        function VerificarAlumno($usuario,$contra){ 
                 $sql = "select usuario.id_usuario,nombres,clave,rol.nombre,estado from tutoria_usuario inner join  tutoria_rol on rol.id_rol = usuario.id_usuario where username ='$usuario'";

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

    function VerValorarTutoria($id_sesion) {
      $sql = "SELECT d.valoracion_tuto as valoracion
              FROM tutoria_sesiones_tutoria_f78 as s
                INNER JOIN tutoria_detalle_sesion as d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
              WHERE d.id_detalle_sesion = '$id_sesion'";

      $arreglo = array();
        if ($consulta = $this->conexion->conexion->query($sql)) {
          while ($consulta_VU = mysqli_fetch_array($consulta)) {

              $arreglo[] = $consulta_VU;

          }
          return $arreglo;
          $this->conexion->cerrar();
        }
    }
    #funcion de valoracion de alumnos
    function ValorarTutoria($id_sesion, $valoracion, $comentario, $id_estu) {
      $sql = "UPDATE tutoria_detalle_sesion SET valoracion_tuto = '$valoracion', comentario_estu = '$comentario' WHERE sesiones_tutoria_id = '$id_sesion' AND id_estu = '$id_estu'";

      if ($consulta = $this->conexion->conexion->query($sql)) {      
        return 1;
      }else{
        return 0;
      }
    }

 function listar_sessiones_tutoria($id_estu, $fecha) {
          $sql=  "SELECT d.id_detalle_sesion as id_tuto, CONCAT(s.horainicio, ' - ', s.horaFin) AS horas, t.des_tipo as tipo
                  FROM tutoria_sesiones_tutoria_f78 as s
                    INNER JOIN tutoria_tipo_sesion as t ON t.id_tipo_sesion = s.tipo_sesion_id
                    INNER JOIN tutoria_detalle_sesion as d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
                    INNER JOIN tutoria_asignacion_tutoria as a ON a.id_asignacion = d.asignacion_id
                  WHERE s.fecha = '$fecha' AND a.id_estudiante = '$id_estu'";

          $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

                    $arreglo["data"][]=$consulta_VU;
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
      }

    function listar_mi_tutor($id_estu, $id_semestre) {
      $sql = "SELECT CONCAT(d.abreviatura_doce, ' ', d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) as nombres, 
                     d.email_doce as correo, d.celu_doce as telefono
                
              FROM tutoria_asignacion_tutoria as a
              INNER JOIN docente as d ON d.id_doce = a.id_docente
              WHERE id_estudiante = '$id_estu' and a.id_semestre = '$id_semestre'";
              
        $arreglo = array();
        if ($consulta = $this->conexion->conexion->query($sql)) {
          while ($consulta_VU = mysqli_fetch_array($consulta)) {

              $arreglo[] = $consulta_VU;

          }
          return $arreglo;
          $this->conexion->cerrar();
        }
    }

      function Extraer_contra_Alum( $idalum){
       $sql = "SELECT id_usuario,clave,foto FROM tutoria_usuario WHERE id_usuario='$idalum'";
            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
              while ($consulta_VU = mysqli_fetch_array($consulta)) {

                  $arreglo[] = $consulta_VU;

              }
              return $arreglo;
              $this->conexion->cerrar();
            }
      }

    function CambiarContra_Alum($usuid,$contranew,$newfoto){
         $sql = "UPDATE tutoria_usuario SET clave = '$contranew',foto='$newfoto' WHERE id_usuario = '$usuid'";
        if ($consulta = $this->conexion->conexion->query($sql)) {      
          return 1;

        }else{
          return 0;
        }
      }

      function CambiarContra_Alum_sinfoto($usuid,$contranew,$fotoActual){
             $sql = "UPDATE tutoria_usuario SET clave = '$contranew',foto='$fotoActual' WHERE id_usuario = '$usuid'";
            if ($consulta = $this->conexion->conexion->query($sql)) {      
              return 1;
              
            }else{
              return 0;
            }
      }

        function listar_Archivos_Grado($idgrado){
         $sql = "SELECT idfile, nombrearchivo, extension, fechaCreate FROM tutoria_files WHERE gradoid_file='$idgrado'";
                    $arreglo = array();
                    if ($consulta = $this->conexion->conexion->query($sql)) {
                        while ($consulta_VU = mysqli_fetch_array($consulta)) {
                           
                               $arreglo['data'][]=$consulta_VU;
                            
                        }
                        return $arreglo;
                        $this->conexion->cerrar();
                    }

        }

 

        //FUNCION DE HORARIOS

         function ListarHoras_Alumno() {

                    $sql = "SELECT * FROM tutoria_hora";
                    $arreglo = array();
                    if ($consulta = $this->conexion->conexion->query($sql)) {
                        while ($consulta_VU = mysqli_fetch_array($consulta)) {
                                $arreglo[] = $consulta_VU;
                        }
                        return $arreglo;
                        $this->conexion->cerrar();
                    }

          }

             function mostratarHorario_Alumno($dia,$hora,$idgrado ){

                $sql="SELECT idhorariocurso,nonbrecurso from tutoria_horario_curso 
                         inner join curso_tuto  on curso.idcurso = horario_curso.idcurso
                         WHERE idhora = '$hora' AND dia = '$dia' AND gradoid='$idgrado '";
                        $arreglo = array();
                        if ($consulta = $this->conexion->conexion->query($sql)) {
                            while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

                                $arreglo[]=$consulta_VU;

                            }
                            return $arreglo;
                            $this->conexion->cerrar();
                        }
                        
               }

            //reporte para escuela profesiona
            function listar_alumnos($semestre){
                $sql="SELECT
                        e.id_estu AS idestu,
                        CONCAT(e.apepa_estu, ' ', e.apema_estu) AS apellido_completo,
                        e.nom_estu AS nombres,
                        e.celu_estu AS telefono,
                        f.ciclo_ficham AS des_ciclo,
                        e.dni_estu AS dni,
                        e.email_estu AS cor_inst, 
                        f.id_semestre AS semestre 
                      FROM ficha_matricula AS f
                        INNER JOIN estudiante AS e ON  f.id_estu = e.id_estu
                      WHERE
                        f.id_semestre='$semestre' AND f.borrado <> '1'";

                        $arreglo = array();
                        if ($consulta = $this->conexion->conexion->query($sql)) {
                            while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

                                $arreglo["data"][]=$consulta_VU;

                            }
                            return $arreglo;
                            $this->conexion->cerrar();
                        }
              }

            function listar_alumnos_asignados($id_doc, $semestre){
                $sql="SELECT
                        a.id_asignacion AS id_asig,
                        e.id_estu AS id_estu,
                        CONCAT(e.apepa_estu, ' ', e.apema_estu) AS apellido_completo,
                        e.nom_estu AS nombres,
                        e.celu_estu AS telefono,
                        f.ciclo_ficham AS des_ciclo,
                        e.dni_estu AS dni,
                        a.seleccionado_estu  AS rendimiento,
                        e.email_estu AS cor_inst,
                        t.des_tipo AS tipo
                      FROM tutoria_asignacion_tutoria as a
                        INNER JOIN estudiante as e ON e.id_estu = a.id_estudiante
                        INNER JOIN ficha_matricula f ON  f.id_estu = e.id_estu
                        INNER JOIN tutoria_tipo_asignacion as t ON t.id_tipo_asignacion = a.tipo_asignacion_id
                      
                      WHERE f.id_semestre='30' AND f.borrado <> '1' AND a.id_docente = '$id_doc'
                      GROUP BY a.id_asignacion";

                        $arreglo = array();
                        if ($consulta = $this->conexion->conexion->query($sql)) {
                            while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

                                $arreglo["data"][]=$consulta_VU;

                            }
                            return $arreglo;
                            $this->conexion->cerrar();
                        }
            }

            function listar_Files_Grado_alumno($idgrado,$alumno,$idfolder){
             $sql  = "SELECT idfile, nombrearchivo, fechaCreate FROM tutoria_files WHERE idfolder='$idfolder' and gradoid_file='$idgrado' ";     
                        $arreglo = array();
                       if ($consulta = $this->conexion->conexion->query($sql)) {
                       while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
                               $arreglo[]=$consulta_VU;
                      }
                      return $arreglo;
                      $this->conexion->cerrar();
                      }
            }

    }

?>
