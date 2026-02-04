<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], [
    'TUTOR DE AULA', 'TUTOR DE CURSO'
])) {
    die('Acceso no autorizado');
}
?>
<link rel='stylesheet' type='text/css' href='fullcalendar/css/fullcalendar.css' />

<script type='text/javascript' src='fullcalendar/js/moment.min.js'></script>
<script type='text/javascript' src='fullcalendar/js/fullcalendar.min.js'></script>
<script type='text/javascript' src='fullcalendar/js/locale/es.js'></script>
<script type="text/javascript" src="fullcalendar/js/calendar.js"></script>

<style type="text/css">
    .asistencia_check {
        width: 15px;
        height: 15px;
        
        
    }
</style>
<div class="container-fluid" style="background-color: white; width: 98%">

    <div class="row">
        <div id="content" class="col-lg-12">
            <!-- ANTES DEL CALENDARIO -->
            <input type="hidden" id="rol_usuario" value="<?php echo $_SESSION['S_ROL_ID']; ?>">
            <input type="hidden" id="textId" value="<?php echo $_SESSION['S_IDUSUARIO']; ?>">

            <!-- AQUÍ COMIENZA EL CALENDARIO -->
            <div id="calendar">
                <!-- Calendario -->
            </div>
            <div class="modal fade bd-example-modal-lg" id="modal-event" tabindex="-1" role="dialog" aria-labelledby="modal-eventLabel">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content" style="border-radius: 5px;">

                        <div class="modal-header" id="modasis" style="background-color: #00a65a; border: none; border-radius: 5px 5px 0 0;">
                            <h4 class="modal-title" style="color: white; float: left;">Registrar asistencia</h4>
                            <div style="position: absolute; left: 90%; ">
                                <button type="button" class="btn" data-dismiss="modal" style="position: relative; background-color: transparent; color: white;">
                                    <i class="fa fa-times"></i>
                                </button>

                            </div>
                        </div>
                        <div class="modal-body">
                            <div id="event-description"></div>
                            <div class="loadingDiv"></div>
                            <!-- QuickSave/Edit FORM -->
                            <form id="modal-form-body" enctype="multipart/form-data" method="post" onsubmit="return false;">
                            <input type="hidden" id="id_cargalectiva" name="id_cargalectiva">
                            <?php
                                $mapaRoles = [
                                    1 => 'COORDINADOR GENERAL DE TUTORIA',
                                    2 => 'TUTOR DE CURSO',
                                    3 => 'ALUMNO',
                                    4 => 'DIRECTOR DE DEPARTAMENTO ACADEMICO',
                                    5 => 'APOYO',
                                    6 => 'TUTOR DE AULA',
                                    7 => 'DIRECCION DE ESCUELA',
                                    8 => 'SUPERVISIÓN',
                                    9 => 'VICEPRESIDENCIA ACADEMICA'
                                ];
                                $rolTexto = isset($_SESSION['S_ROL_ID']) && isset($mapaRoles[$_SESSION['S_ROL_ID']]) ? strtoupper(trim($mapaRoles[$_SESSION['S_ROL_ID']]))  : 'TUTOR DE AULA';
                                ?>
                                <input type="hidden" id="rol_usuario" value="<?php echo $_SESSION['S_ROL_ID']; ?>">
                                <div class="row">
                                    <!-- TEMA -->
                                    <div class="col-md-12">
                                        <h5><span style="color: red; font-size: 20px;">*</span> Tema</h5>
                                    </div>
                                    <div class="col-md-4" style="margin-bottom: 1em;">
                                        <label>Tema a tratar:</label>
                                        <textarea type="text" name="tem" id="tema_tuto" class="areat campoasis form-control"
                                            style="border-radius: 5px; max-width:100%; height: 50px; min-height: 35px; font-weight: 500;"></textarea>
                                    </div>

                                    <!-- COMPROMISO -->
                                    <div class="col-md-4" style="margin-bottom: 1em;">
                                        <label>Compromiso:</label>
                                        <textarea name="com" id="comp_tuto" class="areat campoasis form-control"
                                            style="border-radius: 5px; max-width:100%; height: 50px; min-height: 35px; font-weight: 500;"></textarea>
                                    </div>

                                    <!-- OBSERVACIONES -->
                                    <div class="col-md-4" style="margin-bottom: 1em;">
                                        <label>Observaciones / Problemas:</label>
                                        <textarea name="obs" id="obs_tuto" class="areat campoasis form-control" placeholder="Opcional"
                                            style="border-radius: 5px; max-width:100%; height: 50px; min-height: 35px; font-weight: 500;"></textarea>
                                    </div>

                                    <!-- TIPO DE SESIÓN -->
                                    <div class="col-md-4">
                                        <div class="btn-group" style="width: 100%; margin-bottom: 10px;">
                                            <label><b>Tipo de sesión:</b></label>
                                            <select name="tip" id="tipo_session" class="global_filter form-control campoasis"
                                                style="width:100%; padding: 7px; border-radius: 5px;">
                                            </select>
                                        </div>
                                    </div>

                                    <!-- DETALLE OPCIONAL -->
                                    <div class="col-md-6">
                                        <div id="campo_detalles">
                                            <input type="text" name="det" id="detalles" class="form-control" placeholder="Detalle (opcional)">
                                        </div>
                                    </div>

                                    <!-- FECHA Y HORAS -->
                                    <div class="col-md-12">
                                        <h5><span style="color: red; font-size: 20px;">*</span> Fecha</h5>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Fecha:</label>
                                        <input type="date" name="fec" id="start_date" class="form-control input-sm flatpickr">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Hora Inicial:</label>
                                        <input type="time" name="ini" id="start_time" class="form-control input-sm flatpickr">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Hora Final:</label>
                                        <input type="time" name="fin" id="end_time" class="form-control input-sm flatpickr">
                                    </div>

                                    <!-- LINK DE SESIÓN -->
                                    <!-- <div class="col-md-12" style="margin-top: 10px;">
                                        <label>Link de la sesión:</label>
                                        <input type="text" name="lik" id="link" class="form-control" placeholder="https://meet.google.com/...">
                                    </div> -->
                                    <!-- ASISTENCIA -->
                                    <div class="col-md-9">
                                        <h5><span style="color: red; font-size: 20px;">*</span> Asistencia</h5>
                                    </div>
                                     <div class="col-md-3">
                                        <h5><span >
                                            Marcar a todos: <input type="checkbox" class="marcar_todo"></span>
                                        </h5>
                                    </div>  
                                    <div class="col-md-12">
                                        <table id="table_alumnos_horario" class="table">
                                            <thead>
                                                <tr>
                                                    <th hidden>id</th>
                                                    <th hidden>Alumnos</th>
                                                    <th hidden>Asistencia</th>
                                                </tr>
                                            </thead>
                                            <tbody id="alumnos_asis_list"></tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="evidencias" style="background-color: #3c8dbc; padding: 5px; color: white;"><!-- <em>(Opcional)</em>   -->SUBIR EVIDENCIAS - máx. 2 imágenes:</label>
                                         <input type="file" name="evidencias[]" id="evidencias" accept="image/*" style="padding: 5px;" multiple>
                                         <div id="preview_evidencias" style="margin-top: 10px;"></div> <!-- visualizar las imagenes cargadas -->
                                        <small class="text-muted">Puedes subir hasta 2 fotos JPG, JPEG o PNG.</small>
                                    </div>  
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer" id="modal_footer">
                            <button type="button" class="btn btn-primary" data-dismiss="modal">Salir</button>
                            <button type="button" class="btn btn-success" id="btn_guardar_asistencia">Guardar Cambios</button>
                            <!-- <button type="button" class="btn btn-success" onclick="ActualizarCalendario()">Guardar Cambios</button> -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="calendarModal" tabindex="-1" role="dialog">
               
    <!-- <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 5px;">
            <div class="modal-header" style="background-color: #3c8dbc; border: none; border-radius: 5px 5px 0 0;">
                <h4 class="modal-title" style="color: white;" id="details-body-title">Programar Sesión de Tutoria</h4>
            </div>
            <div class="modal-body">

                <div class="loadingDiv"></div>

                 ✅ ID CAMBIADO 
                <form id="form_programar_evento">
                        <div class="row">
                            <div class="col-md-12">
                                <h5><span style="color: red; font-size: 20px;">*</span> Configuración de Tema</h5>
                            </div>
                            <div class="col-md-12" style="margin-bottom: 1em;">
                                <label for="">Tema a tratar:</label>
                                <textarea type="text" class="areat campoasis form-control" id="tema_asig"
                                    style="border-radius: 5px; max-width:100%;height: 50px; min-height: 35px;"></textarea>
                            </div>
                            <div class="col-md-12" style="margin-bottom: 1em;">
                                <label for="">Compromiso:</label>
                                <textarea type="text" class="areat campoasis form-control" id="compromiso_asig"
                                    style="border-radius: 5px; max-width:100%;height: 50px; min-height: 35px;"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label><b>Tipo de sesión:</b></label>
                                <select class="global_filter form-control campoasis" id="tipo_session_modal"
                                    style="width:100%; padding: 7px;  border-radius: 5px;"></select>
                            </div>
                            <div class="col-md-6">
                                <label>Link de la sesión: </label>
                                <input class="form-control" name="link_sesion" type="text"
                                    style="border-radius: 5px;">
                            </div>

                            <div class="col-md-12">
                                <h5><span style="color: red; font-size: 20px;">*</span> Configuración de Fecha</h5>
                            </div>

                            <div class="col-md-4">
                                <label>Fecha:</label>
                                <input type="date" name="start_date" class="form-control input-sm flatpickr" id="startDate">
                            </div>
                            <div class="col-md-4">
                                <label>Hora Inicial:</label>
                                <input type="time" class="form-control input-sm flatpickr" name="start_time" id="startTime">
                            </div>
                            <div class="col-md-4">
                                <label>Hora Final:</label>
                                <input type="time" class="form-control input-sm flatpickr" name="end_time" id="endTime">
                            </div>

                            <div class="col-md-12" style="margin-top: 1em;">
                                <label>Color del evento:</label><br>
                                <input type="color" value="#3c8dbc" />
                            </div>
                        </div>
                    </form>

                </div>
                <div class="modal-footer">
                    <button type="button" id="delete-event" class="btn btn-danger">Borrar</button>
                    <button type="button" data-dismiss="modal" class="btn btn-warning">Cancelar</button>
                    <button type="button" id="save-changes" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div> -->

            <!-- Modal -->
            <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            ...
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary">Save changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="fullcalendar/js/calendar.js?v=20240614"></script>