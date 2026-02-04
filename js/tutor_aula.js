//TUTOR DE AULA
$(".select_tipo").on('change', function () {
    var id_tipo = $(this).val();
    var id_asig = this.id;

    $.ajax({
        url: '../controlador/tutor_aula/controlador_change_tipo_asig.php',
        type: 'POST',
        data: {
            asig: id_asig,
            tipo: id_tipo
        }
    }).done(function(resp) {
        if (resp == 1) {
            Swal.fire("Mensaje De Confirmacion", "Guardado.", "success");
        } else {
            Swal.fire("Mensaje De Error", "No se completo.", "error");
        }
    });
});

function DerivarEstudiante(id_asig, id_estu) {
    estu_der = id_estu;
    id_asig_der = id_asig;

    var mot_l = document.getElementById('motivo_der');
    mot_l.value = '';

    listar_areas_apoyo(); 

    $("#modal_derivar").modal({
        backdrop: 'static',
        keyboard: false
    })
    $(".modal-header").css("background-color", "#066da7");
    $(".modal-header").css("color", "white");
    $("#modal_derivar").modal('show');
}


function listar_areas_apoyo() {
    var id_doce = $('#textId').val();
    $.ajax({
        url: '../controlador/tutor_aula/controlador_combo_area_apoyo.php',
        type: 'POST',
        data: {
            id_ap: id_doce
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

function FormatoDerivado(estu_id) {
    var formDer = document.getElementById('formder');
    var estuder = document.getElementById('estuder');
    var doceder = document.getElementById('doceder');

    estuder.value = estu_id;
    doceder.value = $('#textId').val();
    formDer.submit();
}

function FormatoTuto(estu_id) {
    var formtuto = document.getElementById('formtuto');
    var estututo = document.getElementById('estututo');
    var docetuto = document.getElementById('docetuto');

    estututo.value = estu_id;
    docetuto.value = $('#textId').val();
    formtuto.submit();
}



function listar_tipo_session() {
    $.ajax({
        "url": "../controlador/tutor_aula/controlador_combo_session.php",
        type: 'POST'
    }).done(function(resp) {
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
 
function CrearSesion() {
	list_check = document.getElementsByClassName('checkasis');
    list_id_estu = document.getElementsByClassName('id_estu_hora');
	list_alumnos = document.getElementById('alumnos_list');

	var vista_config = "";
    var existe = false;
	for (var i=0;i<list_check.length;i++) { 
		if (list_check[i].checked) { 
			vista_config += `
				<tr class="tr-alum">
                    <td hidden class="alumnos_tuto">${list_check[i].id}</td>
					<td hidden class="id_alum_tuto">${list_id_estu[i].id}</td>
					<td style="font-weight: 500; max-width: 25em; ">
						<i style="color: #6ed5b4; font-size: 20px; font-weight: bold;" class="fa fa-angle-double-right" aria-hidden="true"></i>
						&nbsp; ${list_check[i].name}
					</td>
					<td>
						<button type="button" class="btn btn-warning" onclick="QuitarAlumno(this)" style="padding: 1px 5px 1px 5px; font-size: 14px; color: black;">
	                		<i class="fa fa-minus-circle" aria-hidden="true"></i>&nbsp; Quitar
	                	</button>
					</td>
				</tr>
			`;
            existe = true;
		}
	}

    if (!existe) {
        Swal.fire("Mensaje De Alerta", "Seleccione estudiantes para continuar.", "info");
    }else {
        list_alumnos.innerHTML = vista_config;
        $("#calendarModal").modal('show');
    }
}

table_horario = document.getElementById('table_alumnos_horario');
function QuitarAlumno(button) {
  var rowIndex = button.closest('tr').rowIndex;
  table_horario.deleteRow(rowIndex);
}

campo = document.getElementById('campo_detalles');
var set_campo = "";
$('#tipo_session').change(function () {
	if (this.value == 4) {
		set_campo = `
			<label>Link de la sesiÃ³n: </label>
            <input class="form-control" id="link" value="" type="text" style="border-radius: 5px; font-weight: 500;">
		`;
	}else if (this.value == 5) {
		set_campo = `
			<label>Especifique: </label>
            <input class="form-control" id="detalles" value="" type="text" style="border-radius: 5px; font-weight: 500;">
		`;
	}else {
		set_campo = "";
	}

	campo.innerHTML = set_campo;
});

var td_alumnos = document.getElementsByClassName('alumnos_tuto');
var td_id_alumnos = document.getElementsByClassName('id_alum_tuto');

$("#start_time").on('change', function () {
    var tiempoActual = $(this).val();
    var ttwo = $('#end_time');

    var hora = parseInt(`${tiempoActual[0]}${tiempoActual[1]}`) + 1;

    if (hora == 24) {
        hora = "00";
    }

    var nueva_hora = `${hora}:${tiempoActual[3]}${tiempoActual[4]}`;
    ttwo.val(nueva_hora);
});

function GuardarCitas() { 
	var doce = $('#textId').val();
	var tema = $('#tema_tuto').val();
	var comp = $('#comp_tuto').val();
	var tipo = $('#tipo_session').val();
	var link = "";
	var deta = "";
	var date = $('#start_date').val();
	var tone = $('#start_time').val();
	var ttwo = $('#end_time').val();


	if (tema.length <= 0 || td_alumnos.length <= 0 || td_id_alumnos.length <= 0 || date.length <= 0 || tone.length <= 0 || ttwo.length <= 0){
		Swal.fire("Mensaje De Alerta", "Hay compos sin completar", "info");
		return;
	}

	if (tone >= ttwo) {
		Swal.fire("Mensaje De Alerta", "Asegurese de configurar correctamente la hora o fecha", "info");
		return;
	}

	if (tipo == 4) {
		link = $('#link').val();
		if (link.length <= 0) {
			Swal.fire("Mensaje De Alerta", "Ponga el enlace de la session", "info");
			return;
		}
	}else if (tipo == 5) {
		deta = $('#detalles').val();
		if (deta.length <= 0) {
			Swal.fire("Mensaje De Alerta", "Detalle el tipo de session", "info");
			return;
		}
	}

    var array_alumnos = [];
	var array_id_alumnos = [];
	for (var i=0;i<td_alumnos.length;i++){
		array_alumnos.push(td_alumnos[i].innerHTML);
        array_id_alumnos.push(td_id_alumnos[i].innerHTML);
	}

    array_alumnos = array_alumnos.toString();
	array_id_alumnos = array_id_alumnos.toString();
    
    Swal.fire({
          title: "Esta seguro de continuar?",
          text: " ",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "SÃ­, estoy seguro",
          cancelButtonText: "No, cancelar",
        }).then((confirmar3) => {
          if (confirmar3.value) {
             $.ajax({
                "url": "../controlador/docente/controlador_subir_sesion_tuto.php",
                type: 'POST',
                data: {
                    doc: doce,
                    tem: tema,
                    com: comp,
                    tip: tipo,
                    lik: link,
                    det: deta,
                    fec: date,
                    ini: tone,
                    fin: ttwo,
                    alu: array_alumnos,
                    ida: array_id_alumnos
                }
            }).done(function(resp) {

                if (resp == 1) { 
                    $('#calendarModal').modal('hide'); 
                    
                    $('#alumnos_list').val("");
                    $('#tema_tuto').val("");
                    $('#comp_tuto').val("");
                    $('#start_date').val("");
                    $('#start_time').val("");
                    $('#end_time').val("");


                    Swal.fire("Mensaje De Confirmacion", "Registrado", "success").then((value) => {
                        //hora = document.getElementById('horaaa'); 
                        //hora.click();
                        cargar_contenido('contenido_principal','fullcalendar');
                    });
                }else {
                    Swal.fire("Mensaje De Error", "No se pudo registrar", "error");
                }
            });
          } else {
             return null;
          }
    });

	
}
 //DOCENTE POR CURSO
function CrearSesionCurso() {
    list_check = document.getElementsByClassName('checkasis');
    list_id_estu = document.getElementsByClassName('id_estu_hora');
    list_alumnos = document.getElementById('alumnos_list');

    var curso = $('#nombre_curso').val();  // nombre del curso
    var ciclo = $('#ciclo_curso').val();    // ciclo
    var turno = $('#turno_curso').val();    // turno

    var vista_config = "";
    var existe = false;

    for (var i = 0; i < list_check.length; i++) {
        if (list_check[i].checked) {
            vista_config += `
                <tr class="tr-alum">
                    <td hidden class="alumnos_tuto">${list_check[i].id}</td>
                    <td hidden class="id_alum_tuto">${list_id_estu[i].id}</td>
                    <td style="font-weight: 500; max-width: 25em;">
                        <i style="color: #6ed5b4; font-size: 20px; font-weight: bold;" class="fa fa-angle-double-right" aria-hidden="true"></i>
                        &nbsp; ${list_check[i].name}
                        <div style="font-size: 12px; color: gray;">
                            Curso: ${curso} <br>
                            Ciclo: ${ciclo} | Turno: ${turno}
                        </div>
                    </td>
                    <td>
                        <button type="button" class="btn btn-warning" onclick="QuitarAlumno(this)" style="padding: 1px 5px; font-size: 14px; color: black;">
                            <i class="fa fa-minus-circle" aria-hidden="true"></i>&nbsp; Quitar
                        </button>
                    </td>
                </tr>
            `;
            existe = true;
        }
    }

    if (!existe) {
        Swal.fire("Mensaje De Alerta", "Seleccione estudiantes para continuar.", "info");
    } else {
        list_alumnos.innerHTML = vista_config;
        $("#calendarModal").modal('show');
    }
}
// GUARDAR el tipo de asignaciï¿½n
$(".select_tipo_docente").on('change', function () {
    var id_tipo = $(this).val();
    var id_asig = this.id;

    $.ajax({
        url: '../controlador/docente/controlador_change_tipo_asig.php',
        type: 'POST',
        data: { asig: id_asig, tipo: id_tipo }
    }).done(function (resp) {
        if (resp == 1) {
            Swal.fire("Mensaje De Confirmacion", "Guardado.", "success");
        } else {
            Swal.fire("Mensaje De Error", "No se completï¿½.", "error");
        }
    });
});

// LISTAR tipos de sesiï¿½n
function listar_tipo_session_docente() {
    $.ajax({
        url: "../controlador/docente/controlador_combo_session.php",
        type: 'POST'
    }).done(function (resp) {
        var data = JSON.parse(resp);
        var cadena = "";
        if (data.length > 0) {
            for (var i = 0; i < data.length; i++) {
                cadena += `<option value='${data[i][0]}'>${data[i][1]}</option>`;
            }
            $('#tipo_session').html(cadena);
        } else {
            $('#tipo_session').html("<option value=''>NO SE ENCONTRARON REGISTROS</option>");
        }
    });


}

function Derivar_estudiante_TC(){
    var id_doce = $("#textId").val();
    var mot_der = $("#motivo_der").val();
    var id_area = $("#areas_apoyo").val();

    if (mot_der.length <= 0 || estu_der == 0){
        Swal.fire("Mensaje De Alerta", "Hay campos sin completar.", "warning");
        return false;
    }
    if (id_area == 0){
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
    }).done(function(resp) {

        if (resp == 1) {
            Swal.fire("Mensaje De Confirmacion", "Actulizado correctamente.", "success");
            window.location.reload()
            $("#modal_derivar").modal('hide');
        } else {
            Swal.fire("Mensaje De Error", "No se completo la derivaciÃ³n.", "error");
        }
    }); 
}

function GuardarCitas_TC() { 
	var doce = $('#textId').val();
	var tema = $('#tema_tuto').val();
	var comp = $('#comp_tuto').val();
	var tipo = $('#tipo_session').val();
	var link = "";
	var deta = "";
	var date = $('#start_date').val();
	var tone = $('#start_time').val();
	var ttwo = $('#end_time').val();

	if (tema.length <= 0 || td_alumnos.length <= 0 || td_id_alumnos.length <= 0 || date.length <= 0 || tone.length <= 0 || ttwo.length <= 0){
		Swal.fire("Mensaje De Alerta", "Hay compos sin completar", "info");
		return;
	}

	if (tone >= ttwo) {
		Swal.fire("Mensaje De Alerta", "Asegurese de configurar correctamente la hora o fecha", "info");
		return;
	}

	if (tipo == 4) {
		link = $('#link').val();
		if (link.length <= 0) {
			Swal.fire("Mensaje De Alerta", "Ponga el enlace de la session", "info");
			return;
		}
	}else if (tipo == 5) {
		deta = $('#detalles').val();
		if (deta.length <= 0) {
			Swal.fire("Mensaje De Alerta", "Detalle el tipo de session", "info");
			return;
		}
	}

    var array_alumnos = [];
	var array_id_alumnos = [];
	for (var i=0;i<td_alumnos.length;i++){
		array_alumnos.push(td_alumnos[i].innerHTML);
        array_id_alumnos.push(td_id_alumnos[i].innerHTML);
	}

    array_alumnos = array_alumnos.toString();
	array_id_alumnos = array_id_alumnos.toString();
    
    Swal.fire({
          title: "Esta seguro de continuar?",
          text: " ",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Si­, estoy seguro",
          cancelButtonText: "No, cancelar",
        }).then((confirmar3) => {
          if (confirmar3.value) {
             $.ajax({
                "url": "../controlador/docente/controlador_subir_sesion_tuto.php",
                type: 'POST',
                data: {
                    doc: doce,
                    tem: tema,
                    com: comp,
                    tip: tipo,
                    lik: link,
                    det: deta,
                    fec: date,
                    ini: tone,
                    fin: ttwo,
                    alu: array_alumnos,
                    ida: array_id_alumnos
                }
            }).done(function(resp) {

                if (resp == 1) { 
                    $('#calendarModal').modal('hide'); 
                    
                    $('#alumnos_list').val("");
                    $('#tema_tuto').val("");
                    $('#comp_tuto').val("");
                    $('#start_date').val("");
                    $('#start_time').val("");
                    $('#end_time').val("");


                    Swal.fire("Mensaje De Confirmacion", "Registrado", "success").then((value) => {
                        //hora = document.getElementById('horaaa'); 
                        //hora.click();
                        cargar_contenido('contenido_principal','fullcalendar');
                    });
                }else {
                    Swal.fire("Mensaje De Error", "No se pudo registrar", "error");
                }
            });
          } else {
             return null;
          }
    });

}

/* TUTOR DE AULA */

function Derivar_estudiante(){
    var id_doce = $("#textId").val();
    var mot_der = $("#motivo_der").val();
    var id_area = $("#areas_apoyo").val();

    if (mot_der.length <= 0 || estu_der == 0){
        Swal.fire("Mensaje De Alerta", "Hay campos sin completar.", "warning");
        return false;
    }
    if (id_area == 0){
        Swal.fire("Mensaje De Alerta", "Ningun area selecionada.", "warning");
        return false;
    }
    $.ajax({
        url: '../controlador/tutor_aula/controlador_derivar_alumno.php',
        type: 'POST',
        data: {
            motivo: mot_der,
            area: id_area,
            estu: estu_der,
            doce: id_doce,
            asig: id_asig_der
        }
    }).done(function(resp) {

        if (resp == 1) {
            Swal.fire("Mensaje De Confirmacion", "Actulizado correctamente.", "success");
            window.location.reload()
            $("#modal_derivar").modal('hide');
        } else {
            Swal.fire("Mensaje De Error", "No se completo la derivaciÃ³n.", "error");
        }
    }); 
}

function GuardarCitas() { 
	var doce = $('#textId').val();
	var tema = $('#tema_tuto').val();
	var comp = $('#comp_tuto').val();
	var tipo = $('#tipo_session').val();
	var link = "";
	var deta = "";
	var date = $('#start_date').val();
	var tone = $('#start_time').val();
	var ttwo = $('#end_time').val();


	if (tema.length <= 0 || td_alumnos.length <= 0 || td_id_alumnos.length <= 0 || date.length <= 0 || tone.length <= 0 || ttwo.length <= 0){
		Swal.fire("Mensaje De Alerta", "Hay compos sin completar", "info");
		return;
	}

	if (tone >= ttwo) {
		Swal.fire("Mensaje De Alerta", "Asegurese de configurar correctamente la hora o fecha", "info");
		return;
	}

	if (tipo == 4) {
		link = $('#link').val();
		if (link.length <= 0) {
			Swal.fire("Mensaje De Alerta", "Ponga el enlace de la session", "info");
			return;
		}
	}else if (tipo == 5) {
		deta = $('#detalles').val();
		if (deta.length <= 0) {
			Swal.fire("Mensaje De Alerta", "Detalle el tipo de session", "info");
			return;
		}
	}

    var array_alumnos = [];
	var array_id_alumnos = [];
	for (var i=0;i<td_alumnos.length;i++){
		array_alumnos.push(td_alumnos[i].innerHTML);
        array_id_alumnos.push(td_id_alumnos[i].innerHTML);
	}

    array_alumnos = array_alumnos.toString();
	array_id_alumnos = array_id_alumnos.toString();
    
    Swal.fire({
          title: "Esta seguro de continuar?",
          text: " ",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "SÃ­, estoy seguro",
          cancelButtonText: "No, cancelar",
        }).then((confirmar3) => {
          if (confirmar3.value) {
             $.ajax({
                "url": "../controlador/tutor_aula/controlador_subir_sesion_tuto.php",
                type: 'POST',
                data: {
                    doc: doce,
                    tem: tema,
                    com: comp,
                    tip: tipo,
                    lik: link,
                    det: deta,
                    fec: date,
                    ini: tone,
                    fin: ttwo,
                    alu: array_alumnos,
                    ida: array_id_alumnos
                }
            }).done(function(resp) {

                if (resp == 1) { 
                    $('#calendarModal').modal('hide'); 
                    
                    $('#alumnos_list').val("");
                    $('#tema_tuto').val("");
                    $('#comp_tuto').val("");
                    $('#start_date').val("");
                    $('#start_time').val("");
                    $('#end_time').val("");


                    Swal.fire("Mensaje De Confirmacion", "Registrado", "success").then((value) => {
                        //hora = document.getElementById('horaaa'); 
                        //hora.click();
                        cargar_contenido('contenido_principal','fullcalendar');
                    });
                }else {
                    Swal.fire("Mensaje De Error", "No se pudo registrar", "error");
                }
            });
          } else {
             return null;
          }
    });

	
}

