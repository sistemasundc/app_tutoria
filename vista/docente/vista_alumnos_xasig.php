<?php  
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../../modelo/modelo_docente.php';
$MD = new Docente();
if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'TUTOR DE CURSO') {
    die('Acceso no autorizado');
}
$id_doc = $_SESSION['S_IDUSUARIO'];
$id_cargalectiva = isset($_GET['id_cargalectiva']) ? $_GET['id_cargalectiva'] : null;

// Validación básica
if (!$id_cargalectiva) {
    die('ID de asignatura no encontrado.');
}

// NUEVA CONSULTA para sacar escuela profesional, nombre de la asignatura, ciclo, turno, sección (grupo9)
$infoCurso = $MD->obtener_info_asignatura($id_cargalectiva);

$carrera = "";
$nombre_asignatura = "";
$ciclo = "";
$turno = "";
$seccion = "";

if ($infoCurso) {
    $fila = mysqli_fetch_assoc($infoCurso);
    if ($fila) {
		$carrera = $fila['nom_car'];
        $nombre_asignatura = $fila['nom_asi'];
        $ciclo = $fila['ciclo'];
        $turno = $fila['turno'];
        $seccion = $fila['seccion'];
    }
}

// consulta normal de alumnos
$consulta = $MD->listar_alumnos_x_asignatura($id_doc, $id_cargalectiva);
$consulta2 = $MD->listar_alumnos_x_asignatura($id_doc, $id_cargalectiva);
$porcentajes_faltas = $MD->porcentajes_asistencia_TC($id_cargalectiva);
// Inicializar variables
$grupal = 0;
$individual = 0;
$derivado = 0;
$tutorados = 0;

if ($consulta2) {
    $tutorados = mysqli_num_rows($consulta2);
    while ($consulta_estadistica = mysqli_fetch_assoc($consulta2)) {
        if ($consulta_estadistica['id_tipo'] == 2) {
            $grupal++;
        } elseif ($consulta_estadistica['id_tipo'] == 1) {
            $individual++;
        } else {
            $derivado++;
        }
    }
}
?>

<script src="../js/asignacion.js?rev=<?php echo time(); ?>"></script>
<div class="col-md-12">
	<div>
		<style>
			.selecturno {
				display: flex;
				justify-content: end;
			}

			#butsearch {
				border-radius: 5px;
				margin-top: -2px;
				font-size: 10px;
				background-color: #05ccc4;

				position: relative;
			}

			.cmbColumn {

				border-radius: 3px;
				margin-botton: 8px;
				border: 1px solid gray;
				font-size: 14px;
				height: 30px;
				padding: 5px;

			}

			.cmbColumn .option {
				height: 30px;
				border-button: 8px;
			}

			.checkasis {
				width: 20px;
				height: 20px;
			}

			#table_alumno_asignado {
				border-top: 3px solid #3c8dbc;
				border-left: 3px solid #3c8dbc;
				border-right: 3px solid #3c8dbc;
				border-bottom: 3px solid #3c8dbc;
			}

			table {
				border-collapse: collapse;
				box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
				border: 10px 10px 10px 10px solid red;
				border-color: red;
				font-family: 'Roboto', sans-serif;
			}

			th,
			td {
				border-bottom: 1px solid #3c8dbc;
				padding: 10px;
				text-align: center;
			}

			thead th {
				/* Ajustar el estilo para la cabecera */
				border: 1px solid #066da7;
				background-color: transparent;
				color: white;
			}

			th {
				background-color: #184b08d4;
				color: white;
			}

			tr:nth-child(even) {
				background-color: #f2f2f2;
			}

			td {
				color: #333;
			}

			.red-text {
				color: red;
				font-weight: bold;
			}

			h2 {
				margin-bottom: 1px;
			}

			.tr-alum {
				background-color: none;
			}

			.tr-alum,
			.tr-alum td {
				border: none;
				padding: none;
				text-align: left;
			}

			.select_tipo {
				padding: 6px;
				font-size: 12px;
				max-width: 110px;
				border-radius: 2px;
				font-weight: bold;
			}

			.select_tipo:focus {
				outline: none;
				box-shadow: none;
			}

			.btn-cita {
				font-size: 14px;
				font-weight: 400;
			}

			.table_alumnos_horario thead tr th {
				border: transparent;
			}


			/* Estilos para el popup */
			.popup {
				display: none;
				position: fixed;
				left: 50%;
				top: 50%;
				transform: translate(-50%, -50%);
				width: 80%;
				max-width: 735px;
				background: white;
				border: 1px solid #ccc;
				box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
				z-index: 1000;
				padding: 15px;
				font-size: 10px;
				font-family: 'Arial', sans-serif;
			}

			.popup-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				border-bottom: 1px solid #ccc;
				margin-bottom: 10px;
			}

			.popup-body {
				max-height: 490px;
				overflow-y: auto;
			}

			.close-btn {
				cursor: pointer;
			}

			.overlay {
				display: none;
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background: rgba(0, 0, 0, 0.5);
				z-index: 999;
			}
		</style>
		<div class="row align-items-left" style="margin-bottom: 0.5em;">
			<div class="col-md-12" style="font-size: 18px; font-weight: bold; color:rgba(10, 10, 10, 0.83); text-align: center;">
				<?php echo $carrera ." | ". $nombre_asignatura . " | Ciclo: " . $ciclo . " | Turno: " . $turno . " | Sección: " . $seccion; ?>
			</div>
		</div>

		<div class="box-body ">
			<div class="box-body">
				<div class="row align-items-left" style="margin-bottom: 1em;">
					<div class="col-md-5" style="margin-bottom: 1em;">
						<input type="hidden" id="id_cargalectiva_hidden" value="<?= htmlspecialchars($id_cargalectiva) ?>">
						<button type="button" class="btn btn-primary btn-cita" onclick="CrearSesionCurso()"
							style="margin-top: -1em;">
							<i class="fa fa-plus-circle" aria-hidden="true"></i>&nbsp; Nueva Tutoria
						</button>
						<button type="button" class="btn btn-primary" onclick="checkAll()"
							style="margin-top: -1em;">
							<i class="fa fa-check" aria-hidden="true"></i>&nbsp; Marcar todo
						</button>
					</div>
					<div class="col-md-7">
						<div class="row">
							<div class="col-md-12" style=" font-size: 15px;">
								<i class="fa fa-users" style="color: #009587" aria-hidden="true"></i>&nbsp; Tutorados:
								<?php echo $tutorados ?>
								&nbsp;&nbsp;
								<!--<i class="fa fa-calendar-o" style="color: #009587" aria-hidden="true"></i>&nbsp;
								Semestre: <?php #echo $semestre ?>
								&nbsp;&nbsp;-->
								<i class="fa fa-object-group" style="color: #009587" aria-hidden="true"></i>&nbsp;
								Grupal: <?php echo $grupal ?>
								&nbsp;&nbsp;
								<i class="fa fa-graduation-cap" style="color: #009587" aria-hidden="true"></i>&nbsp;
								Individual: <?php echo $individual ?>&nbsp;&nbsp;
								<i class="fa fa-share" style="color: #009587" aria-hidden="true"></i>&nbsp; Derivado:
								<?php echo $derivado ?>
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
								<th>Estudiantes</th>
								<th>Tel&eacute;fono</th>
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
							while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
								echo "<tr>";
								echo "<td>";
								?>
								<input type="checkbox" class="form-check-input checkasis"
									id="<?php echo $consulta_VU['id_asig'] ?>" name="<?php echo $consulta_VU['nombres'] ?>">
								<input type="hidden" class="id_estu_hora" value="<?php echo $consulta_VU['id_estu'] ?>">
								<?php
								echo "</td>";
								echo "<td style='max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>";
								echo htmlspecialchars($consulta_VU['nombres'], ENT_QUOTES, 'UTF-8');
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
									$faltas = isset($porcentajes_faltas[$id_estu]) ? (float)$porcentajes_faltas[$id_estu] : 0.0;

									// Redondeo opcional para mostrar bonito
									$faltas_fmt = rtrim(rtrim(number_format($faltas, 2, '.', ''), '0'), '.'); // 0, 2.5, 10, 12.34

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
									<select class="select_tipo <?php echo $color; ?>"
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
								<?php } else { ?>
									<span class='label label-info' style='font-size: 0.9em;'><?php echo $consulta_VU['tipo']; ?></span>
								<?php }
								echo "</td>";

								echo "<td class=\"btnoption\">";
								?>
								<div class="btnoption">
									<button class='btn btn-success ' style='font-size: 0.9em;'>
										<a style="color: white;" href='javascript:void(0);'
											onclick='mostrarPopup(<?php echo $consulta_VU["id_estu"]; ?>)'>Nota</a>
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
										onclick='FormatoDerivado(<?php echo $consulta_VU['id_estu']; ?>)' class='btn btn-info'>
										<i class="fa fa-file-pdf-o" aria-hidden="true"></i>&nbsp; Generar
									</button>
								<?php } else { ?>
									<button style="font-size:13px;" type='button' class='btn btn-primary'
										onclick="DerivarEstudiante(<?php echo $consulta_VU['id_asig'] ?>,<?php echo $consulta_VU['id_estu'] ?>)">
										<i class='fa fa-share'></i>&nbsp;Derivar
									</button>
								<?php }
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

		<form autocomplete="false" onsubmit="return false">
			<div class="modal fade" id="modal_derivar">
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
							<button class="btn btn-primary" onclick="Derivar_estudiante_TC()"><i
									class="fa fa-check"><b>&nbsp;Derivar</b></i></button>
							<button type="button" class="btn btn-danger" data-dismiss="modal"><i
									class="fa fa-close"><b>&nbsp;Cerrar</b></i></button>
						</div>
					</div>
				</div>
			</div>
	</div>
	</form>

</div>
</div>

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
				<!-- Inputs ocultos para curso, ciclo y turno -->
					<input type="hidden" id="nombre_curso" value="<?= htmlspecialchars($nombre_asignatura) ?>">
					<input type="hidden" id="ciclo_curso" value="<?= htmlspecialchars($ciclo) ?>">
					<input type="hidden" id="turno_curso" value="<?= htmlspecialchars($turno . ' - ' . $seccion) ?>">
				<input type="hidden" id="id_cargalectiva" value="<?= htmlspecialchars($id_cargalectiva) ?>">
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
							<textarea type="text" class="areat campoasis form-control" id="tema_tuto"
								style="border-radius: 5px; max-width:100%;height: 50px; min-height: 35px; font-weight: 500;"></textarea>
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
								<!-- <input type="text" id="detalle_sesion" class="form-control" placeholder="Especifique el detalle"> -->
							</div>
						</div>
						<!-- <div class="col-md-6" id="campo_detalles">
							<label for="detalle_sesion">Detalle sesión:</label>
							<input type="text" id="detalle_sesion" class="form-control" placeholder="Especifique el detalle">
						</div> -->
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
							<input type="date" id="start_date" class="form-control input-sm flatpickr" placeholder="">
						</div>
						
						
						<div class="col-md-4">
							<label>Hora Inicial:</label>
							<input type="time" id="start_time" class="form-control input-sm flatpickr" placeholder="">
						</div>
						<div class="col-md-4">
							<label>Hora Final:</label>
							<input type="time" id="end_time" class="form-control input-sm flatpickr" placeholder="">
						</div>

					</div>

				</form>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" onclick="GuardarCitas_TC()">Guardar</button>

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
	/*function mostrarPopup(idEstu) {
		// Crear un objeto XMLHttpRequest
		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'docente/boleta_notas.php', true);

		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

		// Configurar una función de callback para manejar la respuesta
		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4 && xhr.status === 200) {
				// Insertar el contenido de la respuesta en el cuerpo del popup
				document.getElementById('popup-body').innerHTML = xhr.responseText;
				// Mostrar el popup y la superposición
				document.getElementById('popup').style.display = 'block';
				document.getElementById('overlay').style.display = 'block';
			}
		};

		xhr.send('id_estu=' + idEstu);
	}*/
	function mostrarPopup(idEstu) {
		var idCargaLectiva = <?php echo json_encode($id_cargalectiva); ?>;
		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'docente/boleta_notas.php', true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4 && xhr.status === 200) {
				document.getElementById('popup-body').innerHTML = xhr.responseText;
				document.getElementById('popup').style.display = 'block';
				document.getElementById('overlay').style.display = 'block';
			}
		};

		xhr.send('id_estu=' + idEstu + '&id_cargalectiva=' + idCargaLectiva);
	}
	//asignatura
	function mostrarAsi_Popup(idEstu) {
    var idCargaLectiva = <?php echo json_encode($id_cargalectiva); ?>;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'docente/asistencia.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById('popup-body').innerHTML = xhr.responseText;
            document.getElementById('popup').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }
    };
    xhr.send('id_estu=' + idEstu + '&id_cargalectiva=' + idCargaLectiva);
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
	/* function CrearSesionCurso() {
		const idCarga = <?php //echo json_encode($id_cargalectiva); ?>;
		$('#id_cargalectiva').val(idCarga); // este campo está en index.php
		$('#start_date').val(""); // limpiar fechas
		$('#start_time').val("");
		$('#end_time').val("");
		$('#tema_tuto').val("");
		$('#comp_tuto').val("");
		$('#obs_tuto').val("");
		$('#modal-event').modal('show');
	} */
</script>