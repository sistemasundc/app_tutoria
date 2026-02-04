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
</style>

<?php
    session_start();

    require '../../modelo/modelo_apoyo.php';
    $MU = new Apoyo();

    $id_apoyo = $_SESSION['S_IDUSUARIO'];

    $semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);
    $result = $MU->ListarAlumnosReferidos($id_apoyo, "Atendido", $semestre);
?>

<div class="col-md-12">
    <div class="box box-primary box-solid">
        <div class="box-header with-border">
              <h3 class="box-title" style="text-transform: uppercase;">Historial de atencion a estudiante</h3>

              <!-- /.box-tools -->
        </div>
            <!-- /.box-header -->
            <div class="box-body">
            
     
            <div class="table-responsive">

            <table class="table table-striped  nowrap" style="width:100%;" id="customTable">
                <thead style="background-color: #3c8dbc; color: white; ">
                    <tr> 
                        <TH>NOMBRES</TH>
                        <TH>ESCUELA PROFESIONAL</TH>
                        <TH>CICLO</TH>
                        <TH>FECHA</TH>
                        <TH>TELÉFONO</TH>
                        <TH>DOCENTE</TH>
                        <TH>ESTADO</TH>
                        <TH>MOTIVO</TH>
                    </tr>
                </thead>

                <tbody>
                    <?php
                        if (True) {
                            while ($data = mysqli_fetch_assoc($result)) {
                        ?>
                                <tr>
                                    <td><?php echo $data['nombres']; ?></td>
                                    <td><?php echo $data['escuela']; ?></td>
                                    <td><?php echo $data['ciclo']; ?></td>
                                    <td><?php echo $data['fechad']; ?></td>
                                    <td><?php echo $data['telefono']; ?></td>
                                    <td>
                                        <p class="text-truncate" style="max-width: 12em;"><?php echo $data['nombred']; ?></p>
                                       
                                    </td>
                                    <td>
                                        <span class='label label-success' style='font-size: 0.9em;  '><?php echo $data['estado']; ?></span>
                                    </td>
                                    <td>
                                        <p class="text-truncate" style="max-width: 5em;"><?php echo $data['motivo']; ?></p>
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
<div class="modal fade bd-example-modal-lg" id="modal-event" tabindex="-1" role="dialog" aria-labelledby="modal-eventLabel" aria-hidden="true">
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
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Salir</button>
                </div>
                </div>
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
            dom: 'Bfrtilp',
        buttons:[ 
       {
        "extend":    'excel',
        "text":      '<i class="fa fa-file-text-o"></i> Excel ',
           title: 'REPORTE DE DOCENTES',
        "titleAttr": 'Excel',
        "className": 'btn btn-info'
      },{
        "extend":    'csvHtml5',
        "text":      '<i class="fa  fa-file-excel-o"></i> Csv',
           title: 'REPORTE DE DOCENTES',
        "titleAttr": 'cvs',
        "className": 'btn btn-info'
      }
    ],
        });
    });
</script>      <!-- /.box -->
</div>

<script>
$(document).ready(function() {
    //listar_historial_referidos('Atendido');

    $('.js-example-basic-single').select2();

    $("#modal_registro").on('shown.bs.modal',function(){
        $("#txt_usu").focus();  
    })
  

} );
</script>
