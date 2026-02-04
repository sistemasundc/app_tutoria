<?php
    ob_start();
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    require('pdf/fpdf.php');
    require_once(__DIR__ . '/../modelo/modelo_conexion.php');

// Primero definir la clase PDF
if (!class_exists('PDF')) {
    class PDF extends FPDF {
        protected $widths;
        protected $aligns;

        function Header() {
            $this->SetFont('Arial', 'B', 12);
            $this->Image('pdf/img/undc.jpeg', 20, 10, 70);
        } 

        function Footer() {
            $x = 30;
            $y = 275;
            $ancho = 150;
            $alto = 10;
        }

        function FancyTable($header, $data, $ancho, $tipo) {
            // Colores, ancho de línea y fuente en negrita
            $this->SetFillColor(82, 129, 53);
            $this->SetTextColor(255);
            $this->SetDrawColor(0,0,0);
            $this->SetLineWidth(.3);
            
            // Cabecera
            $fontz = 11;
            if ($tipo == 2) {
                $w = array(70, 70);
            }else if($tipo == 22) {
                $w = array(5, 70, 12, 15, 15, 40);
                $fontz = 9;
            }else {
                $w = array(10, 130);
            }

            $this->SetFont('','B', $fontz);
            
            for($i=0;$i<count($header);$i++)
                $this->Cell($w[$i],7,utf8_decode($header[$i]),1,0,'C',true);

            $this->Ln();
            // Restauración de colores y fuentes
            $this->SetFillColor(224,235,255);
            $this->SetTextColor(0);
            $this->SetFont('');
            // Datos
            $fill = false; 

            $this->SetWidths($w); 

            if ($tipo == 22 ){
                foreach ($data as $row) {
                    $this->Row(array($row[0],$row[1], $row[2], $row[3], $row[4], $row[5]));
                } 
            }else {
                foreach ($data as $row) {
                    $this->Row(array($row[0],$row[1]));
                } 
            }
            $this->Cell(array_sum($w),0,'','T'); 
        }
        function SetWidths($w) {
            $this->widths = $w;
        }

        function SetAligns($a) {
            $this->aligns = $a;
        }

        function Row($data) {
            // Calculate the height of the row
            $nb = 0;
            for($i=0;$i<count($data);$i++)
                $nb = max($nb,$this->NbLines($this->widths[$i],$data[$i]));
            $h = 5*$nb;
            // Issue a page break first if needed
            $this->CheckPageBreak($h);
            // Draw the cells of the row
            for($i=0;$i<count($data);$i++)
            {
                $w = $this->widths[$i];
                $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
                // Save the current position
                $x = $this->GetX();
                $y = $this->GetY();
                // Draw the border
                $this->Rect($x,$y,$w,$h);
                // Print the text
                $this->MultiCell($w,5,utf8_decode($data[$i]),0,$a);
                // Put the position to the right of the cell
                $this->SetXY($x+$w,$y);
            }
            // Go to the next line
            $this->Ln($h);
        }

        function CheckPageBreak($h) {
            // If the height h would cause an overflow, add a new page immediately
            if($this->GetY()+$h>$this->PageBreakTrigger)
                $this->AddPage($this->CurOrientation);
        }

        function NbLines($w, $txt) {
            // Compute the number of lines a MultiCell of width w will take
            if(!isset($this->CurrentFont))
                $this->Error('No font has been set');
            $cw = $this->CurrentFont['cw'];
            if($w==0)
                $w = $this->w-$this->rMargin-$this->x;
            $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
            $s = str_replace("\r",'',(string)$txt);
            $nb = strlen($s);
            if($nb>0 && $s[$nb-1]=="\n")
                $nb--;
            $sep = -1;
            $i = 0;
            $j = 0;
            $l = 0;
            $nl = 1;
            while($i<$nb)
            {
                $c = $s[$i];
                if($c=="\n")
                {
                    $i++;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                    continue;
                }
                if($c==' ')
                    $sep = $i;
                $l += $cw[$c];
                if($l>$wmax)
                {
                    if($sep==-1)
                    {
                        if($i==$j)
                            $i++;
                    }
                    else
                        $i = $sep+1;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                }
                else
                    $i++;
            }
            return $nl;
        }
    }
    class Docente {
        private $conexion;
        function __construct() {
            require_once '../modelo/modelo_conexion.php';
            $this->conexion = new conexion();
            $this->conexion->conectar();
        }

            function DatosIdDeriva($idestue, $iddoce) {
    // Preparar la consulta SQL con parámetros seguros para evitar inyección de SQL
    $sql8 = "SELECT id_derivaciones
             FROM tutoria_derivacion_tutorado_f6
             WHERE id_estudiante = ? AND id_docente = ?
             ORDER BY id_derivaciones DESC
             LIMIT 1";
    $arreglo = array();
    
    // Preparar la sentencia SQL
    $stmt = $this->conexion->conexion->prepare($sql8);
    
    // Vincular parámetros a la sentencia preparada
    $stmt->bind_param("ii", $idestue, $iddoce);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Obtener el resultado de la consulta
        $resultado = $stmt->get_result();
        
        // Recorrer el resultado y almacenarlo en un arreglo
        while ($consulta_VU = $resultado->fetch_assoc()) {
            $arreglo[] = $consulta_VU;
        }
        
        // Cerrar la conexión
        $stmt->close();
        $this->conexion->cerrar();
        
        // Devolver el arreglo de resultados
        return $arreglo;
    } else {
        // En caso de error, devolver un arreglo vacío
        return $arreglo;
    }
}
// -----------TUTOR DE AULA -------ASISTENCIA Y FORMATO--------------------
        function DatosFormatoAsistenci($id_sesion,  $semestre) {
    $sql = "SELECT
            CONCAT('Escuela Profesional de: ', c.nom_car),
            CASE
                WHEN (SELECT COUNT(*) FROM tutoria_detalle_sesion d WHERE d.sesiones_tutoria_id = s.id_sesiones_tuto) >1 THEN 
                    (SELECT CONCAT('Número de estudiantes asistentes: ', COUNT(destu.asignacion_id))
                     FROM tutoria_detalle_sesion as destu 
                     WHERE destu.sesiones_tutoria_id = '$id_sesion')
                ELSE 
                    CONCAT('Estudiante: ', e.apepa_estu, ' ', e.apema_estu,' ', e.nom_estu)
            END,   
            CONCAT('Ciclo académico: ', l.ciclo, '                                Turno: ', CASE
                    WHEN l.turno = 'MANANA' THEN 'MAÑANA'
                    ELSE l.turno
                END),
            CONCAT('Tutor: ', d.apepa_doce, ' ', d.apema_doce,' ', d.nom_doce) as nombredoce,
            d.email_doce,
            CONCAT('Semestre académico: $semestre'),
            CONCAT('Fecha de reunión: ', s.fecha, '                 Hora: ', s.horaInicio, ' a ', s.horaFin),
            CONCAT('Forma de Consejería y Tutoría Académica'),
            s.tipo_sesion_id,
            s.reunion_tipo_otros,
            s.tema,
            s.compromiso_indi,
            IF((SELECT COUNT(*) FROM tutoria_detalle_sesion d WHERE d.sesiones_tutoria_id = s.id_sesiones_tuto) >1, 2, 1) as idtipo,
            s.evidencia_1,
            s.evidencia_2
        FROM tutoria_detalle_sesion as ds
            JOIN tutoria_asignacion_tutoria as a ON ds.asignacion_id = a.id_asignacion
            JOIN carga_lectiva as l ON a.id_carga = l.id_cargalectiva
            JOIN docente as d ON l.id_doce = d.id_doce
            JOIN carrera as c ON l.id_car = c.id_car
            JOIN tutoria_sesiones_tutoria_f78 as s ON ds.sesiones_tutoria_id = s.id_sesiones_tuto
            JOIN estudiante as e ON a.id_estudiante = e.id_estu
        WHERE s.id_sesiones_tuto = '$id_sesion'
        LIMIT 1"; 
            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
               /*  while ($consulta_VU = mysqli_fetch_array($consulta)) { */
                while ($consulta_VU = mysqli_fetch_array($consulta, MYSQLI_NUM)) {
                    $arreglo[] = $consulta_VU;
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }

        function EstudiantesAsistencia($id_sesion){
            $sql = "SELECT ROW_NUMBER() OVER (ORDER BY e.id_estu) AS numero,
                    CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS estu
                FROM tutoria_detalle_sesion d
                INNER JOIN tutoria_asignacion_tutoria a ON a.id_asignacion = d.asignacion_id
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
// -----------TUTOR DE CURSO -------ASISTENCIA Y FORMATO--------------------
        function DatosFormatoAsistenciaCurso($id_sesion, $semestre, $cn) {
            $sql = "SELECT 
                        CONCAT('Escuela Profesional de: ', c.nom_car) AS escuela,
                        CONCAT('Número de estudiantes asistentes: ', 
                            (SELECT COUNT(*) 
                                FROM tutoria_detalle_sesion_curso dc 
                                WHERE dc.sesiones_tutoria_id = f.id_sesion AND dc.marcar_asis_estu = 1)) AS num_asistentes,
                        CONCAT('Ciclo académico: ', f.ciclo, '         Turno: ', f.turno) AS ciclo_turno,
                        CONCAT('Tutor: ', d.apepa_doce, ' ', d.apema_doce, ' ', d.nomb_doce) AS tutor_nombre,
                        d.correo_inst_doce,
                        CONCAT('Semestre académico: ', f.semestre) AS semestre,
                        CONCAT('Fecha de reunión: ', f.fecha_sesion, '     Hora: ', f.hora_inicio, ' a ', f.hora_fin) AS fecha_hora,
                        'Forma de Consejería y Tutoría Académica' AS forma,
                        f.tipo_session,
                        f.reunion_tipo_otros,
                        f.tema_sesion,
                        f.compromiso_sesion,
                        f.tipo_tutoria,
                        d.id_doce,          -- este es el índice [13]
                        f.id_carga          -- este es el índice [14]
                    FROM tutoria_sesiones_tutoria_f78 f
                    INNER JOIN docente d ON f.id_doce = d.id_doce
                    INNER JOIN carga_lectiva cl ON f.id_carga = cl.id_cargalectiva
                    INNER JOIN carrera c ON cl.id_car = c.id_car
                    WHERE f.id_sesion = ? AND f.semestre = ?";

            $stmt = $cn->prepare($sql);
            $stmt->bind_param("is", $id_sesion, $semestre);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all();
            $stmt->close();
            return $data;
        }


       public function EstudiantesAsistencia_TC($id_sesion, $cn) {
            $data = [];
            $sql = "SELECT e.id_estu,
                        CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS nombres
                    FROM tutoria_detalle_sesion_curso dc
                    INNER JOIN estudiante e ON e.id_estu = dc.id_estu
                    WHERE dc.sesiones_tutoria_id = ? AND dc.marcar_asis_estu = 1";

            $stmt = $cn->prepare($sql);
            $stmt->bind_param("i", $id_sesion);
            $stmt->execute();
            $res = $stmt->get_result();

            $i = 1;
            while ($fila = $res->fetch_assoc()) {
                $data[] = [$i++, utf8_decode($fila['nombres'])];
            }
            return $data;
        }
        //-----------------------------------------------------------------------------------------------------------------
        function EstudiantesReferido($id_der){
            $sql = "SELECT ROW_NUMBER() OVER (ORDER BY e.id_estu) AS numero, CONCAT(e.apepa_estu, ' ', e.apema_estu,' ', e.nom_estu) as estu
                    FROM tutoria_derivacion_tutorado_f6 as d
                        JOIN estudiante as e ON e.id_estu = d.id_estudiante
                        JOIN docente ON docente.id_doce = d.id_docente
                    WHERE
                        d.id_derivaciones = '$id_der'";
            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_array($consulta)) {
                    $arreglo[] = $consulta_VU;
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }
        function DatosReferido($id_der) {
           $sql = "SELECT 
                CONCAT('Fecha: ', d.fecha, '                       Hora: ', d.hora),
                CONCAT('Tutor : ', docente.apepa_doce, ' ', docente.apema_doce, ' ', docente.nom_doce),
                CONCAT('Dirigido a: '),
                d.motivo_ref,
                d.area_apoyo_id,
                docente.email_doce
            FROM tutoria_derivacion_tutorado_f6 as d
            JOIN estudiante as e ON e.id_estu = d.id_estudiante
            JOIN docente ON docente.id_doce = d.id_docente
            WHERE d.id_derivaciones = '$id_der'";
            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_array($consulta)) {
                    $arreglo[] = $consulta_VU;
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }

        function EstudiantesContraReferido($id_der, $semestre){
            $sql = "SELECT ROW_NUMBER() OVER (ORDER BY e.id_estu) AS numero, CONCAT(e.apepa_estu, ' ', e.apema_estu,' ', e.nom_estu) as estu
                    FROM tutoria_derivacion_tutorado_f6 as d
                        JOIN estudiante as e ON e.id_estu = d.id_estudiante
                        JOIN docente as dc ON dc.id_doce = d.id_docente
                        JOIN tutoria_area_apoyo as a ON a.idarea_apoyo = d.area_apoyo_id
                        WHERE
                            d.id_derivaciones = '$id_der'
                            AND dc.id_doce = d.id_docente
                            AND e.id_estu = d.id_estudiante";


            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_array($consulta)) {
                    $arreglo[] = $consulta_VU;
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }
// ----------------FORMATO DE CONTRAREFERENCIA-------------------------------
        function DatosContraReferido($id_der) {
            $sql = "SELECT 
                d.fecha,
                d.hora,
                d.fechaDerivacion,
                d.resultado_contra,
                CONCAT(a.ape_encargado, ' ', a.nombre_encargado) AS responsable,
                IFNULL(tu.cor_inst, 'No registrado') AS correo_responsable,
                CONCAT(dc.apepa_doce, ' ', dc.apema_doce, ', ', dc.nom_doce) AS tutor,
                a.des_area_apo  -- ← nuevo campo (posición [6])
            FROM tutoria_derivacion_tutorado_f6 AS d
            JOIN estudiante AS e ON e.id_estu = d.id_estudiante
            JOIN docente AS dc ON dc.id_doce = d.id_docente
            JOIN tutoria_area_apoyo AS a ON a.idarea_apoyo = d.area_apoyo_id
            LEFT JOIN tutoria_usuario AS tu ON tu.id_usuario = a.id_personal_apoyo
            WHERE d.id_derivaciones ='$id_der'";
            
            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_array($consulta)) {
                    $arreglo[] = $consulta_VU;
                }
            }
            return $arreglo;
            $this->conexion->cerrar();
        }

        function EstudiantesTutoria($estu, $doce){
            $sql = "SELECT ROW_NUMBER() OVER (ORDER BY s.id_sesiones_tuto) AS numero, 
                        s.tema,
                        DAY(s.fecha),
                        MONTH(s.fecha),
                        s.horaInicio,
                        e.email_estu
                    FROM tutoria_asignacion_tutoria as a
                        JOIN estudiante as e ON a.id_estudiante = e.id_estu
                        JOIN carga_lectiva as cl ON a.id_carga = cl.id_cargalectiva
                        JOIN docente as dc ON cl.id_doce = dc.id_doce
                        JOIN carrera as c ON cl.id_car = c.id_car
                        JOIN tutoria_sesiones_tutoria_f78 as s ON a.id_docente = s.id_doce
                    WHERE a.id_estudiante = '$estu' AND a.id_docente='$doce'";
            $arreglo = array();
            if ($consulta = $this->conexion->conexion->query($sql)) {
                while ($consulta_VU = mysqli_fetch_array($consulta)) {
                    $arreglo[] = $consulta_VU;
                }
                return $arreglo;
                $this->conexion->cerrar();
            }
        }

        function DatosRegistroTutoria($estu, $doce) {
            $sql = " SELECT
                        CONCAT('Escuela: ', c.nom_car),
                        CONCAT('Tutor (a): ', dc.nom_doce),
                        CONCAT('Código/Asignatura: '),
                        CONCAT('Ciclo: ', cl.ciclo)
                    FROM tutoria_asignacion_tutoria as a
                        JOIN estudiante as e ON a.id_estudiante = e.id_estu
                        JOIN carga_lectiva as cl ON a.id_carga = cl.id_cargalectiva
                        JOIN docente as dc ON cl.id_doce = dc.id_doce
                        JOIN carrera as c ON cl.id_car = c.id_car
                        JOIN tutoria_sesiones_tutoria_f78 as s ON a.id_docente = s.id_doce
                    WHERE a.id_estudiante = '$estu' AND a.id_docente='$doce'";
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
}

   /*  //Formato 7 y 8
    require_once("formato78.php");
    //Formato 7 y 8 curso
    require_once("formatoCurso78.php");
    //Hoja de referencia
    require_once("referencia.php");
    
    //Hoja de Contra referencia
    require_once("contrareferencia.php");

    //Formato registro tutoria
    require_once("regtutoria.php");

    //Formato Registro Tutoria F5
    require_once("formato5.php"); */



    $semestre = $_SESSION['S_SEMESTRE_FECHA'] ?? '2025-1';

    if (isset($_POST['id_his'])) {
        require_once("formato78.php");
        FormatoSesionesTutoria($_POST['id_his'], $semestre);
        exit;

    } else if (isset($_POST['id_his_curso'])) {
        require_once("formatoCurso78.php");
        FormatoSesionesTutoriaCurso($_POST['id_his_curso'], $semestre);
        exit;

    } else if (isset($_POST['id_referencia'])) {
        require_once("referencia.php");
        FormatoReferencia($_POST['id_referencia'], $semestre);
        exit;

    } else if (isset($_POST['contraref'])) {
        require_once("contrareferencia.php");
        FormatoContraReferencia($_POST['contraref'], $semestre);
        exit;

    } else if (isset($_POST['estututo'], $_POST['docetuto'])) {
        require_once("regtutoria.php");
        RegistroTutoria($_POST['estututo'], $_POST['docetuto'], $semestre);
        exit;

    } else if (isset($_POST['contraid'])) {
        require_once("formato5.php");
        FormatoRegistroTutoriaF5($_POST['contraid'], $semestre);
        exit;

    } else if (isset($_GET['id_referencia'])) {
        require_once("referencia.php");
        $id_der = intval($_GET['id_referencia']);
        $semestre = $_SESSION['S_SEMESTRE_FECHA'] ?? '2025-1';
        FormatoReferencia($id_der, $semestre);
        exit;
    }else if (isset($_SESSION['ejecutar_formato']) && $_SESSION['ejecutar_formato'] === 'referencia') {
        require_once("referencia.php");
        FormatoReferencia($_SESSION['id_der'], $semestre);
        unset($_SESSION['ejecutar_formato'], $_SESSION['id_der']);
        exit;

    } else {
        http_response_code(404);
        echo "Error 404";
        exit;
    }

    

    if (isset($_POST['id_his']) && !empty($_POST['id_his'])){       
        $his = $_POST['id_his'];
        FormatoSesionesTutoria($his, $semestre);
    }else if (isset($_POST['id_his_curso']) && !empty($_POST['id_his_curso'])) {
    $his = $_POST['id_his_curso'];
    FormatoSesionesTutoriaCurso($his, $semestre);
  /*   }else if(isset($_POST['contraref']) && !empty($_POST['contraref'])) {
        $id_contra = $_POST['contraref'];   
        FormatoContraReferencia($id_contra,$semestre); */

    }else if (isset($_POST['id_derivacion']) && !empty($_POST['id_derivacion'])) {
        $id_der = $_POST['id_derivacion'];
        $_SESSION['ejecutar_formato'] = 'referencia';
        $_SESSION['id_der'] = $id_der;
    }else if(isset($_POST['estututo']) && !empty($_POST['estututo']) && isset($_POST['docetuto']) && !empty($_POST['docetuto'])) {
        $doce = $_POST['docetuto'];
        $estu = $_POST['estututo'];

        RegistroTutoria($estu, $doce, $semestre);
    }else if (isset($_POST['contraid']) && !empty($_POST['contraid'])){
        $id_contra = $_POST['contraid'];
        FormatoRegistroTutoriaF5($id_contra, $semestre);
    }else if (isset($_POST['estu']) && !empty($_POST['estu']) && isset($_POST['doce']) && !empty($_POST['doce'])) {

           $id_estu = $_POST['estu'];
            $id_doce = $_POST['doce'];
            $MD = new Docente();
            $id_derifinal = $MD->DatosIdDeriva($id_estu, $id_doce);
            $id_derifinal = $id_derifinal[0]['id_derivaciones'];

            FormatoReferencia($id_derifinal);   
            
            
    }else {
        echo "Error 404"; 
    } 
    // Ejecutar FormatoReferencia si fue marcado por POST
    if (isset($_SESSION['ejecutar_formato']) && $_SESSION['ejecutar_formato'] === 'referencia') {
        require_once("referencia.php"); // ya lo tienes correcto
        FormatoReferencia($_SESSION['id_der'], $_SESSION['S_SEMESTRE_FECHA'] ?? '2025-1'); // CORREGIDO
        unset($_SESSION['ejecutar_formato']);
        unset($_SESSION['id_der']);
    }
?>
