function getSemestre(){
  return $('#anio').val();
}
var s_escuela = $("#Uschool").val() || "-";
var s_nombre_escuela = $("#Unameschool").val() || "-";

/* function AbrirModalDocente() {
    $("#modal_registro_docente").modal({
        backdrop: false,   // sin fondo bloqueante
        keyboard: false
    });

    $(".modal-header").css("background-color", "#05ccc4");
    $(".modal-header").css("color", "white");
    $("#modal_registro_docente").modal('show');
 }*/
function AbrirModalDocente() {
  const $modal = $("#modal_registro_docente");
  const $sel   = $("#sdocente");

  $modal.modal({ backdrop: false, keyboard: true });

  // Carga / recarga opciones
  listar_combo_docentes();

  // Re-inicializa Select2 dentro del modal
  if ($sel.data('select2')) $sel.select2('destroy');
  $sel.select2({
    width: '100%',
    dropdownParent: $modal,
    placeholder: 'Seleccione...'
  });

  $(".modal-header").css({ "background-color": "#05ccc4", "color": "white" });
  $modal.on('shown.bs.modal', function(){ $sel.trigger('focus'); });
  $modal.modal('show');
}
function AbrirModalAsignar() {
    $("#modal_agregar_curso").modal({
        backdrop: false,
        keyboard: false
    })
    $('#modal_agregar_curso .modal-body').find("#tbody_tabla_detall").html("");
    $(".modal-header").css("background-color", "#05ccc4");
    $(".modal-header").css("color", "white");
    $("#modal_agregar_curso").modal('show');

}

function ModalverCurso_grado() {
    $("#modal_ver_curso").modal({
        backdrop: 'static',
        keyboard: false
    })
    //$('#modal_ver_curso .modal-body').find("#tabla_cursogrado_docent").html("");
    $(".modal-header").css("background-color", "#05ccc4");
    $(".modal-header").css("color", "white");
    $("#modal_ver_curso").modal('show');

}

var table_docente;

function listar_docente() {

    table_docente = $("#tabla_Docentes").DataTable({
        "ordering": false,
        "bLengthChange": false,
        "searching": {
            "regex": false
        },
        "lengthMenu": [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        "pageLength": 10,
        "destroy": true,
        "async": false,
        "processing": true,
        "ajax": {
            "url": "../controlador/coordinador/controlador_listar_docente.php",
            type: 'POST',
            data: { anio: getSemestre() }
        },
        "columns": [{
            "data": "id_doce_tuto"
        },
        {
            "data": "nombres"
        },
        {
            "data": "escuela"
        },
        {
            "defaultContent": "\
            <button style='font-size:13px;' type='button' class='agregar btn btn-warning'><i class=' glyphicon glyphicon-plus' title='agregar'></i></button>&nbsp;\
            <button style='font-size:13px;' type='button' class='ver btn btn-info'><i class=' glyphicon glyphicon-eye-open' title='ver'></i></button>&nbsp;&nbsp;\
            <button style='font-size:13px;' type='button' class='eliminar btn btn-danger' title='eliminar'><i class='fa fa-trash'></i></button>"
        }],
        "language": idioma_espanol,
        select: true
    });
    let filtro = document.getElementById("tabla_Docentes_filter");
    if (filtro) {
        filtro.style.display = "none";
    }
    $('input.global_filter').on('keyup click', function () {
        filterGlobal();
    });
    $('input.column_filter').on('keyup click', function () {
        filterColumn($(this).parents('tr').attr('data-column'));
    });
}
function filterGlobal() {
    $('#tabla_Docentes').DataTable().search($('#global_filter').val(),).draw();
}

$('#tabla_Docentes').on('click', '.edit', function () {
    var data = table_docente.row($(this).parents('tr')).data();

    if (table_docente.row(this).child.isShown()) {
        var data = table_docente.row(this).data();
    }
    $("#docente_edit").modal({
        backdrop: 'static',
        keyboard: false
    })
    $(".modal-header").css("background-color", "#05ccc4");
    $(".modal-header").css("color", "white");
    $("#docente_edit").modal('show');

    $("#id_docent").val(data.id_usuario);
    $("#docentenom").val(data.nombre);
    $("#appdocent").val(data.apellido);
    $("#statusdocent").val(data.status).trigger("change");
    $("#docentsex").val(data.sexo).trigger("change");
    $("#tipodocebt").val(data.tipo).trigger("change");


});


//BOTON AGREGAR

$('#tabla_Docentes').on('click', '.agregar', function () {
    var data = table_docente.row($(this).parents('tr')).data();

    if (table_docente.row(this).child.isShown()) {
        var data = table_docente.row(this).data();
    }
    //alert(data.id_usuario +'--'+data.nombre );
    const idDocenteReal = data.id_usuario || data.id_doce || data.id_docente || data.id_doce_tuto; 
    $("#id_docent").val(idDocenteReal).trigger("change");

    //listar_combo_niveles();

    listar_combo_ciclos(data.id_doce_tuto);
    AbrirModalAsignar();
})

//BOTON VER

$('#tabla_Docentes').on('click', '.ver', function () {
    var data = table_docente.row($(this).parents('tr')).data();

    if (table_docente.row(this).child.isShown()) {
        var data = table_docente.row(this).data();
    }

    const idDocenteReal = data.id_usuario || data.id_doce || data.id_docente || data.id_doce_tuto;
    VerCursos_grados_docente(idDocenteReal);

})

//--------------------BOTON ELIMINAR-----------------------------------

$('#tabla_Docentes').on('click', '.eliminar', function () {
  let data = table_docente.row($(this).parents('tr')).data();
  if (table_docente.row(this).child.isShown()) data = table_docente.row(this).data();
  if (!data) {
    Swal.fire("Error", "No se pudo obtener la información del docente.", "error");
    return;
  }

  // OJO: este ID en la grilla es el id_doce (no la PK id_docente_tutor)
  const id_doce = Number(data.id_doce ?? data.id_doce_tuto ?? data.id_docente ?? 0);
  const semestreActual = Number($('#anio').val() || 0);

Swal.fire({
  title: '¿Está seguro?',
  text: "Esta acción eliminará al docente tutor.",
  icon: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6', // azul (boton confirmar)
  cancelButtonColor: '#d33',     // rojo (boton cancelar)
  confirmButtonText: 'Sí, eliminar',
  cancelButtonText: 'Cancelar',
  allowOutsideClick: false,
  allowEscapeKey: false

  }).then((result) => {
    if (!(result && (result.isConfirmed || result.value === true))) return;

    $.ajax({
      url: "../controlador/docente/controlador_eliminar_docente.php",
      type: 'POST',
      data: { id_doce: id_doce, semestre: semestreActual }
    }).done(function (resp) {
      console.log("RAW resp ->", resp, "(envió) id_doce:", id_doce, "semestre:", semestreActual);
      const code = parseInt($.trim(resp), 10);

      switch (code) {
        case 1:
          Swal.fire("Eliminado", "El docente ha sido eliminado.", "success")
            .then(() => table_docente.ajax.reload());
          break;
        case 2:
          Swal.fire("Advertencia",
            "No se pudo completar esta acción. El docente tiene asignaciones en el semestre actual. Primero quite los cargos del docente.",
            "warning");
          break;
        case 3:
          Swal.fire("Advertencia",
            "No se puede eliminar porque el docente tiene registros vinculados (sesiones, informes u otros).",
            "warning");
          break;
        default:
          Swal.fire("Error", "No se pudo completar la eliminación.", "error");
      }
    }).fail(function () {
      Swal.fire("Error", "Fallo la comunicación con el servidor.", "error");
    });
  });
});

function Registrar_Docente() {
  var id_docente = $("#sdocente").val();
  var id_coor    = $("#textId").val() || $("#textId_co").val(); // usa el que exista

  if (!id_docente) {
    Swal.fire("Advertencia", "Seleccione un docente.", "warning");
    return;
  }

  $.ajax({
    url: "../controlador/docente/controlador_docente_registro.php",
    type: "POST",
    data: {
      iddoce: id_docente,
      coor: id_coor
      // anio:  <-- ya no se envía; lo toma el servidor de la sesión
    }
  })
  .done(function (resp) {
    if (resp > 0) {
      if (resp == 1) {
        $("#modal_registro_docente").modal('hide');
        Swal.fire("Confirmación", "Docente registrado correctamente", "success")
            .then(() => table_docente.ajax.reload());
      } else {
        Swal.fire("Advertencia", "El docente ya está registrado", "warning");
      }
    } else {
      Swal.fire("Error", "No se pudo registrar el docente", "error");
    }
  });
}


function Limpiarmodal() {
    $('#txt_nombre').val('');
    $('#txt_app').val('');
    $('#txt_con1').val('');
    $('#cbm_sexo').val('');
    $('#cbm_tipo').val('');

}

function listar_combo_niveles() {
    $.ajax({
        "url": "../controlador/coordinador/controlador_combo_niveles.php",
        type: 'POST'
    }).done(function (resp) {

        var data = JSON.parse(resp);

        var cadena = "";
        if (data.length > 0) {
            for (var i = 0; i < data.length; i++) {
                cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
            }

            $('#tipo_asig').html(cadena);////lamndo en vista matricula

        } else {
            cadena += "<option value=''>NO SE ENCONTRARON REGISTROS</option>";
            $("#tipo_asig").html(cadena);
        }
    })
}

/*
$("#cicloa").on("change", function() {
    var idciclos = $(this).val();

    Traer_alumnos(idciclos);
});*/

function listar_combo_ciclos(id_doce) {
    $.ajax({
        "url": "../controlador/coordinador/controlador_combo_ciclos.php",
        type: 'POST',
        data: { doce: id_doce, anio: getSemestre() }
    }).done(function (resp) {

        var data = JSON.parse(resp);

        var cadena = "";
        if (data.length > 0) {
            for (var i = 0; i < data.length; i++) {
                cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
            }
            $('#cicloa').html(cadena);////llamndo en vista matricula 
        } else {
            cadena += "<option value=''>NO SE ENCONTRARON REGISTROS</option>";
            $("#cicloa").html(cadena);
        }
    })
}

function listar_combo_docentes() {

    $.ajax({
        "url": "../controlador/coordinador/controlador_combo_docentes.php",
        type: 'POST'
    }).done(function (resp) {

        if (resp != 0) {
            var data = JSON.parse(resp);
            var cadena = "";
            if (data.length > 0) {
                for (var i = 0; i < data.length; i++) {
                    cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
                }

                $('#sdocente').html(cadena);////lamndo en vista matricula

            } else {
                cadena += "<option value=''>NO SE ENCONTRARON REGISTROS</option>";
                $("#sdocente").html(cadena);
            }
        } else {
            cadena += "<option value=''>NO ENCONTRADO</option>";
            $("#sdocente").html(cadena);
        }

    })
}

function Traer_alumnos(idciclo) {//TRAER CURSO DEL DEL GRADO ESTADO PENDIENTE

    $.ajax({
        "url": "../controlador/coordinador/controlador_cuso_de_nivel.php",
        type: 'POST',
        data: {
            idescuela: s_escuela,
            ciclo: idciclo
        }
    }).done(function (resp) {

        var cont = 0;
        var data = JSON.parse(resp);
        var cadena = "";
        if (data.length > 0) {
            for (var i = 0; i < data.length; i++) {
                cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
                cont++;
            }
            $("#cbm_curso").html(cadena);


        } else {
            cadena += "<option value=''>NO SE ENCONTRARON REGISTROS</option>";
            $("#cbm_curso").html(cadena);

        }
    })
}


//var alumnos_tipo_asign = [];
function Agregar_tabla() {
    var idcurso = $('#cbm_curso').val();
    var nombcurso = $('#cbm_curso option:selected').text();
    var idnivel = s_escuela;
    var nombnivel = s_nombre_escuela;
    var idtipo = $('#tipo_asig').val();
    var nombtipo = $('#tipo_asig option:selected').text();
    //alert(idmat+'--'+nomate);

    if (verificaridcurso(idcurso)) {//cursos no se repiten
        return Swal.fire("Mensaje de Advertencia", "Alumno ya agregado para este docente", "warning");

    }
    var datos_add = "<tr>";
    datos_add += "<td for='id'>" + idcurso + "</td>";
    datos_add += "<td >" + nombcurso + "</td>";
    datos_add += "<td >" + idnivel + "</td>";
    datos_add += "<td >" + nombnivel + "</td>";
    datos_add += "<td >" + idtipo + "</td>";
    datos_add += "<td >" + nombtipo + "</td>";
    datos_add += "<td><button class='btn btn-danger' onclick = 'remove(this)'> <i class='fa fa-trash'></button></i></td>";
    datos_add += "<tr>";
    $("#tbody_tabla_detall").append(datos_add);

    //----
    // alumnos_tipo_asign.push($('#tipo_asig').val());
}

function verificaridcurso(idnuevo) {
    let ident = document.querySelectorAll('#tbody_tabla_detall td[for="id"]');
    return [].filter.call(ident, td => td.textContent == idnuevo).length == 1;
}

function remove(t) {
    var td = t.parentNode;
    var tr = td.parentNode;
    var table = tr.parentNode;
    table.removeChild(tr);

}


function DocentAsignado() {
  const id_carga = $('#cicloa').val();             // (tu combo ciclos dice que es id_carga)
  const id_doce  = $("#id_docent").val();          // id docente real
  const id_coodi = $("#textId").val() || $("#textId_co").val() || $("#textIdCoor").val() || "";
  const anio     = $('#anio').val();               // NO uses var global "sem" aquí

  if (!id_carga) return Swal.fire("Advertencia", "Seleccione un ciclo/carga.", "warning");
  if (!id_doce)  return Swal.fire("Advertencia", "No se detectó el docente.", "warning");
  if (!id_coodi) return Swal.fire("Advertencia", "No se detectó el ID del coordinador.", "warning");

  const btnsubir = document.getElementById('subirasigbtn');
  if (btnsubir) btnsubir.disabled = true;

  Swal.fire({
    title: "Mensaje De Espera",
    text: "Espere un momento por favor.",
    icon: "info",
    allowOutsideClick: false,
    allowEscapeKey: false
  });

  console.log("ASIGNAR ->", { id_carga, id_doce, id_coodi, anio });

  $.ajax({
    url: '../controlador/coordinador/controlador_cursogrado_docente.php',
    type: 'POST',
    data: { id_carga, id_doce, id_coodi, anio }
  })
    .done(function (resp) {
    var limpio = $.trim(resp);
    console.log("RESP ASIGNAR ->", limpio);

    if (limpio === "1") {
        Swal.fire("Éxito", "Asignado.", "success");

        // 🔥 AQUÍ MISMO: recargar la tabla derecha
        VerCursos_grados_docente($("#id_docent").val());

    } else if (limpio === "555") {
        Swal.fire("Info", "Este docente ya tiene alumnos asignados.", "info");
    } else {
        Swal.fire("Advertencia", "No se pudo asignar.", "warning");
    }

    btnsubir.disabled = false;
    $("#modal_agregar_curso").modal('hide');
    })

  .fail(function(xhr){
    console.error("ERROR ASIGNAR -> status:", xhr.status);
    console.error("responseText ->", xhr.responseText); // AQUÍ saldrá el error real del PHP (SQL, etc.)
    Swal.fire("Error", "Falló la asignación. Revisa consola (Network/response).", "error");
  })
  .always(function(){
    if (btnsubir) btnsubir.disabled = false;
  });
}

//ANTIGO EJEMPLO  CON MODAL
/*
function VerCursos_grados_docente(id) { 
    $.ajax({
        url: '../controlador/coordinador/controlador_verGradocurso.php',
        type: 'POST',
        data: {
            id:id
        }
    }).done(function(resp) {
        
        var datos = JSON.parse(resp);
        if ((datos.length == 0)) {
            return;
        }else{
        
        var cont=1;  //idcurso, nonbrecurso,idgrado,gradonombre
        
        var template = '';
        datos["data"].forEach(tarea => {
            template += `
                   <tr guardarId="${tarea.idcurso}" >
                   <td>${cont}</td>
                   <td><a>${tarea.nonbrecurso}</a></td>
                   <td><a >${tarea.gradonombre}</a></td>
                   <td>
                     <button class='btn btn-danger' onclick = 'QuitarCursoDocente(this)'> <i class='fa fa-trash'></button></i></td>
                   </td>
                   </tr>
                 `
                 cont++;

        });
        $('#tabla_cursogrado_docent').html(template);
        }   
    }) 

$('#tabla_cursogrado_docent').html('<br> <center> NO TIENE CURSOS NI GRADOS A CARGO !!</center>');
}
 
function QuitarCursoDocente(t) {
    var td = t.parentNode;
    var tr = td.parentNode;
    var idcapturado = $(tr).attr('guardarId');
     $('.loader').show();
      $.ajax({
        url: '../controlador/coordinador/controlador_Quitar_cursoDocente.php',
        type: 'POST',
        data: {
            idcapturado:idcapturado
        }
    }).done(function(resp) {
        $('.loader').hide();
        if (resp > 0) {
             var table = tr.parentNode;
            table.removeChild(tr);
        } else {
            Swal.fire("Mensaje De Advertencia", "No se pudo QUITAR!!", "warning");
        }
    })
    
}*/

//NUEVO ACTUALIZACION//
var docentecargo;

function VerCursos_grados_docente(iddd) {

  $('#tablasAsignados').show();

  // 🔥 Evita que DataTables se duplique/rompa
  if ($.fn.DataTable.isDataTable('#tablasAsignados')) {
    $('#tablasAsignados').DataTable().clear().destroy();
    $('#tablasAsignados tbody').empty();
  }

  docentecargo = $("#tablasAsignados").DataTable({
    ordering: false,
    bLengthChange: false,
    searching: { regex: false },
    lengthMenu: [[10, 25, 50, 100, -1],[10, 25, 50, 100, "All"]],
    pageLength: 10,
    destroy: true,
    processing: true,
    ajax: {
    url: '../controlador/coordinador/controlador_verGradocurso.php',
    type: 'POST',
    data: {
        id_usuario: iddd,
        anio: getSemestre()
    }
    },
    columns: [
      { data: "id_asignacion" },
      { data: "estudiante_es" },
      {
        defaultContent:
          "<button style='font-size:13px;' type='button' class='eliminar btn btn-danger' title='eliminar'><i class='fa fa-trash'></i></button>"
      }
    ],
    language: idioma_espanol,
    select: true
  });

  const f = document.getElementById("tablasAsignados_filter");
  if (f) f.style.display = "none";

  setTimeout(function(){
    $('#tablasAsignados').DataTable().columns.adjust().draw(false);
  }, 50);
}



$('#tablasAsignados').on('click', '.eliminar', function () {
    var data = docentecargo.row($(this).parents('tr')).data();
    if (docentecargo.row(this).child.isShown()) {
        data = docentecargo.row(this).data();
    }
    if (!data) {
        Swal.fire("Error", "No se pudo obtener la información del estudiante.", "error");
        return;
    }

    var idcap = data.id_asignacion;

    Swal.fire({
        title: '¿Está seguro?',
        text: '¿Desea eliminar de la lista a este estudiante que ya ha tenido sesiones?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        // ✅ compatible con versiones viejas y nuevas
        if (!(result && (result.isConfirmed || result.value === true))) {
            return;
        }

        $.ajax({
            url: '../controlador/coordinador/controlador_quitar_alumno.php',
            type: 'POST',
            data: { idasig: idcap }
        })
        .done(function (resp) {
            var limpio = $.trim(resp);
            if (limpio === "1") {
                Swal.fire(
                    "Estudiante eliminado",
                    "Estudiante eliminado de esta aula exitosamente.",
                    "success"
                ).then(() => {
                    docentecargo.ajax.reload();
                });
            } else {
                Swal.fire(
                    "Error al eliminar",
                    limpio || "Respuesta vacía del servidor.",
                    "error"
                );
            }
        })
        .fail(function () {
            Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
        });
    });
});


//REPORTE DE DOCENTES 
function abrirListaTutores(){
  // ventana flotante centrada
  var w = 900, h = 700;
  var y = (screen.height - h) / 2;
  var x = (screen.width  - w) / 2;
  window.open(
    '../vista/coordinador/reporte_lista_tutores.php',
    'lista_tutores',
    'width='+w+',height='+h+',left='+x+',top='+y+',resizable=yes,scrollbars=yes'
  );
}
//HASTA AQUI SE CAMBIO EL NUEVO VISTA

function Update_Docente() {
    var iddocent = $("#id_docent").val();
    var nomdocent = $("#docentenom").val();
    var appdocent = $("#appdocent").val();
    var estdocent = $("#statusdocent").val();
    var sexdocent = $("#docentsex").val();
    var tipdocent = $("#tipodocebt").val();

    $.ajax({
        url: '../controlador/docente/controlador_Update_Docente.php',
        type: 'POST',
        data: {
            iddocent: iddocent,
            appdocent: appdocent,
            nomdocent: nomdocent,
            estdocent: estdocent,
            sexdocent: sexdocent,
            tipdocent: tipdocent

        }
    }).done(function (resp) {
        if (resp > 0) {
            $("#docente_edit").modal('hide');
            Swal.fire("Mensaje De Confirmacion", "Datos correctamente, Update", "success").then((value) => {
                table_docente.ajax.reload();
            });

        } else {
            Swal.fire("Mensaje De Advertencia", "No se pudo Actualizar", "warning");
        }
    })

}

$(document).ready(function () {
  listar_docente(); 
});