<?php
    class  Horario{
        public  $codigo;

        private $conexion;
        function __construct(){
            require_once 'modelo_conexion.php';
            $this->conexion = new conexion();
            $this->conexion->conectar();
            $this->codigo='';
        }
    
 function listar_alumnos_asignados($id_doc, $tipo, $semestre){
     $sql="SELECT
            a.id_asignacion AS id_asig,
            e.id_estu AS id_estu,
            e.nom_estu AS nombres
          FROM tutoria_asignacion_tutoria as a
            INNER JOIN estudiante as e ON e.id_estu = a.id_estudiante
            INNER JOIN ficha_matricula f ON  f.id_estu = e.id_estu
            INNER JOIN tutoria_tipo_asignacion as t ON t.id_tipo_asignacion = a.tipo_asignacion_id
          WHERE f.id_semestre='$semestre' AND f.borrado <> '1' AND a.id_docente = '$id_doc' AND a.tipo_asignacion_id = '$tipo'
          GROUP BY a.id_asignacion";
      $arreglo = array();
      if ($consulta = $this->conexion->conexion->query($sql)) {
        while ($consulta_VU = mysqli_fetch_array($consulta)) {
                        $arreglo[] = $consulta_VU;
        }
        return $arreglo;
        $this->conexion->cerrar();
      }
  }
function Eliminar_Horario_alumno($idhorario, $tipo, $hora, $dia){
  if ($tipo == '2'){
    $sql=   "DELETE FROM tutoria_horario_curso WHERE grupo_id = '$tipo' AND id_hora = '$hora' AND dia = '$dia'";
  }else {
    $sql=   "DELETE FROM tutoria_horario_curso WHERE id_horario = '$idhorario'";
  }
  
      if ($consulta = $this->conexion->conexion->query($sql)) {
        return 1;
      }else{
        return 0;
      }
}
 function Registar_horario($idhora, $dia, $iddoce, $id_estu, $tipo) {
    if ($tipo == '1'){
      $sql ="INSERT INTO tutoria_horario_curso ( id_usuario, dia, grupo_id, FechRegistro, statushorario, id_hora, id_doce) VALUES ('$id_estu', '$dia', '$tipo',  NOW(),'ACTIVO', '$idhora', $iddoce)";
    }else {
      $sql ="CALL RegistrarHorarioCurso_tuto('$id_estu', '$dia', 'ACTIVO', '$idhora', '$iddoce')";
    }

    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    }else{
      return 0;
      $this->conexion->cerrar();
    } 
       
}
function listar_Horario() {
    $sql = " ";
    $arreglo = array();
    $valoresUnicos = array();  

    if ($consulta = $this->conexion->conexion->query($sql)) {
        while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
            $valorUnico = $consulta_VU['gradoid'];   
            
          
            if (!in_array($valorUnico, $valoresUnicos)) {
                $arreglo["data"][] = $consulta_VU;
                $valoresUnicos[] = $valorUnico;  
            }
        }
        
        $this->conexion->cerrar();   
    }

    return $arreglo;
}

function ListarHoras($id_docente, $idhora) {

  $sql = "SELECT hc.id_horario as id_horario, hc.dia as dia, e.nom_estu as estudiante, a.tipo_asignacion_id as tipo
            FROM tutoria_hora as h
            INNER JOIN tutoria_horario_curso as hc ON hc.id_hora = h.idhora
            INNER JOIN estudiante as e ON e.id_estu = hc.id_usuario
            INNER JOIN tutoria_asignacion_tutoria as a ON a.id_estudiante = e.id_estu
          WHERE hc.id_doce = '$id_docente' AND hc.statushorario='ACTIVO' AND hc.id_hora = '$idhora'";
  $arreglo = array();
  
  if ($consulta = $this->conexion->conexion->query($sql)) {
    while ($consulta_VU = mysqli_fetch_array($consulta)) {
      $arreglo[] = $consulta_VU;
    }
    
    return $arreglo;
    $this->conexion->cerrar();
  }
}
function ListarHorasEstudiante($id_estu, $idhora) {

  $sql = "SELECT hc.id_horario as id_horario, hc.dia as dia, e.nom_estu as estudiante, CONCAT(d.abreviatura_doce, ' ', d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) as nombres
            FROM tutoria_hora as h
            INNER JOIN tutoria_horario_curso as hc ON hc.id_hora = h.idhora
            INNER JOIN estudiante as e ON e.id_estu = hc.id_usuario
            INNER JOIN docente as d ON d.id_doce = hc.id_doce
          WHERE hc.id_usuario = '$id_estu' AND hc.statushorario='ACTIVO' AND hc.id_hora = '$idhora'";
  $arreglo = array();
  
  if ($consulta = $this->conexion->conexion->query($sql)) {
    while ($consulta_VU = mysqli_fetch_array($consulta)) {
      $arreglo[] = $consulta_VU;
    }
    
    return $arreglo;
    $this->conexion->cerrar();
  }
}
function ListaHora() {
  $sql = "SELECT h.idhora as idhora, h.inicio as inicio, h.fin as fin FROM tutoria_hora as h";
  $arreglo = array();
  
  if ($consulta = $this->conexion->conexion->query($sql)) {
    while ($consulta_VU = mysqli_fetch_array($consulta)) {
      $arreglo[] = $consulta_VU;
    }
    
    return $arreglo;
    $this->conexion->cerrar();
  }
}

         function eliminar($xidhorario){
          
             $sql=  "DELETE FROM tutoria_horario_curso WHERE idhorariocurso = '$xidhorario' ";

            if ($consulta = $this->conexion->conexion->query($sql)) {
              return 1;
        
            }else{
                return 0;
             }



    }

    function Verificar_Existe($idgrado){
      $sql = "SELECT id_usu FROM tutoria_horario_curso where id_usu='$idgrado'";
      $arreglo = array();
      
      if ($consulta = $this->conexion->conexion->query($sql)) {
        while ($consulta_VU = mysqli_fetch_array($consulta)) {
          $arreglo[] = $consulta_VU;
        }
        return count($arreglo);
        $this->conexion->cerrar();
      }
    }

  /*   function CargarHorario($id_doce) {
      $sql = "SELECT s.tema, s.compromiso_indi, s.link,
                     CONCAT(s.fecha, 'T', s.horaInicio), 
                     CONCAT(s.fecha, 'T', s.horaFin), 
                     s.color,
                     s.id_sesiones_tuto
              FROM tutoria_sesiones_tutoria_f78 as s
              WHERE id_doce = '$id_doce'";
              $arreglo = array();
     if ($consulta = $this->conexion->conexion->query($sql)) {
        while ($consulta_VU = mysqli_fetch_array($consulta)) {
          $arreglo[] = $consulta_VU;
        }
        return $arreglo;
        $this->conexion->cerrar();
      }
    } */

  /* function CargarHorario($id_doce, $rol) {
      $arreglo = [];
      // Tutor de Aula (ROL = 6)
      if ($rol == 6) {
          $sql = "SELECT 
                      s.tema, 
                      s.compromiso_indi, 
                      s.link,
                      CONCAT(s.fecha, 'T', s.horaInicio),
                      CONCAT(s.fecha, 'T', s.horaFin),
                      s.color,
                      s.id_sesiones_tuto,
                      '' AS id_cargalectiva
                  FROM tutoria_sesiones_tutoria_f78 AS s
                  WHERE s.id_doce = '$id_doce' AND s.id_rol = 6";
      } 
      // Tutor de Curso (ROL = 2)
      else if ($rol == 2) {
          $sql = "SELECT 
                      s.tema, 
                      s.compromiso_indi, 
                      s.link,
                      CONCAT(s.fecha, 'T', s.horaInicio),
                      CONCAT(s.fecha, 'T', s.horaFin),
                      s.color,
                      s.id_sesiones_tuto,
                      '' AS id_cargalectiva
                  FROM tutoria_sesiones_tutoria_f78 AS s
                  WHERE s.id_doce = '$id_doce' AND s.id_rol = 2";
      } 
      // Si no coincide ningún rol
      else {
          return $arreglo;
      }
      if ($consulta = $this->conexion->conexion->query($sql)) {
          while ($fila = mysqli_fetch_array($consulta, MYSQLI_NUM)) {
              $arreglo[] = $fila;
          }
      }
      return $arreglo;
  } */
  public function CargarHorario($id_doce, $rol) {
      $arreglo = [];
      if ($rol == 6) {
          // Tutor de Aula
          $sql = "SELECT 
                      s.tema, 
                      s.compromiso_indi, 
                      s.link,
                      CONCAT(s.fecha, 'T', s.horaInicio),
                      CONCAT(s.fecha, 'T', s.horaFin),
                      s.color,
                      s.id_sesiones_tuto,
                      NULL AS id_cargalectiva
                  FROM tutoria_sesiones_tutoria_f78 AS s
                  WHERE s.id_doce = '$id_doce' AND s.id_rol = 6";
      } else if ($rol == 2) {
          $sql = "SELECT 
                      s.tema, 
                      s.compromiso_indi, 
                      s.link,
                      CONCAT(s.fecha, 'T', s.horaInicio),
                      CONCAT(s.fecha, 'T', s.horaFin),
                      s.color,
                      s.id_sesiones_tuto,
                      -- Obtener la carga desde detalle
                      (
                          SELECT tdsc.id_cargalectiva
                          FROM tutoria_detalle_sesion_curso tdsc
                          WHERE tdsc.sesiones_tutoria_id = s.id_sesiones_tuto
                          LIMIT 1
                      ) AS id_cargalectiva
                  FROM tutoria_sesiones_tutoria_f78 AS s
                  WHERE s.id_doce = '$id_doce' AND s.id_rol = 2";
      } else {
          return $arreglo;
      }

      if ($consulta = $this->conexion->conexion->query($sql)) {
          while ($fila = mysqli_fetch_array($consulta, MYSQLI_NUM)) {
              $arreglo[] = $fila;
          }
      }

      return $arreglo;
  }


    function CargarHorarioAlumno($id_estu) {
      $sql = "SELECT s.tema, s.compromiso_indi, s.link,
                     CONCAT(s.fecha, 'T', s.horaInicio), 
                     CONCAT(s.fecha, 'T', s.horaFin), 
                     s.color,
                     s.id_sesiones_tuto
              FROM tutoria_detalle_sesion as d
                INNER JOIN tutoria_sesiones_tutoria_f78 as s ON s.id_sesiones_tuto = d.sesiones_tutoria_id
              WHERE d.id_estu = '$id_estu'
              GROUP BY s.id_sesiones_tuto";
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
?>