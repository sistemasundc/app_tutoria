<?php 
    error_reporting(0);
    function RegistroTutoria($estu, $doce, $semestre) {
        //=====================================================================================
        //                                      Instancias
        //=====================================================================================
        $pdf = new PDF();
        $MD = new Docente();
        //=====================================================================================
        //                                  Variables de control
        //=====================================================================================
        //$space = 72;
        $space = 60;
        $espaciado = 3;
        $left = 25;
        
        $content = 140;
        $contenido_left = 75;
        $caracteres = 60;
        //=====================================================================================
        //                                      Array
        //===================================================================================== 
        $array_data = $MD->DatosRegistroTutoria($estu, $doce);
        $array_estu = $MD->EstudiantesTutoria($estu, $doce,);
        $tipo_sesion = ['', 'Presencial', 'Correo electrónico', 'Telefónica', 'Google meet', 'Otra(s):'];
        //=====================================================================================
        //                                      Nueva pagina
        //=====================================================================================
        $pdf->AddPage();
        //=====================================================================================
        //                                  Envabezado
        //=====================================================================================
        $rutaFormato = 'pdf/img/undc_f8.jpeg'; // formato 07
        //=====================================================================================
        //                                   Inicializando
        //=====================================================================================
        $pdf->Image($rutaFormato, 30, 35, 155);         

        $pdf->SetY(55);
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 8, utf8_decode('ESCUELA PROFESIONAL'), 0, 1, 'C');
        $pdf->Cell(0, 9, utf8_decode('Registro de Tutoría Académica '), 0, 1, 'C');
        $pdf->SetLeftMargin($left);


        $pdf->SetRightMargin(3);
        $pdf->SetY($space-5);
        $pdf->Cell($content, $space-5, utf8_decode("Año/Semestre:"));
        $pdf->SetFont('Arial', '', 11);
        //=====================================================================================
        //                                      Pdf bloque 1
        //===================================================================================== 
        for ($i=0;$i<4;$i++){
            $imprimir = $array_data[0][$i];

            $pdf->SetY($space);
            $pdf->Cell($content, $space, utf8_decode($imprimir));
            
            $space += $espaciado;
        }
        //=====================================================================================
        //                                      Pdf bloque 2
        //=====================================================================================
        $pdf->SetY($space+10);
        $pdf->Ln(30);

        $header = ['N°', 'Actividad de Tutoría', 'Día', 'Mes', 'Hora', 'Firma del Estudiante'];

        $pdf->FancyTable($header, $array_estu, 6, 22);

        $pdf->Ln(1);
        $pdf->SetY($space+150);
        //$pdf->SetLeftMargin($left-10);
       // $pdf->Cell(28, $space, utf8_decode('Observaciones:'), 0, 1, 'C');
        

        $motivo = "Observaciones:

Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation";
        
        //=====================================================================================
        //                                      Pdf bloque 4
        //=====================================================================================
        $imprimir = "";
        $imprimir = $imprimir.$motivo;
        

        $pdf->MultiCell($content, 5, utf8_decode($imprimir));
        //=====================================================================================
        //                                      Pdf bloque 5
        //===================================================================================== 
        $x = 30;
        $y = 275;
        $ancho = 150;
        $alto = 10;

        $rutaImagen = 'pdf/img/firma.png';
        $pdf->Image($rutaImagen, $x+100, $y-25, $ancho-100, $alto+10);
        //=====================================================================================
        //                                      Pdf Salida
        //=====================================================================================
        $pdf->Output();
    } 
 ?>