
<?php
session_start();
/* var_dump($_SESSION); // temporalmente, para revisar TODO
exit; */
// Aquí incluyes tu conexión correctamente:
require_once __DIR__ . "/../../modelo/modelo_conexion.php";
$conexion = new conexion();
$conn = $conexion->conectar();

$id_estu = $_SESSION['S_IDESTU'] ?? null;// Asegúrate de que tu sesión tenga el ID correcto

$mostrarEncuesta = true;

if ($id_estu) {
    $sql = "SELECT 1 FROM tutoria_encuesta_satisfaccion WHERE id_estu = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_estu);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $mostrarEncuesta = false; // Ya respondió
    }

    $stmt->close();
}
?>
<link rel='stylesheet' type='text/css' href='calendar_estu/css/fullcalendar.css' />

<script type='text/javascript' src='calendar_estu/js/moment.min.js'></script>
<script type='text/javascript' src='calendar_estu/js/fullcalendar.min.js'></script>
<script type='text/javascript' src='calendar_estu/js/locale/es.js'></script>
<script type="text/javascript" src="calendar_estu/js/calendar.js"></script>

<style type="text/css">
    .asistencia_check {
        width: 15px;
        height: 15px;
    }
    .rellenar {
        border-color: red;
        border-top: 2px;
    }
    textarea:focus {
        border-radius: 3px;
        border-color: #3c8dbc;
        border: 2px solid #3c8dbc;

        outline-style: none;
    }

    .sep {
        margin-top: 1em;
        width: 100%;
        height: 2px;
        background-color: gray;
    }
</style>

<style type="text/css">
:root {
    --color-inactivo: #5f5050;
    --color-hover: #ffa400;
}
.valoracion {
    display: flex;
    flex-direction: row-reverse;
    position:relative; 
    #left: 20%;
}
.valoracion .btn-star {
    background-color: initial;
    border: 0;
    color: var(--color-inactivo);
    transition: 1s all;
}
.valoracion .btn-star:hover {
    cursor: pointer;
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star:nth-child(1):hover ~ .btn-star {
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star:nth-child(2):hover ~ .btn-star {
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star:nth-child(3):hover ~ .btn-star {
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star:nth-child(4):hover ~ .btn-star {
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star:nth-child(5):hover ~ .btn-star {
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star i {
    font-size: 20px;
}
.box-star {
    position: absolute;
    
}
</style>
<div class="container-fluid" style="background-color: white; width: 98%">
    <div class="row">
        <div id="content" class="col-lg-12">
            
<!--              <div id="popupDiagnostico" style="position: fixed;top: 0;left: 0;width: 100%;height: 100%;background-color: rgba(0,0,0,0.6);display: flex;justify-content: center;align-items: center;z-index: 9999;">
                <div style="background: white; border-radius: 10px; padding: 25px; max-width: 480px;width: 90%;box-shadow: 0 0 20px rgba(0,0,0,0.3); position: relative; text-align: center;">
                    <h2 style="color: #0073b7; font-size: 20px;"> <strong>📝 Prueba Diagnóstica del Programa de Tutoría</strong></h2>
                    <p style="text-align: center; font-size: 14px; margin-top: 10px;">
                    Desde el Programa de Tutoría queremos conocerte mejor para brindarte un acompañamiento académico y personal más efectivo. 🧠💬 
                    <br><br>Te invitamos a completar la <strong>Prueba Diagnóstica.</strong> ⏳
                    </p>
                    <a href="https://forms.gle/28UEX4uh3ajeYWeF6" target="_blank" style=" display: inline-block; background-color: #0073b7; color: white;padding: 10px 20px; border-radius: 5px; text-decoration: none;margin-top: 15px;font-weight: bold;">
                    👉 Ir al formulario
                    </a>
                    <br>
                    <p style="font-size: 13px; color: #888; margin-top: 1em;">Gracias por tu compromiso. 🌱🎓</p>
                    <button onclick="cerrarPopup()" style=" margin-top: 15px;background-color: transparent;  border: none; color: #888; font-size: 13px;cursor: pointer; text-decoration: underline;">
                    Cerrar</button>
                </div>
                
            </div>  -->
            <!-- //-abre php--// if ($mostrarEncuesta): ?>
                <div id="popupEncuesta" style="position: fixed;top: 0; left: 0; width: 100%; height: 100%;background-color: rgba(0,0,0,0.6);display: flex; justify-content: center; align-items: center;z-index: 9999;">
                    <div style="background: #fff;border-radius: 12px;padding: 30px; max-width: 500px;width: 90%;box-shadow: 0 4px 20px rgba(0,0,0,0.25);text-align: center;position: relative;font-family: Arial, sans-serif;">
                        <h2 style="color: #0073b7; font-size: 28px; margin-bottom: 10px;">
                        <strong> ENCUESTA DE SATISFACCIÓN DEL ESTUDIANTE 📊</strong>
                        </h2>

                        <p style="text-align: center; font-size: 18px; margin-top: 10px; color: #333;">
                        Tu opinión es muy importante para seguir mejorando el 
                        <strong>Programa de Tutoría y Consejería Universitaria</strong>.  
                        <br><br>
                        Te invitamos a completar esta breve encuesta sobre las tutorías recibidas en el presente semestre académico.  
                        🙌✨
                        </p>

                        <a href="https://tutoria.undc.edu.pe/vista/encuesta_satisfaccion_estudiantes.php" target="_blank" style="display: inline-block; background-color: #0073b7;color: white;padding: 12px 25px;border-radius: 6px;
                            text-decoration: none;margin-top: 20px;font-weight: bold;font-size: 18px;transition: background-color 0.3s;" onmouseover="this.style.backgroundColor='#005a91'" onmouseout="this.style.backgroundColor='#0073b7'">
                        👉 Ir a la Encuesta
                        </a>

                        <p style="font-size: 17px; color: #777; margin-top: 1.2em;">
                        Gracias por tu tiempo y compromiso.🎓
                        </p>

                        <button onclick="cerrarPopup()" style="margin-top: 15px;background-color: transparent;border: none;color: #888;font-size: 13px; cursor: pointer;text-decoration: underline;">
                        Cerrar
                        </button>
                    </div>
                </div>
                //-abre php--// endif; ?> -->



            <div id="calendar">
                <!-- Calendario -->
            </div>
            <div class="modal fade" id="modal-event" tabindex="-1" data-backdrop="false" data-keyboard="false" role="dialog" aria-labelledby="modal-eventLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content" style="border-radius: 5px;">
                    <div class="modal-header" id="modasis" style="background-color: #00a65a; border: none; border-radius: 5px 5px 0 0;">
                        <h4 class="modal-title" style="color: white;">Registrar asistencia</h4>
                    </div>

                    <div class="modal-body">
                        <div id="event-description"></div>
                        <div class="loadingDiv"></div>
                        
                        <!-- QuickSave/Edit FORM -->
                        <form id="modal-form-body">
                           <div class="row">
                                <!--------------------------------------------------------------->
                                <div class="col-md-12" style="margin-bottom: 1em;"> 
                                    <label><i class="fa fa-user-circle-o" aria-hidden="true" style="color: #3c8dbc;"></i>&nbsp; Docente:</label><br>
                                    <span class="campoasis" id="doce_tuto" style="text-align: justify;">No registrado</span> 
                                </div>
                                <div class="col-md-12" style="margin-bottom: 1em;"> 
                                    <label><i class="fa fa-bookmark" aria-hidden="true" style="color: #3c8dbc;"></i>&nbsp;
                                    Tema:</label><br>
                                    <p class="campoasis" id="tema_tuto" style="text-align: justify; max-width: 100%; width: 100%;" >
                                        No registrado
                                    </p>
                                </div>
                                <!--------------------------------------------------------------->
                                 <div class="col-md-4">
                                    <label><i class="fa fa-calendar-o" aria-hidden="true" style="color: #3c8dbc;"></i>&nbsp;
                                    Fecha:</label><br>
                                    <span id="start_date">No registrado</span> 
                                </div>
                                <div class="col-md-4">
                                    <label><i class="fa fa-clock-o" aria-hidden="true" style="color: #3c8dbc;"></i>&nbsp;
                                    Hora Inicial:</label><br>
                                    <span id="start_time">No registrado</span> 
                                </div>
                                <div class="col-md-4">
                                    <label><i class="fa fa-clock-o" aria-hidden="true" style="color: #3c8dbc;"></i>&nbsp;
                                    Hora Final:</label><br>
                                    <span id="end_time">No registrado</span> 
                                </div>

                                <!--------------------------------------------------------------->
                                <div class="col-md-12" style="margin-bottom: 1.5em; margin-top: 1em;"> 
                                    <label><i class="fa fa-sliders" aria-hidden="true" style="color: #3c8dbc;"></i>&nbsp;
                                    Tipo de sesión:</label><br>
                                    <span class="campoasis" id="tipo_session">No registrado</span> 
                                    
                                </div>  
                                
                                <!--------------------------------------------------------------->
                                <div class="col-md-12" id="box_comentario">
                                    
                                </div>
                                <!--------------------------------------------------------------->
                                <div class="col-md-12 mt-3" style=" margin-top: 1em;" >
                                    <div id="valo_tuto">
                                        
                                    </div>
                                    <div class="box-star" id="box_valoracion" hidden>
                                        <div class="valoracion">

                                            <button type="button" class="btn-star" name="5">
                                                <i class="fa fa-star iconstar"></i>
                                            </button>

                                            <button type="button" class="btn-star" name="4">
                                                <i class="fa fa-star iconstar"></i>
                                            </button>

                                            <button type="button" class="btn-star" name="3">
                                                <i class="fa fa-star iconstar"></i>
                                            </button>

                                            <button type="button" class="btn-star" name="2">
                                                <i class="fa fa-star iconstar"></i>
                                            </button>

                                            <button type="button" class="btn-star" name="1">
                                                <i class="fa fa-star iconstar"></i>
                                            </button>

                                        </div>
                                    </div>
                                    <br>
                                </div>
                                <!--------------------------------------------------------------->
                                
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success" type="button" onclick="GuardarComentario()">
                            Guardar
                        </button>

                        <button type="button" class="btn btn-danger" data-dismiss="modal">Salir</button>
                </div>
            </div>
                    </div>
                    </div>
                </div>

            </div> 
        </div> 
    </div>
</div>
<!-- <script>
  /* function cerrarPopup() {
    document.getElementById("popupDiagnostico").style.display = "none";
  } */
</script> -->

<script>
    function cerrarPopup() {
    document.getElementById('popupEncuesta').style.display = 'none';
    }
</script>
