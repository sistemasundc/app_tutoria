<?php
// === Formato HTML para la Hoja de Contra-Referencia ===
function FormatoContraReferencia($id_history, $semestre) {
    $MD = new Docente();
    $array_data = $MD->DatosContraReferido($id_history);
    $array_estu = $MD->EstudiantesContraReferido($id_history, $semestre);

    if (empty($array_data)) return "<p style='color:red;'>No se encontraron datos de contrarreferencia con ID $id_history</p>";

    $nombre_estu = isset($array_estu[0][1]) ? $array_estu[0][1] : "..........................................................";

    $html = "";
    $html .= "<div style='page-break-before:always; padding: 30px; font-family: Arial, sans-serif;'>";
    $html .= "<h2 style='text-align:center;'>Hoja de Contra-Referencia</h2>";
    $html .= "<p><strong>Fecha:</strong> {$array_data[0][0]} &nbsp;&nbsp; <strong>Hora:</strong> {$array_data[0][1]}</p>";
    $html .= "<p><strong>Dirigido a Tutor:</strong> {$array_data[0][5]}</p>";
    $html .= "<p><strong>Estudiante(s):</strong><br>&nbsp;&nbsp;&nbsp;&nbsp;1) {$nombre_estu}</p>";
    $html .= "<h4>Resultados de la contrarreferencia:</h4>";
    $html .= "<p style='text-align:justify;'>{$array_data[0][2]}</p>";
    $html .= "<p><strong>Área de Apoyo:</strong> {$array_data[0][6]}</p>";
    $html .= "<p><strong>Fecha:</strong> {$array_data[0][0]} - <strong>Hora:</strong> {$array_data[0][1]}</p>";
    $html .= "<p><strong>Responsable:</strong> {$array_data[0][3]}</p>";
    $html .= "<p><strong>Correo:</strong> {$array_data[0][4]}</p>";
   /*  $html .= "<br><br><div style='text-align:center; font-style: italic;'>Este documento ha sido generado como vista previa de la contrarreferencia.</div>"; */
    $html .= "</div>";

    return $html;
    $output .= '
    <div style="margin-top: 40px; text-align: center;">
        <img src="pdf/img/footer.JPG" alt="footer" style="width: 80%; max-width: 700px;">
    </div>
    ';
}
