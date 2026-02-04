<?php
    class Apoyo {
        private $conexion;
        function __construct(){
            require_once 'modelo_conexion.php';
            $this->conexion = new conexion();
            $this->conexion->conectar();
        } 
       
        function ListarAlumnosReferidos($id_apoyo, $estado, $id_semestre){ //moficar M
            $sql = "SELECT  
                          dv.id_derivaciones as id_der, 
                          CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) as nombres,
                          ce.nom_car as escuela, 
                          ca.ciclo as ciclo,
                          a.id_asignacion as id_asig, 
                          dv.fechaDerivacion as fechad,
                          dv.motivo_ref as motivo,
                          a.id_estudiante as id_estu,
                          CONCAT(doc.apepa_doce, ' ', doc.apema_doce, ' ', doc.nom_doce) AS nombred,
                          dv.estado,
                          dv.id_docente,
                          e.celu_estu as telefono,
                          dv.observaciones as obser,
                          dv.resultado_contra as result
                      FROM tutoria_derivacion_tutorado_f6 as dv
                          INNER JOIN tutoria_area_apoyo as ap ON ap.idarea_apoyo = dv.area_apoyo_id
                          INNER JOIN estudiante as e ON e.id_estu = dv.id_estudiante
                          INNER JOIN tutoria_asignacion_tutoria as a ON a.id_estudiante = e.id_estu
                          INNER JOIN carga_lectiva as ca ON a.id_carga = ca.id_cargalectiva
                          INNER JOIN asignacion_estudiante as i ON i.id_estu = e.id_estu
                          INNER JOIN carrera as ce ON ce.id_car = i.id_car
                          INNER JOIN docente as doc ON dv.id_docente = doc.id_doce
                      WHERE ap.id_personal_apoyo = ? AND dv.estado = ? AND dv.id_semestre=?
                      GROUP BY dv.id_derivaciones
                      ORDER BY dv.id_derivaciones DESC
                      ";

              // Preparar la consulta
              $consulta = $this->conexion->conexion->prepare($sql);
              
              // Verificar si la consulta se preparó correctamente
              if (!$consulta) {
                  // Manejar el error aquí
                  return false;
              }
              
              // Asignar los parámetros y ejecutar la consulta
              $consulta->bind_param('ssi', $id_apoyo, $estado, $id_semestre);
              $consulta->execute();
              
              // Verificar si la consulta se ejecutó correctamente
              $resultado = $consulta->get_result();
              if (!$resultado) {
                  // Manejar el error aquí
                  return false;
              }
              
              // Cerrar la conexión y devolver los resultados
              $consulta->close();
              $this->conexion->cerrar();
              return $resultado;
          }

        function VerDetalleDerivacion($id_estu, $id_der) {
          $sql = "SELECT  a.idarea_apoyo, 
                          CONCAT(a.des_area_apo, ' - ', u.apaterno, ' ', u.amaterno, ' ', u.nombres, ' - ', dv.fecha) as nomarea,
                          dv.motivo_ref as motivo,
                          dv.resultado_contra,
                          dv.observaciones,
                          dv.estado,
                          dv.id_derivaciones
                          
                  FROM tutoria_derivacion_tutorado_f6 as dv
                    INNER JOIN tutoria_area_apoyo as a ON a.idarea_apoyo = dv.area_apoyo_id
                    INNER JOIN tutoria_usuario as u ON u.id_usuario = a.id_personal_apoyo
                  WHERE  dv.id_estudiante = '$id_estu'
                  ORDER BY dv.id_derivaciones DESC";
          
          $arreglo = array();
          if ($consulta = $this->conexion->conexion->query($sql)) {
            while ($consulta_VU = mysqli_fetch_array($consulta)) {
              $arreglo[] = $consulta_VU;
            }
            return $arreglo;
            $this->conexion->cerrar();
          }
        }
        //-------------------IMPRIMIR DETALLE DEL DERIVADO
       public function ObtenerDatosDocenteDerivacion($id_docente) {
            $con = conexion::conectar();
            $sql = "SELECT 
                        CONCAT(d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) AS nombre,
                        d.correo AS correo_doce
                    FROM docente d
                    WHERE d.id_docente = ?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param('i', $id_docente);
            $stmt->execute();
            $resultado = $stmt->get_result();
            return $resultado->fetch_assoc();
        }
        function ActualizarDerivacion($id_der, $result, $obser, $fecha, $id_asig) {
          $sql = "UPDATE tutoria_derivacion_tutorado_f6 SET observaciones = '$obser', resultado_contra = '$result', estado = 'Atendido', fechaDerivacion = '$fecha' WHERE id_derivaciones = '$id_der'";
          if ($this->conexion->conexion->query($sql)) {
            $sql = "UPDATE tutoria_asignacion_tutoria SET tipo_asignacion_id = '1' WHERE id_asignacion = '$id_asig'";
            if ($this->conexion->conexion->query($sql)) {
              echo 1;
            }else {
              echo 127;
            }
          }else {
            echo 0;
          }
        }
    }
?>