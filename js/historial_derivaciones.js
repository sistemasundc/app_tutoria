/* TUTOR CURSOS */
var tabla_historial_derivaciones;
function listar_historial_derivaciones() {
    var iddoc = document.getElementById("textId").value;

    tabla_historial_derivaciones = $("#tabla_historial_derivaciones").DataTable({
       "bFilter": true,
        order: [[1, 'desc']],
        "bLengthChange": false,
        "searching": {
            "regex": false
        },
        "responsive": true,
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
        "lengthMenu": [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        "pageLength": 10,
        "destroy": true,
        "async": false,
        "processing": true,
        "ajax": {
            url: "../controlador/docente/controlador_historial_derivaciones.php",
            type: 'POST',
            data: {
                doce: iddoc
            }
        },
        "columns": [
            {
                "data": "id_der"
            },
            {
                "data": "fecha"
            },
            {
                "data": "nombres"
            },
            {
                "data": "telefono"
            }, 
            {
                "data": "estado",
                render: function(data, type, row) {
                    if (data == 'Atendido') {
                        return "<span class='label label-success' style='font-size: 0.9em;'>"+row.estado+"</span>";
                    } else {
                        return "<span class='label label-warning' style='font-size: 0.9em;'>"+row.estado+"</span>";
                    }
                }
            },
            {
                "data": "des_area"
            },
            {
                "data": "id_estu",
                render: function(data, type, row) {
                    let btnAzul = "<button style='font-size:13px;' type='button' onclick=\"HistorialFormatoDerivado('" + row.id_der + "')\" class='btn btn-primary' title='Formato F6 - Referencia'><i class=\"fa fa-file-text\"></i> F6</button>";
                    
                    let btnVerde = "";
                    if (row.estado === 'Atendido') {
                        btnVerde = " <button style='font-size:13px; background-color: #00a65a; border-color: #00a65a; color: white;' \
                        type='button' onclick=\"FormatoContraReferencia('" + row.id_der + "')\" class='btn' title='Formato Contra-Referencia'><i class='fa fa-file-text'></i> F6</button>";
                    }

                    return btnAzul + btnVerde;
                }
            }
        ],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("tabla_historial_derivaciones_filter").style.display = "none";

    $('input.global_filter').on('keyup click', function () {
        filterUnoGlobal();
    });
}

function filterUnoGlobal() {
    $('#tabla_historial_derivaciones').DataTable().search($('#global_filter').val()).draw();
}

/* TUTOR AULA */

var tabla_historial_derivaciones_TA;
function listar_historial_derivaciones_TA() {
    var iddoc = document.getElementById("textId").value;

    tabla_historial_derivaciones_TA = $("#tabla_historial_derivaciones_ta").DataTable({
       "bFilter": true,
        order: [[1, 'desc']],
        "bLengthChange": false,
        "searching": {
            "regex": false
        },
        "responsive": true,
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
        "lengthMenu": [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        "pageLength": 10,
        "destroy": true,
        "async": false,
        "processing": true,
        "ajax": {
            url: "../controlador/tutor_aula/controlador_historial_derivaciones.php",
            type: 'POST',
            data: {
                doce: iddoc
            }
        },
        "columns": [
            {
                "data": "id_der"
            },
            {
                "data": "fecha"
            },
            {
                "data": "nombres"
            },
            {
                "data": "telefono"
            }, 
            {
                "data": "estado",
                render: function(data, type, row) {
                    if (data == 'Atendido') {
                        return "<span class='label label-success' style='font-size: 0.9em;'>"+row.estado+"</span>";
                    } else {
                        return "<span class='label label-warning' style='font-size: 0.9em;'>"+row.estado+"</span>";
                    }
                }
            },
            {
                "data": "des_area"
            },
            {
                "data": "id_estu",
                render: function(data, type, row) {
                    let btnAzul = "<button style='font-size:13px;' type='button' onclick=\"HistorialFormatoDerivado('" + row.id_der + "')\" class='btn btn-primary' title='Formato F6 - Referencia'><i class=\"fa fa-file-text\"></i> F6</button>";
                    
                    let btnVerde = "";
                    if (row.estado === 'Atendido') {
                        btnVerde = " <button style='font-size:13px; background-color: #00a65a; border-color: #00a65a; color: white;' \
                        type='button' onclick=\"FormatoContraReferencia('" + row.id_der + "')\" class='btn' title='Formato Contra-Referencia'><i class='fa fa-file-text'></i> F6</button>";
                    }

                    return btnAzul + btnVerde;
                }
            }
        ],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("tabla_historial_derivaciones_ta_filter").style.display = "none";

    $('input.global_filter').on('keyup click', function () {
        filterUnoGlobal();
    });
}

function filterUnoGlobal() {
    $('#tabla_historial_derivaciones_ta').DataTable().search($('#global_filter').val()).draw();
}
function FormatoContraReferencia(id_derivacion) {
    // Enviar por POST al archivo index.php el id de derivación con clave 'contraref'
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../pdf_ge/index.php';
    form.target = '_blank';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'contraref';
    input.value = id_derivacion;

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}