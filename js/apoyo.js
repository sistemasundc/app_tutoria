var id_apoyo = $('#textId').val(); 

var tabla_alumnos_referidos;
function listar_alumnos_referidos(estado) {
    tabla_alumnos_referidos = $("#tabla_alumnos_referidos").DataTable({
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
            "url": "../controlador/apoyo/controlador_alumnos_referidos.php",
            type: 'POST',
            data: {
                apoyo: id_apoyo,
                est: estado
            }
        },
        "columns": [
    {
        "data": "id_der"
    },
    {
        "data": "nombres"
    },
    {
        "data": "escuela",
    },
    {
        "data": "ciclo",
    },
    {
       "data": "id_asig",
            render: function (data, type, row) {
            return "<button style='font-size:13px;' type='button' onclick='AbrirModalRegistro(\"" + row.id_der + "\",\"" + row.id_asig + "\")'\
             class='editar btn btn-info'><i class='fa fa-edit' title='editar'></i></button>\
        &nbsp;\
        <button style='font-size:13px;' type='button' onclick='MotivoReferido(\""+row.id_der+"\")' class='btn btn-success'>\
        <i class='fa fa-eye' title='Ver motivo'></i></button>"
            
        }
    }
],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("tabla_alumnos_referidos_filter").style.display = "none";
    $('input.global_filter').on('keyup click', function() {
        filterGlobal();
    });
    $('input.column_filter').on('keyup click', function() {
        filterColumn($(this).parents('tr').attr('data-column'));
    });
}

var tabla_historial_referidos;
function listar_historial_referidos(estado) {
    tabla_historial_referidos = $("#tabla_historial_referidos").DataTable({
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
            "url": "../controlador/apoyo/controlador_alumnos_referidos.php",
            type: 'POST',
            data: {
                apoyo: id_apoyo,
                est: estado
            }
        },
        "columns": [
    {
        "data": "id_der"
    },
    {
        "data": "nombres"
    },
    {
        "data": "escuela",
    },
    {
        "data": "ciclo",
    },
    {
       "data": "fechad" 
    },
    {
       "data": "id_der",
            render: function (data, type, row) {
            return "<button style='font-size:13px;' type='button' onclick='MotivoReferido(\""+data+"\")' class='desactivar btn btn-success'>\
        <i class='fa fa-eye' title='Ver motivo'></i> &nbsp; Ver</button>\
                    <button style='font-size:13px;' type='button' onclick='ContraRef(\""+data+"\")' class='desactivar btn btn-primary'>\
        <i class='fa fa-eye' title='Ver motivo'></i> &nbsp; Ver</button>"
            
        }
    }
],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("tabla_historial_referidos_filter").style.display = "none";
    $('input.global_filter').on('keyup click', function() {
        filterGlobal();
    });
    $('input.column_filter').on('keyup click', function() {
        filterColumn($(this).parents('tr').attr('data-column'));
    });
}

$('.motivoder').on('click', function() {
    var cadena = "Sin derivaciones"; 

    var idDer = $(this).find('#der').text();
      $("#btnImprimir").attr("onclick", `ImprimirDerivacion(${idDer})`);
    var idAsig = $(this).find('#asig').text();
    var idDoce = $(this).find('#doce').text();
    
    var id = $(this).find('#iDestu').text();

    var result = $(this).find('#result').text();
    var obser = $(this).find('#obser').text();
    var mot = $(this).find('#mot').text();
    var name = $(this).find('#nombres').text();

    var estado = $(this).find('#estado').text();

    
    $.ajax({
        "url": "../controlador/apoyo/controlador_ver_motivo.php",
        type: 'POST',
        data: {
            estu: id,
            ider: idDer
        }
    }).done(function(resp) {
        var data = JSON.parse(resp);

        if (data.length > 0) {
            var contador = data.length;
            
            cadena = "";
            var add2 = "";
            var color;
            for (var i = 0; i < data.length; i++) {

                if (contador >= 10){
                    var left = 4;
                }else {
                    var left = 8;
                }

                add2 = " ";
                color = "warning";
                cadena += `<div class=\"col-md-12\" style=\"border-bottom: 2px solid #b3b8c4; margin-bottom: 5px; padding: 10px;\">\
                 <h5 style='font-weight: 700; color: #1c4863; font-size: 15px;'> \
                    <div style=\"width:25px; height:20px; float:left;\">\
                        <div class="historial_dv" style=\"position: absolute; width:25px; height:25px; border-radius: 100%; left: 4px; top: 15px;background-color: #3c8dbc; margin-rigth: 10px;\">\
                            <div style=\"position: relative; color: white; left: ${left}px; top: 2px; font-size: 17px;\">${contador}</div>\
                        </div> \
                    </div>\
                    ${data[i][1]}\
                 </h5>\
                 <p><span style='font-weight: bold;'>Motivo: </span>${data[i][2]}</p>`;

                if (data[i][5] == "Atendido") {
                    color = "success";
                    if (data[i][4] == null){
                        color = "success";
                        add2 += "<p><span style='font-weight: bold;'>Resultado: </span>" + data[i][3] + "</p>";
                        add2 += `<span class='label label-danger' style='font-size: 0.9em;'>Subderivado</span>`;
                    }else {
                        add2 += "<p><span style='font-weight: bold;'>Resultado: </span>" + data[i][3] + "</p>";
                        add2 += "<p><span style='font-weight: bold;'>Observación: </span>" + data[i][4] + "</p>";
                    }
                }
                cadena += add2 + `
                        <span class='label label-${color}' style='font-size: 0.9em;'>${data[i][5]}</span>
                    </div>`;
                contador -= 1;
            }
            var add = "";
            var opciones_der = "";
            if (estado === "pendiente") {
                opciones_der = `
                    <div class="col-md-12" >
                        <h4 style='font-weight: bold;'><i class="fa fa-bookmark" style="color: #066da7;" aria-hidden="true"></i>&nbsp; Motivo Actual</h4>

                        <div class=\"col-md-12\" style=\" margin-bottom: 5px; padding: 10px;\">\
                            <p><span style='font-weight: bold;'>Estudiante: </span>${name}</p>
                            <p><span style='font-weight: bold;'>Motivo: </span>${mot}</p>
                        </div>
                    </div>
                    <div class="col-md-12" style="margin-bottom: 30px; border-bottom: 2px solid #b3b8c4; padding: 10px 10px 10px;">
                                <button style='font-size:15px;' type='button'
                                    onclick='AbrirModalRegistro(${idDer},${idAsig})'
                                    class='editar btn btn-success'>
                                    <i class='fa fa-edit' title='editar'></i>&nbsp; Atender
                                </button>
                            
                                <button style='font-size:15px;' type='button' class='btn btn-primary' 
                                    onclick="DerivarEstudiante(${idAsig},${id}, ${idDer}, ${idDoce})">
                                    <i class='fa fa-share'></i>&nbsp; Derivar
                                </button>
                    </div>
                `;
            }else {
                var campo_obser = obser;
                var campo_result = result;

                if (obser.length <= 0) {
                    campo_obser = "No registrado"
                }
                if (result.length <= 0) {
                    campo_result = "No registrado"
                }
                add = `
                <div class="col-md-12" style="margin-bottom: 30px; border-bottom: 2px solid #b3b8c4; ">
                        <h4 style='font-weight: bold;'><i class="fa fa-bookmark" style="color: #066da7;" aria-hidden="true"></i>&nbsp; Motivo </h4>

                        <div class=\"col-md-12\" style=\" margin-bottom: 5px; padding: 10px;\">\
                            <p><span style='font-weight: bold;'>Estudiante: </span>${name}</p>
                            <p><span style='font-weight: bold;'>Motivo: </span>${mot}</p>
                            <p><span style='font-weight: bold;'>Resultado: </span>${campo_result}</p>
                            <p><span style='font-weight: bold;'>Observación: </span>${campo_obser}</p>
                        </div>
                    </div>

                
                `;
            }
/*
<div class="col-md-6">
                <h4 style='font-weight: bold;'>Resultado</h4>
                <textarea disabled style="width: 100%; max-width: 100%; height: 10em;">${result}</textarea>
            </div>
            <div class="col-md-6">
                <h4 style='font-weight: bold;'>Observaci�n</h4>
                <textarea disabled style="width: 100%; max-width: 100%; height: 10em;">${obser}</textarea>
            </div>


            ${add}
            <div class="col-md-12">
                <h4 style='font-weight: bold;'>Motivo Actualdd</h4>
                <textarea disabled style="width: 100%; max-width: 100%; height: 10em;">${mot}</textarea>
            </div>*/

            var imprim = `
            ${add}
            ${opciones_der}
            
            <div class="col-md-12" >
                <h4 style='font-weight: bold;'><i class="fa fa-th-list" style="color: #066da7;" aria-hidden="true"></i>&nbsp; Historial de Atenci�n</h4>
                    ${cadena}
            </div>`;
            //$(".modal-backdrop").show();   
            $('#modal-event').modal({ backdrop: false, keyboard: true, show: true });
            console.log("holaaa");
            $('#campoder').html(imprim);          
            //Swal.fire("Detalles", `${imprim}`, "info");
        }
    });
});

var camp_id_der = document.getElementById('id_der');
var camp_id_doce = document.getElementById('id_doc');
function DerivarEstudiante(id_asig, id_estu, id_der, id_doce) {
    estu_der = id_estu;
    id_asig_der = id_asig;

    document.getElementById('motivo_der').value = '';
    document.getElementById('resultado_der').value = '';

    camp_id_doce.value = id_doce;
    camp_id_der.value = id_der;

    // Cierra otros modales si están abiertos
    $('.modal').modal('hide');

    // Aplica estilos personalizados
    $("#modal_derivar .modal-header").css({
        "background-color": "#066da7",
        "color": "white"
    });

    // Abre el modal SIN sombreado y FORZAMOS su prioridad con z-index
    $("#modal_derivar").modal({
        backdrop: false,
        keyboard: true
    }).on('shown.bs.modal', function () {
        // 🔥 Eliminamos cualquier fondo oscuro remanente
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');

        // 🔥 Forzamos el z-index más alto al modal
        $('#modal_derivar').css('z-index', 1055);
    }).modal('show');
}

/* function DerivarEstudiante(id_asig, id_estu, id_der, id_doce) {
    estu_der = id_estu;
    id_asig_der = id_asig;

    var mot_l = document.getElementById('motivo_der');
    var res_l = document.getElementById('resultado_der');

    mot_l.value = '';
    res_l.value = '';

    camp_id_doce.value = id_doce;
    camp_id_der.value = id_der;

    $("#modal_derivar").modal({
        backdrop: 'static',
        keyboard: false
    })
    $(".modal-header").css("background-color", "#066da7");
    $(".modal-header").css("color", "white");
    $("#modal_derivar").modal('show');
} */

function Derivar_estudiante(id_der){
    var id_doce = camp_id_doce.value;
    var mot_der = $("#motivo_der").val();
    var res_der = $("#resultado_der").val();
    var id_area = $("#areas_apoyo").val(); 
    var id_dd = camp_id_der.value; 
 
    if (mot_der.length <= 0 || estu_der == 0 || res_der.length <= 0){
        Swal.fire("Mensaje De Alerta", "Hay campos sin completar.", "warning");
        return false;
    }
    if (id_area == 0){
        Swal.fire("Mensaje De Alerta", "Ningun area selecionada.", "warning");
        return false;
    }

    Swal.fire({
          title: "�Est� seguro de continuar?",
          text: " ",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "S�, estoy seguro",
          cancelButtonText: "No, cancelar",
        }).then((confirmar2) => {
          if (confirmar2.value) {
             $.ajax({
                url: '../controlador/docente/controlador_derivar_alumno_apoyo.php',
                type: 'POST',
                data: {
                    der: id_dd,
                    motivo: mot_der,
                    result: res_der,
                    area: id_area,
                    estu: estu_der,
                    doce: id_doce,
                    asig: id_asig_der
                }
            }).done(function(resp) {
                if (resp == 1) {
                    Swal.fire("Mensaje De Confirmacion", "Actulizado correctamente.", "success");

                    $("#modal_derivar").modal('hide');
                    window.location.reload();
                } else {
                    Swal.fire("Mensaje De Error", "No se completo la derivaci�n.", "error");
                }
            }); 
          } else {
             return null;
          }
    });
}

function listar_areas_apoyo() {

    $.ajax({
        "url": "../controlador/docente/controlador_combo_area_apoyo.php",
        type: 'POST',
        data: {
            id_ap: id_apoyo
        }
    }).done(function(resp) {
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

function filterGlobal() {
    $('#tabla_alumnos_referidos').DataTable().search($('#global_filter').val(), ).draw();
    $('#tabla_historial_referidos').DataTable().search($('#global_filter').val(), ).draw();
}

var id_asig = 0;
var id_deri = 0;
function AbrirModalRegistro(id_der, id_asg) {
    // Mostrar modal sin sombreado
    $("#modal_registro").modal({
        backdrop: false,  // No se muestra sombreado
        keyboard: true    // Permitir que se cierre con ESC
    }).modal('show');

    $(".modal-header").css("background-color", "#05ccc4");
    $(".modal-header").css("color", "white");
    
    // Establecer valores en el formulario si es necesario
    id_deri = id_der;
    id_asig = id_asg;
}
/* function AbrirModalRegistro(id_der, id_asg) {
    $("#modal_registro").modal({
        backdrop: 'static',
        keyboard: false
    })
     $(".modal-header").css("background-color", "#05ccc4");
    $(".modal-header").css("color", "white");
    $("#modal_registro").modal('show');

    id_deri = id_der;
    id_asig = id_asg;
} */

/* function RegistrarDerivacion() {
    var result = $('#resultder').val();
    var obser = $('#obserder').val();

    if (result.length <= 0 || obser.length <= 0) {
        return Swal.fire("Mensaje De Advertencia", "Digite todos los campos.", "info");
    }

    Swal.fire({
          title: "Está seguro de continuar?",
          text: " ",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Sì, estoy seguro",
          cancelButtonText: "No, cancelar",
        }).then((confirmar) => {
          if (confirmar.value) {
             $.ajax({
                "url": "../controlador/apoyo/controlador_actualizar_derivacion.php",
                type: 'POST',
                data: {
                    ider: id_deri,
                    idsg: id_asig,
                    res: result,
                    obs: obser
                }
            }).done(function(resp) {
                if (resp == 1) {
                    Swal.fire("Mensaje De Confirmacion", "Registrado correctamente", "success");
                    //tabla_alumnos_referidos.ajax.reload();
                 
                    window.location.reload();
                }else if (resp == 127){
                    Swal.fire("Mensaje De Error", "No se pudo completar", "error");
                }else {
                    Swal.fire("Mensaje De Error", "Error al subir datos", "error");
                }

                $("#modal_registro").modal('hide');
                
            });
          } else {
             return null;
          }
    });
} */
function RegistrarDerivacion() { 
    var result = $('#resultder').val();
    var obser = $('#obserder').val();

    if (result.length <= 0 || obser.length <= 0) {
        return Swal.fire("Mensaje De Advertencia", "Digite todos los campos.", "info");
    }

    Swal.fire({
          title: "Está seguro de continuar?",
          text: " ",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Sí, estoy seguro",
          cancelButtonText: "No, cancelar",
        }).then((confirmar) => {
          if (confirmar.value) {
             $.ajax({
                "url": "../controlador/apoyo/controlador_actualizar_derivacion.php",
                type: 'POST',
                data: {
                    ider: id_deri,
                    idsg: id_asig,
                    res: result,
                    obs: obser
                }
            }).done(function(resp) {
                if (resp == 1) {
                    Swal.fire("Mensaje De Confirmacion", "Registrado correctamente", "success");
                    window.location.reload();
                } else if (resp == 127){
                    Swal.fire("Mensaje De Error", "No se pudo completar", "error");
                } else {
                    Swal.fire("Mensaje De Error", "Error al subir datos", "error");
                }

                // Aquí se quita el sombreado y se cierra el modal
                $("#modal_registro").modal('hide');
                $('.modal-backdrop').remove(); // Remueve el sombreado
                $('body').removeClass('modal-open'); // Elimina la clase que bloquea la interacción
            });
          } else {
             return null;
          }
    });
}
function ContraRef(id_ref) {
    var formref = document.getElementById("formContra");
    var idref = document.getElementById("idcontra");

    idref.value = id_ref;

    formref.submit();
}

function RegistroTutoriaAcademica_F5(id_ref) {
    var formref = document.getElementById("formRegistroTutoriaF5");
    var idref = document.getElementById("idcontra2");
    idref.value = id_ref;

    formref.submit();
}