<?php

include '../../modelo/modelo_cerrar_sesion.php';

class Docente
{
  private $conexion;
  //private $conexion_sivireno; //---
  function __construct()
  {
    require_once 'modelo_conexion.php';
    // require_once 'modelo_conexion_2.php'; //---
    $this->conexion = new conexion();
    $this->conexion->conectar();

    // $this->conexion_sivireno = new conexion_sivireno(); //---
    //  $this->conexion_sivireno->conectar(); //---
  }

  function VerificarDocente($usuario, $contra)
  {
    $sql = "select usuario.id_usuario,nombres,clave,rol.nombre,estado from tutoria_usuario inner join  tutoria_rol on rol.id_rol = usuario.id_usuario where username ='$usuario'";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_array($consulta)) {
        if (password_verify($contra, $consulta_VU["clave"])) {
          $arreglo[] = $consulta_VU;
        }
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }

  function VerificarDelCalendario($id_sesion)
  {
    $sql = "SELECT marcar_asis_estu FROM tutoria_detalle_sesion WHERE sesiones_tutoria_id='$id_sesion' AND marcar_asis_estu = 1";

    if ($consulta = $this->conexion->conexion->query($sql)) {
      $filas_afectadas = $consulta->num_rows;

      if ($filas_afectadas > 0) {
        return 0;
      } else {
        $this->EliminarDetalleCalendario($id_sesion);
        return 1;
      }
      $this->conexion->cerrar();
    }
  }

  function EliminarDetalleCalendario($id_sesion)
  {
    $sql = "DELETE FROM tutoria_detalle_sesion WHERE sesiones_tutoria_id = '$id_sesion'";

    if ($consulta = $this->conexion->conexion->query($sql)) {
      $this->EliminarSesionCalendario($id_sesion);
      return 1;
    } else {
      return 127;
    }
  }

  function EliminarSesionCalendario($id_sesion)
  {
    $sql = "DELETE FROM tutoria_sesiones_tutoria_f78 WHERE id_sesiones_tuto = '$id_sesion'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 127;
    }
  }

  function ChangeTipoAsig($id_asig, $id_tipo)
  {
    $sql = "UPDATE tutoria_asignacion_tutoria SET  tipo_asignacion_id = '$id_tipo' WHERE id_asignacion = '$id_asig'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }
  //--------------------------------LISTAR ESTUDIANTES DOCENTE TUTOR DE AULA------------------------------
  function listar_alumnos_asignados($id_doc, $semestre)
  {
    $sql = "SELECT
                  a.id_asignacion AS id_asig,
                  e.id_estu AS id_estu,
                  CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS nombres,
                  e.celu_estu AS telefono,
                  f.ciclo_ficham AS des_ciclo,
                  CASE 
                      WHEN (COALESCE(ae.ntp1_per, 20) <= 10 OR 
                            COALESCE(ae.ntp2_per, 20) <= 10 OR 
                            COALESCE(ae.ntp3_per, 20) <= 10 OR 
                            COALESCE(ae.ntp4_per, 20) <= 10 OR 
                            COALESCE(ae.pf_per, 20) <= 10 OR 
                            COALESCE(ae.exa_par, 20) <= 10 OR 
                            COALESCE(ae.exa_final, 20) <= 10)
                      THEN 1
                      ELSE 0
                  END AS rendimiento,
                  e.email_estu AS cor_inst,
                  CASE 
                      WHEN (COALESCE(ae.ntp1_per, 20) <= 10 OR 
                            COALESCE(ae.ntp2_per, 20) <= 10 OR 
                            COALESCE(ae.ntp3_per, 20) <= 10 OR 
                            COALESCE(ae.ntp4_per, 20) <= 10 OR 
                            COALESCE(ae.pf_per, 20) <= 10 OR 
                            COALESCE(ae.exa_par, 20) <= 10 OR 
                            COALESCE(ae.exa_final, 20) <= 10)
                      THEN 1
                      ELSE 2
                  END AS id_tipo,
                  CASE 
                      WHEN (COALESCE(ae.ntp1_per, 20) <= 10 OR 
                            COALESCE(ae.ntp2_per, 20) <= 10 OR 
                            COALESCE(ae.ntp3_per, 20) <= 10 OR 
                            COALESCE(ae.ntp4_per, 20) <= 10 OR 
                            COALESCE(ae.pf_per, 20) <= 10 OR 
                            COALESCE(ae.exa_par, 20) <= 10 OR 
                            COALESCE(ae.exa_final, 20) <= 10)
                      THEN 'INDIVIDUAL'
                      ELSE 'GRUPAL'
                  END AS tipo
              FROM tutoria_asignacion_tutoria a
              INNER JOIN estudiante e ON e.id_estu = a.id_estudiante
              INNER JOIN ficha_matricula f ON f.id_estu = e.id_estu
              INNER JOIN asignacion_estudiante ae ON ae.id_estu = e.id_estu
              WHERE f.id_semestre = ? 
                AND f.borrado <> '1' 
                AND a.id_docente = ?
              GROUP BY a.id_asignacion
              ORDER BY id_tipo ASC, rendimiento DESC";

    $stmt = $this->conexion->conexion->prepare($sql);
    if (!$stmt) {
      die('Error en prepare(): ' . $this->conexion->conexion->error);
    }
    $stmt->bind_param("ii", $semestre, $id_doc);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
  }

  //--------------------------------LISTAR ESTUDIANTES DOCENTE TUTOR POR ASIGNATURA ---------------------------------
  public function listar_alumnos_x_asignatura($id_doc, $id_cargalectiva)
  {
    $conexion = new conexion();
    $conexion->conectar();
    $sql = "SELECT 
                  ae.id_aestu AS id_asig,
                  ae.id_estu AS id_estu,
                  CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS nombres,
                  e.celu_estu AS telefono,
                  f.ciclo_ficham AS des_ciclo,
                  e.email_estu AS cor_inst,
                  CASE 
                      WHEN (COALESCE(ae.ntp1_per, 20) <= 10 OR 
                            COALESCE(ae.ntp2_per, 20) <= 10 OR 
                            COALESCE(ae.ntp3_per, 20) <= 10 OR 
                            COALESCE(ae.ntp4_per, 20) <= 10 OR 
                            COALESCE(ae.pf_per, 20) <= 10 OR 
                            COALESCE(ae.exa_par, 20) <= 10 OR 
                            COALESCE(ae.exa_final, 20) <= 10) 
                      THEN 1
                      ELSE 0
                  END AS rendimiento,
                  CASE 
                      WHEN (COALESCE(ae.ntp1_per, 20) <= 10 OR 
                            COALESCE(ae.ntp2_per, 20) <= 10 OR 
                            COALESCE(ae.ntp3_per, 20) <= 10 OR 
                            COALESCE(ae.ntp4_per, 20) <= 10 OR 
                            COALESCE(ae.pf_per, 20) <= 10 OR 
                            COALESCE(ae.exa_par, 20) <= 10 OR 
                            COALESCE(ae.exa_final, 20) <= 10) 
                      THEN 1
                      ELSE 2
                  END AS id_tipo,
                  CASE 
                      WHEN (COALESCE(ae.ntp1_per, 20) <= 10 OR 
                            COALESCE(ae.ntp2_per, 20) <= 10 OR 
                            COALESCE(ae.ntp3_per, 20) <= 10 OR 
                            COALESCE(ae.ntp4_per, 20) <= 10 OR 
                            COALESCE(ae.pf_per, 20) <= 10 OR 
                            COALESCE(ae.exa_par, 20) <= 10 OR 
                            COALESCE(ae.exa_final, 20) <= 10) 
                      THEN 'INDIVIDUAL'
                      ELSE 'GRUPAL'
                  END AS tipo
              FROM asignacion_estudiante ae
              INNER JOIN estudiante e ON e.id_estu = ae.id_estu
              INNER JOIN ficha_matricula f ON f.id_estu = ae.id_estu
              WHERE ae.id_cargalectiva = ?
                AND ae.borrado = 0
                AND f.id_semestre = 32
              GROUP BY ae.id_estu
              ORDER BY nombres ASC";

    $stmt = $conexion->conexion->prepare($sql);
    if (!$stmt) {
      die('Error en prepare(): ' . $conexion->conexion->error);
    }
    $stmt->bind_param("i", $id_cargalectiva);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
  }
  //--------------------------------Obtener informacion de la asignarura ciclo etc ---------------------------------
  public function obtener_info_asignatura($id_cargalectiva)
  {
    $conexion = new conexion();
    $conexion->conectar();

    $sql = "SELECT 
                  a.nom_asi,
                  c.nom_car,
                  cl.ciclo,
                  cl.turno,
                  cl.seccion
              FROM carga_lectiva cl
              INNER JOIN asignatura a ON a.id_asi = cl.id_asi
              INNER JOIN carrera c ON a.id_car = c.id_car
              WHERE cl.id_cargalectiva = ?";

    $stmt = $conexion->conexion->prepare($sql);
    if ($stmt === false) {
      die('Error preparando consulta: ' . $conexion->conexion->error);
    }
    $stmt->bind_param("i", $id_cargalectiva);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
  }

  //--------------------------------REGISTRAR SESIONES DE TUTORIA ---------------------------------
  function RegistrarSessionTutoria($iddocente, $inicio, $final, $tema, $compromiso, $tipo_session, $reu_otros, $link, $fecha)
  {

    $sql = "INSERT INTO tutoria_sesiones_tutoria_f78 (fecha, horaInicio,  horaFin, tema, reunion_tipo_otros, link, compromiso_indi, color, tipo_sesion_id, id_doce, id_rol) VALUES ('$fecha', '$inicio', '$final', '$tema', '$reu_otros', '$link', '$compromiso', '#3c8dbc', '$tipo_session', '$iddocente','2')";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      $ultimoId = $this->conexion->conexion->insert_id;
      return $ultimoId;
    } else {
      return 0;
    }
  }
  /*   function RegistrarDetalleSessionTutoria($id_asignacion, $id_estudiante, $id_sesion)
  {
    $sql = "INSERT INTO tutoria_detalle_sesion (asignacion_id, sesiones_tutoria_id, marcar_asis_doce, marcar_asis_estu, valoracion_tuto, comentario_estu, id_estu) VALUES ('$id_asignacion', '$id_sesion', '1', '0', '0', '0', '$id_estudiante')";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  } */

/* function RegistrarDetalleSessionTutoria($id_asignacion, $id_estudiante, $id_sesion)
{
  $sql = "INSERT INTO tutoria_detalle_sesion 
            (asignacion_id, sesiones_tutoria_id, marcar_asis_doce, marcar_asis_estu, valoracion_tuto, comentario_estu, id_estu)
          VALUES 
            ('$id_asignacion', '$id_sesion', 1, 0, 0, '0', '$id_estudiante')";
  
  if ($this->conexion->conexion->query($sql)) {
    return 1;
  } else {
    echo "ERROR SQL: " . $this->conexion->conexion->error . "<br>";
    echo "Consulta: $sql<br>";
    return 0;
  }
} */

function RegistrarDetalleSessionTutoria($id_asignacion, $id_estudiante, $id_sesion)
{
    $sql = "INSERT INTO tutoria_detalle_sesion 
        (asignacion_id, sesiones_tutoria_id, marcar_asis_doce, marcar_asis_estu, valoracion_tuto, comentario_estu, id_estu)
        VALUES (?, ?, 1, 0, 0, '0', ?)";

    $stmt = $this->conexion->conexion->prepare($sql);
    if (!$stmt) {
        // Opcional: log o mostrar error para debug
        error_log("Error prepare RegistrarDetalleSessionTutoria: " . $this->conexion->conexion->error);
        return 0;
    }

    // Ajusta los tipos segÃºn tus datos. Supongo que $id_asignacion y $id_sesion son enteros, y $id_estudiante es string.
    $stmt->bind_param("iis", $id_asignacion, $id_sesion, $id_estudiante);

    if ($stmt->execute()) {
        return 1;
    } else {
        error_log("Error execute RegistrarDetalleSessionTutoria: " . $stmt->error);
        return 0;
    }
}



  function ActualizarSessionTutoria($sesi, $inic, $fina, $tema, $comp, $tipo, $deta, $link, $fech, $obse)
  {
    $sql = "UPDATE `tutoria_sesiones_tutoria_f78` SET 
                `fecha`='$fech',
                `horaInicio`='$inic',
                `horaFin`='$fina',
                `tema`='$tema',
                `observacione`='$obse',
                `reunion_tipo_otros`='$deta',
                `link`='$link',
                `compromiso_indi`='$comp',
                `color`= '#00a65a',
                `tipo_sesion_id`='$tipo'
              WHERE id_sesiones_tuto='$sesi'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }
  /* --------------------ASISTENCIA VERIFICAR---------------------------- */
  function RegistrarAsistenciaTutoria($id_estu)
  {
    $sql = "UPDATE tutoria_detalle_sesion SET 
                      marcar_asis_estu = '1'
              WHERE id_detalle_sesion='$id_estu'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }

  function AsistenciasGuardadas($inicio, $final, $iddoce, $fecha)
  {
    $sql = "SELECT s.tema, s.compromiso_indi as compromiso, s.observacione,
                     s.tipo_sesion_id as tipo_session, s.reunion_tipo_otros as otros, 
                     d.asignacion_id as id_asig, s.id_sesiones_tuto as id_sesion
              FROM tutoria_sesiones_tutoria_f78 as s
              INNER JOIN tutoria_detalle_sesion as d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
              INNER JOIN tutoria_asignacion_tutoria as a ON a.id_asignacion = d.asignacion_id
              WHERE s.id_doce = '$iddoce' AND s.fecha='$fecha' AND s.horainicio = '$inicio' AND s.horaFin = '$final' AND (a.tipo_asignacion_id = '1' OR a.tipo_asignacion_id = '2')";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_array($consulta)) {

        $arreglo[] = $consulta_VU;
      }

      return $arreglo;
      $this->conexion->cerrar();
    }
  }

  function CambiarContra_Docente($usuid, $contranew, $newfoto)
  {
    $sql = "UPDATE tutoria_usuario SET clave = '$contranew',foto='$newfoto' WHERE id_usuario = '$usuid'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }
  //-------------------------tutor aula---------------------------
  function listar_sessiones_tutoria($id_doce, $dia)
  {
    $sql =  "SELECT e.id_estu as id_estu, h.inicio as inicio, h.fin as final, e.nom_estu as estudiante, a.tipo_asignacion_id as tipo
            FROM tutoria_hora as h
            INNER JOIN tutoria_horario_curso as hc ON hc.id_hora = h.idhora
            INNER JOIN estudiante as e ON e.id_estu = hc.id_usuario
            INNER JOIN tutoria_asignacion_tutoria as a ON a.id_estudiante = e.id_estu
          WHERE hc.id_doce = '$id_doce' AND hc.statushorario='ACTIVO' AND hc.dia = '$dia'
          GROUP BY h.inicio";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

        $arreglo["data"][] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }
  //--------------------TUTOR CURSO---------------------------
  public function listar_sessiones_docente_curso($id_doce, $dia)
  {
    $sql =  "SELECT e.id_estu as id_estu, h.inicio as inicio, h.fin as final, e.nom_estu as estudiante, a.tipo_asignacion_id as tipo
                FROM tutoria_hora as h
                INNER JOIN tutoria_horario_curso as hc ON hc.id_hora = h.idhora
                INNER JOIN estudiante as e ON e.id_estu = hc.id_usuario
                INNER JOIN tutoria_asignacion_tutoria as a ON a.id_estudiante = e.id_estu
                WHERE hc.id_doce = '$id_doce' 
/*                   AND hc.id_cargalectiva = '$id_cargalectiva' */
                  AND hc.statushorario='ACTIVO' 
                  AND hc.dia = '$dia'
                GROUP BY h.inicio";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
        $arreglo["data"][] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }

  function _histlistarorial_sesiones($id_doce)
  {
    $sql =  "SELECT
            s.id_sesiones_tuto as id_tuto,
            s.fecha as fecha,
            CONCAT(s.horainicio, '-', s.horaFin) as horas,
            t.des_tipo as tipo, IF((SELECT COUNT(*) FROM tutoria_detalle_sesion d WHERE d.sesiones_tutoria_id = s.id_sesiones_tuto) >1, 2, 1) as idtipo,
            e.nom_estu as estudiante,
            s.tema
        FROM tutoria_sesiones_tutoria_f78 as s
        INNER JOIN tutoria_tipo_sesion as t ON t.id_tipo_sesion = s.tipo_sesion_id
                    INNER JOIN tutoria_detalle_sesion as d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
                    INNER JOIN tutoria_asignacion_tutoria as a ON a.id_asignacion = d.asignacion_id
                    INNER JOIN estudiante as e ON e.id_estu = a.id_estudiante
                  WHERE s.id_doce = '$id_doce' AND s.id_rol='2'
                  GROUP BY s.id_sesiones_tuto
                  ORDER BY s.id_sesiones_tuto DESC";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

        $arreglo["data"][] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }

  function listar_historial_derivaciones($id_doce)
  {
    $id_rol = 2;
    $sql =  "SELECT
            d.id_derivaciones as id_der,
            d.fecha,
            CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) as nombres,
            e.celu_estu as telefono,
            d.estado,
            a.des_area_apo as des_area,
            d.id_estudiante as id_estu
        FROM tutoria_derivacion_tutorado_f6 as d
                    INNER JOIN estudiante as e ON e.id_estu = d.id_estudiante
                    INNER JOIN tutoria_area_apoyo as a ON a.idarea_apoyo = d.area_apoyo_id
                  WHERE d.id_docente = '$id_doce' AND id_rol='$id_rol'";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

        $arreglo["data"][] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }
  //---------------------------ASSITENCIA ALUMNOS-------------------------------------------------
  function listar_alumnos_asistencia($id_doce, $dia,  $id_estu, $tipo, $horainicio, $semestre)
  {
    // concatenando consultas condicionadas
    if ($tipo == '2') {
      //$inner = "INNER JOIN asignacion_tutoria as a ON a.id_estudiante = e.id_estu";
      $where = "AND a.tipo_asignacion_id = '2' AND a.id_docente='$id_doce' AND a.id_semestre='$semestre'";
    } else {
      $where = "AND hc.id_usuario = '$id_estu'AND a.id_docente='$id_doce'  AND a.id_semestre='$semestre'";
      //$inner = "";
    }
    $sql =  "SELECT a.id_asignacion as id_asig, e.nom_estu as estudiante
            FROM tutoria_hora as h
            INNER JOIN tutoria_horario_curso as hc ON hc.id_hora = h.idhora
            INNER JOIN estudiante as e ON e.id_estu = hc.id_usuario
            INNER JOIN tutoria_asignacion_tutoria as a ON a.id_estudiante = e.id_estu
          WHERE hc.id_doce = '$id_doce' AND hc.statushorario='ACTIVO' AND h.inicio = '$horainicio' AND hc.dia = '$dia' " . $where;

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

        $arreglo["data"][] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }

  function CambiarContra_Docente_sinfoto($usuid, $contranew, $fotoActual)
  {
    $sql = "UPDATE tutoria_usuario SET clave = '$contranew',foto='$fotoActual' WHERE id_usuario = '$usuid'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }
  function TipoSession()
  {
    $sql = "SELECT id_tipo_sesion, des_tipo FROM tutoria_tipo_sesion";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_array($consulta)) {
        $arreglo[] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }
  function AreaApoyo($id_apoyo_dif)
  {
    $sql = "SELECT a.idarea_apoyo, CONCAT(a.des_area_apo, ' - ', u.apaterno, ' ', u.amaterno, ' ', u.nombres) 
                FROM tutoria_area_apoyo as a 
                  INNER JOIN tutoria_usuario as u ON u.id_usuario = a.id_personal_apoyo
                WHERE a.id_personal_apoyo <> '$id_apoyo_dif'";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_array($consulta)) {
        $arreglo[] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }
  /*   function DerivarAlumno($fecha, $hora, $motivo_referido, $area, $id_estu, $id_doce)
  {
    $sql = "INSERT INTO tutoria_derivacion_tutorado_f6 (fecha, hora,  motivo_ref, fechaDerivacion, estado, area_apoyo_id, id_estudiante, id_docente) VALUES ('$fecha', '$hora', '$motivo_referido', '$fecha', 'Pendiente', '$area', '$id_estu', '$id_doce')";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return $consulta;
    }
  } */

  function DerivarAlumno($fecha, $hora, $motivo_referido, $area, $id_estu, $id_doce)
  {
    $sql = "INSERT INTO tutoria_derivacion_tutorado_f6 (fecha, hora,  motivo_ref, fechaDerivacion, estado, area_apoyo_id, id_estudiante, id_docente, id_rol) VALUES ('$fecha', '$hora', '$motivo_referido', '$fecha', 'Pendiente', '$area', '$id_estu', '$id_doce','2')";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return $consulta;
    }
  }
  function UpdateDerivadoApoyo($id_der, $result_der)
  {
    $sql = "UPDATE tutoria_derivacion_tutorado_f6 SET estado = 'Atendido', resultado_contra = '$result_der' WHERE id_derivaciones = '$id_der'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return $consulta;
    }
  }
  //---
  function UpdateDerivado($id_estu,  $id_asig, $area)
  {
    $sql = "UPDATE tutoria_asignacion_tutoria SET tipo_asignacion_id='3', id_apoyo='$area' WHERE id_asignacion = '$id_asig'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }

  function listar_docente($semestre, $id_coordinador)
  {
    $sql = "SELECT 
                    d.id_doce as id_doce_tuto,
                    CONCAT(d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) AS nombres,
                    e.nom_car as escuela
                FROM tutoria_docente_asignado as da
                LEFT JOIN docente as d ON d.id_doce = da.id_doce
                LEFT JOIN carrera as e ON e.id_car = d.id_car
                WHERE da.id_semestre = '$semestre'
                  AND da.id_coordinador = '$id_coordinador'";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
        $arreglo['data'][] = $consulta_VU;
      }
    }

    return $arreglo;
    $this->conexion->cerrar();
  }

  //---
  function Docente_Asignado($iddocente, $arreglo, $vectoC, $Semest)
  {

    $sql = "insert into docenteasignado_tuto(docenteid, grado_id, curso_id ,semestre) values ('$iddocente','$arreglo','$vectoC','$Semest')";

    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }

  function Verificar_YaAsignado($iddocente, $arreglo, $vectoC)
  {
    $sql =  "select docenteid, grado_id, curso_id from docenteasignado_tuto where docenteid='$iddocente' and  curso_id='$vectoC' and grado_id='$arreglo' ";
    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

        $arreglo[] = $consulta_VU;
      }
      return count($arreglo);
      $this->conexion->cerrar();
    }
  }


  function Quitar_cursoDocente($idcurso)
  {
    $sql =   "DELETE FROM docenteasignado_tuto  WHERE curso_id = '$idcurso'";
    if ($consulta = $this->conexion->conexion->query($sql)) {

      $this->Recontruir_stado_curso($idcurso);

      return 1;
    } else {
      return 0;
    }
  }
  function Recontruir_stado_curso($idcurso)
  { //VOLVER EL ESTADO A PENDIENTE

    $sql = "UPDATE curso_tuto SET stadodocent = 'PENDIENTE' WHERE idcurso = '$idcurso'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }


  function Cambiar_estado_curso($vectoC)
  {
    $sql = "UPDATE curso_tuto SET stadodocent = 'DICTANDO' WHERE idcurso = '$vectoC'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }

  function Update_Docente($iddocente, $nomdoce, $doctapell, $statusdoct, $sexodocet, $tipodocet)
  {

    $sql = "UPDATE docente SET nombre = '$nomdoce',apellido='$doctapell',sexo='$sexodocet',status='$statusdoct',tipo='$tipodocet' WHERE iddocente = '$iddocente'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }

  function listar_combo_niveles()
  {
    $sql = "SELECT idgrado, gradonombre FROM grado_tuto";
    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_array($consulta)) {
        $arreglo[] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }



  function Traer_curso($idnivel)
  {

    /* $sql = "select idcurso,nonbrecurso from grado_curso 
                            inner join  curso on curso.idcurso= grado_curso.curso_id  where grado_id='$idnivel'";*/
    $sql = "select idcurso,nonbrecurso from grado_curso_tuto 
                            inner join  curso_tuto on curso.idcurso = grado_curso.curso_id  where grado_id='$idnivel' ";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_array($consulta)) {
        $arreglo[] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }

  function Extraer_Cursos_Estado_Pendiente($cursos, $idcurso)
  {

    $sql =  "SELECT idcurso,nonbrecurso FROM curso_tuto where stadodocent ='PENDIENTE';";

    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

        array_push($cursos, $consulta_VU);
      }
      return $cursos;
      $this->conexion->cerrar();
    }
  }



  function Curso_general()
  {
    $sql = "SELECT idcursos,descripcion FROM cursos_tuto";
    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_array($consulta)) {
        $arreglo[] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }

  function Registrar_Docente($id_doce,  $fecha, $semestre, $id_doce_asig)
  {
    $sql = "INSERT INTO tutoria_docente_asignado (fecha, id_doce, id_rol, id_semestre, id_coordinador) VALUES ('$fecha','$id_doce','2','$semestre','$id_doce_asig')";


    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return "Error SQL: " . $this->conexion->conexion->error;
    }
  }

  function Extraer_contracenaDocent($idprofe)
  {
    $sql = "SELECT id_usuario,clave,foto FROM tutoria_usuario WHERE id_usuario='$idprofe'";
    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_array($consulta)) {

        $arreglo[] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }


  //NETAMENTE DE DOCENTES


  function listar_gradosdocent($idprofe)
  {
    $sql  = "select DISTINCT idgrado,gradonombre,cantidad_alum from docenteasignado_tuto
                  inner join  grado_tuto on grado.idgrado = docenteasignado.grado_id
                  where docenteid ='$idprofe'";

    /* $sql  = "select DISTINCT idgrado,gradonombre,count(grado_id) AS totalAlum from docenteasignado
                  inner join  grado on grado.idgrado = docenteasignado.grado_id
                  where docenteid ='$idprofe' group by idgrado";*/
    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
        $arreglo['data'][] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }
  function listar_gradoscursosdocent($idgrado, $iddoce)
  {

    $sql  = "select idcurso, nonbrecurso  from docenteasignado_tuto
                inner join  curso_tuto on curso.idcurso = docenteasignado.curso_id
                where grado_id ='$idgrado' and docenteid='$iddoce' ";
    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
        $arreglo['data'][] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }

  function ListaSesiones($id_sesiones_tuto)
  {
    $sql  = "SELECT s.tema, 
                        s.compromiso_indi as comp, 
                        s.tipo_sesion_id as tipo, 
                        s.observacione as obs, 
                        s.link, 
                        s.reunion_tipo_otros as otro, 
                        s.fecha, 
                        s.horaInicio as ini, 
                        s.horaFin as fin,
                        s.color
                 FROM tutoria_sesiones_tutoria_f78 as s
                 WHERE s.id_sesiones_tuto = '$id_sesiones_tuto'";

    $arreglo = array();

    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
        $arreglo[] = $consulta_VU;
      }

      return $arreglo;
      $this->conexion->cerrar();
    }
  }
  function ListaSesionesEstu($id_sesiones_tuto, $id_estu)
  {
    $sql  = "SELECT s.tema, 
                        s.compromiso_indi as comp, 
                        s.tipo_sesion_id as tipo, 
                        s.observacione as obs, 
                        s.link, 
                        s.reunion_tipo_otros as otro, 
                        s.fecha, 
                        s.horaInicio as ini, 
                        s.horaFin as fin,
                        t.des_tipo,
                        CONCAT(d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) as nombres,
                        de.valoracion_tuto as valo,
                        de.marcar_asis_estu as asis,
                        s.color,
                        de.comentario_estu as coment
                 FROM tutoria_sesiones_tutoria_f78 as s
                  INNER JOIN tutoria_tipo_sesion as t ON s.tipo_sesion_id = t.id_tipo_sesion
                  INNER JOIN docente as d ON d.id_doce = s.id_doce 
                  INNER JOIN tutoria_detalle_sesion as de ON de.sesiones_tutoria_id = s.id_sesiones_tuto
                 WHERE s.id_sesiones_tuto = '$id_sesiones_tuto' AND de.id_estu = '$id_estu'";

    $arreglo = array();

    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
        $arreglo[] = $consulta_VU;
      }

      return $arreglo;
      $this->conexion->cerrar();
    }
  }
  function ListaAsistenciaAlumnos($id_sesiones_tuto)
  {
    $sql  = "SELECT d.id_detalle_sesion as id, 
                    CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) as nombres,
                    d.marcar_asis_estu as asis
                FROM tutoria_detalle_sesion as d
                  INNER JOIN estudiante as e ON e.id_estu = d.id_estu
                WHERE d.sesiones_tutoria_id = '$id_sesiones_tuto'";

    $arreglo = array();

    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
        $arreglo[] = $consulta_VU;
      }

      return $arreglo;
      $this->conexion->cerrar();
    }
  }

  function ListarHoras_docent()
  {

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

  /* TUTOR DE AULA */

  function listar_historial_derivaciones_TA($id_doce)
  {
    $id_rol = 6;
    $sql =  "SELECT
            d.id_derivaciones as id_der,
            d.fecha,
            CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) as nombres,
            e.celu_estu as telefono,
            d.estado,
            a.des_area_apo as des_area,
            d.id_estudiante as id_estu
        FROM tutoria_derivacion_tutorado_f6 as d
                    INNER JOIN estudiante as e ON e.id_estu = d.id_estudiante
                    INNER JOIN tutoria_area_apoyo as a ON a.idarea_apoyo = d.area_apoyo_id
                  WHERE d.id_docente = '$id_doce' AND id_rol='$id_rol'";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

        $arreglo["data"][] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }


  function DerivarAlumno_TA($fecha, $hora, $motivo_referido, $area, $id_estu, $id_doce)
  {
    $sql = "INSERT INTO tutoria_derivacion_tutorado_f6 (fecha, hora,  motivo_ref, fechaDerivacion, estado, area_apoyo_id, id_estudiante, id_docente, id_rol) VALUES ('$fecha', '$hora', '$motivo_referido', '$fecha', 'Pendiente', '$area', '$id_estu', '$id_doce','6')";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return $consulta;
    }
  }
  function UpdateDerivadoApoyo_TA($id_der, $result_der)
  {
    $sql = "UPDATE tutoria_derivacion_tutorado_f6 SET estado = 'Atendido', resultado_contra = '$result_der' WHERE id_derivaciones = '$id_der'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return $consulta;
    }
  }
  //---
  function UpdateDerivado_TA($id_estu,  $id_asig, $area)
  {
    $sql = "UPDATE tutoria_asignacion_tutoria SET tipo_asignacion_id='3', id_apoyo='$area' WHERE id_asignacion = '$id_asig'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }

  function listar_historial_sesiones_TA($id_doce)
  {
    $sql =  "SELECT
            s.id_sesiones_tuto as id_tuto,
            s.fecha as fecha,
            CONCAT(s.horainicio, '-', s.horaFin) as horas,
            t.des_tipo as tipo, IF((SELECT COUNT(*) FROM tutoria_detalle_sesion d WHERE d.sesiones_tutoria_id = s.id_sesiones_tuto) >1, 2, 1) as idtipo,
            e.nom_estu as estudiante,
            s.tema
        FROM tutoria_sesiones_tutoria_f78 as s
        INNER JOIN tutoria_tipo_sesion as t ON t.id_tipo_sesion = s.tipo_sesion_id
                    INNER JOIN tutoria_detalle_sesion as d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
                    INNER JOIN tutoria_asignacion_tutoria as a ON a.id_asignacion = d.asignacion_id
                    INNER JOIN estudiante as e ON e.id_estu = a.id_estudiante
                  WHERE s.id_doce = '$id_doce' AND s.id_rol='6'
                  GROUP BY s.id_sesiones_tuto
                  ORDER BY s.id_sesiones_tuto DESC";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

        $arreglo["data"][] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }


  function RegistrarSessionTutoria_TA($iddocente, $inicio, $final, $tema, $compromiso, $tipo_session, $reu_otros, $link, $fecha)
  {
    $sql = "INSERT INTO tutoria_sesiones_tutoria_f78 (fecha, horainicio,  horaFin, tema, reunion_tipo_otros, link, compromiso_indi, color, tipo_sesion_id, id_doce, id_rol) VALUES ('$fecha', '$inicio', '$final', '$tema', '$reu_otros', '$link', '$compromiso', '#3c8dbc', '$tipo_session', '$iddocente','6')";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      $ultimoId = $this->conexion->conexion->insert_id;
      return $ultimoId;
    } else {
      return 0;
    }
  }
  function RegistrarDetalleSessionTutoria_TA($id_asignacion, $id_estudiante, $id_sesion)
  {
    $sql = "INSERT INTO tutoria_detalle_sesion (asignacion_id, sesiones_tutoria_id, marcar_asis_doce, marcar_asis_estu, valoracion_tuto, comentario_estu, id_estu) VALUES ('$id_asignacion', '$id_sesion', '1', '0', '0', '0', '$id_estudiante')";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }

  function ActualizarSessionTutoria_TA($sesi, $inic, $fina, $tema, $comp, $tipo, $deta, $link, $fech, $obse)
  {
    $sql = "UPDATE `tutoria_sesiones_tutoria_f78` SET 
                `fecha`='$fech',
                `horaInicio`='$inic',
                `horaFin`='$fina',
                `tema`='$tema',
                `observacione`='$obse',
                `reunion_tipo_otros`='$deta',
                `link`='$link',
                `compromiso_indi`='$comp',
                `color`= '#00a65a',
                `tipo_sesion_id`='$tipo'
              WHERE id_sesiones_tuto='$sesi'";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  }

  function listar_historial_sesiones($id_doce)
  {
    $sql =  "SELECT
            s.id_sesiones_tuto as id_tuto,
            s.fecha as fecha,
            CONCAT(s.horainicio, '-', s.horaFin) as horas,
            t.des_tipo as tipo, IF((SELECT COUNT(*) FROM tutoria_detalle_sesion d WHERE d.sesiones_tutoria_id = s.id_sesiones_tuto) >1, 2, 1) as idtipo,
            e.nom_estu as estudiante,
            s.tema
        FROM tutoria_sesiones_tutoria_f78 as s
        INNER JOIN tutoria_tipo_sesion as t ON t.id_tipo_sesion = s.tipo_sesion_id
                    INNER JOIN tutoria_detalle_sesion as d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
                    INNER JOIN tutoria_asignacion_tutoria as a ON a.id_asignacion = d.asignacion_id
                    INNER JOIN estudiante as e ON e.id_estu = a.id_estudiante
                  WHERE s.id_doce = '$id_doce' AND s.id_rol='2'
                  GROUP BY s.id_sesiones_tuto
                  ORDER BY s.id_sesiones_tuto DESC";

    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
      while ($consulta_VU = mysqli_fetch_assoc($consulta)) {

        $arreglo["data"][] = $consulta_VU;
      }
      return $arreglo;
      $this->conexion->cerrar();
    }
  }
}
