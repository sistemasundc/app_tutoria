<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

if (!isset($data_contraref) || empty($data_contraref)) {
    return;
}

$tipo_sesion = ['', 'Médico', 'Asistente Social', 'Psicólogo', 'Defensoría'];

foreach ($data_contraref as $d): 
    $fecha = isset($d['fechaDerivacion']) ? $d['fechaDerivacion'] : '';
    $hora = isset($d['hora']) ? $d['hora'] : '';
    $anio = !empty($fecha) ? date('Y', strtotime($fecha)) : '';
    
    $tutor = isset($d['nombre_tutor']) ? strtoupper($d['nombre_tutor']) : '';
    $area_id = isset($d['area_apoyo_id']) ? intval($d['area_apoyo_id']) : 0;
    
    $estudiante = isset($d['nombre_estudiante']) ? strtoupper($d['nombre_estudiante']) : '';
    $resultado = isset($d['resultado_contra']) ? nl2br(htmlspecialchars($d['resultado_contra'])) : '';

    // ✅ Datos del responsable del área de apoyo (ya vienen desde la consulta principal)
    $encargado = '[NO DEFINIDO]';
    if (!empty($d['ape_encargado']) && !empty($d['nombre_encargado'])) {
        $encargado = $d['ape_encargado'] . ' ' . $d['nombre_encargado'];
    }
    $correo_encargado = $d['email_encargado'] ?? '';
?>
<style>
    .documento {
  width: 210mm;
  min-height: 297mm;
  padding: 20mm;
  margin: auto;
  background: white;
  box-shadow: 0 0 5px rgba(0,0,0,0.1);
  position: relative; /* necesario */
  box-sizing: border-box;
}

footer {
  position: absolute;
  bottom: 10mm;
  left: 20mm;
  right: 20mm;
  text-align: center;
  font-size: 10px;
}
</style>
<div class="documento" style="page-break-after: always; font-family: 'Times New Roman', serif; padding: 30px;">
   <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
        <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
        </div>
        <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
            <tr>
                <td style="padding: 5px 8px; width: 70px; text-align: center; vertical-align: middle; line-height: 1.4;">
                <strong>Código:</strong> F-M01.04-VRA-006<br>
                <strong>Fecha de Aprobación:</strong> 07/05/2024
                </td>
                <td style="border-left: 1px solid #000; width: 31px; text-align: center; vertical-align: middle;">
                <strong>Versión: 03</strong>
                </td>
            </tr>
        </table>
    </div>
    <br><br>
    <div style="clear: both;"></div>

    <h2 style="text-align: center; margin-top: 30px;">Hoja de Contra-Referencia</h2>
    <br>
    <pre style=" font-size: 15px;">
        <strong>Fecha:</strong> <?= $fecha ?>                                   <strong>Hora:</strong> <?= $hora ?><br>
        <strong>Dirigido a Tutor:</strong> <?= $tutor ?><br>

        <strong>Estudiante(s):</strong><br>
            <?= $estudiante ?><br>
        
        <p style="text-align: center;"><strong>Resultados de la contrarreferencia:</strong></p>
            <div style="text-align: center;font-size: 15px; white-space: pre-wrap;"><?= $resultado ?></div>

        <br><br>

        <em>Fecha: <?= $fecha ?></em><br>
        <strong>Responsable del informe:</strong> <?= $encargado ?><br>
        <em>Correo: <?= $correo_encargado ?></em>
    </pre>
     <footer>
       <p style="text-align: center; font-size:10px;"> Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
    </footer>
</div>
<?php endforeach; ?>