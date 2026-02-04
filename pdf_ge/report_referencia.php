<?php
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once(__DIR__ . '/../modelo/modelo_conexion.php');
$conexion = new conexion();
$cn = $conexion->conectar();

$id_estu = intval($_GET['id_estu'] ?? 0);
$mes_url = $_GET['mes'] ?? '';

if ($id_estu <= 0) {
    die('<p style="color:red;">ID de estudiante no válido.</p>');
}

$mes_map = [
    'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
    'julio'=>7,'agosto'=>8,'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
];
$mes_num = $mes_map[strtolower($mes_url)] ?? 0;

// --- Consulta principal ---
$sqlDerivaciones = "
SELECT
    d.id_derivaciones AS id_derivacion,
    d.id_estudiante,
    d.id_docente,
    d.fechaDerivacion,
    d.hora,
    d.motivo_ref,
    d.area_apoyo_id,
    d.observaciones,
    d.resultado_contra,
    e.apepa_estu, e.apema_estu, e.nom_estu,
    doc.abreviatura_doce, doc.apepa_doce, doc.apema_doce, doc.nom_doce, doc.email_doce
FROM tutoria_derivacion_tutorado_f6 d
INNER JOIN estudiante e ON e.id_estu = d.id_estudiante
INNER JOIN docente doc ON doc.id_doce = d.id_docente
WHERE d.estado IN ('atendido', 'pendiente')
  AND d.id_estudiante = $id_estu
";

if ($mes_num > 0) {
    $sqlDerivaciones .= " AND MONTH(d.fechaDerivacion) = $mes_num";
}

$sqlDerivaciones .= " ORDER BY d.fechaDerivacion ASC";

$resDerivaciones = $cn->query($sqlDerivaciones);

if (!$resDerivaciones) {
    die("<p style='color:red;'>ERROR SQL: " . $cn->error . "</p>");
}

$tipo_sesion = ['', 'Médico', 'Asistente Social', 'Psicólogo', 'Defensoría'];
$contador = 0;

while ($d = $resDerivaciones->fetch_assoc()) {
    $contador++;

    // Obtener carrera del docente
    $id_docente = intval($d['id_docente'] ?? 0);
    $data_nom_car = '[NO DEFINIDO]';

    if ($id_docente > 0) {
        $sqlCarrera = "SELECT c.nom_car 
                       FROM docente d
                       INNER JOIN carrera c ON d.id_car = c.id_car
                       WHERE d.id_doce = $id_docente
                       LIMIT 1";
        $resCarrera = $cn->query($sqlCarrera);

        if ($resCarrera && ($filaCar = $resCarrera->fetch_assoc())) {
            $data_nom_car = $filaCar['nom_car'] ?? '[NO DEFINIDO]';
        }
    }

    $fecha = $d['fechaDerivacion'] ?? '';
    $hora = $d['hora'] ?? date('H:i:s');
    $anio = !empty($fecha) ? date('Y', strtotime($fecha)) : date('Y');

    $tutor = '';
    $correo = '';
    if (isset($d['abreviatura_doce'], $d['apepa_doce'], $d['apema_doce'], $d['nom_doce'])) {
        $tutor = strtoupper(trim($d['abreviatura_doce'] . ' ' . $d['apepa_doce'] . ' ' . $d['apema_doce'] . ' ' . $d['nom_doce']));
        $correo = $d['email_doce'] ?? '';
    }

    $estudiante = strtoupper(($d['apepa_estu'] ?? '') . ' ' . ($d['apema_estu'] ?? '') . ' ' . ($d['nom_estu'] ?? ''));
    $motivo = htmlspecialchars($d['motivo_ref'] ?? '');
    $tipo_id = intval($d['area_apoyo_id'] ?? 0);
?>

<style>
  .documento {
    width: 210mm;
    min-height: 297mm;
    padding: 20mm;
    margin: auto;
    background: white;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
    box-sizing: border-box;
    /* page-break-after: always; */
    position: relative;
  }


footer {
  position: absolute;
  bottom: 10mm;
  left: 20mm;
  right: 20mm;
  text-align: center;
  font-size: 10px;
}
@media print {
  body {
    background-color: white !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .documento {
    background-color: white !important;
    box-shadow: none !important;
    margin: 0 !important;
    padding: 20mm !important;
    page-break-after: always;
    page-break-inside: avoid;
  }

  #btn-imprimir {
    display: none !important;
  }

  .boton-imprimir {
    display: none !important; /* ocultar ícono al imprimir */
  }

  .celda-activa {
    background-color: #50aaff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
.boton-imprimir {
  position: fixed;
  top: 20px;
  right: 20px; /* ahora esquina superior derecha */
  z-index: 9999;
  background-color: #007bff;
  color: white;
  padding: 10px 12px;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  transition: background 0.3s;
}

.boton-imprimir:hover {
  background-color: #0056b3;
}

@media print {
  .boton-imprimir {
    display: none !important;
  }
}      
</style>

<div class="documento" style="page-break-after: always;">
         <div class="boton-imprimir" id="btn-imprimir" onclick="window.print()" title="Imprimir documento">
        🖨️
        </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
        <div style="flex: 1;">
            <img src="../../img/undc.png" alt="Logo UNDC" style="width: 180px;">
        </div>

        <div style="flex: 1; text-align: right;">
            <table style="border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif; width: 100%; max-width: 300px;">
                <tr>
                    <td style="padding: 5px; text-align: center; vertical-align: middle; line-height: 1.4;">
                        <strong>Código:</strong> F-M01.04-VRA-006<br>
                        <strong>Fecha de Aprobación:</strong> 07/05/2024
                    </td>
                    <td style="border-left: 1px solid #000; text-align: center; vertical-align: middle; width: 60px;">
                        <strong>Versión:</strong><br>03
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <br><br>
    
    <h3 style="text-align:center;">ESCUELA PROFESIONAL DE <?= htmlspecialchars($data_nom_car) ?></h3>
    <h4 style="text-align:center;"><strong>HOJA DE REFERENCIA</strong></h4>
    <p style="text-align:center;"><strong>Año Académico:</strong> <?= $anio ?></p>
    <br><br>

    <pre style="font-size: 15px;">
Fecha: <?= $fecha ?>                                        Hora: <?= $hora ?> <br>

<strong>Tutor:</strong> <?= $tutor ?> <br>

<strong>Estudiante derivado:</strong> <?= $estudiante ?> <br>

<p><strong>Motivo de la referencia:</strong></p>
<p style="white-space: pre-line; font-size: 15px;"><?= $motivo ?></p>

<strong>Dirigido a:</strong><br>
<?php
for ($i = 1; $i < count($tipo_sesion); $i++) {
    echo ($i === $tipo_id ? '[X]' : '[ ]') . ' ' . $tipo_sesion[$i] . "\n";
}
?> <br>
<p style="text-align:center;"><em> Fecha: <?= $fecha ?> </em> 
<?= $tutor ?><br>
<?= $correo ?></p>
</pre>
<footer>
<p style="text-align: center; font-size:10px;"> Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
</footer>
</div>

<?php
} // fin while
if ($contador === 0) {
    echo "<p style='color:red;'>No se encontraron derivaciones atendidas para este estudiante.</p>";
}
?>
