<?php
ini_set("display_errors", 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_PE.UTF-8', 'spanish');
date_default_timezone_set('America/Lima');
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

$conexion = new conexion();
$conexion->conectar();

// ====== AUTORIZACIÓN ======
if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'TUTOR DE AULA') {
  die('Acceso no autorizado');
}
$id_doce_login = (int)$_SESSION['S_IDUSUARIO'];   // <- docente logueado (206)
$id_semestre    = (int)$_SESSION['S_SEMESTRE'];

// ====== PARÁMETROS GET ======
$id_cargalectiva = isset($_GET['id_cargalectiva']) ? (int)$_GET['id_cargalectiva'] : 0;
$mes_param = $_GET['mes'] ?? '';

$date = new DateTime('now', new DateTimeZone('America/Lima'));
$dia   = $date->format('d');
$mesEn = $date->format('F');
$anio  = $date->format('Y');

$meses = [
  'January'=>'enero','February'=>'febrero','March'=>'marzo',
  'April'=>'abril','May'=>'mayo','June'=>'junio',
  'July'=>'julio','August'=>'agosto','September'=>'septiembre',
  'October'=>'octubre','November'=>'noviembre','December'=>'diciembre'
];
$fecha_actual = $dia.' de '.$meses[$mesEn].' de '.$anio;

$meses_inverso = [4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>"septiembre",10=>"octubre",11=>"noviembre",12=>"diciembre"];
$meses_map = ['abril'=>4,'mayo'=>5,'junio'=>6,'julio'=>7,'agosto'=>8,'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12];

if (is_numeric($mes_param)) {
  $mes_num  = (int)$mes_param;
  $mesTexto = $meses_inverso[$mes_num] ?? '';
} else {
  $mesTexto = strtolower(trim($mes_param));
  $mes_num  = $meses_map[$mesTexto] ?? 0;
}
$mes_actual = ucfirst($mesTexto);

// ====== FIX 1: UPDATE RESTRINGIDO AL DOCENTE Y SEMESTRE ======
$sqlUpd = "
  UPDATE tutoria_sesiones_tutoria_f78 s
  JOIN (
      SELECT d.sesiones_tutoria_id
      FROM tutoria_detalle_sesion d
      GROUP BY d.sesiones_tutoria_id
      HAVING COUNT(*) = 1
  ) sub ON sub.sesiones_tutoria_id = s.id_sesiones_tuto
  SET s.color = '#3c8dbc'
  WHERE s.id_rol = 6
    AND s.color = '#00a65a'
    AND s.id_doce = ?
    AND s.id_semestre = ?
";
if ($stmtUpd = $conexion->conexion->prepare($sqlUpd)) {
  $stmtUpd->bind_param("ii", $id_doce_login, $id_semestre);
  $stmtUpd->execute();
}

// ====== FIX 2: VALIDAR QUE EL DOCENTE LOGUEADO ESTÁ ASIGNADO A ESA CARGA/SEMESTRE ======
$sqlCheck = "
  SELECT 1
  FROM tutoria_asignacion_tutoria
  WHERE id_carga = ?
    AND id_semestre = ?
    AND id_docente = ?
  LIMIT 1
";
$st = $conexion->conexion->prepare($sqlCheck);
$st->bind_param("iii", $id_cargalectiva, $id_semestre, $id_doce_login);
$st->execute();

/* Opción A (más compatible): usar store_result/num_rows */
$st->store_result();
$ok = ($st->num_rows > 0);
$st->free_result();

/* O, si prefieres get_result (requiere mysqlnd):
$res = $st->get_result();
$ok  = ($res && $res->num_rows > 0);
*/

if (!$ok) {
  die("El docente {$id_doce_login} no está asignado a la carga {$id_cargalectiva} en el semestre {$id_semestre}.");
}

// ====== FIX 3: DATOS DEL DOCENTE (EL LOGUEADO), CICLO Y CARRERA SEGÚN LA CARGA ======
$sqlInfo = "
  SELECT
    d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.email_doce,
    cl.ciclo, cl.id_car, ca.nom_car
  FROM docente d
  JOIN carga_lectiva cl ON cl.id_cargalectiva = ? AND cl.id_semestre = ?
  JOIN carrera ca       ON ca.id_car = cl.id_car
  WHERE d.id_doce = ?
  LIMIT 1
";
$stmtInfo = $conexion->conexion->prepare($sqlInfo);
$stmtInfo->bind_param("iii", $id_cargalectiva, $id_semestre, $id_doce_login);
$stmtInfo->execute();
$info = $stmtInfo->get_result()->fetch_assoc();
if (!$info) {
  die("No se pudo obtener la información del docente {$id_doce_login} para la carga {$id_cargalectiva}.");
}

$abreviatura      = $info['abreviatura_doce'];
$apellido_paterno = $info['apepa_doce'];
$apellido_materno = $info['apema_doce'];
$nombres          = $info['nom_doce'];
$email_doce       = $info['email_doce'];
$ciclo            = $info['ciclo'];
$id_car           = (int)$info['id_car'];
$nom_car          = $info['nom_car'];

$id_doce_informe  = $id_doce_login; // 🔒 usar SIEMPRE el logueado en todo el informe

// ====== FIX 4: OBTENER PLAN SOLO DEL DOCENTE/CARGA/SEMESTRE ======
// OJO: NO uses "--" dentro del string SQL (comenta con /* ... */ si necesitas)
$sqlPlan = "
  SELECT id_plan_tutoria
  FROM tutoria_plan_compartido
  WHERE id_cargalectiva = ?
    /* Si tu tabla tiene estas columnas, deja estas líneas;
       si no existen, bórralas del SQL y de los bind_param. */
    AND id_docente  = ?
    AND id_semestre = ?
  LIMIT 1
";

$stmtPlan = $conexion->conexion->prepare($sqlPlan);
if (!$stmtPlan) {
  die("Error al preparar SQL del plan: " . $conexion->conexion->error);
}
$stmtPlan->bind_param("iii", $id_cargalectiva, $id_doce_informe, $id_semestre);
$stmtPlan->execute();

/* Forma 1: compatible siempre (sin mysqlnd) */
$stmtPlan->store_result();
$stmtPlan->bind_result($id_plan_tutoria);
$stmtPlan->fetch();
$stmtPlan->free_result();
$stmtPlan->close();

/*  // Forma 2 (alternativa si usas mysqlnd):
$res = $stmtPlan->get_result();
$row = $res ? $res->fetch_assoc() : null;
$id_plan_tutoria = $row['id_plan_tutoria'] ?? null;
*/

if (!$id_plan_tutoria || $mes_num === 0 || $mesTexto === '') {
  die("No es posible registrar el informe. Verifique su Plan de Tutoría y el mes seleccionado.");
}

// ====== (Opcional) DOCENTES COMPARTIDOS DEL MISMO PLAN ======
$docentesCompartidos = [];
$sqlCargasCompartidas = "SELECT id_cargalectiva FROM tutoria_plan_compartido WHERE id_plan_tutoria = ?";
$stmtCargas = $conexion->conexion->prepare($sqlCargasCompartidas);
$stmtCargas->bind_param("i", $id_plan_tutoria);
$stmtCargas->execute();
$resCargas = $stmtCargas->get_result();
$cargasTutores = [];
while ($row = $resCargas->fetch_assoc()) $cargasTutores[] = (int)$row['id_cargalectiva'];

if ($cargasTutores) {
  $ids_cargas = implode(',', array_map('intval', $cargasTutores));
  $sqlDocentes = "
    SELECT d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.email_doce
    FROM carga_lectiva cl
    INNER JOIN docente d ON cl.id_doce = d.id_doce
    WHERE cl.id_cargalectiva IN ($ids_cargas)
  ";
  if ($resDocentes = $conexion->conexion->query($sqlDocentes)) {
    while ($r = $resDocentes->fetch_assoc()) $docentesCompartidos[] = $r;
  }
}

// ====== DIRECTOR DE ESCUELA ======
// ====== DIRECTOR DE ESCUELA ======
$sqlDirector = "SELECT CONCAT(grado,' ',apaterno,' ',amaterno,' ',nombres)
                FROM tutoria_usuario
                WHERE rol_id = 7 AND id_car = ?";
$stmtDirector = $conexion->conexion->prepare($sqlDirector);
$stmtDirector->bind_param("i", $id_car);
$stmtDirector->execute();

$stmtDirector->bind_result($nombre_director_escuela);
$stmtDirector->fetch();           // trae la única columna de la primera fila
$stmtDirector->close();

$nombre_director_escuela = $nombre_director_escuela ?? '';

// ====== SESIONES DEL DOCENTE (FILTRADAS POR SEMESTRE) ======
$sesionesGrupales = [];
$sesionesIndividuales = [];

$sqlSesiones = "
  SELECT
    s.id_sesiones_tuto, s.fecha, s.tema, ts.des_tipo AS modalidad, s.id_doce,
    (SELECT COUNT(*) FROM tutoria_detalle_sesion WHERE sesiones_tutoria_id = s.id_sesiones_tuto) AS asignados,
    (SELECT COUNT(*) FROM tutoria_detalle_sesion WHERE sesiones_tutoria_id = s.id_sesiones_tuto AND marcar_asis_estu = 1) AS asistentes
  FROM tutoria_sesiones_tutoria_f78 s
  INNER JOIN tutoria_tipo_sesion ts ON s.tipo_sesion_id = ts.id_tipo_sesion
  WHERE s.id_rol = 6
    AND s.id_doce = ?
    AND s.id_semestre = ?
    AND MONTH(s.fecha) = ?
    AND s.color IN ('#00a65a','#3c8dbc')
  ORDER BY s.fecha ASC
";
$stmt = $conexion->conexion->prepare($sqlSesiones);
$stmt->bind_param("iii", $id_doce_informe, $id_semestre, $mes_num);
$stmt->execute();
$resSesiones = $stmt->get_result();

while ($row = $resSesiones->fetch_assoc()) {
  $asignados  = (int)$row['asignados'];
  $asistentes = (int)$row['asistentes'];
  $row['porcentaje'] = ($asignados > 0) ? round(($asistentes/$asignados)*100,2).'%' : '0%';

  if ($asignados > 1) {
    $sesionesGrupales[] = $row;
  } else {
    $sqlEst = "
      SELECT CONCAT(e.apepa_estu,' ',e.apema_estu,' ',e.nom_estu) AS asistente
      FROM tutoria_detalle_sesion d
      INNER JOIN estudiante e ON e.id_estu = d.id_estu     -- <-- clave correcta
      WHERE d.sesiones_tutoria_id = ? 
        AND d.marcar_asis_estu = 1
      LIMIT 1
    ";

    $stmtEst = $conexion->conexion->prepare($sqlEst);
    if (!$stmtEst) {
      die("Error preparar SQL asistente: " . $conexion->conexion->error);
    }
    $stmtEst->bind_param("i", $row['id_sesiones_tuto']);
    $stmtEst->execute();

    /* Forma compatible (sin depender de mysqlnd): */
    $stmtEst->bind_result($asistente);
    $stmtEst->fetch();
    $stmtEst->close();

    $row['asistente'] = $asistente ?: '—';
    $sesionesIndividuales[] = $row;

  }
}

// ====== DERIVACIONES DEL DOCENTE (FILTRADAS POR SEMESTRE) ======
// ====== DERIVACIONES DEL DOCENTE (FILTRADAS POR SEMESTRE) ======
$derivaciones = [];

$sqlDerivaciones = "
  SELECT 
      e.apepa_estu, 
      e.apema_estu, 
      e.nom_estu,
      d.fechaDerivacion,               -- <- usar el nombre real del campo
      d.motivo_ref,
      d.resultado_contra,
      d.fecha,                         -- fecha de contrarreferencia/atención (según tu modelo)
      d.estado,
      d.observaciones,
      CONCAT(a.ape_encargado,' ',a.nombre_encargado) AS responsable,
      a.des_area_apo AS dirigido_a
  FROM tutoria_derivacion_tutorado_f6 d
  INNER JOIN estudiante e        ON e.id_estu        = d.id_estudiante   -- <- CORREGIDO
  INNER JOIN tutoria_area_apoyo a ON a.idarea_apoyo  = d.area_apoyo_id
  WHERE d.id_docente  = ?
    AND d.id_rol      = 6
    AND d.id_semestre = ?
    AND MONTH(d.fecha) = ?
  ORDER BY d.fecha ASC
";

$stmtDer = $conexion->conexion->prepare($sqlDerivaciones);
if (!$stmtDer) {
    die("Error al preparar SQL de derivaciones: " . $conexion->conexion->error);
}
$stmtDer->bind_param("iii", $id_doce_informe, $id_semestre, $mes_num);
$stmtDer->execute();

$resDer = $stmtDer->get_result();          // requiere mysqlnd; si no lo tienes, avísame y te paso la versión bind_result()
while ($row = $resDer->fetch_assoc()) {
    $derivaciones[] = $row;
}
$stmtDer->close();

// ====== CONTAR SESIONES COMPLETAS (color #00a65a) DEL MES ======
$sqlSesionesOk = "
  SELECT COUNT(*) AS total
  FROM tutoria_sesiones_tutoria_f78
  WHERE id_rol = 6
    AND id_doce = ?
    AND id_semestre = ?
    AND MONTH(fecha) = ?
    AND color = '#00a65a'
";
$stmtOk = $conexion->conexion->prepare($sqlSesionesOk);
$stmtOk->bind_param("iii", $id_doce_informe, $id_semestre, $mes_num);
$stmtOk->execute();
$rowOk = $stmtOk->get_result()->fetch_assoc();
$stmtOk->close();

$totalSesionesCompletasMes = (int)($rowOk['total'] ?? 0);


// ====== ESTADO DE ENVÍO ======
$sqlVerifica = "
  SELECT estado_envio, fecha_envio
  FROM tutoria_informe_mensual
  WHERE id_docente = ? AND id_cargalectiva = ? AND mes_informe = ?
  LIMIT 1
";
$stmtVerifica = $conexion->conexion->prepare($sqlVerifica);
$stmtVerifica->bind_param("iis", $id_doce_informe, $id_cargalectiva, $mesTexto);
$stmtVerifica->execute();
$ver = $stmtVerifica->get_result()->fetch_assoc();

$estado_envio = (int)($ver['estado_envio'] ?? 0);
$ya_enviado   = ($estado_envio === 2);
$bloqueado    = $ya_enviado;

if ($ya_enviado && !empty($ver['fecha_envio'])) {
  $fdt = new DateTime($ver['fecha_envio']);
  $fecha_mostrar = $fdt->format('d') . ' de ' . ($meses[$fdt->format('F')] ?? $fdt->format('F')) . ' de ' . $fdt->format('Y');
  $fecha_envio = $fdt->format('d/m/Y h:i A');
} else {
  $fecha_mostrar = $fecha_actual;
  $fecha_envio = '';
}

// ====== DATOS GUARDADOS PREVIAMENTE POR ESTE DOCENTE ======
$sqlInformeCargado = "
  SELECT *
  FROM tutoria_informe_mensual
  WHERE id_plan_tutoria = ?
    AND id_cargalectiva = ?
    AND id_docente = ?
    AND mes_informe = ?
  LIMIT 1
";
$stmtInfCargado = $conexion->conexion->prepare($sqlInformeCargado);
$stmtInfCargado->bind_param("iiis", $id_plan_tutoria, $id_cargalectiva, $id_doce_informe, $mesTexto);
$stmtInfCargado->execute();
$datosCargados = $stmtInfCargado->get_result()->fetch_assoc();

// ====== CAMPOS PRE-LLENADOS ======
$anio_actual = date("Y");

// 1) SI YA HAY INFORME GUARDADO PARA ESE MES, RESPETAMOS SU NÚMERO
$numeroExistente = $datosCargados['numero_informe'] ?? '';

// 2) SI NO HAY NÚMERO GUARDADO, CALCULAMOS EL CORRELATIVO
if ($numeroExistente === '' || $numeroExistente === null) {

    // IMPORTANTE: esta tabla debe tener id_semestre.
    // Si en tu BD se llama distinto, cámbialo aquí.
    $sqlNum = "
        SELECT COUNT(*) AS total
        FROM tutoria_informe_mensual
        WHERE id_docente     = ?
          AND id_cargalectiva = ?
          AND id_semestre    = ?
    ";
    $stmtNum = $conexion->conexion->prepare($sqlNum);
    $stmtNum->bind_param("iii", $id_doce_informe, $id_cargalectiva, $id_semestre);
    $stmtNum->execute();
    $rowNum = $stmtNum->get_result()->fetch_assoc();
    $stmtNum->close();

    // total informes ya registrados en el semestre
    $total = (int)($rowNum['total'] ?? 0);

    // correlativo = total + 1 → 001, 002, 003, ...
    $correlativo = $total + 1;
    $correlativoStr = str_pad($correlativo, 3, '0', STR_PAD_LEFT);

    $formato_informe = "INFORME Nº {$correlativoStr} - {$anio_actual} - UNDC/ ";
} else {
    // Si ya hay número en la BD, lo usamos tal cual
    $formato_informe = $numeroExistente;
}

// Este será el valor que se muestra en el input
$numero_informe     = $formato_informe;

$para_director      = $datosCargados['para_director']     ?? $nombre_director_escuela;
$asunto             = $datosCargados['asunto']            ?? "Informe del Programa de Tutoría Universitaria - Mes de " . $mes_actual;
$resultados_finales = $datosCargados['resultados_finales']?? '';
$logros             = $datosCargados['logros']            ?? '';
$dificultades       = $datosCargados['dificultades']      ?? '';

?>



<style>
  h3 { background-color: #17a2b8; color: white; padding: 10px; border-radius: 6px; font-weight: bold; }
  .documento { max-width: 1280px; width: 95%; margin: 30px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0px 2px 10px rgba(0,0,0,0.1);}
  input[type=text], textarea { width: 100%; padding: 5px; margin: 5px 0; }
  th, td { padding: 5px; text-align: center; } 
  table.informe-tabla { width: 100%; border-collapse: collapse; margin: 15px 0; background-color: #fff;  font-size: 14px; box-shadow: 0px 2px 5px rgba(0,0,0,0.05); border: 1px solid #ccc;}
  table.informe-tabla thead { background-color:rgb(184, 189, 219); color: black; font-weight: bold;}
  table.informe-tabla th,
  table.informe-tabla td { border: 1px solid #ccc;  padding: 8px 10px; text-align: center; vertical-align: middle;}
  table.informe-tabla tbody tr:nth-child(even) { background-color: #f9f9f9;}
  table.informe-tabla input[type="text"] { width: 100%; border: none; background: transparent; font-weight: bold; text-align: center;}
  table.informe-tabla input[readonly] { color: #333;}
  table.informe-tabla { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 14px; background: #fff; box-shadow: 0px 2px 5px rgba(0,0,0,0.05); border: 1px solid #ccc;}
  table.informe-tabla thead {  background-color: rgb(184, 189, 219); color: black; font-weight: bold;}
  table.informe-tabla th,
  table.informe-tabla td { border: 1px solid #ccc; padding: 8px 10px; text-align: center; vertical-align: middle;}
  #toast-area{ position: fixed; top: 90px;               /* <- súbelo lo que necesites (60–120px suele ir bien) */ right: 20px;z-index: 2147483647;     /* <- por si el header tiene z-index altísimo */  display: flex; flex-direction: column; gap: 10px; max-width: 360px;}
  .toast{  box-shadow: 0 8px 24px rgba(0,0,0,.15);  border-radius: 10px;  padding: 14px 16px 12px 14px;  color: #222;  background: #fff;  border-left: 6px solid #999;  animation: slideIn .25s ease-out;  position: relative;}
  .toast .title{ font-weight: 700; margin: 0 28px 6px 0; font-size: 15px;}
  .toast .msg{ margin: 0 0 10px 0; line-height: 1.35; font-size: 14px;}
  .toast .actions{display: flex; gap: 8px; justify-content:flex-end;}
  .toast .btn{  border: 0; padding: 6px 10px; border-radius: 8px; cursor: pointer;  background: #f2f2f2;}
  .toast .btn.primary{ background:#1f8bff; color:#fff; }
  .toast .close{ position:absolute; top:8px; right:10px; cursor:pointer; font-weight:700; border:none; background:transparent; font-size:16px; line-height:1;}
  .toast.info    { border-left-color:#17a2b8; }
  .toast.success { border-left-color:#28a745; }
  .toast.warn    { border-left-color:#ffc107; }
  .toast.error   { border-left-color:#dc3545; }

  @keyframes slideIn{
    from{ transform: translateY(-10px); opacity: 0; }
    to  { transform: translateY(0); opacity: 1; }
  }
</style>
<div id="toast-area"></div>
<div class="documento">
  <form action="tutor_aula/guardar_informe_mensual.php" method="POST">

    <h3>INFORME MENSUAL DE TUTORÍA</h3>

    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 10px;">
      <label style="min-width: 110px;"><b>Informe Nº:</b></label>
      <input type="text" name="numero_informe" value="<?= htmlspecialchars($numero_informe) ?>" style="flex: 1; min-width: 250px;">
    </div>
   <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 10px;">
      <label style="min-width: 110px;"><b>Para:</b> </label>
      <input 
        style="flex:1; min-width: 250px;" 
         
        type="text" 
        name="director" 
        value="<?= htmlspecialchars($para_director) ?>">
    </div>
    <p>Responsable de la Dirección de Escuela Profesional de <?= htmlspecialchars($nom_car) ?>.</p>
   <b>De:</b><br>
    <?= "$abreviatura $apellido_paterno $apellido_materno $nombres" ?><br>
    <p>
    Tutor(a) del <?= htmlspecialchars($ciclo) ?> Ciclo de la Escuela Profesional de <?= htmlspecialchars($nom_car) ?>.
    </p>
   <!-- Campo ASUNTO bien alineado y estilizado -->
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
    <label for="asunto" style="min-width: 100px; font-weight: bold;">ASUNTO:</label>
    <input 
      type="text" 
      id="asunto" 
      name="asunto" 
      value="<?= htmlspecialchars($asunto) ?>" 
      style="flex: 1 1 300px; padding: 6px; border: 1px solid #ccc; border-radius: 5px; min-width: 250px;">
    </div>
    <p><strong>FECHA:</strong> <?= ucfirst($fecha_mostrar) ?></p>
    <hr>
    <p style="text-align: justify;">
    Me es grato dirigirme a usted, a fin de saludarle cordialmente y, mediante la presente remitir a su despacho el Informe mensual del cumplimiento del Plan de tutoría universitaria, correspondiente al mes de 
    <input type="text" name="mes_informe" style="width: 100px; display: inline-block;" value="<?= $mes_actual ?>"> del ciclo académico 
    <input type="text" name="ciclo_informe" style="width: 100px; display: inline-block;" value="<?= htmlspecialchars($ciclo) ?>">.
    </p>
    <p style="text-align: justify;">Es de señalar que este Informe responde a los indicadores planteados:</p>
    <ul>
        <li>Consolidado del Registro de la Consejería grupal e individual.</li>
        <li>Consolidado de Hoja de referencia y Contrarreferencia.</li>
    </ul>

    <p style="text-align: justify;">
    Agradeciendo la atención que le brinde al presente, hago propicia la oportunidad para reiterarle las muestras de mi especial consideración y estima personal.
    </p>

    <br><br>
    <div style="text-align: center;">
        Atentamente:<br><br>

        <div style="min-width: 250px;">
          <?php if ($fecha_envio): ?>
            <p style="font-style: italic; margin-bottom: 2px;">
              Fecha de Envío: <?= htmlspecialchars($fecha_envio) ?>
            </p>
          <?php endif; ?>
          <strong><?= "$abreviatura $apellido_paterno $apellido_materno $nombres" ?></strong><br>
          <?= htmlspecialchars($email_doce) ?>
        </div>
    </div>
  

    <h3>1. OBJETIVO</h3>
    <li> Facilitar la integración y adaptación de los estudiantes a la vida universitaria, 
        apoyando su desarrollo académico y personal. </li>
    <li> Implementar asesorías individuales y grupales de manera eficiente, derivando 
        a especialistas cuando sea necesario. </li>
    <li> Utilizar datos sobre rendimiento académico, deserción y asistencia para 
        mejorar las acciones de tutoría. </li>
    <li> Evaluar periódicamente el programa de tutoría, elaborando informes 
        detallados y proponiendo mejoras para el avance educativo y profesional de 
        los estudiantes.</li>

    <h3>2. REPORTE DE ASISTENCIA, ATENCIÓN Y SEGUIMIENTO</h3>
    <!-- Tabla para 2.1 Consejería y Tutoría Académica Grupal -->
    <h4>2.1 Consejería y Tutoría Académica Grupal</h4>
      <table class="informe-tabla">
          <thead>
            <tr>
              <th>N°</th>
              <th>Fecha</th>
              <th>Tema</th>
              <th>Modalidad</th>
              <th>Asignados</th>
              <th>Asistentes</th>
              <th>% Participación</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($sesionesGrupales)): ?>
              <?php foreach ($sesionesGrupales as $i => $sesion): ?>
                  <tr>
                      <td><?= $i + 1 ?></td>
                      <td><?= htmlspecialchars($sesion['fecha']) ?></td>
                      <td><?= htmlspecialchars($sesion['tema']) ?></td>
                      <td><?= htmlspecialchars($sesion['modalidad']) ?></td>
                      <td><?= $sesion['asignados'] ?></td>
                      <td><?= $sesion['asistentes'] ?></td>
                      <td><?= $sesion['porcentaje'] ?></td>
                  </tr>
              <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5">No se registraron sesiones individuales en este mes.</td>
                </tr>
             <?php endif; ?>
          </tbody>
      </table>

 <h4>2.2 Consejería y Tutoría Académica Individual</h4>
    <table class="informe-tabla">
      <thead>
        <tr>
          <th>N°</th>
          <th>Fecha</th>
          <th>Tema</th>
          <th>Modalidad</th>
          <th>Asistente</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($sesionesIndividuales)): ?>
          <?php foreach ($sesionesIndividuales as $i => $sesion): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($sesion['fecha']) ?></td>
              <td><?= htmlspecialchars($sesion['tema']) ?></td>
              <td><?= htmlspecialchars($sesion['modalidad']) ?></td>
              <td><?= htmlspecialchars($sesion['asistente']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5">No se registraron sesiones individuales en este mes.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

   <h3> 3. ALUMNOS DERIVADOS A SERVICIOS ESPECIALIZADOS</h3>
      <table class="informe-tabla">
        <thead>
          <tr>
            <th rowspan="2">Alumno</th>
            <th rowspan="2">Dirigido a<br><small>(Área de Apoyo)</small></th>
            <th colspan="2">REFERENCIA</th>
            <th colspan="3">CONTRA-REFERENCIA</th>
          </tr>
          <tr>
            <th>Fecha</th>
            <th>Motivo</th>
            <th>Fecha</th>
            <th>Responsable</th>
            <th>Resultados</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($derivaciones)): ?>
            <?php foreach ($derivaciones as $d): ?>
              <tr>
                <td><?= htmlspecialchars($d['apepa_estu'] . ' ' . $d['apema_estu'] . ' ' . $d['nom_estu']) ?></td>
                <td><?= htmlspecialchars($d['dirigido_a'] ?? 'Pendiente') ?></td>
                <td><?= htmlspecialchars($d['fechaDerivacion']) ?></td>
                <td><?= htmlspecialchars($d['motivo_ref']) ?></td>
                <td><?= ($d['estado'] === 'Pendiente') ? 'Pendiente' : htmlspecialchars($d['fecha']) ?></td>
                <td><?= ($d['estado'] === 'Pendiente') ? 'Pendiente' : htmlspecialchars($d['responsable']) ?></td>
                <td><?= ($d['estado'] === 'Pendiente' || empty($d['resultado_contra'])) ? 'Pendiente' : htmlspecialchars($d['resultado_contra']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="7">No se registraron derivaciones en este mes.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>


    <h3>4. RESULTADOS</h3>
    <textarea id="resultados_finales" name="resultados_finales" rows="4" <?= $bloqueado ? 'readonly' : '' ?> required><?= htmlspecialchars($resultados_finales) ?></textarea>

    <h3>5. LOGROS</h3>
    <textarea id="logros" name="logros" rows="4" <?= $bloqueado ? 'readonly' : '' ?> required><?= htmlspecialchars($logros) ?></textarea>

    <h3>6. DIFICULTADES</h3>
    <textarea id="dificultades" name="dificultades" rows="4" <?= $bloqueado ? 'readonly' : '' ?> required><?= htmlspecialchars($dificultades) ?></textarea>


    <h3>7. ANEXOS</h3>
    <p>- F-M01.04-VRA-006 Hoja de Referencia y Contrarreferencia</p>
    <p>- F-M01.04-VRA-007 Consejería y Tutoría Grupal</p>
    <p>- F-M01.04-VRA-008 Consejería y Tutoría Individual</p>

    <!-- INICIO de mensajes y botones -->
    <div class="actions mt-4" id="acciones-form"> <!-- <- ancla para volver aquí -->
      <div style="text-align: center; margin-top: 20px;">
        <input type="hidden" id="id_plan_tutoria" name="id_plan_tutoria" value="<?= (int)$id_plan_tutoria ?>">
        <input type="hidden" id="id_cargalectiva" name="id_cargalectiva" value="<?= (int)$id_cargalectiva ?>">

        <!-- IMPORTANTE: mes NUMÉRICO (no el texto) -->
        <input type="hidden" id="mes" name="mes" value="<?= (int)$mes_num ?>">

        <!-- (Opcional) Solo deja este hidden si NO usas el input visible arriba -->
        <input type="hidden" name="mes_informe" value="<?= $mes_actual ?>">

        <?php $totalSesionesMes = count($sesionesGrupales) + count($sesionesIndividuales); ?>
        <input type="hidden" id="total_sesiones_mes" value="<?= (int)$totalSesionesMes ?>">
        <input type="hidden" id="total_sesiones_completas_mes" value="<?= (int)$totalSesionesCompletasMes ?>">


        <?php if ($ya_enviado): ?>
          <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; 
                      padding: 15px; border-radius: 5px; text-align: center; max-width: 700px; margin: 20px auto;">
            Este informe ya fue enviado al Director de la Escuela Profesional de <strong><?= htmlspecialchars($nom_car) ?></strong>.
          </div>
        <?php else: ?>
          <!-- Botones activos si aún no ha enviado -->

          <!--   <button style="background-color: #34f123ff; color:black; pading:2px"  type="button" class="btn btn-outline-primary btn-sm" onclick="generarResumenIA()">
        <i class="fa fa-magic"></i> <strong>Autocompletar</strong>
    </button>  -->
    
          <a href="tutor_aula/vista_prev_informe_mensual.php?id_plan_tutoria=<?= $id_plan_tutoria ?>&id_cargalectiva=<?= $id_cargalectiva ?>&mes=<?= strtolower($mesTexto) ?>" class="btn btn-secondary" target="_blank">Vista Previa</a>

          <button type="submit" name="accion" value="guardar" class="btn btn-success" style="padding: 8px 20px; font-weight: bold;">
            Guardar
          </button>

          <?php if ($datosCargados): ?>
            <button type="submit" name="accion" value="enviar" class="btn btn-primary" style="padding: 8px 20px; font-weight: bold; margin-left: 10px;">
              Enviar
            </button>
          <?php else: ?>
            <button class="btn btn-primary" disabled title="Debe guardar primero para habilitar el envío" style="padding: 8px 20px; margin-left: 10px;">
              Enviar
            </button>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
 </form>
<script>
/* ====== TOAST helper ====== */
function showToast({type='info', title='Aviso', msg='', okText='OK', autoCloseMs=6500}) {
  const area = document.getElementById('toast-area');
  const box  = document.createElement('div');
  box.className = 'toast ' + type;

  box.innerHTML = `
    <button class="close" aria-label="Cerrar" title="Cerrar">✖</button>
    <p class="title">${title}</p>
    <p class="msg">${msg}</p>
    <!--<div class="actions">
      <button class="btn" data-action="dismiss">Cancelar</button>
      <button class="btn primary" data-action="ok">${okText}</button>
    </div>-->
  `;

  // Cerrar por X, Cancelar u OK
  /* box.querySelector('.close').onclick = () => area.removeChild(box);
  box.querySelector('[data-action="dismiss"]').onclick = () => area.removeChild(box);
  box.querySelector('[data-action="ok"]').onclick = () => area.removeChild(box); */

  area.appendChild(box);

  if (autoCloseMs > 0) {
    setTimeout(() => { if (box.parentNode) area.removeChild(box); }, autoCloseMs);
  }
}

/* ====== Mostrar la alerta que vino por PHP (SESSION) ====== */
<?php if (isset($_SESSION['alerta_informe'])): 
      $alerta = $_SESSION['alerta_informe'];
      unset($_SESSION['alerta_informe']);
      $titulo = 'Aviso';
      $tipo   = 'info';
      $msg    = '';

      if ($alerta === 'sin_sesiones') {
        $titulo = 'SIN SESIONES COMPLETAS';
        $tipo   = 'warn';
        $msg    = 'No se encontraron sesiones registradas para el mes seleccionado o las sesiones no están completas.';

      } elseif ($alerta === 'faltan_campos') {
        $titulo = 'FALTAN DATOS';
        $tipo   = 'error';
        $msg    = '❌ Complete todos los campos obligatorios antes de enviar el informe.';
      } elseif ($alerta === 'guardado') {
        $titulo = 'GUARDADO';
        $tipo   = 'success';
        $msg    = '✅ Informe guardado correctamente.';
      } elseif ($alerta === 'enviado') {
        $titulo = 'ENVIADO';
        $tipo   = 'success';
        $msg    = '📤 Informe enviado correctamente.';
      }
?>
showToast({
  type: '<?= $tipo ?>',
  title:'<?= $titulo ?>',
  msg:  '<?= $msg ?>'
});
<?php endif; ?>

/* ====== Validación del envío en el cliente ======
   — Si pulsan ENVIAR:
   - Verifica campos obligatorios
   - Verifica que haya al menos 1 sesión en el mes
*/
(function wireFormGuardaEnvio(){
  const form = document.querySelector('form[action="tutor_aula/guardar_informe_mensual.php"]');
  if (!form) return;

  // Localiza los botones
  const btnGuardar = form.querySelector('button[name="accion"][value="guardar"]');
  const btnEnviar  = form.querySelector('button[name="accion"][value="enviar"]');

  // IDs/names de los campos obligatorios
  const sel = {
    numero:    'input[name="numero_informe"]',
    director:  'input[name="director"]',
    asunto:    '#asunto',
    resultados:'#resultados_finales, textarea[name="resultados_finales"]',
    logros:    'textarea[name="logros"]',
    difs:      'textarea[name="dificultades"]'
  };

  function getVal(q){ const el = form.querySelector(q); return (el ? el.value.trim() : ''); }

  // Interceptar click en ENVIAR
  if (btnEnviar) {
    btnEnviar.addEventListener('click', function(ev){
      // Recuento de sesiones del mes
      const totalSesionesMes = parseInt(
        document.getElementById('total_sesiones_mes')?.value || '0',
        10
      );
      // NUEVO: sesiones COMPLETAS (color #00a65a)
      const totalSesionesCompletas = parseInt(
        document.getElementById('total_sesiones_completas_mes')?.value || '0',
        10
      );

      // Chequeo campos
      const faltan = [];
      if (!getVal(sel.numero))    faltan.push('Informe Nº');
      if (!getVal(sel.director))  faltan.push('Para');
      if (!getVal(sel.asunto))    faltan.push('Asunto');
      if (!getVal(sel.resultados))faltan.push('Resultados');
      if (!getVal(sel.logros))    faltan.push('Logros');
      if (!getVal(sel.difs))      faltan.push('Dificultades');

      if (faltan.length > 0) {
        ev.preventDefault();
        showToast({
          type: 'error',
          title:'Faltan campos',
          msg:  'Complete: ' + faltan.join(', ') + '.'
        });
        return;
      }

      // NUEVO: bloquear envío si no hay sesiones o no hay sesiones completas
      if (totalSesionesMes < 1 || totalSesionesCompletas < 1) {
        ev.preventDefault();
        showToast({
          type: 'warn',
          title:'SIN SESIONES COMPLETAS',
          msg:  'No se encontraron sesiones registradas para el mes seleccionado o las sesiones no están completas.'
        });
        return;
      }

      // Si todo ok, dejamos enviar normalmente
    });
  }


  // Feedback inmediato al GUARDAR (opcional, solo visual)
  if (btnGuardar) {
    btnGuardar.addEventListener('click', function(){
      // No prevenimos envío; el backend pondrá la alerta real.
      // Mostramos un pre-toast de "procesando" si deseas:
      // showToast({type:'info', title:'Guardando', msg:'Procesando…', autoCloseMs: 1800});
    });
  }
})();
/* ====================  AUTOCOMPLETAR IA  ================== */
  function generarResumenIA() {
    const id_carga = document.getElementById('id_cargalectiva')?.value?.trim();
    const mes = document.getElementById('mes')?.value?.trim();

    if (!mes || !id_carga) {
        alert("⚠️ Selecciona un mes válido y asegúrate de tener ID de carga lectiva.");
        return;
    }

    const formData = new FormData();
    formData.append('id_cargalectiva', id_carga);
    formData.append('mes', mes);

    fetch('tutor_aula/autocompletar_informe_ia.php', {
        method: 'POST',
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP ${res.status} - ${res.statusText}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.error) {
            alert("⚠️ " + data.error);
        } else {
            document.getElementById('resultados_finales').value = data.resultados || '';
            document.getElementById('logros').value = data.logros || '';
            document.getElementById('dificultades').value = data.dificultades || '';
        }
    })
    .catch(err => {
        console.error("Error al generar resumen con IA:", err);
        alert("❌ Ocurrió un error inesperado. Revisa la consola o comunícate con soporte.");
    });
}
// Calcular % Participación
function calcularPorcentaje() {
  document.querySelectorAll('#tabla-grupal tbody tr').forEach(tr => {
    const asignados = parseInt(tr.querySelector('.asignados').value) || 0;
    const asistentes = parseInt(tr.querySelector('.asistentes').value) || 0;
    const porcentaje = tr.querySelector('.porcentaje');
    if (asignados > 0) {
      porcentaje.value = ((asistentes / asignados) * 100).toFixed(2) + '%';
    } else {
      porcentaje.value = '0%';
    }
  });
}

// Eventos
const inputs = document.querySelectorAll('.asignados, .asistentes');
inputs.forEach(input => input.addEventListener('input', calcularPorcentaje));
</script>
</div>
