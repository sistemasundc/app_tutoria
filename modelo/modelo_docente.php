<?php
require_once(__DIR__ . '/../modelo/modelo_docente.php');
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
//------------------------MARCAR ASISTENCIA ESTUDIANTES POR AULA ----------------------------
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
  //-----------------------------MARCAR ASISTENCIA ESTUDIANTES POR CURSO-----------
  function VerificarDelCalendario_TC($id_sesion)
  {
    $sql = "SELECT marcar_asis_estu FROM tutoria_detalle_sesion_curso WHERE sesiones_tutoria_id='$id_sesion' AND marcar_asis_estu = 1";

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
//---------------------------------------------------------------
// -----------TUTOR DE CURSO -------ASISTENCIA Y FORMATO--------------------
        function DatosFormatoAsistenciaCurso($id_sesion, $semestre) {
           $sql = "SELECT 
                CONCAT('Escuela Profesional de: ', c.nom_car),
                (SELECT CONCAT('Número de estudiantes asistentes: ', COUNT(id_estu))
                FROM tutoria_detalle_sesion_curso
                WHERE sesiones_tutoria_id = s.id_sesiones_tuto),
                CONCAT('Ciclo académico: ', cl.ciclo, '        Turno: ', 
                    CASE WHEN cl.turno = 'MANANA' THEN 'MAÑANA' ELSE cl.turno END),
                CONCAT('Tutor: ', d.apepa_doce, ' ', d.apema_doce,' ', d.nom_doce),
                d.email_doce,
                CONCAT('Semestre académico: $semestre'),
                CONCAT('Fecha de reunión: ', s.fecha, '     Hora: ', s.horaInicio, ' a ', s.horaFin),
                'Forma de Consejería y Tutoría Académica',
                s.tipo_sesion_id,
                s.reunion_tipo_otros,
                s.tema,
                s.compromiso_indi,
                2 AS idtipo
            FROM tutoria_sesiones_tutoria_f78 s
            INNER JOIN docente d ON s.id_doce = d.id_doce
            LEFT JOIN carga_lectiva cl ON cl.id_doce = d.id_doce AND cl.id_semestre = $semestre
            INNER JOIN carrera c ON d.id_car = c.id_car
            WHERE s.id_sesiones_tuto = '$id_sesion'
            LIMIT 1";
            
            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($fila = mysqli_fetch_array($consulta, MYSQLI_NUM)) {
                    $arreglo[] = $fila;
                }
            }
            return $arreglo;
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
/* function listar_alumnos_asignados($id_doc, $semestre)
{
    $sql = "SELECT
        a.id_asignacion AS id_asig,
        e.id_estu AS id_estu,
        CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS nombres,
        e.celu_estu AS telefono,
        f.ciclo_ficham AS des_ciclo,

        (
          SELECT ROUND(AVG(nota_final), 1) FROM (
              SELECT a1.ntp1_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp2_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.exa_par FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp3_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp4_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.exa_final FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
          ) AS notas(nota_final) 
          WHERE nota_final IS NOT NULL
        ) AS promedio_general,

        (
          SELECT CASE
            WHEN ROUND(AVG(nota_final), 1) <= 8 THEN 'Bajo'
            WHEN ROUND(AVG(nota_final), 1) BETWEEN 9 AND 14 THEN 'Regular'
            ELSE 'Alto'
          END
          FROM (
              SELECT a1.ntp1_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp2_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.exa_par FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp3_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp4_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.exa_final FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
          ) AS notas(nota_final)
          WHERE nota_final IS NOT NULL
        ) AS rendimiento,

        (
          SELECT COUNT(*) FROM (
              SELECT a1.ntp1_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp2_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.exa_par FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp3_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp4_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.exa_final FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
          ) AS todas(nota_total)
          WHERE nota_total IS NOT NULL
        ) AS total_notas,

        (
          SELECT COUNT(*) FROM (
              SELECT a1.ntp1_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp2_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.exa_par FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp3_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.ntp4_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              UNION ALL SELECT a1.exa_final FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
          ) AS desap(nota_desap)
          WHERE nota_desap IS NOT NULL AND nota_desap <= 10
        ) AS notas_desaprobadas,

        e.email_estu AS cor_inst,

        CASE 
            WHEN (
              SELECT ROUND(AVG(nota_final), 1) FROM (
                  SELECT a1.ntp1_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
                  UNION ALL SELECT a1.ntp2_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
                  UNION ALL SELECT a1.exa_par FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
                  UNION ALL SELECT a1.ntp3_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
                  UNION ALL SELECT a1.ntp4_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
                  UNION ALL SELECT a1.exa_final FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              ) AS notas(nota_final)
              WHERE nota_final IS NOT NULL
            ) <= 8 THEN 1 ELSE 2
        END AS id_tipo,

        CASE 
            WHEN (
              SELECT ROUND(AVG(nota_final), 1) FROM (
                  SELECT a1.ntp1_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
                  UNION ALL SELECT a1.ntp2_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
                  UNION ALL SELECT a1.exa_par FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
                  UNION ALL SELECT a1.ntp3_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
                  UNION ALL SELECT a1.ntp4_per FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
                  UNION ALL SELECT a1.exa_final FROM asignacion_estudiante a1 WHERE a1.id_estu = e.id_estu AND a1.id_semestre = f.id_semestre
              ) AS notas(nota_final)
              WHERE nota_final IS NOT NULL
            ) <= 8 THEN 'INDIVIDUAL'
            ELSE 'GRUPAL'
        END AS tipo

    FROM tutoria_asignacion_tutoria a
    INNER JOIN estudiante e ON e.id_estu = a.id_estudiante
    INNER JOIN ficha_matricula f ON f.id_estu = e.id_estu
    INNER JOIN asignacion_estudiante ae ON ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
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
} */
/* function listar_alumnos_asignados($id_doc, $semestre) {
    $sql = "
    SELECT
        a.id_asignacion AS id_asig,
        e.id_estu AS id_estu,
        CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS nombres,
        e.celu_estu AS telefono,
        f.ciclo_ficham AS des_ciclo,

        
        (
          SELECT ROUND(
            SUM( IFNULL(ae.ntp1_per,0) + IFNULL(ae.ntp2_per,0) + IFNULL(ae.exa_par,0)
               + IFNULL(ae.ntp3_per,0) + IFNULL(ae.ntp4_per,0) + IFNULL(ae.exa_final,0) )
            / NULLIF( SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                         + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) ), 0)
          , 1)
          FROM asignacion_estudiante ae
          WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
        ) AS promedio_general,

       
        (
          SELECT CASE
            WHEN ROUND(
              SUM( IFNULL(ae.ntp1_per,0) + IFNULL(ae.ntp2_per,0) + IFNULL(ae.exa_par,0)
                 + IFNULL(ae.ntp3_per,0) + IFNULL(ae.ntp4_per,0) + IFNULL(ae.exa_final,0) )
              / NULLIF( SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                           + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) ), 0)
            , 1) <= 8  THEN 'Bajo'
            WHEN ROUND(
              SUM( IFNULL(ae.ntp1_per,0) + IFNULL(ae.ntp2_per,0) + IFNULL(ae.exa_par,0)
                 + IFNULL(ae.ntp3_per,0) + IFNULL(ae.ntp4_per,0) + IFNULL(ae.exa_final,0) )
              / NULLIF( SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                           + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) ), 0)
            , 1) BETWEEN 9 AND 14 THEN 'Regular'
            ELSE 'Alto'
          END
          FROM asignacion_estudiante ae
          WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
        ) AS rendimiento,

        
        (
          SELECT SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                    + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) )
          FROM asignacion_estudiante ae
          WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
        ) AS total_notas,

   
        (
          SELECT SUM( (ae.ntp1_per  IS NOT NULL AND ae.ntp1_per  <= 10)
                    + (ae.ntp2_per  IS NOT NULL AND ae.ntp2_per  <= 10)
                    + (ae.exa_par   IS NOT NULL AND ae.exa_par   <= 10)
                    + (ae.ntp3_per  IS NOT NULL AND ae.ntp3_per  <= 10)
                    + (ae.ntp4_per  IS NOT NULL AND ae.ntp4_per  <= 10)
                    + (ae.exa_final IS NOT NULL AND ae.exa_final <= 10) )
          FROM asignacion_estudiante ae
          WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
        ) AS notas_desaprobadas,

        e.email_estu AS cor_inst,

       
        CASE
          WHEN (
            SELECT ROUND(
              SUM( IFNULL(ae.ntp1_per,0) + IFNULL(ae.ntp2_per,0) + IFNULL(ae.exa_par,0)
                 + IFNULL(ae.ntp3_per,0) + IFNULL(ae.ntp4_per,0) + IFNULL(ae.exa_final,0) )
              / NULLIF( SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                           + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) ), 0)
            ,1)
            FROM asignacion_estudiante ae
            WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
          ) <= 8 THEN 1 ELSE 2
        END AS id_tipo,

        CASE
          WHEN (
            SELECT ROUND(
              SUM( IFNULL(ae.ntp1_per,0) + IFNULL(ae.ntp2_per,0) + IFNULL(ae.exa_par,0)
                 + IFNULL(ae.ntp3_per,0) + IFNULL(ae.ntp4_per,0) + IFNULL(ae.exa_final,0) )
              / NULLIF( SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                           + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) ), 0)
            ,1)
            FROM asignacion_estudiante ae
            WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
          ) <= 8 THEN 'INDIVIDUAL' ELSE 'GRUPAL'
        END AS tipo

    FROM tutoria_asignacion_tutoria a
    INNER JOIN estudiante e   ON e.id_estu = a.id_estudiante
    INNER JOIN ficha_matricula f
           ON f.id_estu = e.id_estu
          AND f.id_semestre = ?
          AND f.borrado <> '1'
    WHERE a.id_docente = ?
      AND a.id_semestre = ?
    GROUP BY a.id_asignacion
      ORDER BY 
      id_tipo ASC, 
      rendimiento DESC, 
      e.apepa_estu ASC";

    $stmt = $this->conexion->conexion->prepare($sql);
    if (!$stmt) { die('Error en prepare(): ' . $this->conexion->conexion->error); }

    
    $stmt->bind_param('iii', $semestre, $id_doc, $semestre);

    $stmt->execute();
    return $stmt->get_result();
} */
public function listar_alumnos_asignados($id_doc, $semestre, $id_carga = null) {

  $whereExtra = "";
  if ($id_carga !== null) {
    $whereExtra = " AND a.id_carga = ? ";
  }

  $sql = "
    SELECT
      a.id_asignacion AS id_asig,
      e.id_estu AS id_estu,
      CONCAT(e.apepa_estu,' ',e.apema_estu,' ',e.nom_estu) AS nombres,
      e.celu_estu AS telefono,
      f.ciclo_ficham AS des_ciclo,

      /* ======= tus subconsultas de promedio / rendimiento / totales ======= */
      (
        SELECT ROUND(
          SUM( IFNULL(ae.ntp1_per,0) + IFNULL(ae.ntp2_per,0) + IFNULL(ae.exa_par,0)
             + IFNULL(ae.ntp3_per,0) + IFNULL(ae.ntp4_per,0) + IFNULL(ae.exa_final,0) )
          / NULLIF( SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                       + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) ), 0)
        , 1)
        FROM asignacion_estudiante ae
        WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
      ) AS promedio_general,

      (
        SELECT CASE
          WHEN ROUND(
            SUM( IFNULL(ae.ntp1_per,0) + IFNULL(ae.ntp2_per,0) + IFNULL(ae.exa_par,0)
               + IFNULL(ae.ntp3_per,0) + IFNULL(ae.ntp4_per,0) + IFNULL(ae.exa_final,0) )
            / NULLIF( SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                         + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) ), 0)
          , 1) <= 8  THEN 'Bajo'
          WHEN ROUND(
            SUM( IFNULL(ae.ntp1_per,0) + IFNULL(ae.ntp2_per,0) + IFNULL(ae.exa_par,0)
               + IFNULL(ae.ntp3_per,0) + IFNULL(ae.ntp4_per,0) + IFNULL(ae.exa_final,0) )
            / NULLIF( SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                         + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) ), 0)
          , 1) BETWEEN 9 AND 14 THEN 'Regular'
          ELSE 'Alto'
        END
        FROM asignacion_estudiante ae
        WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
      ) AS rendimiento,

      (
        SELECT SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                  + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) )
        FROM asignacion_estudiante ae
        WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
      ) AS total_notas,

      (
        SELECT SUM( (ae.ntp1_per  IS NOT NULL AND ae.ntp1_per  <= 10)
                  + (ae.ntp2_per  IS NOT NULL AND ae.ntp2_per  <= 10)
                  + (ae.exa_par   IS NOT NULL AND ae.exa_par   <= 10)
                  + (ae.ntp3_per  IS NOT NULL AND ae.ntp3_per  <= 10)
                  + (ae.ntp4_per  IS NOT NULL AND ae.ntp4_per  <= 10)
                  + (ae.exa_final IS NOT NULL AND ae.exa_final <= 10) )
        FROM asignacion_estudiante ae
        WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
      ) AS notas_desaprobadas,

      e.email_estu AS cor_inst,

      CASE
        WHEN (
          SELECT ROUND(
            SUM( IFNULL(ae.ntp1_per,0) + IFNULL(ae.ntp2_per,0) + IFNULL(ae.exa_par,0)
               + IFNULL(ae.ntp3_per,0) + IFNULL(ae.ntp4_per,0) + IFNULL(ae.exa_final,0) )
            / NULLIF( SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                         + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) ), 0)
          ,1)
          FROM asignacion_estudiante ae
          WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
        ) <= 8 THEN 1 ELSE 2 END AS id_tipo,

      CASE
        WHEN (
          SELECT ROUND(
            SUM( IFNULL(ae.ntp1_per,0) + IFNULL(ae.ntp2_per,0) + IFNULL(ae.exa_par,0)
               + IFNULL(ae.ntp3_per,0) + IFNULL(ae.ntp4_per,0) + IFNULL(ae.exa_final,0) )
            / NULLIF( SUM( (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL)
                         + (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL) ), 0)
          ,1)
          FROM asignacion_estudiante ae
          WHERE ae.id_estu = e.id_estu AND ae.id_semestre = f.id_semestre
        ) <= 8 THEN 'INDIVIDUAL' ELSE 'GRUPAL' END AS tipo,

      a.id_carga
    FROM tutoria_asignacion_tutoria a
    INNER JOIN estudiante e   ON e.id_estu = a.id_estudiante
    INNER JOIN ficha_matricula f
            ON f.id_estu = e.id_estu
           AND f.id_semestre = ?
           AND f.borrado <> '1'
    WHERE a.id_docente = ?
      AND a.id_semestre = ?
      $whereExtra
    GROUP BY a.id_asignacion
    ORDER BY id_tipo ASC, rendimiento DESC, e.apepa_estu ASC";

  $stmt = $this->conexion->conexion->prepare($sql);
  if(!$stmt){ die('Error en prepare(): '.$this->conexion->conexion->error); }

  if ($id_carga === null) {
    $stmt->bind_param('iii', $semestre, $id_doc, $semestre);
  } else {
    $stmt->bind_param('iiii', $semestre, $id_doc, $semestre, $id_carga);
  }

  $stmt->execute();
  return $stmt->get_result();
}

//================== FILTRO POR AULA SI TIENE MAS DE UN AULA ASIGNADA ======
public function aulas_del_docente_TA($id_doc, $id_semestre){
  $sql = "SELECT DISTINCT
            cl.ciclo,
            cl.turno,
            cl.seccion,
            cl.id_cargalectiva AS id_carga
          FROM tutoria_asignacion_tutoria ta
          JOIN carga_lectiva cl ON cl.id_cargalectiva = ta.id_carga
          WHERE ta.id_docente = ? AND ta.id_semestre = ?
          ORDER BY
            FIELD(cl.ciclo,'I','II','III','IV','V','VI','VII','VIII','IX','X'),
            FIELD(cl.turno,'MAÑANA','TARDE','NOCHE'),
            cl.seccion";
  $st = $this->conexion->conexion->prepare($sql);
  $st->bind_param('ii', $id_doc, $id_semestre);
  $st->execute();
  return $st->get_result(); // ciclo, turno, seccion, id_carga
}
//  ---------PORCENTAJE DE ASITENCIAS SEMAFORO TUTOR AULA
public function porcentajes_asistencia_TA($id_docente, $id_semestre) {
    $sql = "SELECT ta.id_estudiante AS id_estu, MAX(faltas.total_falta) AS max_falta
            FROM tutoria_asignacion_tutoria ta
            INNER JOIN asignacion_estudiante ae ON ta.id_estudiante = ae.id_estu
            INNER JOIN (
                SELECT id_aestu, SUM(porcentaje) AS total_falta
                FROM asistencias
                WHERE anulado = 0 AND UPPER(condicion) = 'F'
                GROUP BY id_aestu
            ) AS faltas ON faltas.id_aestu = ae.id_aestu
            WHERE ta.id_docente = ? AND ae.id_semestre = ?
            GROUP BY ta.id_estudiante";

    $stmt = $this->conexion->conexion->prepare($sql);
    $stmt->bind_param("ii", $id_docente, $id_semestre);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $porcentajes = [];
    while ($fila = $resultado->fetch_assoc()) {
        $porcentajes[$fila['id_estu']] = floatval($fila['max_falta']);
    }

    return $porcentajes;
    //asegurar que todos los estudiantes del docente estén en el array
    $sqlTodos = "SELECT DISTINCT ae.id_estu 
                FROM tutoria_asignacion_tutoria ta 
                INNER JOIN asignacion_estudiante ae ON ta.id_estudiante = ae.id_estu 
                WHERE ta.id_docente = ? AND ae.id_semestre = ?";

    $stmtTodos = $this->conexion->prepare($sqlTodos);
    $stmtTodos->bind_param("ii", $id_doc, $id_semestre);
    $stmtTodos->execute();
    $resultTodos = $stmtTodos->get_result();

    while ($row = $resultTodos->fetch_assoc()) {
        $id = $row['id_estu'];
        if (!isset($faltas_estudiantes[$id])) {
            $faltas_estudiantes[$id] = 0; // Asignar 0% si no tiene faltas registradas
        }
    }
}

// ---------- LISTAR ESTUDIANTES DOCENTE TUTOR POR ASIGNATURA (con semáforo y contadores) ----------
public function listar_alumnos_x_asignatura($id_doc, $id_cargalectiva) {
    $conexion = new conexion();
    $conexion->conectar();

    $sql = "SELECT 
                ae.id_aestu AS id_asig,
                ae.id_estu AS id_estu,
                CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS nombres,
                e.celu_estu AS telefono,
                f.ciclo_ficham AS des_ciclo,
                f.turno_ficham AS turno,
                f.seccion_ficham AS seccion,
                e.email_estu AS cor_inst,
                d.activo,
                d.situacion,

                /* ---- promedio general del curso (solo notas no nulas) ---- */
                ROUND(
                    (
                        COALESCE(ae.ntp1_per,0) + COALESCE(ae.ntp2_per,0) + COALESCE(ae.exa_par,0) +
                        COALESCE(ae.ntp3_per,0) + COALESCE(ae.ntp4_per,0) + COALESCE(ae.exa_final,0)
                    )
                    / NULLIF(
                        (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL) +
                        (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL)
                    , 0)
                , 1) AS promedio_general,

                /* ---- etiquetas Bajo / Regular / Alto según promedio ---- */
                CASE
                    WHEN ROUND((
                        COALESCE(ae.ntp1_per,0) + COALESCE(ae.ntp2_per,0) + COALESCE(ae.exa_par,0) +
                        COALESCE(ae.ntp3_per,0) + COALESCE(ae.ntp4_per,0) + COALESCE(ae.exa_final,0)
                    ) / NULLIF(
                        (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL) +
                        (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL)
                    ,0), 1) <= 8 THEN 'Bajo'
                    WHEN ROUND((
                        COALESCE(ae.ntp1_per,0) + COALESCE(ae.ntp2_per,0) + COALESCE(ae.exa_par,0) +
                        COALESCE(ae.ntp3_per,0) + COALESCE(ae.ntp4_per,0) + COALESCE(ae.exa_final,0)
                    ) / NULLIF(
                        (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL) +
                        (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL)
                    ,0), 1) BETWEEN 9 AND 14 THEN 'Regular'
                    ELSE 'Alto'
                END AS rendimiento,

                /* ---- contadores para el texto 'X de Y' ---- */
                (
                    (ae.ntp1_per IS NOT NULL) + (ae.ntp2_per IS NOT NULL) + (ae.exa_par IS NOT NULL) +
                    (ae.ntp3_per IS NOT NULL) + (ae.ntp4_per IS NOT NULL) + (ae.exa_final IS NOT NULL)
                ) AS total_notas,

                (
                    (ae.ntp1_per IS NOT NULL AND ae.ntp1_per <= 10) +
                    (ae.ntp2_per IS NOT NULL AND ae.ntp2_per <= 10) +
                    (ae.exa_par  IS NOT NULL AND ae.exa_par  <= 10) +
                    (ae.ntp3_per IS NOT NULL AND ae.ntp3_per <= 10) +
                    (ae.ntp4_per IS NOT NULL AND ae.ntp4_per <= 10) +
                    (ae.exa_final IS NOT NULL AND ae.exa_final <= 10)
                ) AS notas_desaprobadas,

                /* ---- lógica de tipo (como ya usabas) ---- */
                CASE 
                  WHEN (COALESCE(ae.ntp1_per, 20) <= 10 OR COALESCE(ae.ntp2_per, 20) <= 10 OR
                        COALESCE(ae.ntp3_per, 20) <= 10 OR COALESCE(ae.ntp4_per, 20) <= 10 OR
                        COALESCE(ae.pf_per,   20) <= 10 OR COALESCE(ae.exa_par,   20) <= 10 OR
                        COALESCE(ae.exa_final,20) <= 10) 
                  THEN 1 ELSE 2 
                END AS id_tipo,

                CASE 
                  WHEN (COALESCE(ae.ntp1_per, 20) <= 10 OR COALESCE(ae.ntp2_per, 20) <= 10 OR
                        COALESCE(ae.ntp3_per, 20) <= 10 OR COALESCE(ae.ntp4_per, 20) <= 10 OR
                        COALESCE(ae.pf_per,   20) <= 10 OR COALESCE(ae.exa_par,   20) <= 10 OR
                        COALESCE(ae.exa_final,20) <= 10) 
                  THEN 'INDIVIDUAL' ELSE 'GRUPAL' 
                END AS tipo

            FROM asignacion_estudiante ae
            INNER JOIN estudiante e    ON e.id_estu = ae.id_estu
            INNER JOIN ficha_matricula f ON f.id_estu = ae.id_estu AND f.id_semestre = 33   /* <--- ojo: si quieres, sustituye 32 por el semestre en sesión */
            INNER JOIN detestudiante d ON d.id_estu = ae.id_estu
            WHERE ae.id_cargalectiva = ?
              AND ae.borrado = 0
            GROUP BY ae.id_estu
            ORDER BY nombres ASC";

    $stmt = $conexion->conexion->prepare($sql);
    if (!$stmt) {
        die('Error en prepare(): ' . $conexion->conexion->error);
    }
    $stmt->bind_param("i", $id_cargalectiva);
    $stmt->execute();
    return $stmt->get_result();
}


// ---------- PORCENTAJE DE ASISTENCIAS - TUTOR DE CURSO ----------
public function porcentajes_asistencia_TC($id_cargalectiva) {
    $sql = "SELECT 
                ae.id_estu                                    AS id_estu,
                ROUND(SUM(a.porcentaje), 2)                   AS total_falta
            FROM asignacion_estudiante ae
            LEFT JOIN asistencias a 
                   ON a.id_aestu = ae.id_aestu
                  AND a.anulado = 0
                  AND UPPER(a.condicion) = 'F'
            WHERE ae.id_cargalectiva = ?
              AND ae.borrado = 0
            GROUP BY ae.id_estu";

    $stmt = $this->conexion->conexion->prepare($sql);
    $stmt->bind_param("i", $id_cargalectiva);
    $stmt->execute();
    $res = $stmt->get_result();

    $porcentajes = [];
    while ($row = $res->fetch_assoc()) {
        // si no tiene faltas, SUM será NULL; colócalo en 0
        $porcentajes[$row['id_estu']] = $row['total_falta'] !== null ? floatval($row['total_falta']) : 0.0;
    }

    // asegurar 0% para quienes no aparecen en asistencias
    $sqlTodos = "SELECT DISTINCT ae.id_estu 
                 FROM asignacion_estudiante ae
                 WHERE ae.id_cargalectiva = ? AND ae.borrado = 0";
    $stmt2 = $this->conexion->conexion->prepare($sqlTodos);
    $stmt2->bind_param("i", $id_cargalectiva);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($r = $res2->fetch_assoc()) {
        if (!isset($porcentajes[$r['id_estu']])) {
            $porcentajes[$r['id_estu']] = 0.0;
        }
    }

    return $porcentajes;
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
  public function RegistrarSessionTutoria($id_docente, $inicio, $final, $tema, $compromiso, $tipo_session, $reu_otros, $link, $fecha, $id_cargalectiva, $evidencia1 = null, $evidencia2 = null) {
    $color = '#3c8dbc';
    if ($tipo_session == 4) $color = '#00c0ef';
    if ($tipo_session == 5) $color = '#605ca8';

    $rol = 2; // Tutor de Curso
    $observacione = ''; // en este momento no se usa

    $sql = "INSERT INTO tutoria_sesiones_tutoria_f78 
            (fecha, horaInicio, horaFin, tema, observacione, reunion_tipo_otros, link, compromiso_indi, color, tipo_sesion_id, id_doce, id_rol, id_carga, evidencia_1, evidencia_2) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->conexion->conexion->prepare($sql);
    if (!$stmt) {
        error_log("ERROR PREPARANDO SQL: " . $this->conexion->conexion->error);
        return 0;
    }

    $stmt->bind_param("ssssssssiiiiiss",
        $fecha,         // 1
        $inicio,        // 2
        $final,         // 3
        $tema,          // 4
        $observacione,  // 5
        $reu_otros,     // 6
        $link,          // 7
        $compromiso,    // 8
        $color,         // 9
        $tipo_session,  // 10
        $id_docente,    // 11
        $rol,           // 12
        $id_cargalectiva, // 13
        $evidencia1,    // 14
        $evidencia2     // 15
    );

    if (!$stmt->execute()) {
        error_log("ERROR EJECUTANDO SQL: " . $stmt->error);
        $stmt->close();
        return 0;
    }

    $id_insertado = $stmt->insert_id;
    $stmt->close();
    return $id_insertado;
}

/* public function RegistrarSessionTutoria($id_docente, $inicio, $final, $tema, $compromiso, $tipo_session, $reu_otros, $link, $fecha, $id_cargalectiva) {
    $color = '#3c8dbc';
    if ($tipo_session == 4) $color = '#00c0ef';
    if ($tipo_session == 5) $color = '#605ca8';

    // Incluir observacione en el INSERT
    $sql = "INSERT INTO tutoria_sesiones_tutoria_f78 
            (fecha, horaInicio, horaFin, tema, observacione, reunion_tipo_otros, link, compromiso_indi, color, tipo_sesion_id, id_doce, id_rol, id_carga) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->conexion->conexion->prepare($sql);
    if (!$stmt) {
        error_log("ERROR PREPARANDO SQL: " . $this->conexion->conexion->error);
        return 0;
    }

    $rol = 2; // Tutor de Curso
    $observacione = ''; // puedes luego hacer que se envíe si lo necesitas

    $stmt->bind_param("ssssssssiiiii",
        $fecha,
        $inicio,
        $final,
        $tema,
        $observacione,
        $reu_otros,
        $link,
        $compromiso,
        $color,
        $tipo_session,
        $id_docente,
        $rol,
        $id_cargalectiva
    );

    if (!$stmt->execute()) {
        error_log(" ERROR EJECUTANDO SQL: " . $stmt->error);
        $stmt->close();
        return 0;
    }

    $id_insertado = $stmt->insert_id;
    $stmt->close();
    return $id_insertado;
}
 */



/*   public function RegistrarSessionTutoria($id_doce, $horaInicio, $horaFin, $tema, $compromiso, $tipoSesion, $detalleSesion, $link, $fecha, $id_cargalectiva) {
      $sql = "INSERT INTO tutoria_sesiones_tutoria_f78
              (id_doce, fecha, horaInicio, horaFin, tema, observacion, reunion_tipo_otros, link, compromiso_indi, color, tipo_session_id, id_cargalectiva, id_rol)
              VALUES
              ('$id_doce', '$fecha', '$horaInicio', '$horaFin', '$tema', '', '$detalleSesion', '$link', '$compromiso', '#00a65a', '$tipoSesion', '$id_cargalectiva', 2)";
      
      if ($this->conexion->conexion->query($sql)) {
          return $this->conexion->conexion->insert_id;
      } else {
          // LOG DETALLE DEL ERROR PARA DEPURAR:
          error_log("ERROR SQL: " . $this->conexion->conexion->error);
          return 0;
      }
  } */
  /* 30_05 function RegistrarSessionTutoria($iddocente, $inicio, $final, $tema, $compromiso, $tipo_session, $reu_otros, $link, $fecha) 
{
    $sql = "INSERT INTO tutoria_sesiones_tutoria_f78 
        (fecha, horaInicio, horaFin, tema, reunion_tipo_otros, link, compromiso_indi, color, tipo_sesion_id, id_doce, id_rol)
        VALUES (?, ?, ?, ?, ?, ?, ?, '#3c8dbc', ?, ?, '2')";

    $stmt = $this->conexion->conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssssssii", $fecha, $inicio, $final, $tema, $reu_otros, $link, $compromiso, $tipo_session, $iddocente);
        if ($stmt->execute()) {
            $ultimoId = $stmt->insert_id;
            $stmt->close();
            return $ultimoId;
        } else {
            error_log(" Error al ejecutar RegistrarSessionTutoria: " . $stmt->error);
            $stmt->close();
        }
    } else {
        error_log(" Error al preparar RegistrarSessionTutoria: " . $this->conexion->conexion->error);
    }
    return 0;
} */
  /* function RegistrarSessionTutoria($iddocente, $inicio, $final, $tema, $compromiso, $tipo_session, $reu_otros, $link, $fecha)
  {

    $sql = "INSERT INTO tutoria_sesiones_tutoria_f78 (fecha, horaInicio,  horaFin, tema, reunion_tipo_otros, link, compromiso_indi, color, tipo_sesion_id, id_doce, id_rol) VALUES ('$fecha', '$inicio', '$final', '$tema', '$reu_otros', '$link', '$compromiso', '#3c8dbc', '$tipo_session', '$iddocente','2')";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      $ultimoId = $this->conexion->conexion->insert_id;
      return $ultimoId;
    } else {
      return 0;
    }
  } */
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

public function RegistrarDetalleSessionTutoria($id_cargalectiva, $id_sesion, $id_estudiante) {
    $valoracion = 0; // por defecto
    $comentario = 0; // por defecto

    $sql = "INSERT INTO tutoria_detalle_sesion_curso 
            (id_cargalectiva, sesiones_tutoria_id, marcar_asis_doce, marcar_asis_estu, valoracion_tuto, comentario_estu, id_estu) 
            VALUES (?, ?, 1, 0, ?, ?, ?)";

    $stmt = $this->conexion->conexion->prepare($sql);
    if (!$stmt) {
        error_log("ERROR PREPARANDO detalle: " . $this->conexion->conexion->error);
        return 0;
    }

    $stmt->bind_param("iiisi", $id_cargalectiva, $id_sesion, $valoracion, $comentario, $id_estudiante);

    if (!$stmt->execute()) {
        error_log("ERROR detalle EXECUTE: " . $stmt->error);
        $stmt->close();
        return 0;
    }

    $stmt->close();
    return 1;
}
//----------------------------TUROR DE CURSO--------------------------------
  public function ActualizarSessionTutoria($sesi, $inic, $fina, $tema, $comp, $tipo, $deta, $link, $fech, $obse, $evidencia1 = null, $evidencia2 = null) {
      $sqlVerificar = "SELECT COUNT(*) AS total FROM tutoria_detalle_sesion_curso 
                      WHERE sesiones_tutoria_id = ? AND marcar_asis_estu = 1";
      $stmtVer = $this->conexion->conexion->prepare($sqlVerificar);
      $stmtVer->bind_param("i", $sesi);
      $stmtVer->execute();
      $res = $stmtVer->get_result()->fetch_assoc();
      $stmtVer->close();

      $color = ($res['total'] > 0) ? '#00a65a' : '#3c8dbc';

      if (!$fech || !$inic || !$fina || !$tema || !$sesi) {
          return json_encode([
              "status" => "error",
              "message" => "Faltan campos requeridos."
          ]);
      }

      // Armado de campos SQL dinámicamente
      $campos = "fecha = ?, horaInicio = ?, horaFin = ?, tema = ?, observacione = ?, 
                reunion_tipo_otros = ?, link = ?, compromiso_indi = ?, color = ?, tipo_sesion_id = ?";
      $params = [$fech, $inic, $fina, $tema, $obse, $deta, $link, $comp, $color, $tipo];
      $types = "sssssssssi";

      if ($evidencia1 !== null) {
          $campos .= ", evidencia_1 = ?";
          $params[] = $evidencia1;
          $types .= "s";
      }

      if ($evidencia2 !== null) {
          $campos .= ", evidencia_2 = ?";
          $params[] = $evidencia2;
          $types .= "s";
      }

      $params[] = $sesi;
      $types .= "i";

      $sql = "UPDATE tutoria_sesiones_tutoria_f78 SET $campos WHERE id_sesiones_tuto = ?";
      $stmt = $this->conexion->conexion->prepare($sql);

      if (!$stmt) {
          return json_encode([
              "status" => "error",
              "message" => "Error al preparar: " . $this->conexion->conexion->error
          ]);
      }

      $stmt->bind_param($types, ...$params);

      if (!$stmt->execute()) {
          return json_encode([
              "status" => "error",
              "message" => "Error al ejecutar: " . $stmt->error
          ]);
      }

      return $sesi;
  }
/* public function ActualizarSessionTutoria($sesi, $inic, $fina, $tema, $comp, $tipo, $deta, $link, $fech, $obse) {
    $sqlVerificar = "SELECT COUNT(*) AS total FROM tutoria_detalle_sesion_curso 
                     WHERE sesiones_tutoria_id = ? AND marcar_asis_estu = 1";
    $stmtVer = $this->conexion->conexion->prepare($sqlVerificar);
    $stmtVer->bind_param("i", $sesi);
    $stmtVer->execute();
    $res = $stmtVer->get_result()->fetch_assoc();
    $stmtVer->close();

    $color = ($res['total'] > 0) ? '#00a65a' : '#3c8dbc';

    // Validación antes de ejecutar
    if (!$fech || !$inic || !$fina || !$tema || !$sesi) {
        echo json_encode([
            "status" => "error",
            "message" => "Campos vacíos detectados en el UPDATE. Verifica los datos."
        ]);
        return 0;
    }
  $sql = "UPDATE tutoria_sesiones_tutoria_f78 SET 
          fecha = ?, horaInicio = ?, horaFin = ?, tema = ?, observacione = ?, 
          reunion_tipo_otros = ?, link = ?, compromiso_indi = ?, 
          color = ?, tipo_sesion_id = ?
          WHERE id_sesiones_tuto = ?";

    $stmt = $this->conexion->conexion->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Error en PREPARE SQL: " . $this->conexion->conexion->error
        ]);
        return 0;
    }

    $stmt->bind_param("ssssssssssi", $fech, $inic, $fina, $tema, $obse, $deta, $link, $comp, $color, $tipo, $sesi);

    if (!$stmt->execute()) {
        echo json_encode([
            "status" => "error",
            "message" => "Error en EXECUTE: " . $stmt->error
        ]);
        return 0;
    }

    return $sesi;
}
 */

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
  //-------------------CURSO-----------------------------------------------------
 /* public function RegistrarAsistenciaTutoria_TC($id_estudiante, $id_sesion, $id_cargalectiva)
  {
      $sql_asig = "SELECT id_asignacion FROM tutoria_asignacion_curso 
                  WHERE id_estudiante = '$id_estudiante' AND id_carga = '$id_cargalectiva' LIMIT 1";
      $asignacion_id = 0;

      $res_asig = $this->conexion->conexion->query($sql_asig);
      if ($fila = mysqli_fetch_assoc($res_asig)) {
          $asignacion_id = $fila['id_asignacion'];
      }

      $sql_insert = "INSERT INTO tutoria_detalle_sesion_curso (asignacion_id, sesiones_tutoria_id, marcar_asis_docente, marcar_asis_estu, id_estu)
                    VALUES ('$asignacion_id', '$id_sesion', 1, 1, '$id_estudiante')";

      if ($this->conexion->conexion->query($sql_insert)) {
          return 1;
      } else {
          return 0;
      }
  } */
  /* function AsistenciasGuardadas_TC($inicio, $final, $iddoce, $fecha) {
      $sql = "SELECT 
                  s.tema, 
                  s.compromiso_indi as compromiso, 
                  s.observacione, 
                  s.tipo_sesion_id as tipo_session, 
                  s.reunion_tipo_otros as otros, 
                  d.asignacion_id as id_asig, 
                  s.id_sesiones_tuto as id_sesion,
                  s.enlace as link
              FROM tutoria_sesiones_tutoria_f78 as s
              INNER JOIN tutoria_detalle_sesion_curso as d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
              INNER JOIN carga_lectiva as c ON c.id_cargalectiva = s.id_carga
              WHERE s.id_doce = '$iddoce' 
                AND s.fecha = '$fecha' 
                AND s.horainicio = '$inicio' 
                AND s.horaFin = '$final'";
                
      $arreglo = array();
      if ($consulta = $this->conexion->conexion->query($sql)) {
          while ($consulta_VU = mysqli_fetch_array($consulta)) {
              $arreglo[] = $consulta_VU;
          }
          return $arreglo;
      }
  } */
  /* public function RegistrarAsistenciaTutoria_TC($id_estudiante, $id_sesion, $id_cargalectiva) {
      $sql_insert = "INSERT INTO tutoria_detalle_sesion_curso (
                          id_cargalectiva, sesiones_tutoria_id, marcar_asis_doce, marcar_asis_estu, id_estu
                      ) VALUES (
                          '$id_cargalectiva', '$id_sesion', 1, 1, '$id_estudiante')";
      return $this->conexion->conexion->query($sql_insert) ? 1 : 0;
  } */
  public function RegistrarAsistenciaTutoria_TC($id_estudiante, $id_sesion, $id_cargalectiva) {
      // Verificar si ya existe
      $sql_check = "SELECT COUNT(*) AS total FROM tutoria_detalle_sesion_curso 
                    WHERE sesiones_tutoria_id = ? AND id_estu = ? AND id_cargalectiva = ?";
      $stmt_check = $this->conexion->conexion->prepare($sql_check);
      if (!$stmt_check) {
          error_log("Error PREPARE check asistencia: " . $this->conexion->conexion->error);
          return 0;
      }
      $stmt_check->bind_param("iii", $id_sesion, $id_estudiante, $id_cargalectiva);
      $stmt_check->execute();
      $result = $stmt_check->get_result()->fetch_assoc();
      $stmt_check->close();

      if ($result['total'] > 0) {
          // Ya existe: actualizar la asistencia
          $sql_update = "UPDATE tutoria_detalle_sesion_curso 
                        SET marcar_asis_estu = 1 
                        WHERE sesiones_tutoria_id = ? AND id_estu = ? AND id_cargalectiva = ?";
          $stmt_update = $this->conexion->conexion->prepare($sql_update);
          if (!$stmt_update) {
              error_log("ERROR PREPARE UPDATE asistencia: " . $this->conexion->conexion->error);
              return 0;
          }
          $stmt_update->bind_param("iii", $id_sesion, $id_estudiante, $id_cargalectiva);
          if (!$stmt_update->execute()) {
              error_log("ERROR EXECUTE UPDATE asistencia: " . $stmt_update->error);
              return 0;
          }
          return 1;
      }

      // Insertar porque no existe
      $sql_insert = "INSERT INTO tutoria_detalle_sesion_curso (
                        id_cargalectiva, sesiones_tutoria_id, marcar_asis_docente, marcar_asis_estu, id_estu
                    ) VALUES (?, ?, 1, 1, ?)";
      $stmt = $this->conexion->conexion->prepare($sql_insert);
      if (!$stmt) {
          error_log("ERROR PREPARE INSERT asistencia: " . $this->conexion->conexion->error);
          return 0;
      }
      $stmt->bind_param("iii", $id_cargalectiva, $id_sesion, $id_estudiante);
      if (!$stmt->execute()) {
          error_log("ERROR EXECUTE INSERT asistencia: " . $stmt->error);
          return 0;
      }

      return 1;
  }



  public function AsistenciasGuardadas_TC($inicio, $final, $iddoce, $fecha) {
      $sql = "SELECT 
                  s.tema, 
                  s.compromiso_indi AS compromiso, 
                  s.observacione, 
                  s.tipo_sesion_id AS tipo_session, 
                  s.reunion_tipo_otros AS otros, 
                  d.asignacion_id AS id_asig, 
                  s.id_sesiones_tuto AS id_sesion,
                  s.enlace AS link
              FROM tutoria_sesiones_tutoria_f78 s
              INNER JOIN tutoria_detalle_sesion_curso d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
              INNER JOIN carga_lectiva c ON c.id_cargalectiva = s.id_carga
              WHERE s.id_doce = '$iddoce' 
                AND s.fecha = '$fecha' 
                AND s.horainicio = '$inicio' 
                AND s.horafin = '$final'";

      $arreglo = array();
      if ($consulta = $this->conexion->conexion->query($sql)) {
          while ($fila = mysqli_fetch_assoc($consulta)) {
              $arreglo[] = $fila;
          }
      }
      return $arreglo;
  }

  //-----------------------------------------------------------------------------
  public function SesionExiste($id_sesion) {
      $sql = "SELECT id_sesiones_tuto FROM tutoria_sesiones_tutoria_f78 WHERE id_sesiones_tuto = ?";
      $stmt = $this->conexion->conexion->prepare($sql);
      $stmt->bind_param("i", $id_sesion);
      $stmt->execute();
      $stmt->store_result();
      return $stmt->num_rows > 0;
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

  /* function Registrar_Docente($id_doce,  $fecha, $semestre, $id_doce_asig)
  {
    $sql = "INSERT INTO tutoria_docente_asignado (fecha, id_doce, id_rol, id_semestre, id_coordinador) VALUES ('$fecha','$id_doce','2','$semestre','$id_doce_asig')";


    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return "Error SQL: " . $this->conexion->conexion->error;
    }
  } */
  public function Registrar_Docente($id_doce, $fecha, $semestre, $id_coordinador, $id_car) {
      $sql = "INSERT INTO tutoria_docente_asignado
              (fecha, id_doce, id_rol, id_semestre, id_coordinador, id_car)
              VALUES (?, ?, 2, ?, ?, ?)";
      $stmt = $this->conexion->conexion->prepare($sql);
      if (!$stmt) {
          return "Error prep: " . $this->conexion->conexion->error;
      }
      $stmt->bind_param("siiii", $fecha, $id_doce, $semestre, $id_coordinador, $id_car);
      if ($stmt->execute()) {
          return 1;
      }
      return "Error SQL: " . $stmt->error;
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

  function ListaSesiones($id_sesiones_tuto) {
        $sql = "SELECT s.tema,
                       s.compromiso_indi as comp,
                       s.tipo_sesion_id as tipo,
                       s.observacione as obs,
                       s.link,
                       s.reunion_tipo_otros as otro,
                       s.fecha,
                       s.horaInicio as ini,
                       s.horaFin as fin,
                       s.color,
                       s.evidencia_1 as evi1,
                       s.evidencia_2 as evi2
                FROM tutoria_sesiones_tutoria_f78 s
                WHERE s.id_sesiones_tuto = '$id_sesiones_tuto'";

        $arreglo = array();

        if ($consulta = $this->conexion->conexion->query($sql)) {
            while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
                $arreglo[] = $consulta_VU;
            }

            $this->conexion->cerrar();  // Cerramos la conexión después de obtener los resultados
            return $arreglo;
        }

        return $arreglo;  // Si no hay resultados, devolver arreglo vacío
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

// -------TUTOR DE AULA
/*   function ListaAsistenciaAlumnos($id_sesiones_tuto) {
    $id_sesiones_tuto = intval($id_sesiones_tuto); // orzar entero
    $sql = "SELECT d.id_detalle_sesion as id,
                   CONCAT(e.apepa_estu, ' ', e.apema_estu, ', ', e.nom_estu) as nombres,
                   d.marcar_asis_estu as asis
            FROM tutoria_detalle_sesion as d
            INNER JOIN estudiante as e ON e.id_estu = d.id_estu
            WHERE d.sesiones_tutoria_id = $id_sesiones_tuto";  
    $arreglo = array();
    if ($consulta = $this->conexion->conexion->query($sql)) {
        while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
            $arreglo[] = $consulta_VU;
        }
    }
    $this->conexion->cerrar();
    return $arreglo;
  } */
 public function ListaAsistenciaAlumnos($id_sesiones_tuto) {
    $sql = "SELECT 
                d.id_detalle_sesion as id,
                CONCAT(e.apepa_estu, ' ', e.apema_estu, ', ', e.nom_estu) as nombres,
                d.marcar_asis_estu as asis
            FROM tutoria_detalle_sesion d
            INNER JOIN estudiante e ON e.id_estu = d.id_estu
            WHERE d.sesiones_tutoria_id = '$id_sesiones_tuto'";

    $arreglo = array();
    $consulta = $this->conexion->conexion->query($sql);
    if ($consulta) {
        while ($fila = mysqli_fetch_assoc($consulta)) {
            $arreglo[] = $fila;
        }
    }
    return $arreglo;
}
// - -  TUTOR DE CURSO---------------------------------

/* public function ListaAsistenciaAlumnos_TC($id_cargalectiva, $id_sesion) {
    $sql = "SELECT 
                tdsc.id_estu AS id,
                CONCAT(e.apepa_estu, ' ', e.apema_estu, ', ', e.nom_estu) AS nombres,
                tdsc.marcar_asis_estu AS asis
            FROM tutoria_detalle_sesion_curso tdsc
            INNER JOIN estudiante e ON tdsc.id_estu = e.id_estu
            WHERE tdsc.id_cargalectiva = '$id_cargalectiva' 
              AND tdsc.sesiones_tutoria_id = '$id_sesion'";

    $arreglo = array();
    $consulta = $this->conexion->conexion->query($sql);

    if (!$consulta) {
        error_log("ERROR SQL en ListaAsistenciaAlumnos_TC: " . $this->conexion->conexion->error);
        return false;
    }

    while ($fila = $consulta->fetch_assoc()) {
        $arreglo[] = $fila;
    }

    return $arreglo;
} */
  function ListaAsistenciaAlumnos_TC($id_cargalectiva, $id_session) {
    $sql = "SELECT
            tdsc.id_estu AS id,
            CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS nombres,
            tdsc.marcar_asis_estu AS asis
        FROM tutoria_detalle_sesion_curso tdsc
        INNER JOIN estudiante e ON tdsc.id_estu = e.id_estu
        WHERE tdsc.id_cargalectiva = ? AND tdsc.sesiones_tutoria_id = ?";

    $stmt = $this->conexion->conexion->prepare($sql);
    if (!$stmt) {
        error_log("ERROR PREPARANDO SQL: " . $this->conexion->conexion->error);
        return false;
    }

    $stmt->bind_param("ii", $id_cargalectiva, $id_session);

    if (!$stmt->execute()) {
        error_log("ERROR EJECUTANDO SQL: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $resultado = $stmt->get_result();
    $arreglo = [];

    while ($fila = $resultado->fetch_assoc()) {
        $arreglo[] = $fila;
    }

    $stmt->close();
    return $arreglo;
  }




  function EstudiantesAsistencia_TC($id_sesion){
      $sql = "SELECT ROW_NUMBER() OVER (ORDER BY e.id_estu) AS numero,
                    CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS estu
              FROM tutoria_detalle_sesion_curso d
              INNER JOIN estudiante e ON a.id_estudiante = e.id_estu
              WHERE d.sesiones_tutoria_id = '$id_sesion' AND d.marcar_asis_estu = 1";
      $arreglo = array();
      if ($consulta = $this->conexion->conexion->query($sql)) {
          while ($consulta_VU = mysqli_fetch_array($consulta)) {
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
  public function listar_historial_derivaciones_TA($id_doce, $id_semestre)
  {
      $sql = "SELECT
                  d.id_derivaciones AS id_der,
                  d.fecha,
                  CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS nombres,
                  e.celu_estu AS telefono,
                  d.estado,
                  a.des_area_apo AS des_area,
                  d.id_estudiante AS id_estu
              FROM tutoria_derivacion_tutorado_f6 d
              INNER JOIN estudiante e        ON e.id_estu      = d.id_estudiante
              INNER JOIN tutoria_area_apoyo a ON a.idarea_apoyo = d.area_apoyo_id
              WHERE d.id_docente = ?
                AND d.id_rol = 6
                AND d.id_semestre = ?
              ORDER BY d.id_derivaciones DESC";

      $out = ['data' => []];
      if ($st = $this->conexion->conexion->prepare($sql)) {
          $st->bind_param('ii', $id_doce, $id_semestre);
          if ($st->execute()) {
              $res = $st->get_result();
              while ($row = $res->fetch_assoc()) {
                  $out['data'][] = $row;
              }
          }
          $st->close();
      }
      return $out;
  }


 public function DerivarAlumno_TA($fecha, $hora, $motivo_referido, $area, $id_estu, $id_doce, $id_semestre)
  {
      $sql = "INSERT INTO tutoria_derivacion_tutorado_f6
                (fecha, hora, motivo_ref, fechaDerivacion, estado,
                area_apoyo_id, id_estudiante, id_docente, id_rol, id_semestre)
              VALUES
                (?, ?, ?, ?, 'Pendiente',
                ?, ?, ?, 6, ?)";

      if ($st = $this->conexion->conexion->prepare($sql)) {
          // s = string, i = int
          $st->bind_param('ssssiiii',
              $fecha, $hora, $motivo_referido, $fecha,  // 4 strings
              $area, $id_estu, $id_doce, $id_semestre   // 4 ints
          );
          $ok = $st->execute();
          $st->close();
          return $ok ? 1 : 0;
      }
      return 0;
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


  public function listar_historial_sesiones_TA($id_doce, $id_semestre){
    $sql = "SELECT
              s.id_sesiones_tuto AS id_tuto,
              s.fecha AS fecha,
              CONCAT(s.horainicio, '-', s.horaFin) AS horas,
              t.des_tipo AS tipo,
              IF((SELECT COUNT(*) FROM tutoria_detalle_sesion d2
                  WHERE d2.sesiones_tutoria_id = s.id_sesiones_tuto) > 1, 2, 1) AS idtipo,
              e.nom_estu AS estudiante,
              s.tema
            FROM tutoria_sesiones_tutoria_f78 s
            INNER JOIN tutoria_tipo_sesion        t ON t.id_tipo_sesion      = s.tipo_sesion_id
            INNER JOIN tutoria_detalle_sesion     d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
            INNER JOIN tutoria_asignacion_tutoria a ON a.id_asignacion       = d.asignacion_id
            INNER JOIN estudiante                 e ON e.id_estu              = a.id_estudiante
            WHERE s.id_doce = ?
              AND s.id_rol  = 6
              AND COALESCE(s.id_semestre, a.id_semestre) = ?
            GROUP BY s.id_sesiones_tuto
            ORDER BY s.id_sesiones_tuto DESC";

    $out = ["data"=>[]];
    if ($st = $this->conexion->conexion->prepare($sql)) {
      $st->bind_param('ii', $id_doce, $id_semestre);
      $st->execute();
      $res = $st->get_result();
      while ($row = $res->fetch_assoc()) $out["data"][] = $row;
      $st->close();
    }
    return $out;
  }


  // Dentro de la clase Docente
  public function RegistrarSessionTutoria_TA(
      $fecha,         // s
      $inicio,        // s
      $final,         // s
      $tema,          // s
      $detalle,       // s
      $link,          // s
      $compromiso,    // s
      $tipo_sesion,   // i
      $iddocente,     // i
      $id_semestre = 0, // i
      $evidencia1 = null, // s
      $evidencia2 = null  // s
  ) {
      $sql = "INSERT INTO tutoria_sesiones_tutoria_f78
              (fecha, horaInicio, horaFin, tema, reunion_tipo_otros, link, compromiso_indi,
              color, tipo_sesion_id, id_doce, id_rol, id_semestre, evidencia_1, evidencia_2)
              VALUES (?, ?, ?, ?, ?, ?, ?, '#3c8dbc', ?, ?, 6, ?, ?, ?)";

      $stmt = $this->conexion->conexion->prepare($sql);
      if (!$stmt) {
          error_log('Error preparando SQL: ' . $this->conexion->conexion->error);
          return 0;
      }

      // 7 strings + 3 ints + 2 strings = 12 tipos
      if (!$stmt->bind_param(
          "sssssssiiiss",
          $fecha,        // 1
          $inicio,       // 2
          $final,        // 3
          $tema,         // 4
          $detalle,      // 5
          $link,         // 6
          $compromiso,   // 7
          $tipo_sesion,  // 8
          $iddocente,    // 9
          $id_semestre,  // 10
          $evidencia1,   // 11
          $evidencia2    // 12
      )) {
          error_log('Error bind_param: ' . $stmt->error);
          return 0;
      }

      return $stmt->execute() ? $this->conexion->conexion->insert_id : 0;
  }

/* 
  function RegistrarSessionTutoria_TA($iddocente, $inicio, $final, $tema, $detalle, $link, $compromiso, $tipo_sesion, $fecha, $id_semestre = 0, $evidencia1, $evidencia2) {
    $sql = "INSERT INTO tutoria_sesiones_tutoria_f78
            (fecha, horaInicio, horaFin, tema, reunion_tipo_otros, link, compromiso_indi,
             color, tipo_sesion_id, id_doce, id_rol, id_semestre, evidencia_1, evidencia_2)
            VALUES (?, ?, ?, ?, ?, ?, ?, '#3c8dbc', ?, ?, 6, ?, ?, ?)";
    $stmt = $this->conexion->conexion->prepare($sql);
    if (!$stmt) {
        error_log(" Error preparando SQL: " . $this->conexion->conexion->error);
        return 0;
    }
    $stmt->bind_param("sssssssiiss",
        $fecha,         // 1
        $inicio,        // 2
        $final,         // 3
        $tema,          // 4
        $detalle,       // 5
        $link,          // 6
        $compromiso,    // 7
        $tipo_sesion,   // 8
        $iddocente,     // 9
        $id_semestre,  // 10 i
        $evidencia1,    // 11
        $evidencia2     // 12
    );
    return $stmt->execute() ? $this->conexion->conexion->insert_id : 0;
  } */

 /*  function RegistrarSessionTutoria_TA($iddocente, $inicio, $final, $tema, $compromiso, $tipo_session, $reu_otros, $link, $fecha)
  {
    $sql = "INSERT INTO tutoria_sesiones_tutoria_f78 (fecha, horainicio,  horaFin, tema, reunion_tipo_otros, link, compromiso_indi, color, tipo_sesion_id, id_doce, id_rol) VALUES ('$fecha', '$inicio', '$final', '$tema', '$reu_otros', '$link', '$compromiso', '#3c8dbc', '$tipo_session', '$iddocente','6')";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      $ultimoId = $this->conexion->conexion->insert_id;
      return $ultimoId;
    } else {
      return 0;
    }
  } */
 /*  function RegistrarDetalleSessionTutoria_TA($id_asignacion, $id_estudiante, $id_sesion)
  {
    $sql = "INSERT INTO tutoria_detalle_sesion (asignacion_id, sesiones_tutoria_id, marcar_asis_doce, marcar_asis_estu, valoracion_tuto, comentario_estu, id_estu) VALUES ('$id_asignacion', '$id_sesion', '1', '0', '0', '0', '$id_estudiante')";
    if ($consulta = $this->conexion->conexion->query($sql)) {
      return 1;
    } else {
      return 0;
    }
  } */
    function RegistrarDetalleSessionTutoria_TA($id_asignacion, $id_estudiante, $id_session) {
      $id_asignacion = ($id_asignacion === '' || $id_asignacion === null) ? 0 : $id_asignacion;

      if (empty($id_estudiante) || empty($id_session)) {
          error_log("❌ Datos faltantes en detalle sesión: estudiante=$id_estudiante, sesion=$id_session");
          return 0;
      }

      $sql = "INSERT INTO tutoria_detalle_sesion 
              (asignacion_id, sesiones_tutoria_id, marcar_asis_doce, marcar_asis_estu, valoracion_tuto, comentario_estu, id_estu)
              VALUES (?, ?, '1', '0', '0', '0', ?)";

      $stmt = $this->conexion->conexion->prepare($sql);
      if (!$stmt) {
          error_log("❌ Prepare fallido: " . $this->conexion->conexion->error);
          return 0;
      }

      $stmt->bind_param("iis", $id_asignacion, $id_session, $id_estudiante);

      if ($stmt->execute()) {
          return 1;
      } else {
          error_log("❌ Execute fallido: " . $stmt->error);
          return 0;
      }
  }
  
  function ActualizarSessionTutoria_TA($sesi, $inic, $fina, $tema, $comp, $tipo, $deta, $link, $fecha, $obse, $evi1 = null, $evi2 = null)
  {
      // Verificar asistencia
      $sqlVerificar = "SELECT COUNT(*) as total FROM tutoria_detalle_sesion WHERE sesiones_tutoria_id = ? AND marcar_asis_estu = 1";
      $stmtVerif = $this->conexion->conexion->prepare($sqlVerificar);
      $stmtVerif->bind_param("i", $sesi);
      $stmtVerif->execute();
      $res = $stmtVerif->get_result()->fetch_assoc();
      $stmtVerif->close();

      // Color según asistencia
      /* $color = ($res['total'] > 0) ? '#00a65a' : '#00406a'; */
      $color = '#00a65a';

      // Actualizar sesión (con evidencias)
      $sql = "UPDATE tutoria_sesiones_tutoria_f78 
              SET fecha = ?, horainicio = ?, horafin = ?, tema = ?, observacione = ?, 
                  reunion_tipo_otros = ?, link = ?, compromiso_indi = ?, 
                  color = ?, tipo_sesion_id = ?, evidencia_1 = ?, evidencia_2 = ?
              WHERE id_sesiones_tuto = ?";

      $stmt = $this->conexion->conexion->prepare($sql);
      if (!$stmt) return 0;

      $stmt->bind_param(
          "ssssssssssssi",
          $fecha, $inic, $fina, $tema, $obse,
          $deta, $link, $comp,
          $color, $tipo, $evi1, $evi2,
          $sesi
      );

      return $stmt->execute() ? 1 : 0;
  }

 /*  public function ActualizarSessionTutoria_TA($sesi, $inic, $fina, $tema, $comp, $tipo, $deta, $link, $fech, $obse)
  {
      // Verificar si ya se marcó al menos una asistencia
      $sqlVerificar = "SELECT COUNT(*) as total FROM tutoria_detalle_sesion 
                      WHERE sesiones_tutoria_id = ? AND marcar_asis_estu = 1";
      $stmtVerif = $this->conexion->conexion->prepare($sqlVerificar);
      $stmtVerif->bind_param("i", $sesi);
      $stmtVerif->execute();
      $res = $stmtVerif->get_result()->fetch_assoc();
      $stmtVerif->close();

      // Si hay al menos una asistencia, color verde; si no, azul
      $color = ($res['total'] > 0) ? '#00a65a' : '#00406a';

      // Actualizar la sesión
      $sql = "UPDATE tutoria_sesiones_tutoria_f78 
              SET fecha = ?, horaInicio = ?, horaFin = ?, tema = ?, observacione = ?, 
                  reunion_tipo_otros = ?, link = ?, compromiso_indi = ?, 
                  color = ?, tipo_sesion_id = ?
              WHERE id_sesiones_tuto = ?";

      $stmt = $this->conexion->conexion->prepare($sql);
      if (!$stmt) return 0;

      $stmt->bind_param("ssssssssssi", 
          $fech, $inic, $fina, $tema, $obse, $deta, $link, $comp, $color, $tipo, $sesi
      );

      return $stmt->execute() ? 1 : 0;
  } */


  function listar_historial_sesiones($id_doce){
    $sql =  "SELECT
            s.id_sesiones_tuto as id_tuto,
            s.fecha as fecha,
            CONCAT(s.horainicio, '-', s.horaFin) as horas,
            t.des_tipo as tipo, IF((SELECT COUNT(*) FROM tutoria_detalle_sesion_curso d WHERE d.sesiones_tutoria_id = s.id_sesiones_tuto) >1, 2, 1) as idtipo,
            e.nom_estu as estudiante,
            s.tema
        FROM tutoria_sesiones_tutoria_f78 as s
        INNER JOIN tutoria_tipo_sesion as t ON t.id_tipo_sesion = s.tipo_sesion_id
                    INNER JOIN tutoria_detalle_sesion_curso as d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
                    INNER JOIN estudiante as e ON e.id_estu = d.id_estu
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