<?php 
    #error_reporting(0);
    function FormatoRegistroTutoriaF5($id_history, $semestre) {
        //=====================================================================================
        //                                      Instancias
        //=====================================================================================
        $pdf = new PDF();
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
        $tipo_sesion = ['', 'Presencial', 'Correo electrónico', 'Telefónica', 'Google meet', 'Otra(s):'];
        //=====================================================================================
        //                                      Nueva pagina
        //=====================================================================================
        $pdf->AddPage();

        //=====================================================================================
        //                                  Grupos o Individual
        //=====================================================================================
        if ($array_data[0][11] == '2'){
            $tipo = 'Grupal';
            $rutaFormato = 'pdf/img/undc_f8.jpeg'; // formato 07
        }else {
            $tipo = 'Individual'; 
            $rutaFormato = 'pdf/img/undc_f8.jpeg'; //formato 08
        }
        //=====================================================================================
        //                                   Inicializando
        //=====================================================================================
        $pdf->Image($rutaFormato, 30, 35, 155);         
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
        for ($i=0;$i<=6;$i++){
            $imprimir = $array_data[0][$i];

            $pdf->SetY($space);
            $pdf->Cell($content, $space, utf8_decode($imprimir));
            
            $space += $espaciado;
        }
        //=====================================================================================
        //                                      Pdf bloque 2
        //=====================================================================================
        $espaciado = 5;
        $space -= 1;
        $tipo_s = $array_data[0][7];  
            
        for ($i = 1; $i < count($tipo_sesion) - 1; $i += 2) {
            $pdf->SetY($space);

            if ($tipo_s == $i) {
                $one = ' (x)';
            } else {
                $one = '( )';
            }

            if ($tipo_s == $i + 1) {
                $two = ' (x)';
            } else {
                $two = '( )';
            }

            $pdf->SetY($space);
            $pdf->Cell($content, $space, utf8_decode("           ".$tipo_sesion[$i].$one."                                 ".$tipo_sesion[$i+1].$two));

            $space += $espaciado;
        }

        $pdf->SetY($space-2);
        $space += 5;
        $pdf->MultiCell($content, $space, utf8_decode("           ".$tipo_sesion[$i]." ".$array_data[0][8]));
        //=====================================================================================
        //                                      Pdf bloque 3
        //=====================================================================================
        $pdf->SetY($space+60);

        $array_sesion = array(
                   array(
                      0 => $array_data[0][9],
                      1 => $array_data[0][10]
                   ),
                );

        $header = ['MOTIVO O ASUNTO', 'COMPROMISOS ASUMIDOS'];

        $pdf->FancyTable($header, $array_sesion, 50, 2); 
        //=====================================================================================
        //                           Pdf bloque 4 - Relacion de estudiantes
        //===================================================================================== 

        if ($array_data[0][11] == '2'){ 
            $pdf->SetX(20); 
            $pdf->SetY(270); 
    
            $pdf->Cell(0, 55, utf8_decode("RELACIÓN DE ESTUDIANTES ASISTENTES")); 
            $pdf->Ln(30);
            $pdf->Text(35, 174 + 140, utf8_decode("RELACIÓN DE ESTUDIANTES ASISTENTES")); 
           
            $array_estu = $MD->EstudiantesAsistencia($id_history);
            $header = ['N°', 'APELLIDOS Y NOMBRES', '324'];

            $pdf->FancyTable($header, $array_estu, 6, 1);
        }
        //=====================================================================================
        //                                      Pdf bloque 5
        //===================================================================================== 
        $x = 30;
        $y = 275;
        $ancho = 150;
        $alto = 10;

        date_default_timezone_set('America/Lima');

        $fecha = "Cañete " . date('d \d\e F \d\e Y');
        $pdf->Text($x+100, $y-35, utf8_decode($fecha));

        $rutaImagen = 'pdf/img/firma.png';
        $pdf->Image($rutaImagen, $x+55, $y-25, $ancho-100, $alto+10);
        //=====================================================================================
        //                                      Pdf Salida
        //=====================================================================================
        $pdf->Output();
    } 
 ?>