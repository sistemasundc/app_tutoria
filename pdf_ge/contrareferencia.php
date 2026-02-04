<?php  
error_reporting(0);

function FormatoContraReferencia($id_history, $semestre) {
    ob_start(); 

    //=====================================================================================
    //                                      Instancias
    //=====================================================================================
    $pdf = new PDF();
    $MD = new Docente();

    //=====================================================================================
    //                                  Variables de control
    //=====================================================================================
    $left = 30;
    $tab = 10;
    $content = 160;

    //=====================================================================================
    //                                      Datos
    //===================================================================================== 
    $array_data = $MD->DatosContraReferido($id_history);
    $array_estu = $MD->EstudiantesContraReferido($id_history, $semestre);

    if (empty($array_data)) {
        echo "No se encontraron datos de contrarreferencia con ID $id_history";
        exit;
    }

    if (empty($array_estu)) {
        echo "No hay estudiantes vinculados a esta contrarreferencia ($id_history)";
        exit;
    }

    setlocale(LC_TIME, 'es_PE.UTF-8');
    date_default_timezone_set('America/Lima');
    $fecha_actual = strftime('%e de %B de %Y'); // Ejemplo: 15 de mayo de 2025

    //=====================================================================================
    //                                      Nueva página
    //=====================================================================================
    $pdf->AddPage(); 
    $rutaFormato = 'pdf/img/undc_f6.JPG';
    $pdf->Image($rutaFormato, 90, 15, 100);         

    $pdf->SetFont('Arial', 'B', 15);
    $pdf->SetY(55);
    $pdf->Cell(0, 15, utf8_decode('Hoja de Contra-Referencia'), 0, 1, 'C');

    $pdf->SetFont('Arial', '', 13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetLeftMargin($left);
    $pdf->SetRightMargin(3);
    $pdf->Ln(10); // Espaciado debajo del título

    //=====================================================================================
    //                               Fecha, Tutor, Estudiante
    //=====================================================================================
    // Fecha y hora de derivación original (cuando el tutor refirió)
    $pdf->Cell(0, 8, utf8_decode("Fecha: " . $array_data[0][0] . "     Hora: " . $array_data[0][1]), 0, 1);

    // Correo del tutor
    $pdf->Cell(0, 8, utf8_decode("Dirigido a Tutor: " . $array_data[0][6]), 0, 1);

    // Estudiante
    $pdf->Cell(0, 8, utf8_decode("Estudiante(s):"), 0, 1);
    $pdf->SetLeftMargin($left + $tab);
    $nombre_estu = isset($array_estu[0][1]) ? $array_estu[0][1] : "..........................................................";
    $pdf->Cell(0, 8, utf8_decode("1) " . $nombre_estu), 0, 1);
    $pdf->Ln(5);

    // Resultados
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetLeftMargin($left);
    $pdf->Cell(0, 8, utf8_decode("RESULTADOS:"), 0, 1);
    $pdf->MultiCell($content, 6, utf8_decode($array_data[0][3]));
    $pdf->Ln(15);

    // Área de Apoyo
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 8, utf8_decode("Área de Apoyo: " . $array_data[0][7]), 0, 1, 'L');

    // Fecha de atención del área de apoyo
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, utf8_decode("Fecha de Atención: " . $array_data[0][2]), 0, 1, 'L');

    // Responsable
    $pdf->Cell(0, 8, utf8_decode("Responsable: " . $array_data[0][4]), 0, 1, 'L');

    // Correo
    $pdf->Cell(0, 8, utf8_decode("Correo: " . $array_data[0][5]), 0, 1, 'L');

    //=====================================================================================
    //                                      Footer
    //=====================================================================================
    $x = 30;
    $y = 275;
    $ancho = 150;
    $alto = 10;
    $rutaImagen = 'pdf/img/footer.JPG';
    $pdf->Image($rutaImagen, $x, $y, $ancho, $alto);

    //=====================================================================================
    //                                      Salida
    //=====================================================================================
    ob_end_clean(); // Limpia sin enviar nada al navegador
    $pdf->Output(); 
    exit;
}
