var id_apoyo = $('#textId').val();

var table_docente;
function listar_Docentes() {

    table_docente = $("#tabla_Docentex").DataTable({
        "ordering": true,
        "bLengthChange": false,
        "searching": {
            "regex": false
        },

        "responsive": true,
        dom: 'Bfrtilp',
        buttons: [

            {
                "extend": 'excel',
                "text": '<i class="fa fa-file-text-o"></i> Excel ',
                title: 'REPORTE DE DOCENTES',
                "titleAttr": 'Excel',
                "className": 'btn btn-info'
            }, {
                "extend": 'csvHtml5',
                "text": '<i class="fa  fa-file-excel-o"></i> Csv',
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
            url: "../controlador/docente/controlador_reporte_Docentes.php",
            type: 'POST'
        },

        "columns": [{
            "data": "iddocente"
        }, {
            "data": "nombre"
        }, {
            "data": "apellido"
        }, {
            "data": "sexo",
            render: function (data, type, row) {
                if (data == 'M') {
                    return "MASCULINO";
                } else {
                    return "FEMINO";
                }
            }
        }, {
            "data": "tipo"
        }, {
            "data": "dni"
        }, {
            "data": "status",
            render: function (data, type, row) {
                if (data == 'ACTIVO') {
                    return "<span class='label label-success'>" + data + "</span>";
                } else {
                    return "<span class='label label-danger'>" + data + "</span>";
                }
            }
        }],
        "language": idioma_espanol,
        select: true
    });
}

var tab_alumno;
var tab_alumno_es;
var tabla_asignado;

$("#ciclo_estu").on('change', function () {
    var selectedValue = $(this).val();
    tab_alumno_es.column(4).search('^' + selectedValue + '$', true, false).draw();
});
$("#sem").on('change', function () {
    var selectedValue = $(this).val();
    tabla_asignado.column(4).search('^' + selectedValue + '$', true, false).draw();
});




function listar_Alumnos() {

    tab_alumno_es = $("#table_alumnox").DataTable({
        "bFilter": false,
        "ordering": true,
        "bLengthChange": false,
        "searching": {
            "regex": false
        },
        "responsive": true,
        dom: 'Bfrtilp',
        buttons: [
            {
                extend: 'pdfHtml5',
                text: '<i class="fa fa-file-pdf-o"></i> PDF',
                title: 'LISTA DE ALUMNOS',
                className: 'btn btn-danger',
                customize: function (doc) {
                    // Puedes personalizar el PDF aquí si es necesario
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Print',
                title: 'LISTA DE ALUMNOS',
                titleAttr: 'Imprimir',
                className: 'btn btn-info'
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-text-o"></i> Excel',
                title: 'LISTA DE ALUMNOS',
                titleAttr: 'Excel',
                className: 'btn btn-info'
            },
            {
                extend: 'csvHtml5',
                text: '<i class="fa fa-file-excel-o"></i> Csv',
                title: 'LISTA DE ALUMNOS',
                titleAttr: 'Csv',
                className: 'btn btn-info'
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
            url: "../controlador/alumno/controlador_lista_Alumno.php",
            type: 'POST',

        },
        "columns": [
            {
                "data": "idestu"
            },
            {
                "data": "apellido_completo"
            },
            {
                "data": "nombres"
            },
            {
                "data": "telefono"
            },
            {
                "data": "des_ciclo"
            },
            {
                "data": "dni"
            },
            {
                "data": "cor_inst"
            },
            {
                "data": "semestre"
            },
            {
                "defaultContent": "<button style='font-size:13px;' type='button' class='cambiartutor btn btn-primary'\
                 title='cambiartutor'>Cambiar tutor</button>"
            }
        ],
        "language": idioma_espanol,
        select: true
    });

    document.getElementById("table_alumnox_filter").style.display = "none";

    $('input.global_filter').on('keyup click', function () {
        filterUnoGlobal();
    });
    $('input.column_filter').on('keyup click', function () {
        filterColumn($(this).parents('tr').attr('data-column'));
    });
}

$('#table_alumnox').on('click', '.cambiartutor', function () {
  const $modal = $("#cambiarTutorModal");
  const $sel   = $("#docentes-tutores");

  // Abrir SIN bloquear el fondo
  $modal.modal({ backdrop: false, keyboard: true });

  // Mensaje mientras carga
  $sel.html("<option value=''>Espere un momento...</option>");

  // (Re)inicializa Select2 dentro del modal
  if ($sel.data('select2')) $sel.select2('destroy');
  $sel.select2({
    width: '100%',
    dropdownParent: $modal,
    placeholder: 'Selecciona un docente'
  });

  // si quedó alguna capa previa, elimínala
  $('.modal-backdrop').remove();

  // --- tu código tal cual a partir de aquí ---
  var tabla = tab_alumno_es;
  var data  = tabla.row($(this).parents('tr')).data();
  if (tabla.row(this).child.isShown()) { data = tabla.row(this).data(); }

  var des_ciclo     = data.des_ciclo;
  var id_estudiante = data.idestu;
  document.getElementById('textId_es').value = id_estudiante;


    var id_alumno = $("#textId").val();
    var mistutores = document.getElementById('tutores-asignados');
    var data_tutores = "";
    var data_docente = 0;

    $.ajax({
        "url": "../controlador/alumno/controlador_listar_mi_tutor.php",
        type: 'POST',
        data: {
            idalumno: id_estudiante
        }
    }).done(function (resp) {
        if (resp == 0) {
            data_tutores = `
                <label for="">Docente:</label> <span>No tiene docentes asignados</span>`;
        } else {
            data_docente = 1;
            var datos = JSON.parse(resp);

            for (var i = 0; i < datos.length; i++) {
                data_tutores += `
                  <label for="">Docente:</label> <span>${datos[i]['nombres']}</span><br>
                  <label for="">Correo:</label> <span>${datos[i]['correo']}</span><br>
                  <label for="">Número:</label> <span>${datos[i]['telefono']}</span>
                  <br><br>
                  `;
            }
        }
        mistutores.innerHTML = data_tutores;

        if (data_docente == 0 || data_docente == null) {
            cadena = "<label for=\"\">Aún no se ha asignado un tutor</label>";
            $("#combotutores").html(cadena);
            return;
        }

        $.ajax({
            "url": "../controlador/coordinador/controlador_combo_docentes_tutores.php",
            type: 'POST',
            data: {
                ciclo: des_ciclo,
                id_estu: id_estudiante
            }
        }).done(function (resp) {
            var data = JSON.parse(resp);

            cadena = "";
            if (data.length > 0) {
                cadena += "<option value='0' selected>Selecciona un docente</option>";
                for (var i = 0; i < data.length; i++) {
                    cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
                }
                $('#docentes-tutores').html(cadena);////lamndo en vista matricula 
            } else {
                cadena += "<option value=''>NO SE ENCONTRARON REGISTROS</option>";
                $("#docentes-tutores").html(cadena);
            }
        })
    });


});

function CambiarTutor() {
    var id_tutor = $('#docentes-tutores').val();
    var id_estu = $('#textId_es').val();
    var id_cor = $('#textId').val(); // <-- ahora con jQuery, correctamente

    // Validar campos obligatorios
    if (!id_cor || !id_tutor || !id_estu) {
        Swal.fire("Mensaje de Alerta", "Faltan datos requeridos para asignar el tutor.", "warning");
        return;
    }

    // Confirmaci�n
    Swal.fire({
        title: "¿Está seguro de cambiar el tutor?",
        text: "Este estudiante tendrá un solo tutor asignado.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Sí, cambiar",
        cancelButtonText: "Cancelar",
    }).then((confirmar) => {
        if (confirmar.isConfirmed) {
            $.ajax({
                url: "../controlador/coordinador/controlador_actualizar_tutor_asignado.php",
                type: 'POST',
                data: {
                    tutor: id_tutor,
                    cor: id_cor,
                    estu: id_estu
                }
            }).done(function (resp) {
                if (resp == 1) {
                    $('#cambiarTutorModal').modal('hide');
                    Swal.fire("Éxito", "Tutor asignado correctamente.", "success");
                } else if (resp == 'existente') {
                    Swal.fire("Atención", "Este estudiante ya tiene un tutor asignado.", "info");
                } else {
                    Swal.fire("Error", "No se pudo registrar la asignación.", "error");
                }
            });
        }
    });
}


function listar_Alumnos_Asignados() {
    var iddoc = document.getElementById("textId").value;

    tabla_asignado = $("#table_alumno_asignado").DataTable({
        "bFilter": true,
        "ordering": true,
        "bLengthChange": false,
        "searching": {
            "regex": false
        },
        "responsive": true,
        dom: 'Bfrtilp',
        "order": [[8, 'desc']], // Ordenar por la columna "tipo" (��ndice 6) en orden ascendente
        buttons: [
            {
                extend: 'pdfHtml5',
                text: '<i class="fa fa-file-pdf-o"></i> PDF',
                title: 'LISTA DE ALUMNOS',
                className: 'btn btn-danger',
                customize: function (doc) {
                    // Puedes personalizar el PDF aquí si es necesario
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Print',
                title: 'LISTA DE ALUMNOS',
                titleAttr: 'Imprimir',
                className: 'btn btn-info'
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-text-o"></i> Excel',
                title: 'LISTA DE ALUMNOS',
                titleAttr: 'Excel',
                className: 'btn btn-info'
            },
            {
                extend: 'csvHtml5',
                text: '<i class="fa fa-file-excel-o"></i> Csv',
                title: 'LISTA DE ALUMNOS',
                titleAttr: 'Csv',
                className: 'btn btn-info'
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
            url: "../controlador/alumno/controlador_alumnos_asignados.php",
            type: 'POST',
            data: {
                iddocente: iddoc
            }
        },
        "columns": [
            {
                "data": "id_asig"
            },
            {
                "data": "id_estu"
            },
            {
                "data": "apellido_completo"
            },
            {
                "data": "nombres"
            },
            {
                "data": "telefono"
            },
            {
                "data": "des_ciclo"
            },
            {
                "data": "dni"
            },
            {
                "data": "cor_inst"
            },

            {
                "data": "tipo",
                render: function (data, type, row) {
                    if (data == 'Grupal') {
                        return "<span class='label label-primary' style='font-size: 0.9em;'>" + data + "</span>";
                    } else if (data == 'Derivado') {
                        return "<span class='label label-info' style='font-size: 0.9em;'>" + data + "</span>";
                    } else {
                        return "<span class='label label-warning' style='font-size: 0.9em;'>" + data + "</span>";
                    }
                }
            },
            {
                "data": "rendimiento",
                render: function (data, type, row) {
                    if (data == '1') {
                        return "<span class='label label-danger' style='font-size: 0.9em;'>Bajo</span>";
                    } else {
                        return "<span class='label label-success' style='font-size: 0.9em;'>Normal</span>";
                    }
                }
            },
            {
                "data": "tipo",
                render: function (data, type, row) {
                    if (data == 'Derivado') {
                        return "<button style='font-size:13px;' type='button' onclick='FormatoDerivado(" + row.id_estu + ")' class='btn btn-info'>\
                        <i class='fa fa-share'>&nbsp; Generar</i></button>\
                        <button style='font-size:13px;' type='button' onclick='cargarHorario1()' class='btn btn-info'>\
                        <i class='fa fa-share'>&nbsp; Citas</i></button>\
                        <button style='font-size:13px;' type='button' onclick='FormatoTuto("+ row.id_estu + ")' class='btn btn-warning'>\
                        <i class='fa fa-file-text'>&nbsp; Tutoria</i></button>";
                    } else {
                        return "<button style='font-size:13px;' type='button' class='derivar btn btn-primary'>\
                        <i class='fa fa-share'>&nbsp; Derivar</i></button>\
                        <button style='font-size:13px;' type='button' onclick='FormatoTuto("+ row.id_estu + ")' class='btn btn-warning'>\
                        <i class='fa fa-file-text'>&nbsp; Tutoria</i></button>";
                    }
                }
            }

        ],
        "language": idioma_espanol,
        select: true

    });
    document.getElementById("table_alumno_asignado_filter").style.display = "none";

    $('input.global_filter').on('keyup click', function () {
        filterUnoGlobal();
    });
    $('#tipo_asignacion').on('change click', function () {
        $('#table_alumno_asignado').DataTable().search($('#tipo_asignacion').val()).draw();
    });
    $('input.column_filter').on('keyup click', function () {
        filterColumn($(this).parents('tr').attr('data-column'));
    });

}



function FormatoDerivado(estu_id) {
    var formDer = document.getElementById('formder');
    var estuder = document.getElementById('estuder');
    var doceder = document.getElementById('doceder');

    estuder.value = estu_id;
    doceder.value = $('#textId').val();
    formDer.submit();
}
//tutor de aula
var tabla_sessiones;

function listar_mis_sesiones() {
    var iddoc = document.getElementById("textId").value;
    var hoy = document.getElementById("ntoday").value;

    tabla_sessiones = $("#tabla_historial_sesiones").DataTable({
        "bFilter": true,
        "ordering": true,
        "bLengthChange": false,
        "searching": {
            "regex": false
        },
        "responsive": true,

        "lengthMenu": [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        "pageLength": 10,
        "destroy": true,
        "async": false,
        "processing": true,
        "ajax": {
            url: "../controlador/tutor_aula/controlador_sessiones_tutoria.php",
            type: 'POST',
            data: {
                doce: iddoc,
                dia: hoy
            }
        },
        "columns": [
            {
                "data": "id_estu"
            },
            {
                "data": "inicio"
            },
            {
                "data": "final"
            },
            {
                "data": "tipo",
                render: function (data, type, row) {
                    if (data == '2') {
                        return "<span class='label label-primary' style='font-size: 0.9em;'>Grupal</span>";
                    } else {
                        return "<span class='label label-warning' style='font-size: 0.9em;'>" + row.estudiante + "</span>";
                    }
                }
            },
            {
                "defaultContent": "<button style='font-size:13px;' type='button' class='sesiones btn btn-primary' \
                    title='Sesiones'><i class=\"fa fa-bookmark\" aria-hidden=\"true\" ></i>&nbsp; Asistencia </button>"
            }
        ],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("table_sessiones_filter").style.display = "none";

    $('input.global_filter').on('keyup click', function () {
        filterUnoGlobal();
    });
}

//-------------------------------------TUTOR CURSO--------------------------------------------
var tabla_sessiones_curso;

function listar_mis_sesiones_curso() {
    console.log('holi');
    var iddoc = document.getElementById("textId").value;
    var hoy = document.getElementById("ntoday").value;
    console.log(iddoc);
    console.log(hoy);
    tabla_sessiones_curso = $("#tabla_historial_sesiones_curso").DataTable({
        "bFilter": true,
        "ordering": true,
        "bLengthChange": false,
        "searching": { "regex": false },
        "responsive": true,
        "lengthMenu": [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        "pageLength": 10,
        "destroy": true,
        "async": false,
        "processing": true,
        "ajax": {
            url: "../controlador/docente/controlador_sessiones_tutoria.php",
            type: 'POST',
            data: {
                iddoc: iddoc,
                dia: hoy

            }
        },
        "columns": [
            { "data": "id_estu" },
            { "data": "inicio" },
            { "data": "final" },
            {
                "data": "tipo",
                render: function (data, type, row) {
                    if (data == '2') {
                        return "<span class='label label-primary' style='font-size: 0.9em;'>Grupal</span>";
                    } else {
                        return "<span class='label label-warning' style='font-size: 0.9em;'>" + row.estudiante + "</span>";
                    }
                }
            },
            {
                "defaultContent": "<button style='font-size:13px;' type='button' class='sesiones btn btn-primary' title='Sesiones'><i class=\"fa fa-bookmark\" aria-hidden=\"true\"></i>&nbsp; Asistencia </button>"
            }
        ],
        "language": idioma_espanol,
        select: true
    });

    document.getElementById("table_sessiones_filter").style.display = "none";
}


var tabla_asistencia_estudiante;
function listar_asistencia_estudiante(idestu, tipo_asg, hora_inicio) {

    var iddoc = document.getElementById("textId").value;
    var hoy = document.getElementById("ntoday").value;

    tabla_asistencia_estudiante = $("#table_asistencia_estudiante").DataTable({
        "bFilter": true,
        "ordering": true,
        "bLengthChange": false,
        "searching": {
            "regex": false
        },
        "responsive": true,

        "lengthMenu": [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        "pageLength": 45,
        "destroy": true,
        "async": false,
        "processing": true,
        "ajax": {
            url: "../controlador/docente/controlador_table_asistencia_estudiante.php",
            type: 'POST',
            data: {
                doce: iddoc,
                dia: hoy,
                estu: idestu,
                tipo: tipo_asg,
                hora: hora_inicio
            }
        },
        "columns": [
            {
                "data": "id_asig"
            },
            {
                "data": "estudiante"
            },
            {
                "defaultContent": "<div class='checkbox'>\
                              <label class='checkbox__container'>\
                                <input class='checkbox__toggle check_asistencia_estu' type='checkbox'>\
                                <span class='checkbox__checker'></span>\
                                <svg class='checkbox__bg' space='preserve' style='enable-background:new 0 0 110 43.76;' version='1.1' viewbox='0 0 110 43.76'>\
                                  <path class='shape asisbg' d='M88.256,43.76c12.188,0,21.88-9.796,21.88-21.88S100.247,0,88.256,0c-15.745,0-20.67,12.281-33.257,12.281,S38.16,0,21.731,0C9.622,0-0.149,9.796-0.149,21.88s9.672,21.88,21.88,21.88c17.519,0,20.67-13.384,33.263-13.384,S72.784,43.76,88.256,43.76z'></path>\
                                </svg>\
                              </label>\
                            </div>"
            }
        ],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("table_asistencia_estudiante_filter").style.display = "none";

    $('input.global_filter2').on('keyup click', function () {
        filterUnoGlobal();
    });


}

var asistenciaPrecargada = false;
var id_sesion_precargada = 0;
var hora_inicio = "";
var hora_fin = "";
var id_estu = "";
var checkbox_todos = document.getElementById("checkbox_todos");
var box_todos = document.getElementById("box_todos");

$('#table_sessiones').on('click', '.sesiones', function () {
    var tabla = tabla_sessiones;
    var data = tabla.row($(this).parents('tr')).data();

    if (tabla.row(this).child.isShown()) {
        var data = tabla.row(this).data();
    }

    hora_inicio = data.inicio;
    hora_fin = data.final;
    id_estu = data.id_estu;
    tipo_asg = data.tipo;

    asistenciaPrecargada = false;
    checkbox_todos.checked = false;
    box_todos.hidden = false;

    // Crear una promesa simulada con setTimeout
    var listarAsistenciaPromise = new Promise(function (resolve, reject) {
        listar_asistencia_estudiante(id_estu, tipo_asg, hora_inicio);
        setTimeout(function () {
            resolve(); // Resuelve la promesa después de la función
        }, 1000);
    });

    // Encadenar la ejecución de PrecargarAsistencia después de que la promesa se resuelva
    listarAsistenciaPromise.then(function () {
        PrecargarAsistencia();
    });
});

function PrecargarAsistencia() {
    var iddoc = document.getElementById("textId").value;
    var today = document.getElementById("ftoday").value;

    $.ajax({
        "url": "../controlador/docente/controlador_asistencias_guardadas.php",
        type: 'POST',
        data: {
            inicio: hora_inicio,
            final: hora_fin,
            iddoce: iddoc,
            fecha: today
        }
    }).done(function (resp) {
        //--modify new
        var campos_asis = document.getElementsByClassName('campoasis');
        var len_tarea = campos_asis.length;
        var box_otros = document.getElementById("esp_otros");

        if (resp != 0) {
            var data = JSON.parse(resp);

            asistenciaPrecargada = true;

            id_sesion_precargada = data[0][6];

            //box_otros.hidden = false;

            //campos de textarea
            for (var i = 0; i < len_tarea; i++) {

                if (data[0][3] == 5) {
                    box_otros.hidden = false;
                } else {
                    box_otros.hidden = true;
                }

                campos_asis[i].value = data[0][i];

            }
            var tabla = tabla_asistencia_estudiante;
            var data_table = tabla.rows().data();

            var arr_asig = new Array();
            //extraer los id_asig de la tabla de estudiantes para hacer la comparacion de asistencia o no y poner checkbox = true
            for (var i = 0; i <= data_table.length - 1; i++) {
                arr_asig.push(data_table[i]['id_asig']);
            }

            //checkbox
            var asis_checkbox = document.getElementsByClassName("check_asistencia_estu");

            //funcion 2
            for (var i = 0; i < data.length; i++) {
                var valor = parseInt(data[i][5]);

                if (arr_asig.indexOf(valor)) {
                    var indice_asig = arr_asig.indexOf(data[i][5]);
                    asis_checkbox[indice_asig].checked = true;
                    asis_checkbox[indice_asig].disabled = true;
                }
            }
        } else {
            /*if (box_otros.hidden == false){
                box_otros.hidden = true;
            }*/
            for (var i = 0; i < len_tarea; i++) {
                if (i == 3) {
                    campos_asis[i].value = '1';
                } else {
                    campos_asis[i].value = '';
                }
            }
            box_otros.hidden = true;
        }
    });
}


$("#checkbox_todos").change(function () {
    var checkboxes = document.getElementsByClassName("check_asistencia_estu");
    //var asisbg = document.getElementsByClassName("asisbg");

    if ($(this).is(":checked")) {
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = true;
            //asisbg[i].style = "fill: #71d6b5;";
        }

    } else {
        for (var i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].disabled != true) {
                checkboxes[i].checked = false;
            }
            //asisbg[i].style = "fill: #aaaaaa;";
        }
    }
});
$('#tipo_session').change(function () {
    var valor_tipo = $('#tipo_session').val();
    var box_otros = document.getElementById("esp_otros");

    if (valor_tipo == 5) {
        box_otros.hidden = false;
    } else {
        box_otros.hidden = true;
    }
});

/* TUTOR DE AULA */
var tabla_historial_sesiones_TA;
function listar_historial_sesiones_TA() {
    var iddoc = document.getElementById("textId").value;

    tabla_historial_sesiones_TA = $("#tabla_historial_sesiones_ta").DataTable({
        "bFilter": true,
        "ordering": false,
        "bLengthChange": false,
        "searching": {
            "regex": false
        },
        "responsive": true,
        dom: 'Bfrtilp',
        buttons: [

            {
                "extend": 'excel',
                "text": '<i class="fa fa-file-text-o"></i> Excel ',
                title: 'REPORTE DE DOCENTES',
                "titleAttr": 'Excel',
                "className": 'btn btn-info'
            }, {
                "extend": 'csvHtml5',
                "text": '<i class="fa  fa-file-excel-o"></i> Csv',
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
            url: "../controlador/tutor_aula/controlador_historial_sesiones.php",
            type: 'POST',
            data: {
                doce: iddoc
            }
        },
        "columns": [
            {
                "data": "id_tuto"
            },
            {
                "data": "tema",
                render: function (data, type, row) {
                    return "<p class=\"text-truncate\" style=\"max-width:500px;\">" + data + "</p>";
                }
            },
            {
                "data": "fecha"
            },
            {
                "data": "horas"
            },
            {
                "data": "tipo"
            },
            {
                "data": "idtipo",
                render: function (data, type, row) {
                    if (data == '2') {
                        return "<span class='label label-primary' style='font-size: 0.9em;'>Grupal</span>";
                    } else {
                        return "<span class='label label-warning' style='font-size: 0.9em;'>Individual</span>";
                    }
                }
            },
            {
                "data": "id_tuto",
                render: function (data, type, row) {
                    var formato = row.idtipo == '2' ? 7 : 8;

                    return `<button style='font-size:13px;' type='button' onclick='Generar("${row.id_tuto}")' class=' btn btn-primary' \
                title='Sesiones'><i class=\"fa fa-file-text\" aria-hidden=\"true\"></i> &nbsp; F${formato}</button>`;
                }
            }
        ],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("tabla_historial_sesiones_ta_filter").style.display = "none";

    $('input.global_filter').on('keyup click', function () {
        filterUnoGlobal();
    });
}

/* DOCENTE */
var tabla_historial_sesiones_curso;
function listar_historial_sesiones_curso() {
    var iddoc = document.getElementById("textId").value;

    tabla_historial_sesiones_curso = $("#tabla_historial_sesiones_curso").DataTable({
        "bFilter": true,
        "ordering": false,
        "bLengthChange": false,
        "searching": {
            "regex": false
        },
        "responsive": true,
        dom: 'Bfrtilp',
        buttons: [

            {
                "extend": 'excel',
                "text": '<i class="fa fa-file-text-o"></i> Excel ',
                title: 'REPORTE DE DOCENTES',
                "titleAttr": 'Excel',
                "className": 'btn btn-info'
            }, {
                "extend": 'csvHtml5',
                "text": '<i class="fa  fa-file-excel-o"></i> Csv',
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
            url: "../controlador/docente/controlador_historial_sesiones.php",
            type: 'POST',
            data: {
                doce: iddoc
            }
        },
        "columns": [
            {
                "data": "id_tuto"
            },
            {
                "data": "tema",
                render: function (data, type, row) {
                    return "<p class=\"text-truncate\" style=\"max-width:500px;\">" + data + "</p>";
                }
            },
            {
                "data": "fecha"
            },
            {
                "data": "horas"
            },
            {
                "data": "tipo"
            },
            {
                "data": "idtipo",
                render: function (data, type, row) {
                    if (data == '2') {
                        return "<span class='label label-primary' style='font-size: 0.9em;'>Grupal</span>";
                    } else {
                        return "<span class='label label-warning' style='font-size: 0.9em;'>Individual</span>";
                    }
                }
            },
            {
                data: "id_tuto",
                render: function (data, type, row) {
                    return `
      <button
        style="font-size:13px;"
        type="button"
        class="btn btn-primary"
        title="Sesiones"
        onclick="GenerarCurso(${row.id_tuto})"      >
        <i class="fa fa-file-text" aria-hidden="true"></i>&nbsp; F${row.idtipo === 2 ? 8 : 7}
      </button>`;
                }
            }



        ],
        "language": idioma_espanol,
        select: true
    });
    //document.getElementById("tabla_historial_sesiones_filter").style.display = "none";
    var filtroTabla = document.getElementById("tabla_historial_sesiones_filter");
        if (filtroTabla) {
            filtroTabla.style.display = "none";
        }
    $('input.global_filter').on('keyup click', function () {
        filterUnoGlobal();
    });
}

var estu_der = 0;
var id_asig_der = 0;
$('#table_alumno_asignado').on('click', '.derivar', function () {
    var tabla = tabla_asignado;
    var data = tabla.row($(this).parents('tr')).data();

    if (tabla.row(this).child.isShown()) {
        var data = tabla.row(this).data();
    }

    estu_der = data.id_estu;
    id_asig_der = data.id_asig;

    var mot_l = document.getElementById('motivo_der');
    mot_l.value = '';

    $("#modal_derivar").modal({
        backdrop: 'static',
        keyboard: false
    })
    $(".modal-header").css("background-color", "#066da7");
    $(".modal-header").css("color", "white");
    $("#modal_derivar").modal('show');
});
function Derivar_estudiante() {
    var id_doce = $("#textId").val();
    var mot_der = $("#motivo_der").val();
    var id_area = $("#areas_apoyo").val();

    if (mot_der.length <= 0 || estu_der == 0) {
        Swal.fire("Mensaje De Alerta", "Hay campos sin completar.", "warning");
        return false;
    }
    if (id_area == 0) {
        Swal.fire("Mensaje De Alerta", "Ningun area selecionada.", "warning");
        return false;
    }
    $.ajax({
        url: '../controlador/docente/controlador_derivar_alumno.php',
        type: 'POST',
        data: {
            motivo: mot_der,
            area: id_area,
            estu: estu_der,
            doce: id_doce,
            asig: id_asig_der
        }
    }).done(function (resp) {

        if (resp == 1) {
            Swal.fire("Mensaje De Confirmacion", "Actulizado correctamente.", "success");
            tabla_asignado.ajax.reload();
            $("#modal_derivar").modal('hide');
        } else {
            Swal.fire("Mensaje De Error", "No se completo la derivación.", "error");
        }
    });
}
function listar_areas_apoyo() {
    $.ajax({
        "url": "../controlador/docente/controlador_combo_area_apoyo.php",
        type: 'POST'
    }).done(function (resp) {
        var data = JSON.parse(resp);
        var cadena = "";
        if (data.length > 0) {
            for (var i = 0; i < data.length; i++) {
                cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
            }

            $('#areas_apoyo').html(cadena);////lamndo en crer horari
        } else {
            cadena += "<option value=''>NO SE ENCONTRARON REGISTROS</option>";
            $("#areas_apoyo").html(cadena);

        }
    })
}
function listar_tipo_session() {
    $.ajax({
        "url": "../controlador/docente/controlador_combo_session.php",
        type: 'POST'
    }).done(function (resp) {
        var data = JSON.parse(resp);
        var cadena = "";
        if (data.length > 0) {
            for (var i = 0; i < data.length; i++) {
                cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
            }

            $('#tipo_session').html(cadena);////lamndo en crer horari
        } else {
            cadena += "<option value=''>NO SE ENCONTRARON REGISTROS</option>";
            $("#tipo_session").html(cadena);

        }
    })
}


function ActualizarAsistencia() {

    var iddocente = $("#textId").val();
    var tema_asig = $("#tema_asig").val();
    var compr_asig = $("#compr_asig").val();
    var obs_asig = $("#obser").val();
    var tipo_session = $("#tipo_session").val();

    var box_otros = document.getElementById("esp_otros");
    var esp_otros = $("#espes").val();

    if (!box_otros.hidden) {
        if (esp_otros.length <= 0) {
            Swal.fire("Mensaje De Alerta", "Espesifique el tipo se sesión.", "warning");
            return false;
        }
    }
    //var tabla = tabla_asistencia_estudiante;
    var tabla = $("#table_asistencia_estudiante").DataTable();
    var data = tabla.rows().data();

    var arr_asig = new Array();

    var asis_estu = document.getElementsByClassName("check_asistencia_estu");

    if (data.length <= 0) {
        Swal.fire("Mensaje De Alerta", "Selecciona una sesión.", "warning");
        return false;
    } else {
        for (var i = 0; i <= data.length - 1; i++) {
            if (asis_estu[i].checked && asis_estu[i].disabled != true) {
                arr_asig.push(data[i]['id_asig']);
            }
        }
    }

    arr_asig = arr_asig.toString();

    if (tema_asig.length <= 0 || compr_asig.length <= 0 || tipo_session.length <= 0) {
        Swal.fire("Mensaje De Alerta", "Rellene los campos incompletos.", "warning");
        return false;
    }
    if (obs_asig.length <= 0) {
        obs_asig = '.';
    }
    $.ajax({
        url: '../controlador/docente/controlador_actualizar_session_tutoria.php',
        type: 'POST',
        data: {
            id_docente: iddocente,
            tema: tema_asig,
            compromiso: compr_asig,
            obs: obs_asig,
            tipo: tipo_session,
            array_asig: arr_asig,
            reu_otro: esp_otros,
            id_update: id_sesion_precargada
        }
    }).done(function (resp) {

        if (resp == 1) {
            Swal.fire("Mensaje De Confirmacion", "Actulizado correctamente.", "success");
            cargar_contenido('contenido_principal', 'docente/vista_historial_sesiones.php');
        } else {
            Swal.fire("Mensaje De Error", "No se completo la actulización.", "error");
        }
    });
}

function Registrar_session() {

    if (asistenciaPrecargada) {
        ActualizarAsistencia();
    } else {

        var iddocente = $("#textId").val();
        var tema_asig = $("#tema_asig").val();
        var compr_asig = $("#compr_asig").val();
        var obs_asig = $("#obser").val();
        var tipo_session = $("#tipo_session").val();
        var today = $("#ftoday").val();

        var box_otros = document.getElementById("esp_otros");
        var esp_otros = $("#espes").val();

        if (!box_otros.hidden) {
            if (esp_otros.length <= 0) {
                Swal.fire("Mensaje De Alerta", "Espesifique el tipo se sesión.", "warning");
                return false;
            }
        }
        //var tabla = tabla_asistencia_estudiante;
        var tabla = $("#table_asistencia_estudiante").DataTable();
        var data = tabla.rows().data();

        var arr_asig = new Array();

        var asis_estu = document.getElementsByClassName("check_asistencia_estu");

        if (data.length <= 0) {
            Swal.fire("Mensaje De Alerta", "Selecciona una sesión.", "warning");
            return false;
        } else {
            for (var i = 0; i <= data.length - 1; i++) {
                if (asis_estu[i].checked) {
                    arr_asig.push(data[i]['id_asig']);
                }
            }
        }

        arr_asig = arr_asig.toString();

        if (tema_asig.length <= 0 || compr_asig.length <= 0 || tipo_session.length <= 0 || arr_asig.length <= 0) {
            Swal.fire("Mensaje De Alerta", "Rellene los campos incompletos.", "warning");
            return false;
        }

        if (obs_asig.length <= 0) {
            obs_asig = '.';
        }

        $.ajax({
            url: '../controlador/docente/controlador_resgistrar_session_tutoria.php',
            type: 'POST',
            data: {
                id_docente: iddocente,
                id_estudiante: id_estu,
                inicio: hora_inicio,
                final: hora_fin,
                tema: tema_asig,
                compromiso: compr_asig,
                obs: obs_asig,
                tipo: tipo_session,
                array_asig: arr_asig,
                reu_otro: esp_otros,
                fecha: today
            }
        }).done(function (resp) {

            if (resp == 1) {
                Swal.fire("Mensaje De Confirmacion", "Registrardo correctamente.", "success");

                cargar_contenido('contenido_principal', 'docente/vista_historial_sesiones.php');

            } else {
                Swal.fire("Mensaje De Error", "No se completo el registro", "error");
            }
        });
    }
}


function listar_Alumnos_es() {
    tab_alumno_es = $("#table_alumno").DataTable({
        "ordering": true,
        "bLengthChange": false,
        "searching": {
            "regex": false
        },
        "responsive": true,
        dom: 'Bfrtilp',
        buttons: [
            {
                extend: 'pdfHtml5',
                text: '<i class="fa fa-file-pdf-o"></i> PDF',
                title: 'LISTA DE ALUMNOS',
                className: 'btn btn-danger',
                customize: function (doc) {
                    // Puedes personalizar el PDF aquí si es necesario
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Print',
                title: 'LISTA DE ALUMNOS',
                titleAttr: 'Imprimir',
                className: 'btn btn-info'
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-text-o"></i> Excel',
                title: 'LISTA DE ALUMNOS',
                titleAttr: 'Excel',
                className: 'btn btn-info'
            },
            {
                extend: 'csvHtml5',
                text: '<i class="fa fa-file-excel-o"></i> Csv',
                title: 'LISTA DE ALUMNOS',
                titleAttr: 'Csv',
                className: 'btn btn-info'
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
            url: "../controlador/alumno/controlador_reporte_Alumno.php",
            type: 'POST'
        },
        "columns": [
            {
                "data": "idusuario"
            },
            {
                "data": "apellido_completo"
            },
            {
                "data": "nombres"
            },
            {
                "data": "telefono"
            },
            {
                "data": "des_ciclo"
            },
            {
                "data": "dni"
            },
            {
                "data": "cor_inst"
            },
            {
                "data": "estado"
            }
        ],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("table_alumno_filter").style.display = "none";
    $('input.global_filter').on('keyup click', function () {
        filterUnoGlobal();
    });
    $('input.column_filter').on('keyup click', function () {
        filterColumn($(this).parents('tr').attr('data-column'));
    });
}

function filterUnoGlobal() {
    $('#table_alumnox').DataTable().search($('#global_filter').val()).draw();
    $('#table_alumno_asignado').DataTable().search($('#global_filter').val()).draw();
    $('#table_sessiones').DataTable().search($('#global_filter').val()).draw();
    $('#tabla_historial_sesiones').DataTable().search($('#global_filter').val()).draw();
    $('#table_asistencia_estudiante').DataTable().search($('#global_filter2').val()).draw();
}

var talb_filAlum;
function Estraer_Lista_Range_Alum() {

    var finicio = $("#reportFechainicio").val();
    var fFinal = $("#reportFechafin").val();
    if (finicio.length == 0 || fFinal.length == 0) {
        return;
    }

    talb_filAlum = $("#table_alumnox").DataTable({
        "ordering": true,
        "bLengthChange": false,
        "searching": {
            "regex": false
        },

        "responsive": true,
        dom: 'Bfrtilp',
        buttons: [
            {
                "extend": 'excel',
                "text": '<i class="fa fa-file-text-o"></i> Excel ',
                title: 'REPORTE DE ALUMNOS',
                "titleAttr": 'Excel',
                "className": 'btn btn-info'
            }, {
                "extend": 'csvHtml5',
                "text": '<i class="fa  fa-file-excel-o"></i> Csv',
                title: 'REPORTE DE ALUMNOS',
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
            "url": "../controlador/alumno/reporte_lista_rango_fechas.php",
            type: 'POST',
            data: { finicio: finicio, fFinal: fFinal }
        },
        "columns": [{
            "data": "idalumno"
        }, {
            "data": "apellidop"
        }, {
            "data": "alumnonombre"
        }, {
            "data": "dni"
        }, {
            "data": "telefono"
        }, {
            "data": "gradonombre"
        }, {
            "data": "sexo",
            render: function (data, type, row) {
                if (data == 'M') {
                    return "MASCULINO";
                } else {
                    return "FEMINO";
                }
            }
        }, {
            "data": "codigo"
        },
        {
            "data": "fechaRegisto"
        },
        {
            "data": "stadoalumno",
            render: function (data, type, row) {
                if (data == 'ACTIVO') {
                    return "<span class='label label-success'>" + data + "</span>";
                } else {
                    return "<span class='label label-danger'>" + data + "</span>";
                }
            }
        }],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("table_alumno_filter").style.display = "none";
    $('input.global_filter').on('keyup click', function () {
        filterGlobal();
    });
    $('input.column_filter').on('keyup click', function () {
        filterColumn($(this).parents('tr').attr('data-column'));
    });
}

