<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// RUTAS ABSOLUTAS SEGURAS
require_once __DIR__ . '/../../modelo/modelo_docente.php';
require_once __DIR__ . '/../../modelo/modelo_conexion.php';

$CN = new conexion();
$CN->conectar();
$MD = new Docente();

$id_doc   = $_SESSION['S_IDUSUARIO'];
$semestre = $_SESSION['S_SEMESTRE'];
$id_carga   = isset($_GET['id_cargalectiva']) ? (int)$_GET['id_cargalectiva'] : null;


/* ================ ESTUDIANTES NEE ENE L PRESENTE SEMESTRE ================ */

$nee_ids = []; // set: id_estu => 1
$sqlNEE = " 
  SELECT DISTINCT tat.id_estudiante AS id_estu
  FROM tutoria_asignacion_tutoria tat
  INNER JOIN tutoria_estudiantes_conadis tec
          ON tec.id_estu = tat.id_estudiante
  WHERE tat.id_docente = ? AND tat.id_semestre = ?
";
$stmt = $CN->conexion->prepare($sqlNEE);
$stmt->bind_param('ii', $id_doc, $semestre);
$stmt->execute();
$resNEE = $stmt->get_result();
while ($r = $resNEE->fetch_assoc()) {
  $nee_ids[(int)$r['id_estu']] = 1;
}
$stmt->close();

/* Total de NEE asignados */
$nee_total = count($nee_ids);

/* =========================================================================== */

/* ================ ESTUDIANTES REPITENTES EN EL PRESENTE SEMESTRE================ */
/* vez_a >= 2  */
$sr_ids = [];
$sqlSR = "
  SELECT DISTINCT ae.id_estu AS id_estu
  FROM asignacion_estudiante ae
  INNER JOIN tutoria_asignacion_tutoria tat
          ON tat.id_estudiante = ae.id_estu
         AND tat.id_semestre   = ae.id_semestre
  WHERE tat.id_docente = ?
    AND tat.id_semestre = ?
    AND COALESCE(CAST(ae.vez_a AS UNSIGNED), 0) >= 2
";
$stmtSR = $CN->conexion->prepare($sqlSR);
$stmtSR->bind_param('ii', $id_doc, $semestre);
$stmtSR->execute();
$resSR = $stmtSR->get_result();
while ($r = $resSR->fetch_assoc()) {
  $sr_ids[(int)$r['id_estu']] = 1;
}
$stmtSR->close();

/* Totales para la leyenda */
$nee_total = isset($nee_total) ? $nee_total : count($nee_ids);
$sr_total  = count($sr_ids);


/* ================================================================================ */
$consulta  = $MD->listar_alumnos_asignados($id_doc, $semestre, $id_carga);
$consulta2 = $MD->listar_alumnos_asignados($id_doc, $semestre, $id_carga);


$tutorados = mysqli_num_rows($consulta2);


$porcentajes_faltas = $MD->porcentajes_asistencia_TA($id_doc, $semestre);
$grupal= 0;
$individual= 0;
$derivado= 0;

while ($consulta_estadistica = mysqli_fetch_assoc($consulta2)){
	if($consulta_estadistica['id_tipo'] == 2) {
		$grupal += 1;
	}else if($consulta_estadistica['id_tipo'] == 1){
		$individual += 1;
	}else{
		$derivado += 1;
	}
}


function url_con(array $extra = [], array $omit = []){
  $u  = parse_url($_SERVER['REQUEST_URI']);
  $qs = [];
  if (!empty($u['query'])) parse_str($u['query'], $qs);
  foreach($omit as $k) unset($qs[$k]);
  $qs = array_merge($qs, $extra);
  $q  = http_build_query($qs);
  return htmlspecialchars(($u['path'] ?? '') . ($q ? '?'.$q : ''), ENT_QUOTES, 'UTF-8');
}
?>

<script type="text/javascript" src="../js/asignacion.js?rev=<?php echo time(); ?>"></script>
<div class="col-md-12">
	<div>
		<style>
			.selecturno {display: flex;	justify-content: end;}
			#butsearch {	border-radius: 5px;	margin-top: -2px;	font-size: 10px;	background-color: #05ccc4;	position: relative;}
			.cmbColumn {	border-radius: 3px;	margin-bottom: 8px;	border: 1px solid gray;	font-size: 14px;	height: 30px;	padding: 5px;	}
			.cmbColumn .option {	height: 30px;	border-button: 8px;}
			.checkasis {	width: 20px;	height: 20px;	}
			#table_alumno_asignado {	border-top: 3px solid #3c8dbc;	border-left: 3px solid #3c8dbc;	border-right: 3px solid #3c8dbc;	border-bottom: 3px solid #3c8dbc;	}
			table {	border-collapse: collapse;	box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);	border: 10px 10px 10px 10px solid red;	border-color: red;	font-family: 'Roboto', sans-serif;	}
			th,	td {	border-bottom: 1px solid #3c8dbc;	padding: 10px;	text-align: center;	}
			thead th {	/* Ajustar el estilo para la cabecera */	border: 1px solid #066da7;	background-color: transparent;		color: white;	}
			th {	background-color: #184b08d4;	color: white;}
			tr:nth-child(even) {	background-color: #f2f2f2;	}
			td {color: #333;		}
			.red-text {	color: red;	font-weight: bold;}
			h2 {	margin-bottom: 1px;	}
			.tr-alum {	background-color: none;		}
			.tr-alum,
			.tr-alum td {	border: none;	padding: none;		text-align: left;	}
			.select_tipo {	padding: 6px;	font-size: 12px;	max-width: 110px;	border-radius: 2px;	font-weight: bold;	}
			.select_tipo:focus {	outline: none;	box-shadow: none;	}
			.btn-cita {	font-size: 14px;	font-weight: 400;	}
			.table_alumnos_horario thead tr th {	border: transparent;	}
			/* Estilos para el popup */
			.popup {	display: none;	position: fixed;	left: 50%;	top: 50%;	transform: translate(-50%, -50%);	width: 80%;	max-width: 735px;	background: white;	border: 1px solid #ccc;	box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);	z-index: 1000;	padding: 15px;	font-size: 10px;	font-family: 'Arial', sans-serif;}
			.popup-header {	display: flex;	justify-content: space-between;	align-items: center;	border-bottom: 1px solid #ccc;	margin-bottom: 10px;}
			.popup-body {	max-height: 490px;	overflow-y: auto;	}
			.close-btn {	cursor: pointer;}
			.overlay {	z-index: 999;	}
	/* ---- Leyendas ---- */
			.legend-box{display:inline-block; width:20px; height:16px; border-radius:2px; margin:0 6px 0 14px; vertical-align:middle;box-shadow: inset 0 0 0 1px rgba(0,0,0,.08);}
			.legend-nee { background: #f7ce54ff; } /* ámbar suave */
			.legend-sr  { background: #f06969ff; } /* rojo suave  */
			/* ---- Filas completas resaltadas ---- */
			.row-nee { 	background: #f7ce54ff !important; }
			.row-sr { background: #f06969ff !important; 	}
			/* Si es NEE y SR a la vez */
			.row-nee.row-sr{background: #f3e8ff !important; /* lila suave para combinación */}
			/* Evitar que .table-striped sobreescriba el fondo */
			.table.table-striped > tbody > tr.row-nee:nth-of-type(odd),
			.table.table-striped > tbody > tr.row-nee:nth-of-type(even),
			.table.table-striped > tbody > tr.row-sr:nth-of-type(odd),
			.table.table-striped > tbody > tr.row-sr:nth-of-type(even){	background: inherit !important;	}
		/* Celda de nombre para NEE (CONADIS) */
			.td-nee{background:#fff7d6 !important; /* ámbar suave compatible */font-weight:600;border-radius:3px;	}
			.badge-nee{display:inline-block; font-size:11px; line-height:1; padding:.2em .35em;	border-radius:3px; background:#f39c12; color:#fff; margin-left:6px;	}
		/* Celda del nombre para SR (repitente) */
			.td-sr{	background:#ffe6e6 !important; /* rojo suave */	font-weight:600;	border-radius:3px;	}
			.badge-sr{	display:inline-block; font-size:11px; line-height:1; padding:.2em .35em;	border-radius:3px; background:#dd4b39; color:#fff; margin-left:6px;	}
			/* Cuando es NEE + SR al mismo tiempo */
			.td-both{	background:#f3e8ff !important; /* lila suave */	font-weight:600;	border-radius:3px;	}
			/* Barra de métricas/leyendas en una sola línea */
			.metrics-bar{	display:flex;align-items:center;	gap:12px;          /* separación controlada */	flex-wrap:nowrap;  /* no salta de línea en desktop */	white-space:nowrap;	}
			.metrics-bar i{ margin-right:4px; }
			/* opcional: si la pantalla es chica, permite que salte */
			@media (max-width:1200px){
			.metrics-bar{ flex-wrap:wrap; }
			}
			/* un poco menos de sangría a las cajitas de leyenda */
			.legend-box{ margin:0 6px 0 10px; }
			.toolbar-left  { display:inline-block; }
			.toolbar-right { display:inline-block; margin-left:12px; vertical-align:middle; }
			.btn-aula.active { filter: brightness(.93); }
		</style>

		<div class="box-body ">
			<div class="box-body">
				<div class="row align-items-left" style="margin-bottom: 1em;">
					<div class="col-md-5" style="margin-bottom: 1em;">
						<button type="button" class="btn btn-primary btn-cita" onclick="CrearSesion()"
							style="margin-top: -1em;">
							<i class="fa fa-plus-circle" aria-hidden="true"></i>&nbsp; Nueva Tutoria
						</button>
						<button type="button" class="btn btn-primary" onclick="checkAll()"
							style="margin-top: -1em;">
							<i class="fa fa-check" aria-hidden="true"></i>&nbsp; Marcar todo
						</button>

					</div>
					<div class="col-md-7">
					<!-- NUEVO: LEYENDAS -->
						<div class="row">
							<div class="col-md-16" style=" font-size: 13px;">
								<strong>
									<i class="fa fa-users" style="color: #009587" aria-hidden="true"></i>&nbsp; Tutorados:
									<?php echo $tutorados ?>
									&nbsp;&nbsp;
									<i class="fa fa-calendar-o" style="color: #009587" aria-hidden="true"></i>&nbsp;
									Semestre: <?php echo $semestre ?>
									&nbsp;&nbsp;
									<i class="fa fa-object-group" style="color: #009587" aria-hidden="true"></i>&nbsp;
									Grupal: <?php echo $grupal ?>
									&nbsp;&nbsp;
									<i class="fa fa-graduation-cap" style="color: #009587" aria-hidden="true"></i>&nbsp;
									Individual: <?php echo $individual ?>&nbsp;&nbsp;
									<i class="fa fa-share" style="color: #009587" aria-hidden="true"></i>&nbsp; Derivado:
									<?php echo $derivado ?>

									<!-- NUEVO: LEYENDAS -->
									<span class="legend-box legend-nee" title="Estudiantes con Necesidades Educativas Especiales"></span>
										<span>Estudiantes NEE: <?php echo (int)$nee_total; ?></span>
									<span class="legend-box legend-sr"  title="Estudiantes en segunda matrícula (repitentes)"></span>
										<span>Estudiantes Repitentes: <?php echo (int)$sr_total; ?></span>
								</strong>
							</div>
						</div>
					</div>
				</div>

				<div class="table-responsive">

					<table id="table_alumno_asignado" class="table table-hover table-striped  nowrap"
						style="width:100%;">
						<thead style="background-color: #3c8dbc; color: white; ">
							<tr>
								<th class="checkbox-header">
									Selec.
								</th>
								<th >Estudiantes</th>
								<th>Teléfono</th>
								<!-- <th>Ciclo</th> -->
								<th>Correo</th>
								<th>Rendimiento</th>
								<th>% Faltas</th>
								<th>Tipo</th>
								<th>Información</th>
								<th>Derivar</th>
							</tr>
						</thead>
						<tbody>
							<?php
							/* echo "<pre>";
							print_r(array_keys($porcentajes_faltas)); // Muestra los ID que vienen desde la función
							echo "</pre>"; */

							while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
								/* $id_estu = intval($consulta_VU['id_estu']);
								echo "Fila ID: " . $id_estu . "<br>"; */
								echo "<tr>";
								
								echo "<td>";
								?>

								<input type="checkbox" class="form-check-input checkasis"
									id="<?php echo $consulta_VU['id_asig'] ?>" name="<?php echo $consulta_VU['nombres'] ?>">
								<input type="hidden" class="id_estu_hora" value="<?php echo $consulta_VU['id_estu'] ?>">
								<!-- <input type="text" class="id_estu_hora" id="<?php //echo $consulta_VU['id_estu'] ?>" hidden> -->
								<?php
								// === CELDA DE NOMBRE (pintar solo si es NEE en el semestre actual) ===
									$isNEE = isset($nee_ids[(int)$consulta_VU['id_estu']]);
									$isSR  = isset($sr_ids[(int)$consulta_VU['id_estu']]);

									if ($isNEE && $isSR)      $tdClass = " class='td-both'";
									elseif ($isSR)            $tdClass = " class='td-sr'";
									elseif ($isNEE)           $tdClass = " class='td-nee'";
									else                      $tdClass = "";

									echo "<td{$tdClass} style='max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left;'>";
									echo htmlspecialchars($consulta_VU['nombres'], ENT_QUOTES, 'UTF-8');
									if ($isNEE) echo " <span class='badge-nee' title='Necesidades Educativas Especiales'>NEE</span>";
									if ($isSR)  echo " <span class='badge-sr'  title='Segunda matrícula (repitente)'>REPITENTE</span>";
									echo "</td>";
								echo "</td>";


								echo "<td>";
								echo $consulta_VU['telefono'];
								echo "</td>";
								/* echo "<td>";
								echo $consulta_VU['des_ciclo'];
								echo "</td>"; */
								echo "<td>";
								echo $consulta_VU['cor_inst'];
								echo "</td>";

								// RENDIMIENTO
					
								echo "<td>";
								 	$rend = strtoupper(trim($consulta_VU['rendimiento']));
									switch ($rend) {
										case 'BAJO':
											echo "<span class='label label-danger' style='font-size: 0.9em;'>Bajo</span>";
											break;
										case 'REGULAR':
											echo "<span class='label label-warning' style='font-size: 0.9em;'>Regular</span>";
											break;
										case 'ALTO':
											echo "<span class='label label-success' style='font-size: 0.9em;'>Alto</span>";
											break;
										default:
											echo "<span class='label label-default'>N/A</span>";
									}
									// Texto debajo del semáforo
									$desap = (int)$consulta_VU['notas_desaprobadas'];
									$total = (int)$consulta_VU['total_notas'];

									if ($total > 0) {
										echo "<div style='font-size:11px; color:red; margin-top:3px;'>$desap de $total</div>";
									} else {
										echo "<div style='font-size:11px; color:#777; margin-top:3px;'>Sin notas</div>";
									}

								echo "</td>"; 

								// ASISTENCIA
								echo "<td>";
									$id_estu = $consulta_VU['id_estu'];

									// antes: null -> "N/T"
									// ahora: si no existe en el array, mostrar 0.0
									$faltas = isset($porcentajes_faltas[$id_estu]) ? (float)$porcentajes_faltas[$id_estu] : 0.0;

									// formato bonito (quita ceros a la derecha)
									$faltas_fmt = rtrim(rtrim(number_format($faltas, 2, '.', ''), '0'), '.');

									if ($faltas <= 10) {
										echo "<span class='label label-success' style='font-size:0.9em;'>{$faltas_fmt}%</span>";
									} elseif ($faltas <= 20) {
										echo "<span class='label label-warning' style='font-size:0.9em;'>{$faltas_fmt}%</span>";
									} else {
										echo "<span class='label label-danger' style='font-size:0.9em;'>{$faltas_fmt}%</span>";
									}
								echo "</td>";

								echo "<td>";

								if ($consulta_VU['id_tipo'] < 3) { ?>
									<select class="select_tipo  <?php echo $color; ?>"
										id="<?php echo $consulta_VU['id_asig']; ?>"
										style=" <?php echo ($consulta_VU['id_tipo'] == 1) ? 'border: 1.2px solid orange;' : 'border: 1.2px solid green;'; ?>">

										<?php if ($consulta_VU['id_tipo'] == 2) { ?>
											<option value='<?php echo $consulta_VU['id_tipo']; ?>' style='color: black;'>
												<?php echo $consulta_VU['tipo']; ?>
											</option>
											<option value='1'>Individual</option>
										<?php } else { ?>
											<option value='<?php echo $consulta_VU['id_tipo']; ?>' style='color: black;'>
												<?php echo $consulta_VU['tipo']; ?>
											</option>
											<option value='2'>Grupal</option>
										<?php } ?>

									</select>
									<?php
								} else {
									?>
									<span class='label label-info'
										style='font-size: 0.9em;'><?php echo $consulta_VU['tipo']; ?></span>
									<?php
								}
								echo "</td>";
								echo "<td class=\"btnoption\">";

								?>
								<div class="btnoption">
									<button class='btn btn-success ' style='font-size: 0.9em;'>
										<a style="color: white;" href='javascript:void(0);'
											onclick='mostrarPopup(<?php echo $consulta_VU["id_estu"]; ?>)'>Notas</a>
									</button>

									<button type="button" class="btn btn-info ">
										<a style="color: white;" href='javascript:void(0);'
											onclick='mostrarAsi_Popup(<?php echo $consulta_VU["id_estu"]; ?>)'>Asist</a>
									</button>

									<button type="button" class="btn btn-warning ">
										<a style="color: white;"
											href='https://sivireno.undc.edu.pe/Proceso/fs_datos_2022.php?estudiante=<?php echo $consulta_VU['id_estu']; ?>'
											target='_blank'>FS</a>
									</button>
								</div>

								<?php
								echo "</td>";
								echo "<td>";
								if ($consulta_VU['id_tipo'] == 3) {
									?>
									<button style='font-size:13px;' type='button'
										onclick='FormatoDerivado(<?php echo $consulta_VU["id_estu"]; ?>)' class='btn btn-info'>
										<i class="fa fa-file-pdf-o" aria-hidden="true"></i>&nbsp; Generar
									</button>

									<?php
								} else {
									?>
									<button style="font-size:13px; " type='button' class='btn btn-primary'
										onclick="DerivarEstudiante(<?php echo $consulta_VU['id_asig'] ?>,<?php echo $consulta_VU['id_estu'] ?>)">
										<i class='fa fa-share'></i>&nbsp;Derivar
									</button>


									<?php
								}
								echo "</td>";

								echo "</tr>";
							}
							?>

						</tbody>
					</table>
				</div>

			</div>
			<div class="modal-footer">

			</div>
		</div>
</div>
</div>
<form autocomplete="false" onsubmit="return false" >
			<!-- <div class="modal fade" id="modal_derivar"> -->
			<div class="modal fade" id="modal_derivar" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false" style="z-index: 1060;">

				<div class="modal-dialog">
					<div class="modal-content">

						<!-- Modal Header -->
						<div class="modal-header" style="border: none;">
							<center>
								<h4 class="modal-title"><b>Derivar Estudiante</b></h4>
							</center>
							<button type="button" class="close" data-dismiss="modal"
								style="position: absolute; left: 90%; top: 7%;">&times;</button>
						</div>

						<!-- Modal body -->
						<div class="modal-body">
							<div class="row ">
								<div class="col-md-12">
									<div class="box-body">
										<div class="btn-group" style="width: 100%; margin-bottom: 10px;">
											<label for=""><b>Area de Apoyo:<b></label>
											<select
												class="js-example-basic-single global_filter form-control campoasis "
												id="areas_apoyo" style="width:100%;">
											</select>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-12">
								<label for="">Motivo:</label>
								<textarea type="text" class="areat campoasis form-control" id="motivo_der"
									style="border-radius: 5px; max-width: 100%; font-weight: 500;"
									maxlength="600"></textarea>
							</div>

						</div>
						<br>
						<br>
						<!-- Modal footer -->
						<div class="modal-footer" style="margin-top: 2em;">
							<button class="btn btn-primary" onclick="Derivar_estudiante()"><i
									class="fa fa-check"><b>&nbsp;Derivar</b></i></button>
							<button type="button" class="btn btn-danger" data-dismiss="modal"><i
									class="fa fa-close"><b>&nbsp;Cerrar</b></i></button>
						</div>
					</div>
				</div>
			</div>
	</div>
</form>
<form method="post" action="../pdf_ge/index.php" target="_blank" id="formtuto">
	<input type="text" name="estututo" id="estututo" hidden>
	<input type="text" name="docetuto" id="docetuto" hidden>
</form>
<form method="post" action="../pdf_ge/index.php" target="_blank" id="formder">
	<input type="text" name="estu" id="estuder" hidden>
	<input type="text" name="doce" id="doceder" hidden>
</form>

<!-- <div class="modal fade" id="calendarModal" tabindex="-1" role="dialog" aria-hidden="true"> -->
<div class="modal fade" id="calendarModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="false">
	<div class="modal-dialog">
		<div class="modal-content" style="border-radius: 5px;">
			<div class="modal-header" id="dff"
				style="background-color: #3c8dbc; border: none; border-radius: 5px 5px 0 0;">
				<h4 class="modal-title" style="color: white; float: left;">Programar Sesión de Tutoria</h4>
				<div style="position: absolute; left: 90%; ">
					<button type="button" class="btn" data-dismiss="modal"
						style="position: relative; background-color: transparent; color: white;">
						<i class="fa fa-times" aria-hidden="true"></i>
					</button> 
				</div>
			</div> <!-- close modal -->

			<div class="modal-body">

				<div class="loadingDiv"></div>

				<!-- QuickSave/Edit FORM -->
				<form id="modal-form-body">
					<div class="row">
						<!--------------------------------------------------------------->
						<div class="col-md-12">
							<h5><span style="color: red; font-size: 20px;">*</span> Configuración de Tema</h5>
						</div>
						<div class="col-md-12" style="margin-bottom: 1em;">
							<label for="">Tema a tratar:</label>
							<br>
							<textarea name="tem" id="tema_tuto" class="form-control" 
							style="border-radius: 5px; max-width: 100%; height: 50px; min-height: 35px; font-weight: 500;"></textarea>
						</div>
						<div class="col-md-6" style="margin-bottom: 1em;" hidden>
							<label for="">Compromiso:</label>
							<br>
							<textarea type="text" class="areat campoasis form-control" id="comp_tuto"
								style="border-radius: 5px; max-width:100%;height: 50px; min-height: 35px; font-weight: 500;"></textarea>
						</div>
						<div class="col-md-6">
							<div class="btn-group" style="width: 100%; margin-bottom: 10px;">
								<label for=""><b>Tipo de sesión:<b></label>
								<select class="global_filter form-control campoasis" id="tipo_session"
									style="width:100%; padding: 7px;  border-radius: 5px;">
								</select>
							</div>
						</div>
						<div class="col-md-6">
							<div id="campo_detalles">

							</div>
						</div>

						<!--------------------------------------------------------------->
						<div class="col-md-12">
							<h5><span style="color: red; font-size: 20px;">*</span> Alumno(s)</h5>
						</div>
						<div class="col-md-12">

							<table id="table_alumnos_horario" class="table">
								<thead>
									<tr>
										<th hidden>id</th>
										<th hidden>id</th>
										<th hidden>Alumnos</th>
										<th hidden>Quitar</th>
									</tr>
								</thead>
								<tbody id="alumnos_list">

								</tbody>
							</table>
						</div>
						<!--------------------------------------------------------------->
						<div class="col-md-12">
							<h5><span style="color: red; font-size: 20px;">*</span> Configuración de Fecha</h5>
						</div>

						<div class="col-md-4">
							<label>Fecha:</label>
							<input type="date" id="start_date" name="fec" class="form-control input-sm flatpickr" placeholder="">
						</div>
						<div class="col-md-4">
							<label>Hora Inicial:</label>
							<input type="time" id="start_time" name="ini" class="form-control input-sm flatpickr" placeholder="">
						</div>
						<div class="col-md-4">
							<label>Hora Final:</label>
							<input type="time" id="end_time" name="fin" class="form-control input-sm flatpickr" placeholder="">
						</div>

					</div>

				</form>

			</div>
			<div class="modal-footer">
				<button type="button" id="save-changes" class="btn btn-success"
					onclick="GuardarCitas()">Guardar</button>
				<button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-danger">Salir</button>
			</div>
		</div>
	</div>
</div>



<div id="overlay" class="overlay" onclick="cerrarPopup()"></div>
<div id="popup" class="popup">
	<div class="popup-header">

		<span class="close-btn" onclick="cerrarPopup()">×</span>
	</div>
	<div id="popup-body" class="popup-body">
		<!-- Aquí se cargarán los resultados -->
	</div>
</div>

<script>
	$(document).ready(function () {
		$('.js-example-basic-single').select2();
		//listar_Alumnos_Asignados();//esta en index.js==> cargar semestre actual
		listar_areas_apoyo();
		listar_tipo_session();

	});
	//not
	function mostrarPopup(idEstu){
	const xhr = new XMLHttpRequest();
	xhr.open('POST', 'tutor_aula/boleta_notas.php', true);
	xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	xhr.onload = function(){
		if(xhr.status === 200){
		// Insertar el HTML del modal (verde) directamente en el body
		document.body.insertAdjacentHTML('beforeend', xhr.responseText);
		// si el modal trae su propio overlay/cerrar, no abras #popup/#overlay
		}
		// Centrar el/los modales insertados
		document.querySelectorAll('.modal-notas').forEach(m => {
		Object.assign(m.style, {
			position: 'fixed',
			top: '50%',
			left: '50%',
			transform: 'translate(-50%, -50%)',
			maxHeight: '85vh',
			width: 'min(1000px, 95vw)',
			overflow: 'auto',
			zIndex: 2001
		});
		});

		// (opcional) crear un overlay si tu HTML del modal no trae uno
		if (!document.querySelector('.modal-notas-overlay')) {
		const ov = document.createElement('div');
		ov.className = 'modal-notas-overlay';
		Object.assign(ov.style, {
			position:'fixed', inset:'0', background:'rgba(0,0,0,.1)', zIndex:'2000'
		});
		ov.addEventListener('click', ()=> ov.remove());
		document.body.appendChild(ov);
		}
	};
	xhr.send('id_estu='+idEstu);
	}

	//asi
	function mostrarAsi_Popup(idEstu) {
	fetch('tutor_aula/asistencia.php', {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: 'id_estu=' + encodeURIComponent(idEstu)
	})
	.then(r => r.text())
	.then(html => {
		document.body.insertAdjacentHTML('beforeend', html);
	});
	}

	function cerrarPopup() {
		document.getElementById('popup').style.display = 'none';
		document.getElementById('overlay').style.display = 'none';
	}

	var selected_all = false;
	function checkAll() {
		filas_estudiantes = document.getElementsByClassName('checkasis');

		selected_all = !selected_all;

		for (var i = 0; i < filas_estudiantes.length; i++) {
			filas_estudiantes[i].checked = selected_all;
		}
	}


</script>