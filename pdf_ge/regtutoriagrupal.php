<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Anexo Registro Consejería y Tutoría</title>
  <style>
    @page {
      size: A4;
      margin: 20mm 25mm 20mm 25mm;
    }

    html,
    body {
      height: 100%;
      margin: 0;
      padding: 0;
      background: transparent;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 12pt;
      color: #000;
      -webkit-print-color-adjust: exact;
    }

    .page {
      width: 210mm;
      min-height: 297mm;
      padding: 20mm 25mm;
      margin: 10mm auto;
      box-sizing: border-box;
      border: 1px solid #000;
      /* Borde visible */
      background: transparent;
      /* Sin fondo */
      page-break-after: always;
    }

    h3,
    h4 {
      margin: 0 0 8px 0;
    }

    h3 strong,
    h4 strong {
      font-weight: bold;
    }

    h3 {
      font-size: 16pt;
    }

    h4 {
      font-size: 14pt;
    }

    p {
      margin: 6px 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }

    th,
    td {
      border: 1px solid #000;
      padding: 6px 8px;
    }

    th {
      background-color: #61841f;
      color: antiquewhite;
    }

    td {
      height: 60px;
      vertical-align: top;
    }

    .center {
      text-align: center;
    }

    .right {
      text-align: right;
    }

    .indent {
      margin-left: 30px;
    }
  </style>
</head>

<body>
  <div class="page">
    <table style="width: 100%; margin-bottom: 20px; border-collapse: collapse; border: none;">
      <tr>
        <td style="width: 180px; border: none;;">
          <img src="https://repositorio.undc.edu.pe/logo.png" alt="Logo Universidad Nacional de Cañete" style="width: 180px; height: auto;" />
        </td>
        <td style="border: none; text-align: left; vertical-align: top; padding: 0;">
          <table style="border-collapse: collapse; font-size: 12px; border: 1px solid #000; width: auto; margin: 0;">
            <tr>
              <td style="background-color: #b6d7a8; padding: 4px 8px; border: 1px solid #000; white-space: nowrap; vertical-align: middle;">
                Código: F-M01.04-VRA-007<br />
                Fecha de Aprobación: 07-05-2024
              </td>
              <td style="background-color: #b6d7a8; padding: 4px 8px; border: 1px solid #000; width: 80px; text-align: center; white-space: nowrap; vertical-align: middle;">
                Versión: 02
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>




    <h3 class="center"><strong>ANEXO N° 2: REGISTRO DE LA CONSEJERÍA Y TUTORÍA ACADÉMICA GRUPAL</strong></h3>
    <h3 class="center" style="color:#61841f;"><strong>Registro de la Consejería y Tutoría Académica Grupal</strong></h3>

    <h4>I. DATOS INFORMATIVOS</h4>
    <p>Escuela Profesional de ......................................................................................................................</p>
    <p>Número de estudiantes asistentes .................................................................................................</p>
    <p>Ciclo ................................................................. Turno .................................................................</p>
    <p>Tutor ......................................................................................................................................................</p>
    <p>Semestre académico: 202__ - __</p>
    <p>Fecha de reunión ......................................................... Hora .......................................................</p>

    <p>Forma de Consejería y Tutoría Académica</p>
    <p class="indent">☐ Presencial</p>
    <p class="indent">☐ Otra(s): ...........................................................................................................................</p>

    <table>
      <tr>
        <th>TEMA TRATADO</th>
        <th>COMPROMISOS ASUMIDOS</th>
      </tr>
      <tr>
        <td></td>
        <td></td>
      </tr>
    </table>

    <h4>II. RELACIÓN DE ESTUDIANTES ASISTENTES</h4>
    <table>
      <tr>
        <th style="width: 50px;">N°</th>
        <th>APELLIDOS Y NOMBRES</th>
        <th style="width: 150px;">FIRMA</th>
      </tr>
      <?php for ($i = 1; $i <= 5; $i++): ?>
        <tr>
          <td class="center"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></td>
          <td></td>
          <td></td>
        </tr>
      <?php endfor; ?>
    </table>

    <p class="right">Cañete, .... de .................. de 202_</p>

    <div class="center" style="margin-top: 10px;">
      <p><strong>(Fecha y hora de registro)</strong></p>
      <p><strong>(Nombre y apellido del tutor)</strong></p>
      <p><strong>Correo institucional</strong></p>
    </div>

    <h4>EVIDENCIAS</h4>
    <p>(Adjuntar fotografías, capturas, materiales, etc.)</p>
  </div>
</body>

</html>