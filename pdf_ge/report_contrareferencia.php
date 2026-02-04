<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once(__DIR__ . '/../modelo/modelo_conexion.php');
$conexion = new conexion();
$cn = $conexion->conectar();

$id_estu = intval($_GET['id_estu'] ?? 0);
if ($id_estu <= 0) {
    die('<p style="color:red;">ID de estudiante no válido.</p>');
}

$sql = "
SELECT
    d.id_derivaciones,
    d.id_estudiante,
    d.id_docente,
    d.fechaDerivacion AS fecha,
    d.hora,
    d.motivo_ref,
    d.resultado_contra,
    e.apepa_estu, e.apema_estu, e.nom_estu,
    doc.abreviatura_doce, doc.apepa_doce, doc.apema_doce, doc.nom_doce,
    ta.des_area_apo, ta.ape_encargado, ta.nombre_encargado,
    tu.cor_inst AS email_encargado
FROM tutoria_derivacion_tutorado_f6 d
INNER JOIN estudiante e ON e.id_estu = d.id_estudiante
INNER JOIN docente doc ON doc.id_doce = d.id_docente
INNER JOIN tutoria_area_apoyo ta ON ta.idarea_apoyo = d.area_apoyo_id
LEFT JOIN tutoria_usuario tu ON tu.id_usuario = ta.id_personal_apoyo
WHERE d.estado = 'atendido' AND d.id_estudiante = ?
ORDER BY d.fechaDerivacion ASC
";

$stmt = $cn->prepare($sql);
if (!$stmt) {
    die("Error en prepare: " . $cn->error);
}

$stmt->bind_param('i', $id_estu);
$stmt->execute();
$res = $stmt->get_result();

$data_contraref = [];
while ($d = $res->fetch_assoc()) {
    $data_contraref[] = $d;
}

if (empty($data_contraref)) {
    echo "<p style='color:red;'>No se encontraron contrarreferencias atendidas para este estudiante.</p>";
    exit;
}

foreach ($data_contraref as $d):
    $fecha = $d['fecha'] ?? '';
    $hora = $d['hora'] ?? '';
    $anio = !empty($fecha) ? date('Y', strtotime($fecha)) : '';
    $tutor = strtoupper(trim("{$d['abreviatura_doce']} {$d['apepa_doce']} {$d['apema_doce']} {$d['nom_doce']}"));
    $estudiante = strtoupper(trim("{$d['apepa_estu']} {$d['apema_estu']} {$d['nom_estu']}"));
    $resultado = nl2br(htmlspecialchars($d['resultado_contra'] ?? ''));
    $encargado = strtoupper(trim("{$d['ape_encargado']} {$d['nombre_encargado']}")) ?: '[NO DEFINIDO]';
    $correo_encargado = $d['email_encargado'] ?? '[NO DEFINIDO]';
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

<div class="documento" style="page-break-after: always; font-family: 'Times New Roman', serif; padding: 30px;">
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

    <h2 style="text-align: center; margin-top: 30px;">Hoja de Contra-Referencia</h2>
    <pre style="font-size: 15px;">
<strong>Fecha:</strong> <?= $fecha ?>                                   <strong>Hora:</strong> <?= $hora ?><br>
<strong>Dirigido a Tutor:</strong> <?= $tutor ?><br>

<strong>Estudiante(s):</strong><br>
    <?= $estudiante ?><br>

<p style="text-align: center;"><strong>Resultados de la contrarreferencia:</strong></p>
<div style="text-align: justify; font-size: 15px; white-space: pre-wrap;"><?= $resultado ?></div>

<br><br>

<em>Fecha: <?= $fecha ?></em><br>
<em><?= htmlspecialchars($d['des_area_apo']) ?></em><br>
<strong>Responsable del informe:</strong> <?= $encargado ?><br>
<em>Correo: <?= $correo_encargado ?></em>
    </pre>

<footer>
<p style="text-align: center; font-size:10px;">Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
</footer>
</div>

<?php endforeach; ?>
