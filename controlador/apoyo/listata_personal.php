<?php
include_once "../../modelo/modelo_conexion.php";

// Crea una instancia de la clase de conexión
$miConexion = new conexion();

// Conecta a la base de datos
$miConexion->conectar();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        

        #customTable {
            width: 99%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        #customTable th,
        #customTable td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        #customTable th {
            background-color: #000;
            color: #fff;
        }

        #customTable tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <!-- Begin Page Content -->
    <div class="container-fluid" style="width: 95%; background:#e5e5e5;" >

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h4 mb-0 text-gray-800"> <strong>LISTA ALUMNOS DERIVADO AL ÁREA</strong></h3>
        </div>

        <div class="row">
            <div class="col-lg-12">

                  <div class="table-responsive">
                <table class="table table-striped table-bordered" id="customTable">
                    <thead class="table-dark">
                        <tr>
                            <th width="8%">ID</th>
                            <th width="12%">FECHA</th>
                            <th width="32%">NOMBRES</th>
                            <th width="34%">DOCENTE</th>
                            <th width="18%" align="center">ESTADO</th>
                            <th width="18%" align="center">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Utilizar MySQLi en lugar de PDO
                        $query = "SELECT * FROM tutoria_derivacion_tutorado_f6";

                        $result = $miConexion->conexion->query($query);

                        if (mysqli_num_rows($result) > 0) {
                            while ($data = $result->fetch_assoc()) {
                        ?>
                                <tr>
                                    <td><?php echo $data['id_derivaciones']; ?></td>
                                    <td><?php echo $data['fecha']; ?></td>
                                    <td><?php echo $data['hora'] . " " . $data['fechaDerivacion'] . " " . $data['id_derivaciones']; ?></td>
                                    <td><?php echo $data['motivo_ref']; ?></td>
                                    <td><?php echo $data['motivo_ref']; ?></td>

                                    <td align="center">
                                        <a href="personal_jefe.php?codigo=<?php echo $data['id_derivaciones']; ?>&nombre=<?php echo $data['id_derivaciones']; ?>" class="btn btn-primary"><i ></i>Atender</a>
                                    </td>
                                </tr>
                        <?php }
                        } ?>
                    </tbody>
                </table>
            </div>

            </div>
        </div>
    </div>
</body>

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
        });
    });
</script>

</html>
