<?php  
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'TUTOR DE AULA') {
    die('Acceso no autorizado');
}

$conexion = new conexion();
$conexion->conectar();
$id_doce = $_SESSION['S_IDUSUARIO']; 
$semestre = $_SESSION['S_SEMESTRE'];

/* =========================
 * TABLA DE CUMPLIMIENTO (rol 6) 
 * ========================= */
$cn    = $conexion->conexion;
$idDoc = (int)$id_doce;
$semId = (int)$semestre;

/* ----- Nombre del docente ----- */
$docenteNombre = '—';
$stmtDoc = $cn->prepare("
  SELECT TRIM(CONCAT(UPPER(COALESCE(abreviatura_doce,'')),' ', apepa_doce,' ', apema_doce,' ', nom_doce)) AS nom
  FROM docente WHERE id_doce = ? LIMIT 1
");
$stmtDoc->bind_param("i", $idDoc);
$stmtDoc->execute();
if ($r = $stmtDoc->get_result()->fetch_assoc()) { $docenteNombre = $r['nom']; }
$stmtDoc->close();

/* ----- Escuela (si ya la cargaste arriba, úsala; si no, la buscamos) ----- */
if (empty($escuela)) {
  $stmtEsc = $cn->prepare("
    SELECT c.nom_car AS escuela
    FROM tutoria_asignacion_tutoria ta
    JOIN carga_lectiva cl ON cl.id_cargalectiva = ta.id_carga
    JOIN carrera c ON c.id_car = cl.id_car
    WHERE ta.id_docente = ? AND ta.id_semestre = ?
    LIMIT 1
  ");
  $stmtEsc->bind_param("ii",$idDoc,$semId);
  $stmtEsc->execute();
  if ($re = $stmtEsc->get_result()->fetch_assoc()) { $escuela = $re['escuela']; }
  $stmtEsc->close();
}

/* ----- Meses del semestre (auto) -----
   Si el mes actual ∈ [3..8] => 1° semestre (mar-ago); si no => 2° (sep-dic).
   Cambia $usaPrimSem si deseas forzarlo. */
$mesActual = (int)date('n');
$usaPrimSem = ($mesActual >= 3 && $mesActual <= 8);

$MESES = $usaPrimSem
  ? ['marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,'julio'=>7,'agosto'=>8]
  : ['septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12];

/* ----- Badges ----- */
function badgeOK($txt){ return '<span class="pill ok">'.$txt.'</span>'; }
function badgeNO($txt){ return '<span class="pill no">'.$txt.'</span>'; }

/* ----- PLAN (estado_envio=2) ----- */
$planEnviado = 0;
$stmt = $cn->prepare("
  SELECT 1 FROM tutoria_plan_compartido
  WHERE id_docente = ? AND id_semestre = ? AND estado_envio = 2 LIMIT 1
");
$stmt->bind_param("ii",$idDoc,$semId);
$stmt->execute();
$planEnviado = $stmt->get_result()->num_rows > 0 ? 1 : 0;
$stmt->close();

/* ----- SESIONES por mes (rol=6, color #00a65a) ----- */
$sesionesMes = [];
$stmtSes = $cn->prepare("
  SELECT COUNT(*) AS c
  FROM tutoria_sesiones_tutoria_f78
  WHERE id_doce = ?
    AND id_rol = 6
    AND id_semestre = ?
    AND color = '#00a65a'
    AND MONTH(fecha) = ?
");
foreach($MESES as $nombre=>$nro){
  $stmtSes->bind_param("iii",$idDoc,$semId,$nro);
  $stmtSes->execute();
  $r = $stmtSes->get_result()->fetch_assoc();
  $sesionesMes[$nombre] = (int)($r['c'] ?? 0);
}
$stmtSes->close();

/* ----- INFORME mensual (estado_envio=2) ----- */
$informeMes = [];
$stmtInf = $cn->prepare("
  SELECT 1
  FROM tutoria_informe_mensual
  WHERE id_docente = ?
    AND id_semestre = ?
    AND estado_envio = 2
    AND LOWER(mes_informe) = ?
  LIMIT 1
");
foreach($MESES as $nombre=>$nro){
  $mesLower = strtolower($nombre);
  $stmtInf->bind_param("iis",$idDoc,$semId,$mesLower);
  $stmtInf->execute();
  $informeMes[$nombre] = $stmtInf->get_result()->num_rows > 0 ? 1 : 0;
}
$stmtInf->close();

/* ----- INFORME FINAL (estado_envio=2) ----- */
$informeFinal = 0;
$stmtF = $cn->prepare("
  SELECT 1 FROM tutoria_informe_final_aula
  WHERE id_doce = ? AND semestre_id = ? AND estado_envio = 2
  LIMIT 1
");
$stmtF->bind_param("ii",$idDoc,$semId);
$stmtF->execute();
$informeFinal = $stmtF->get_result()->num_rows > 0 ? 1 : 0;
$stmtF->close();

/* ----- Anchos proporcionales: caben sin scrollbar ----- */
$totalCols = 1 + (count($MESES)*2) + 1; // PLAN + (2x mes) + FINAL
$colW = round(100 / $totalCols, 2);

//=======================================================================================
$escuela = '';

// TRAE TODAS LAS ASIGNACIONES DEL TUTOR EN ESTE SEMESTRE
$sql = "
SELECT 
    pc.estado_envio,
    COALESCE(rd.estado_revision, 0) AS estado_revision,
    c.nom_car AS escuela
FROM tutoria_docente_asignado tda
INNER JOIN tutoria_asignacion_tutoria tat 
    ON tat.id_docente = tda.id_doce AND tat.id_semestre = tda.id_semestre
INNER JOIN tutoria_plan_compartido pc 
    ON pc.id_cargalectiva = tat.id_carga
LEFT JOIN tutoria_revision_director rd 
    ON rd.id_plan_tutoria = pc.id_plan_tutoria
INNER JOIN carga_lectiva cl 
    ON cl.id_cargalectiva = tat.id_carga
INNER JOIN carrera c 
    ON c.id_car = cl.id_car
WHERE tda.id_doce = ?
  AND tda.id_semestre = ?
";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("ii", $id_doce, $_SESSION['S_SEMESTRE']);
$stmt->execute();
$res = $stmt->get_result();


//CARGA LECTIVA
// CARGA LECTIVA AGRUPADA POR CICLO
$sql = "
SELECT
    cl.ciclo,
    -- texto unido: TURNO X — SECCION | TURNO Y — SECCION ...
    GROUP_CONCAT(
        DISTINCT CONCAT('TURNO ', cl.turno, ' — ', cl.seccion)
        ORDER BY FIELD(cl.turno,'MAÑANA','TARDE','NOCHE'), cl.seccion
        SEPARATOR '  |  '
    ) AS aulas,
    -- usaremos una carga “representante” del ciclo para los botones
    MIN(cl.id_cargalectiva) AS id_cargalectiva_ref
FROM tutoria_asignacion_tutoria ta
JOIN carga_lectiva cl ON cl.id_cargalectiva = ta.id_carga
WHERE ta.id_docente = ? AND ta.id_semestre = ?
GROUP BY cl.ciclo
ORDER BY CASE cl.ciclo
    WHEN 'I' THEN 1 WHEN 'II' THEN 2 WHEN 'III' THEN 3 WHEN 'IV' THEN 4
    WHEN 'V' THEN 5 WHEN 'VI' THEN 6 WHEN 'VII' THEN 7 WHEN 'VIII' THEN 8
    WHEN 'IX' THEN 9 WHEN 'X' THEN 10 ELSE 99 END
";
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("ii", $id_doce, $semestre);
$stmt->execute();
$result = $stmt->get_result();
$asignaturas = $result->fetch_all(MYSQLI_ASSOC);


// CONSULTAR INFORMES MENSUALES YA ENVIADOS
$informesMensuales = [];

$sqlInf = "
    SELECT im.id_plan_tutoria, pc.id_cargalectiva, im.mes_informe
    FROM tutoria_informe_mensual im
    INNER JOIN tutoria_plan_compartido pc ON im.id_plan_tutoria = pc.id_plan_tutoria
    WHERE im.estado_envio = 2
    ORDER BY FIELD(LOWER(im.mes_informe), 'enero','febrero','marzo','abril','mayo','junio','julio','agosto','setiembre','octubre','noviembre','diciembre') DESC
";

$resInf = $conexion->conexion->query($sqlInf);
$informesMensuales = [];

if ($resInf) {
    while ($row = $resInf->fetch_assoc()) {
        $id_carga = $row['id_cargalectiva'];
        if (!isset($informesMensuales[$id_carga])) {
            $informesMensuales[$id_carga] = [
                'mes' => strtolower(trim($row['mes_informe'])),
                'id_plan' => $row['id_plan_tutoria']
            ];
        }
    }
}
//CONSULTA INFOREME MENSUAL
// Verificar si el informe mensual fue enviado
$sqlEstadoInf = "SELECT estado_envio FROM tutoria_informe_mensual 
                 WHERE id_plan_tutoria = ? AND mes_informe = ? 
                 AND estado_envio = 2 LIMIT 1";
$stmtEstadoInf = $conexion->conexion->prepare($sqlEstadoInf);
$stmtEstadoInf->bind_param("is", $id_plan_tutoria, $mes); // $mes = 'Mayo' (como lo usas en la URL)
$stmtEstadoInf->execute();
$resultInf = $stmtEstadoInf->get_result();
$hayInformeEnviado = $resultInf->num_rows > 0;

//CONSULTA INFOREME FINAL
$informesFinales = [];

$sqlFinal = "SELECT id_cargalectiva FROM tutoria_informe_final_aula 
             WHERE id_doce = ? AND semestre_id = ? AND estado_envio = 2";

$stmtFinal = $conexion->conexion->prepare($sqlFinal);
if ($stmtFinal === false) {
    die("Error en prepare: " . $conexion->conexion->error);
}

// "ii" → ambos son enteros (id_doce, semestre)
if (!$stmtFinal->bind_param("ii", $id_doce, $semestre)) {
    die("Error en bind_param: " . $stmtFinal->error);
}

if (!$stmtFinal->execute()) {
    die("Error en execute: " . $stmtFinal->error);
}

$resFinal = $stmtFinal->get_result();
$informesFinales = [];

while ($row = $resFinal->fetch_assoc()) {
    $informesFinales[] = intval($row['id_cargalectiva']);
}

$stmtFinal->close();

while ($row = $resFinal->fetch_assoc()) {
    $informesFinales[] = intval($row['id_cargalectiva']);
}
?>


<style>
    .contenedor-asignaturas { max-width: 1100px;   margin: 40px auto;    padding: 0 25px; }
    .titulo-asignaturas {     background-color: rgb(65, 117, 196);   color: white;   padding: 12px 25px;    border-radius: 10px 10px 0 0;    font-size: 14px;    font-weight: 600;   display: flex; align-items: center; gap: 10px;  width: 100%;  box-sizing: border-box; }
    .bloque-asignatura {  background: #fff;   border-radius: 0 0 10px 10px;   box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);  padding: 25px 30px;  margin-bottom: 30px; display: flex;   justify-content: space-between;    align-items: center;  gap: 20px; flex-wrap: wrap; }
    .contenido-asig {   flex: 1;   display: flex;  justify-content: center;  }
    .datos {   font-weight: 600;    font-size: 14px;  text-align: center;    background-color: rgba(121, 105, 105, 0.05);    padding: 12px 20px;    border-radius: 6px;    width: fit-content;}
    .acciones-total {    display: flex;    flex-direction: column;    gap: 10px;    align-items: flex-end;}
    .acciones {   display: flex;   align-items: center;   gap: 8px;}
    .acciones button,
    .acciones a {   min-width: 160px;   justify-content: center;   font-weight: bold;}
    @media (max-width: 768px) {
        .bloque-asignatura { flex-direction: column;  align-items: center;}
        .acciones-total {  width: 100%;  align-items: center;}
        .acciones {   flex-wrap: wrap;  justify-content: center;  width: 100%;}
        .acciones button,
        .acciones a {    flex: 1 1 45%;    margin-bottom: 5px;  }
    }
    .mes-selector {   display: none;    margin-top: 5px; }
    .cumplimiento-wrap{max-width:1100px;margin:25px auto 10px;padding:0 25px;}
    .cumplimiento-head{background-color: rgb(65, 117, 196);color:#fff;padding:10px 18px;border-radius:10px 10px 0 0; font-weight:700;display:flex;align-items:center;gap:10px;font-size: 14px; }
    .cumplimiento-meta{display:flex;gap:18px;flex-wrap:wrap;
        background:#f5f7fb;border:1px solid #e7eaf3;border-top:0;padding:10px 18px}
    .cumplimiento-meta b{color:#334}
    .tabla-box{border:1px solid #e0e0e0;border-top:0;border-radius:0 0 10px 10px;background:#fff}
    .tabla-cumplimiento{width:100%;border-collapse:collapse;table-layout:fixed;font-size:13px}
    .tabla-cumplimiento th,.tabla-cumplimiento td{border:1px solid #e0e0e0;padding:8px 6px;text-align:center}
    .tabla-cumplimiento thead th{background:#f0f3fa;color:#2c3e50;font-weight:700}
    .tabla-cumplimiento th small{display:block;font-weight:600}
    .pill{display:inline-block;min-width:32px;padding:2px 5px;border-radius:10px;color:#fff;font-weight:700}
    .pill.ok{background:#00a65a}
    .pill.no{background:#dd4b39}
    @media (max-width: 900px){
        .cumplimiento-wrap{padding:0 12px}
        .tabla-cumplimiento{font-size:12px}
        .pill{min-width:28px}
    }

</style>
<div class="cumplimiento-wrap">
  <div class="cumplimiento-head">
    <i class="fas fa-user-check  "></i>  TABLA DE CUMPLIMIENTO - <?= htmlspecialchars($docenteNombre) ?>
  </div>

  <div class="tabla-box">
    <table class="tabla-cumplimiento">
      <thead>
        <tr>
          <th style="width:<?= $colW ?>%">PLAN</th>
          <?php foreach($MESES as $nombre=>$n): ?>
            <th style="width:<?= $colW ?>%"><small>N° SESIONES</small><?= ucfirst($nombre) ?></th>
            <th style="width:<?= $colW ?>%"><small>INFORME</small><?= ucfirst($nombre) ?></th>
          <?php endforeach; ?>
          <th style="width:<?= $colW ?>%">INF. FINAL</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?= $planEnviado ? badgeOK('SÍ') : badgeNO('NO'); ?></td>

          <?php foreach($MESES as $nombre=>$n): ?>
            <td><?= $sesionesMes[$nombre] > 0 ? badgeOK($sesionesMes[$nombre]) : badgeNO('0'); ?></td>
            <td><?= $informeMes[$nombre] ? badgeOK('SÍ') : badgeNO('NO'); ?></td>
          <?php endforeach; ?>

          <td><?= $informeFinal ? badgeOK('SÍ') : badgeNO('NO'); ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="contenedor-asignaturas">
 <!--Mesaje de alertas para los tutores que no enviaron sus planes de tutoria -->

   
  <!--INICIO MOSTAR AUL ASIGNADA-->
    <div class="titulo-asignaturas"><i class="fa fa-chalkboard-teacher"></i> AULA TUTORADA</div>

    <?php if (count($asignaturas) === 0): ?>
        <div class="alert alert-warning mt-3">No tienes aula asignada para tutoría en este semestre.</div>
    <?php else: ?>
        <?php foreach ($asignaturas as $asig): ?>
    <div class="bloque-asignatura">
        <div class="contenido-asig">
        <div class="datos">
            CICLO <?= htmlspecialchars($asig['ciclo']) ?> — <?= htmlspecialchars($asig['aulas']) ?>
        </div>
        </div>

        <div class="acciones-total">
           <?php
            // usa la carga “representante” del ciclo para los botones
            $id_carga = (int)$asig['id_cargalectiva_ref'];
            $id_plan = null;
            $mostrarVisualizar = false;

            $sqlOjo = "
                SELECT tpc.id_plan_tutoria, tpc.estado_envio
                FROM tutoria_plan_compartido tpc
                WHERE tpc.id_cargalectiva = ?
                ORDER BY (tpc.estado_envio = 2) DESC, tpc.fecha_envio DESC, tpc.id_comp DESC
                LIMIT 1
            ";
            $stmtOjo = $conexion->conexion->prepare($sqlOjo);
            $stmtOjo->bind_param("i", $id_carga);
            $stmtOjo->execute();
            $rowOjo = $stmtOjo->get_result()->fetch_assoc();

            if ($rowOjo) {
                $id_plan = (int)$rowOjo['id_plan_tutoria'];
                $mostrarVisualizar = ((int)$rowOjo['estado_envio'] === 2);

            }

            $ultimoMes = isset($informesMensuales[$id_carga]) ? $informesMensuales[$id_carga]['mes'] : '';
            $ultimoPlan = isset($informesMensuales[$id_carga]) ? $informesMensuales[$id_carga]['id_plan'] : null;
            ?>

            <!-- PLAN DE TUTORÍA -->
            <div class="acciones">
                <button class="btn btn-primary btn-sm"
                        onclick="cargar_contenido('contenido_principal', 'tutor_aula/form_plan_tutoria.php?id_cargalectiva=<?= $id_carga ?>')">
                    <i class="fa fa-clipboard-list"></i> Plan de Tutoría
                </button>

                <?php if ($mostrarVisualizar && $id_plan): ?>
                    <button class="btn btn-outline-secondary btn-sm icono-ojo"
                            onclick="abrirVentanaPopup('tutor_aula/vista_prev_plan_tutoria.php?id_plan=<?= $id_plan ?>&id_cargalectiva=<?= $id_carga ?>'); return false;">
                    <i class="fa fa-eye"></i>
                    </button>
                <?php else: ?>
                    <button class="btn btn-outline-secondary btn-sm icono-ojo" disabled>
                    <i class="fa fa-eye"></i>
                    </button>
                <?php endif; ?>
            </div>

            <!-- INFORME MENSUAL -->
            <div class="acciones">
                <button class="btn btn-warning btn-sm" onclick="mostrarSelectMes(<?= $id_carga ?>)">
                    <i class="fa fa-calendar-alt"></i> Informe Mensual
                </button>

                <select id="meses_<?= $id_carga ?>" class="form-control form-control-sm mes-selector" onchange="verificarYMostrar(<?= $id_carga ?>, this.value)">
                    <option value="">Seleccione mes</option>
                    <!-- <option value="abril">Abril</option>
                    <option value="mayo">Mayo</option>
                    <option value="junio">Junio</option>
                    <option value="julio">Julio</option> -->
                  <!--   <option value="agosto">Agosto</option> -->
                    <option value="septiembre">Septiembre</option>
                    <option value="octubre">Octubre</option>
                    <option value="noviembre">Novienbre</option>
                    <option value="diciembre">Diciembre</option>
                </select>

                <?php if (!empty($ultimoMes) && $ultimoPlan): ?>
                    <button id="btnVer_<?= $id_carga ?>" class="btn btn-outline-secondary btn-sm"
                            onclick="abrirVentanaPopup('tutor_aula/vista_prev_informe_mensual.php?id_plan_tutoria=<?= $ultimoPlan ?>&id_cargalectiva=<?= $id_carga ?>&mes=<?= strtolower($ultimoMes) ?>'); return false;">
                        <i class="fa fa-eye"></i>
                    </button>
                <?php else: ?>
                    <button id="btnVer_<?= $id_carga ?>" class="btn btn-outline-secondary btn-sm" disabled>
                        <i class="fa fa-eye"></i>
                    </button>
                <?php endif; ?>
            </div>

            <!-- INFORME FINAL -->
            <div class="acciones">
                <button class="btn btn-success btn-sm" onclick="cargar_contenido('contenido_principal', 'tutor_aula/form_informe_final.php?id_cargalectiva=<?= $id_carga ?>')">
                    <i class="fa fa-file-alt"></i> Informe Final
                </button>

                <?php if (in_array($id_carga, $informesFinales)): ?>
                    <button class="btn btn-outline-secondary btn-sm"
                            onclick="abrirVentanaPopup('tutor_aula/vista_prev_informe_final.php?id_cargalectiva=<?= $id_carga ?>&id_plan_tutoria=<?= $id_plan ?>'); return false;">
                        <i class="fa fa-eye"></i>
                    </button>
                <?php else: ?>
                    <button class="btn btn-outline-secondary btn-sm" disabled>
                        <i class="fa fa-eye"></i>
                    </button>
                <?php endif; ?>
            </div>

            <div id="mensaje_envio_<?= $id_carga ?>" style="margin-top: 4px; font-size: 13px;"></div>
        </div>
    </div>
<?php endforeach; ?>

    <?php endif; ?>
</div>
<script>
//------DESPLEGABLE POR MES INFOREM MENSUAL
    function mostrarSelectMes(id) {
        const select = document.getElementById('meses_' + id);
        const mensaje = document.getElementById('mensaje_envio_' + id);
        const btn = document.getElementById('btnVer_' + id);

        // Mostrar el select y ocultar todos los demás selects abiertos (opcional)
        document.querySelectorAll('.mes-selector').forEach(sel => sel.style.display = 'none');
        if (select) select.style.display = 'inline-block';

        // Limpiar botón y mensaje
        if (btn) {
            btn.disabled = true;
            btn.onclick = null;
        }
        if (mensaje) {
            mensaje.innerHTML = '';
        }
    }

    function verificarYMostrar(id_carga, mes) {
        if (!mes) return;

        fetch(`tutor_aula/verificar_informe_mensual.php?id_cargalectiva=${id_carga}&mes=${mes}`)
            .then(res => res.json())
            .then(data => {
                const btn = document.getElementById('btnVer_' + id_carga);
                const mensaje = document.getElementById('mensaje_envio_' + id_carga);

                if (data.existe) {
                    btn.disabled = false;
                    btn.onclick = function () {
                        abrirVentanaPopup(`tutor_aula/vista_prev_informe_mensual.php?id_cargalectiva=${id_carga}&mes=${mes}`);
                    };

                    mensaje.innerHTML = `<span style="color: green; font-weight: bold;"><i class="fa fa-check-circle"></i> Informe de ${mes.charAt(0).toUpperCase() + mes.slice(1)} enviado</span>`;
                } else {
                    btn.disabled = true;
                    btn.onclick = null;
                    mensaje.innerHTML = `<span style="color: red;"><i class="fa fa-times-circle"></i> Informe no enviado</span>`;
                }
            });

        cargar_contenido('contenido_principal', `tutor_aula/form_informe_mensual.php?id_cargalectiva=${id_carga}&mes=${mes}`);
    }

    //VISTA DEL DOC EN VENTANA PEQUEÑA
  function abrirVentanaPopup(url) {
    const ancho = 900;
    const alto = 700;
    const izquierda = (screen.width - ancho) / 2;
    const arriba = (screen.height - alto) / 2;

    window.open(
      url,
      'VistaPrevTutoria',
      `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes,toolbar=no,location=no,status=no,menubar=no`
    );
  }
</script>


