<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED); 
session_start();
require_once('pdf/fpdf.php');
require_once('../modelo/modelo_docente.php');
require_once(__DIR__ . '/../modelo/modelo_conexion.php');
$conexion = new conexion();
$cn = $conexion->conectar();


class PDF extends FPDF {
    function Header() {
        $this->Image('pdf/img/undc.jpeg', 20, 10, 70);
        $this->SetFont('Arial', 'B', 12);
    }

    function Footer() {
        $this->Image('pdf/img/footer.JPG', 20, 275, 150, 10);
    }

    function FancyTable($header, $data, $ancho = [90, 90], $tipo = 2) {
        $this->SetFillColor(82, 129, 53); // Verde UNDC
        $this->SetTextColor(255);         // Blanco
        $this->SetDrawColor(0);           // Borde negro
        $this->SetFont('Arial', 'B', 11);

        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($ancho[$i], 8, utf8_decode($header[$i]), 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 10);

        foreach ($data as $fila) {
            $x = $this->GetX();
            $y = $this->GetY();
            $lineHeight = 6;

            $nb1 = $this->NbLines($ancho[0], utf8_decode($fila[0]));
            $nb2 = $this->NbLines($ancho[1], utf8_decode($fila[1]));
            $altura = $lineHeight * max($nb1, $nb2);

            // MOTIVO
            $this->MultiCell($ancho[0], $lineHeight, utf8_decode($fila[0]), 1, 'L');
            $x1 = $this->GetX();
            $this->SetXY($x + $ancho[0], $y);

            // COMPROMISO
            $this->MultiCell($ancho[1], $lineHeight, utf8_decode($fila[1]), 1, 'L');
            $this->SetY($y + $altura);
        }
    }

    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }
}

function insertarImagenSeguro($pdf, $ruta, $x, $y, $ancho) {
    if (file_exists($ruta)) {
        $pdf->Image($ruta, $x, $y, $ancho);
    }
}
function EstudiantesAsistencia_TC($id_sesion, $cn) {
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
// Obtener evidencias para cada sesión (Tutor de Curso → tipo_tutoria = 2)

function obtenerEvidenciasSesionesCurso($cn, $id_doce, $id_cargalectiva) {
    $sql = "SELECT evidencia_1, evidencia_2
            FROM tutoria_sesiones_tutoria_f78
            WHERE id_doce = ?
              AND id_carga = ?
              AND id_rol = 2";

    $stmt = $cn->prepare($sql);
    $stmt->bind_param("ii", $id_doce, $id_cargalectiva);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if (!isset($_GET['id_tuto']) || !isset($_SESSION['S_SEMESTRE'])) die("Faltan datos.");

$id_history = $_GET['id_tuto'];
$semestre = $_SESSION['S_SEMESTRE'];
$MD = new Docente();

$array_data = $MD->DatosFormatoAsistenciaCurso($id_history, $semestre);
$tipo_sesion = [1 => 'Presencial', 4 => 'Google meet', 5 => 'Otro(s):'];

$pdf = new PDF();
$pdf->AddPage();
$tipo = ($array_data[0][12] == '2') ? 'Grupal' : 'Individual';
$pdf->Image(($tipo == 'Grupal') ? 'pdf/img/undc_f7.JPG' : 'pdf/img/undc_f8.JPG', 90, 15, 100);

$pdf->SetTextColor(45, 125, 87);
$pdf->SetY(55);
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 8, utf8_decode('Registro de la Consejería y Tutoría'), 0, 1, 'C');
$pdf->Cell(0, 8, utf8_decode('Académica ' . $tipo), 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(0);
$pdf->SetY(74);
$pdf->Cell(90, 8, utf8_decode('I.      DATOS INFORMATIVOS'), 0, 1, 'C');

$pdf->SetFont('Arial', '', 11);
$pdf->SetLeftMargin(40);
$pdf->SetRightMargin(3);
$lineas = [
    $array_data[0][0],
    $array_data[0][1],
    $array_data[0][2],
    $array_data[0][3],
    $array_data[0][5] . "     " . $array_data[0][6]
];
foreach ($lineas as $linea) $pdf->Cell(0, 8, utf8_decode($linea), 0, 1);

$pdf->Ln(2);
$linea_tipo = "";
foreach ($tipo_sesion as $id => $label) {
    $marcado = ($array_data[0][8] == $id) ? '(x)' : '( )';
    $linea_tipo .= "$label $marcado     ";
}
$pdf->MultiCell(0, 8, utf8_decode($linea_tipo));

$pdf->Ln(6);
$pdf->FancyTable(['MOTIVO O ASUNTO', 'COMPROMISOS ASUMIDOS'], [[ $array_data[0][10], $array_data[0][11] ]], [75, 75]);

//=====================================================
//     Pdf bloque 4 - Relación de estudiantes (Curso)
//=====================================================
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetY(30);
$pdf->Cell(0, 10, utf8_decode("RELACIÓN DE ESTUDIANTES ASISTENTES"), 0, 1, 'C');

// Obtener asistentes del curso (rol 2)
/* echo "ID recibido: $id_history<br>"; */
$array_estu = EstudiantesAsistencia_TC($id_history, $cn);
/* echo "<pre>";
print_r($array_estu);
echo "</pre>"; 
exit;*/

$header = ['N°', 'APELLIDOS Y NOMBRES'];

if (!empty($array_estu)) {
    $pdf->FancyTable($header, $array_estu, [10, 140], 1);
} else {
    $pdf->Ln(15);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 10, utf8_decode("No hay estudiantes asistentes registrados."), 0, 1, 'C');
}

$pdf->SetY(250);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, utf8_decode("Fecha: " . date('Y-m-d') . " - Hora: " . date('H:i:s')), 0, 1, 'C');
$pdf->Cell(0, 6, utf8_decode($array_data[0][3]), 0, 1, 'C');
$pdf->Cell(0, 6, utf8_decode("Correo: " . $array_data[0][4]), 0, 1, 'C');

//=====================================================
//     Pdf bloque 5 - EVIDENCIAS DE LA SESION
//=====================================================
/* echo "<pre>";
print_r($array_data[0]);
echo "</pre>";
exit;

$id_doce = isset($array_data[0][13]) ? $array_data[0][13] : null;
$id_cargalectiva = isset($array_data[0][14]) ? $array_data[0][14] : null;

if (!$id_doce || !$id_cargalectiva) {
    die("Error: faltan datos para generar evidencias de sesión.");
}

$evidencias = obtenerEvidenciasSesionesCurso($cn, $id_doce, $id_cargalectiva);

if (count($evidencias) > 0) {
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Ln(10);
    $pdf->Cell(0, 10, utf8_decode("EVIDENCIAS DE LAS SESIONES"), 0, 1, 'C');
    $pdf->Ln(10);

    foreach ($evidencias as $evi) {
        $ruta1 = __DIR__ . '/../' . $evi['evidencia_1'];
        $ruta2 = __DIR__ . '/../' . $evi['evidencia_2'];

        if (!empty($evi['evidencia_1']) && empty($evi['evidencia_2'])) {
            insertarImagenSeguro($pdf, $ruta1, 30, 55, 150); // Imagen centrada
            $pdf->Ln(10);
        }

        if (!empty($evi['evidencia_1']) && !empty($evi['evidencia_2'])) {
            insertarImagenSeguro($pdf, $ruta1, 30, 60, 140);  // Imagen arriba
            insertarImagenSeguro($pdf, $ruta2, 30, 155, 140); // Imagen abajo
            $pdf->Ln(10);
        }
    }
}
 */

$pdf->Output();
exit;