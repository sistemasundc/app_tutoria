<?php  

    #error_reporting(0);

    function FormatoSesionesTutoria($id_history, $semestre) {
        //=====================================================================================
        //                                      Instancias
        //=====================================================================================
        $pdf = new PDF();
            function insertarImagenSeguro($pdf, $ruta, $x, $y, $w = 70) {
                if (file_exists($ruta)) {
                    $info = @getimagesize($ruta);
                    if ($info && in_array($info['mime'], ['image/jpeg', 'image/png', 'image/gif'])) {
                        $pdf->Image($ruta, $x, $y, $w);
                    } else {
                        $pdf->SetFont('Arial', '', 10);
                        $pdf->Cell(0, 8, utf8_decode("❌ Formato inválido: " . basename($ruta)), 0, 1);
                    }
                } else {
                    $pdf->SetFont('Arial', '', 10);
                    $pdf->Cell(0, 8, utf8_decode("❌ No encontrado: " . basename($ruta)), 0, 1);
                }
            }
        $MD = new Docente();
        //=====================================================================================
        //                                  Variables de control
        //=====================================================================================
        //$space = 72;
        $space = 58;
        $espaciado = 6;
        $salto = 6;
        $rigth = 4;
        $left = 40;
        $datos = 7;
        
        $content = 140;
        $contenido_left = 75;
        $caracteres = 60;
        //=====================================================================================
        //                                      Array
        //===================================================================================== 
        $array_data = $MD->DatosFormatoAsistenci($id_history, $semestre);
        $tipo_sesion = [
            1 => 'Presencial',
            4 => 'Google meet',
            5 => 'Otra(s):'
        ];
        //=====================================================================================
        //                                      Nueva pagina
        //=====================================================================================
        $pdf->AddPage();

        //=====================================================================================
        //                                  Grupos o Individual
        //=====================================================================================
        if ($array_data[0][12] == '2'){
            $tipo = 'Grupal';
            $rutaFormato = 'pdf/img/undc_f7.JPG'; // formato 07
            $pdf->Image($rutaFormato,  90, 15, 100);
        }else {
            $tipo = 'Individual'; 
            $rutaFormato = 'pdf/img/undc_f8.JPG'; //formato 08
            $pdf->Image($rutaFormato,  90, 15, 100);
        } 
        //=====================================================================================
        //                                   Inicializando
        //===================================================================================== 
        $pdf->SetTextColor(45,125,87);
        $pdf->SetY(55);
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 8, utf8_decode('Registro de la Consejería y Tutoría'), 0, 1, 'C');
        $pdf->Cell(0, 8, utf8_decode('Académica '.$tipo), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetY(74); 
        
        $pdf->Cell(90, 8, utf8_decode('I.      DATOS INFORMATIVOS'), 0, 1, 'C'); 
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetLeftMargin($left);
        $pdf->SetRightMargin(3);
        //=====================================================================================
        //                                      Pdf bloque 1
        //===================================================================================== 
        $indices_bloque1 = [0, 1, 2, 3, 5, 6];
        foreach ($indices_bloque1 as $i) {
            $imprimir = $array_data[0][$i];
            $pdf->SetY($space);
            $pdf->Cell($content, $space, utf8_decode($imprimir));
            $space += $espaciado;
        }
        //=====================================================================================
        //                                      Pdf bloque 2
        //=====================================================================================
        // ======================= BLOQUE 2 - Tipo de sesión (CORREGIDO) =========================
        $space += 50;  // <<< Ajuste para mover más abajo esta parte
        $espaciado = 5;
        $space -= 1;
        $tipo_s = $array_data[0][8];  // El ID del tipo de sesión (1, 4, 5)

        $linea = "           ";
        foreach ($tipo_sesion as $id => $label) {
            $checked = ($tipo_s == $id) ? "(x)" : "( )";
            $linea .= $label . " " . $checked . "     ";
        }
        $pdf->SetY($space);
        $pdf->MultiCell($content, $espaciado, utf8_decode($linea));
        $space += $espaciado;
        //=====================================================================================
        //                                      Pdf bloque 3
        //=====================================================================================
        $len1 = strlen($array_data[0][10]);
        $len2 = strlen($array_data[0][11]);
        #$pdf->Cell(90, 8, utf8_decode('Logitud 1: '.$len1.' Longitud 2: '.$len2), 0, 1, 'C');
        if ($len1 <= 400 && $len2 <= 400){
            $pdf->SetY($space+10);
        }else {
            
            $pdf->AddPage();
            $space = 40;
            $pdf->SetY($space);
        }
    
        $array_sesion = array(
                   array(
                      0 => $array_data[0][10],
                      1 => $array_data[0][11]
                   ),
                );

        $header = ['MOTIVO O ASUNTO', 'COMPROMISOS ASUMIDOS'];

        $pdf->FancyTable($header, $array_sesion, 50, 2); 
        //=====================================================================================
        //                           Pdf bloque 4 - Relacion de estudiantes
        //===================================================================================== 
        if ($array_data[0][12] == '2') {
            $pdf->SetX(25); 
            $pdf->SetY(270); 
            $pdf->Cell(0, 55, utf8_decode("RELACIÓN DE ESTUDIANTES ASISTENTES")); 
            $pdf->Ln(30);
            $pdf->Text(35, 174 + 140, utf8_decode("RELACIÓN DE ESTUDIANTES ASISTENTES")); 

            // Esta función ya trae solo a los que tienen asistencia = 1
            $array_estu = $MD->EstudiantesAsistencia($id_history);
            $header = ['N°', 'APELLIDOS Y NOMBRES'];

            $pdf->FancyTable($header, $array_estu, 6, 1);
        }
        //=====================================================================================
        //                                      Pdf bloque 5
        //===================================================================================== 
    

        //=====================================================================================
        //                             Footer formato (ajustado para F7 y F8 también)
        //=====================================================================================
        $x_footer = 20;
        $y_footer = 275;
        $ancho_footer = 150;
        $alto_footer = 10;

        $rutaImagen = 'pdf/img/footer.JPG'; // mismo para F7, F8, si deseas puedes cambiar dinámicamente según el formato
        insertarImagenSeguro($pdf, __DIR__ . "/$rutaImagen", $x_footer, $y_footer, $ancho_footer);

        // ====================== Pie final con datos del tutor =========================
        $tutor = $array_data[0][3];      // Tutor (nombre completo)
        $correo = $array_data[0][4];     // Email (nuevo índice después del cambio)
        $fecha_registro = $array_data[0][5];  // Esto es 'Fecha de reunión: ...'
        $hora_inicio = $array_data[0][6];     // Esto incluye la hora (ya formateada)

        // Puedes desglosar si quieres más preciso, pero ya están completos arriba

        // Ajustar posición final
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetY(230);
        $pdf->Cell(0, 5, utf8_decode( $hora_inicio), 0, 1, 'C');
        $pdf->Cell(0, 5, utf8_decode($tutor), 0, 1, 'C');
        $pdf->Cell(0, 5, utf8_decode("Correo: $correo"), 0, 1, 'C');


        //=====================================================================================
        //                                    EVIDENCIAS DE LA SESION
        //=====================================================================================
        $evi1 = $array_data[0][13] ?? null;
        $evi2 = $array_data[0][14] ?? null;

        if (!empty($evi1) || !empty($evi2)) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Ln(10);
            $pdf->Cell(120, 40, utf8_decode('EVIDENCIAS'), 0, 1, 'C');
            $pdf->Ln(10);

            if (!empty($evi1) && empty($evi2)) {
                // Solo una imagen -> centrada y grande
                $ruta1 = __DIR__ . '/../' . $evi1;
                insertarImagenSeguro($pdf, $ruta1, 30, 55, 150); // Y = 80 centrado vertical, ancho 150
            }

            if (!empty($evi1) && !empty($evi2)) {
                // Dos imágenes -> una arriba, otra abajo
                $ruta1 = __DIR__ . '/../' . $evi1;
                $ruta2 = __DIR__ . '/../' . $evi2;

                insertarImagenSeguro($pdf, $ruta1, 30, 60, 140);  // Imagen 1 arriba
                insertarImagenSeguro($pdf, $ruta2, 30, 155, 140); // Imagen 2 más abajo
            }
        }


        //=====================================================================================
        //                                      Pdf Salida
        //=====================================================================================
        $pdf->Output(); 
        ob_end_flush(); // <- Limpia el buffer y permite mostrar el PDF
        exit; // <- Siempre ciérralo
    } 
 ?>