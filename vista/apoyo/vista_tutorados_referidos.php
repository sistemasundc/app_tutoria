<script type="text/javascript" src="../js/apoyo.js?rev=<?php echo time();?>"></script>

<style type="text/css">
    .table-container {
    max-height: 900px; /* Establece la altura máxima deseada */
    overflow-y: auto; /* Agrega una barra de desplazamiento vertical si es necesario */
}

 

.vertical-table th, .vertical-table td {
    text-align: justify; /* Justificar el texto en las celdas */
    word-wrap: break-word; /* Permitir que el texto se divida en varias líneas */
}

.text-truncate {
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        table {
            border-collapse: collapse;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            font-family: 'Roboto', sans-serif;
        }

        th, td {
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
            background-color: #141414;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        td {
            color: #333;
        }

        .historial_dv {
            -webkit-box-shadow: 15px 5px 7px -6px rgba(0,0,0,0.75);
            -moz-box-shadow: 5px 5px 7px -6px rgba(0,0,0,0.75);
            box-shadow: 5px 5px 7px -6px rgba(0,0,0,0.75);
        }
</style>

<?php
    session_start();

    require '../../modelo/modelo_apoyo.php';
    $MU = new Apoyo();

    $id_apoyo = $_SESSION['S_IDUSUARIO']; 
    $semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);
    $result = $MU->ListarAlumnosReferidos($id_apoyo, "Pendiente", $semestre);

?>

<div class="col-md-12">
    <div class="box box-primary box-solid">
        <div class="box-header with-border">
              <h3 class="box-title" style="text-transform: uppercase;">Estudiantes derivados en espera de atención</h3>

              <!-- /.box-tools -->
        </div>
            <!-- /.box-header -->
            <div class="box-body">
            
            <div class="table-responsive">

             <table class="table table-striped  nowrap" style="width:100%;" id="customTable">
                <thead style="background-color: #3c8dbc; color: white; ">
                    <TH>NOMBRES</TH>
                    <TH>ESCUELA</TH>
                    <TH>CICLO</TH>
                    <TH>FECHA</TH>
                    <TH>TELÉFONO</TH>
                    <TH>DOCENTE</TH>
                    <TH>ESTADO</TH>
                    <TH>MOTIVO</TH>
                    <TH>DETALLE</TH>
                </thead>
                <tbody>
                    <?php
                        if (True) {
                            while ($data = mysqli_fetch_assoc($result)) {
                        ?>
                                <tr>
                                    <td><?php echo $data['nombres']; ?></td>
                                    <td class="text-truncate" style="max-width: 7em;"><?php echo $data['escuela']; ?></td>
                                    <td><?php echo $data['ciclo']; ?></td>
                                    <td><?php echo $data['fechad']; ?></td>
                                    <td><?php echo $data['telefono']; ?></td>
                                    <td><?php echo $data['nombred']; ?></td>
                                    <td>
                                        <span class='label label-warning' style='font-size: 0.9em;'><?php echo $data['estado']; ?></span>
                                    </td>
                                    <td>
                                        <p class="text-truncate" style="max-width: 5em; "><?php echo $data['motivo']; ?></p>
                                    </td>

                                    <td align="center">
                                        <button class='btn btn-success motivoder' type='button' style='font-size:13px;'
                                            data-nombre="<?php echo $data['nombred']; ?>"
                                            data-correo="<?php echo $data['correo']; ?>">
                                            <i class='fa fa-file-archive-o' aria-hidden='true'></i>
                                            <span id="der" hidden><?php echo $data['id_der']; ?></span>
                                            <span id="asig" hidden><?php echo $data['id_asig']; ?></span>
                                            <span id="doce" hidden><?php echo $data['id_docente']; ?></span>

                                            <span id="iDestu" hidden><?php echo $data['id_estu']; ?></span>
                                            <span id="estado" hidden>pendiente</span>

                                            <span id="result" hidden><?php echo $data['result']; ?></span>
                                            <span id="obser" hidden><?php echo $data['obser']; ?></span>
                                            <span id="mot" hidden><?php echo $data['motivo']; ?></span>
                                            <span id="nombres" hidden><?php echo $data['nombres']; ?></span>

                                            <!-- NUEVOS CAMPOS PARA IMPRESIÓN -->
                                            <span id="escuela_d" hidden><?php echo $data['escuela']; ?></span>
                                            <span id="ciclo_d" hidden><?php echo $data['ciclo']; ?></span>
                                            <span id="telefono_d" hidden><?php echo $data['telefono']; ?></span>
                                            <span id="turno_d" hidden><?php echo $data['turno'] ?? '---'; ?></span>

                                            <span id="fecha_derivacion" hidden><?php echo $data['fechad']; ?></span>
                                        </button>
                                    </td>
                                  
                                </tr>
                        <?php }
                        } ?>                
                    </tbody>
            </table>
            </div>
        </div>
            <!-- /.box-body -->
    </div>

<div class="modal fade bd-example-modal-lg" id="modal-event" tabindex="-1" role="dialog" aria-labelledby="modal-eventLabel" aria-hidden="true" data-backdrop="false">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content" style="border-radius: 5px;">
                <div class="modal-header" id="dff" style="background-color: #00a65a; border: none; border-radius: 5px 5px 0 0;">
                    <h4 class="modal-title" style="color: white; float: left;">Detalles de derivación</h4>
                    <div style="position: absolute; left: 90%; ">
                        <button type="button" class="btn" data-dismiss="modal"  style="position: relative; background-color: transparent; color: white;">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-body">
                    <div id="event-description"></div>

                    <div class="loadingDiv"></div>

               <div class="row" id="campoder">
                <!--------------------------------------------------------------->

                </div>

            </div>

                <div class="modal-footer">
                   
                   <button onclick="ImprimirDerivacion()" id="btnImprimir" class="btn btn-default"  style="float: left; background-color:rgb(33, 118, 230); color:#f2f2f2;">
                        <i class="fa fa-print"></i> Imprimir
                    </button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Salir</button>
                </div>

                <!-- ---- Contenido oculto para impresión --------
                <div id="contenidoImprimir" style="display:none;">
                    <h2 style="text-align:center;">Reporte de Derivación</h2>
                    <p><strong>Docente que derivó:</strong> <span id="imp_nombre_docente"></span></p>
                    <p><strong>Correo del docente:</strong> <span id="imp_correo_docente"></span></p>
                    <p><strong>Fecha de derivación:</strong> <span id="imp_fecha_derivacion"></span></p>
                    <hr>
                    <p><strong>Escuela Profesional:</strong> <span id="imp_escuela"></span></p>
                    <p><strong>Ciclo:</strong> <span id="imp_ciclo"></span> &nbsp;&nbsp;&nbsp; <strong>Turno:</strong> <span id="imp_turno"></span></p>
                    <p><strong>Estudiante derivado:</strong> <span id="imp_nombre_estu"></span></p>
                    <p><strong>Teléfono:</strong> <span id="imp_telefono_estu"></span></p>
                    <p><strong>Motivo de derivación:</strong> <span id="imp_motivo"></span></p>
                    <p><strong>Resultado:</strong> <span id="imp_resultado"></span></p>
                    <p><strong>Observaciones:</strong> <span id="imp_observaciones"></span></p>
                </div>
                </div> -->
            </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#customTable').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay datos",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(Filtro de _MAX_ total registros)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron coincidencias",
                "paginate": {
                    "first": "Primero",
                    "last": "Ultimo",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "aria": {
                    "sortAscending": ": Activar orden de columna ascendente",
                    "sortDescending": ": Activar orden de columna desendente"
                }
            },
            ordering: false, // Deshabilitar ordenación inicial
            lengthMenu: [[15, 20, 50, 100, -1], [15, 20, 50, 100, "Todos"]],
            pageLength: 15,
        });
    });
</script>      <!-- /.box -->
</div>

<form autocomplete="false" onsubmit="return false">
    <div class="modal fade" id="modal_derivar">
        <div class="modal-dialog">
            <div class="modal-content">
            <input type="text" name="" id="id_der" hidden>
            <input type="text" name="" id="id_doc" hidden>
        <!-- Modal Header -->
                <div class="modal-header" style="border: none;">
                 <center> <h4 class="modal-title"><b>Derivar Estudiante</b></h4>
                 </center>
                  <button type="button" class="close" data-dismiss="modal" style="position: absolute; left: 90%; top: 7%;">&times;</button>
                </div>
                
                <!-- Modal body -->
                <div class="modal-body">
                 <div class="row ">
                    <div class="col-md-12">
                        <div class="box-body">
                            <div class="btn-group" style="width: 100%; margin-bottom: 10px;">
                                <label for=""><b>Area de Apoyo:<b></label>
                                <select class="js-example-basic-single global_filter form-control campoasis " id="areas_apoyo" style="width:100%;" > 
                                </select>
                            </div>
                        </div>  

                        
                    </div>
                    </div>
                    <div class="col-md-12">
                        <label for="">Motivo:</label>
                        <textarea type="text" class="areat campoasis form-control" id="motivo_der" style="border-radius: 5px; max-width: 100%; font-weight: 500"></textarea>
                    </div>

                    <div class="col-md-12">
                        <label for="">Resultado:</label>
                        <textarea type="text" class="areat campoasis form-control" id="resultado_der" style="border-radius: 5px; max-width: 100%; font-weight: 500; margin-bottom: 10px;"></textarea>
                    </div>
                    
                  </div>
                    <br>
                    <br>
        <!-- Modal footer -->
                <div class="modal-footer" style="margin-top: 2em;">
                    <button class="btn btn-primary" onclick="Derivar_estudiante()" ><i class="fa fa-check"><b>&nbsp;Derivar</b></i></button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-close"><b>&nbsp;Cerrar</b></i></button>
                </div>
            </div>
        </div>
    </div>
</form>
    
<form autocomplete="false" onsubmit="return false">
    <div class="modal fade" id="modal_registro" role="dialog" aria-hidden="true" data-backdrop="false">
        <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title"><b>REGISTRO DE CONTRA REFERENCIA DEL ALUMNO</b></h4>
            </div>
            <div class="modal-body">
                <div class="col-lg-12">
                    <label for="">Resultado:</label>
                    <textarea type="text" class="areat campoasis form-control" id="resultder" style="border-radius: 5px; max-width: 100%"></textarea>
                </div>
                <div class="col-lg-12" style="margin-bottom: 10px;">
                    <label for="">Observaciones:</label>
                    <textarea type="text" class="areat campoasis form-control" id="obserder" style="border-radius: 5px; max-width: 100%"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="RegistrarDerivacion()"><i class="fa fa-check"><b>&nbsp;Registrar</b></i></button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-close"><b>&nbsp;Cerrar</b></i></button>
            </div>
        </div>
        </div>
    </div>
</form> 

<script>
$(document).ready(function() {
    //listar_alumnos_referidos('Pendiente');
    listar_areas_apoyo();

    $('.js-example-basic-single').select2();

    $("#modal_registro").on('shown.bs.modal',function(){
        $("#txt_usu").focus();  
    })
  

} );
function ImprimirDerivacion(id_derivacion) {
    if (!id_derivacion || isNaN(id_derivacion)) {
        Swal.fire("Error", "No se encontró el ID de derivación", "error");
        return;
    }

    window.open(
        '../controlador/apoyo/controlador_obtener_docente.php?id_derivacion=' + id_derivacion,
        'ReporteDerivacion',
        'width=900,height=1000,scrollbars=yes,resizable=yes'
    );
}
/* $('#btnImprimir').on('click', function () {
    const idDer = $('#iDestu').siblings('#der').text(); // ← este es tu diseño actual
    if (!idDer) {
        alert("No se encontró el ID de derivación");
        return;
    }

    window.open(
        "../controlador/apoyo/controlador_obtener_docente.php?id_derivacion=" + idDer,
        "_blank",
        "width=900,height=1000"
    );
}); */
</script>
