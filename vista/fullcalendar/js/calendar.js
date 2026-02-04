//CALENDAR
var mod_footer = document.getElementById("modal_footer");
var btn_action = `
  <button type="button" class="btn btn-danger" data-dismiss="modal">Salir</button>
  <button type="button" class="btn btn-primary" id="btn_guardar_asistencia">Guardar</button>
`;
function addZero(i) {
    if (i < 10) {
        i = '0' + i;
    }
    return i;
}

var hoy = new Date();
var dd = hoy.getDate();
var mm = hoy.getMonth() + 1;
var yyyy = hoy.getFullYear();

dd = addZero(dd);
mm = addZero(mm);

var data_horario = [];
var rol = document.getElementById("rol_usuario")?.value || "6";
var id_user = document.getElementById("textId")?.value || "";
//
function limpiarModales() {
    $('.modal').each(function () {
        $(this).modal('hide');
    });
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
}

function CargarHorario() {
    $.ajax({
        url: "../controlador/horario/controlador_horario.php",
        type: 'POST',
        data: { doce: id_user, rol_usuario: rol }
    }).done(function(resp) {
        try {
            const data = JSON.parse(resp);
            data_horario = [];

            for (let i = 0; i < data.length; i++) {
                const evento = {
                    title: data[i][0],
                    description: data[i][1],
                    start: data[i][3],
                    end: data[i][4],
                    color: data[i][5],
                    textColor: "#fff",
                    id: data[i][6],
                    cargalectiva: data[i][7] ?? ""
                };
                data_horario.push(evento);
            }

            Calendar();
        } catch (error) {
            console.error("Error al parsear JSON:", error);
            console.warn("RESPUESTA RECIBIDA:", resp);
        }
    });
}


var id_sesion_actual = "";
function Calendar() {
    $('#calendar').fullCalendar({
        timeZone: 'local',
        defaultView: 'month',
        locale: 'es',
        header: {
            left: 'prev,next',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        defaultDate: yyyy + '-' + mm + '-' + dd,
        buttonIcons: true,
        weekNumbers: false,
        editable: false,
        eventLimit: true,
        events: data_horario,
        dayClick: function(date, jsEvent, view) {
            console.log("Día clickeado:", date.format());
            NuevoCalendar(date.format());
        },
        eventClick: function(calEvent, jsEvent, view) {
            if (!calEvent || !calEvent.id) {
                console.error("Evento inválido o sin ID:", calEvent);
                return;
            }

            const inputCarga = document.getElementById("id_cargalectiva");
            if (inputCarga) {
                inputCarga.value = calEvent.cargalectiva || "";
            }

            $('#event-title').text(calEvent.title);
            $('#tema_tuto').val(calEvent.title);
            id_sesion_actual = calEvent.id;
            CargarAsistencia(calEvent.id);

            //  Aquí abrimos el modal sin backdrop
            $('#modal-event').modal({
                backdrop: false,
                keyboard: true
            });
        }
        /* eventClick: function(calEvent, jsEvent, view) {
            if (!calEvent || !calEvent.id) {
                console.error("Evento inválido o sin ID:", calEvent);
                return;
            }

            const inputCarga = document.getElementById("id_cargalectiva");
            if (inputCarga) {
                inputCarga.value = calEvent.cargalectiva || "";
            }

            $('#event-title').text(calEvent.title);
            $('#tema_tuto').val(calEvent.title);
            id_sesion_actual = calEvent.id;
            CargarAsistencia(calEvent.id);
            $('#modal-event').modal();
        } */
    });
}

function NuevoCalendar(date) {
    console.log("Abriendo modal para nueva sesión en fecha:", date);

    // También aquí sin backdrop
    $('#calendarModal').modal({
        backdrop: false,
        keyboard: true
    });

    $('#startDate').val(date);
}

function ActualizarCalendario() {
  const formData = new FormData();
  formData.append("ses", id_sesion_actual);
  formData.append("tem", $("#tema_tuto").val());
  formData.append("com", $("#comp_tuto").val());
  formData.append("obs", $("#obs_tuto").val());
  formData.append("tip", $("#tipo_session").val());
  formData.append("fec", $("#start_date").val());
  formData.append("ini", $("#start_time").val());
  formData.append("fin", $("#end_time").val());
  formData.append("carga", $("#id_cargalectiva").val());

  const tipo = $("#tipo_session").val();
  if (tipo == 4) {
    const link = $("#link").val()?.trim();
    if (!link) return Swal.fire("Alerta", "Debe ingresar el enlace de la sesión", "info");
    formData.append("lik", link);
  }

  if (tipo == 5) {
    const detalle = $("#detalles").val()?.trim();
    if (!detalle) return Swal.fire("Alerta", "Debe detallar el tipo de sesión", "info");
    formData.append("det", detalle);
  }

  const ids = [];
  $(".id_det_tuto").each((i, el) => {
    if ($(".asistencia_check").eq(i).prop("checked")) {
      ids.push($(el).text().trim());
    }
  });
  if (ids.length === 0) return Swal.fire("Alerta", "Debe marcar al menos una asistencia", "info");
  formData.append("ida", ids.join(","));

  const evidenciasInput = document.getElementById("evidencias");
  const maxSize = 20 * 1024 * 1024;
  if (evidenciasInput && evidenciasInput.files.length > 0) {
    const files = Array.from(evidenciasInput.files).slice(0, 2);
    for (const file of files) {
      if (!file.type.startsWith("image/")) return Swal.fire("Error", `Solo se permiten imágenes JPG o PNG`, "error");
      if (file.size > maxSize) return Swal.fire("Error", `La imagen \"${file.name}\" excede los 20 MB permitidos`, "error");
      formData.append("evidencias[]", file);
    }
  }

  const urlDestino = rol == 2
    ? "../controlador/docente/controlador_actualizar_calendario.php"
    : "../controlador/tutor_aula/controlador_actualizar_calendario.php";

  $.ajax({
    url: urlDestino,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false
  }).done(resp => {
    let data;
    try {
      data = typeof resp === "string" ? JSON.parse(resp) : resp;
    } catch (err) {
      console.error("? Error al parsear JSON:", err, resp);
      return Swal.fire("Error", "Respuesta inesperada del servidor", "error");
    }

    if (data.status === "success") {
     /*  $(".asistencia_check").attr("disabled", true); */
     /*  $("#btn_guardar_asistencia, #btn_delete").remove(); */
      Swal.fire("Confirmación", data.message || "Asistencia y evidencia guardadas", "success")
        .then(() => {
          $('#modal-event').modal('hide');
          $("#hora").click();
        });
    } else {
      Swal.fire("Error", data.message || "No se pudo registrar la asistencia", "error");
    }
  }).fail(err => {
    console.error("? Error en petición AJAX:", err);
    Swal.fire("Error", "Ocurrió un error en la petición al servidor", "error");
  });
}

// Previsualizaci�n de im�genes
document.addEventListener("DOMContentLoaded", function () {
  const inputFile = document.getElementById("evidencias");
  const preview = document.getElementById("preview_evidencias");

  if (!inputFile || !preview) return;

  let selectedFiles = [];

  inputFile.addEventListener("change", function () {
    const files = Array.from(inputFile.files);
    selectedFiles = selectedFiles.concat(files).slice(0, 2); // M�x. 2 im�genes
    renderPreview();
  });

  function renderPreview() {
    preview.innerHTML = "";

    selectedFiles.forEach((file, index) => {
      if (!file.type.startsWith("image/")) return;

      const reader = new FileReader();
      reader.onload = function (e) {
        const imgContainer = document.createElement("div");
        imgContainer.style.position = "relative";
        imgContainer.style.display = "inline-block";
        imgContainer.style.margin = "5px";

        const img = document.createElement("img");
        img.src = e.target.result;
        img.style.maxWidth = "120px";
        img.style.maxHeight = "120px";
        img.style.border = "1px solid #ccc";

        const btnX = document.createElement("span");
        btnX.innerHTML = "?";
        btnX.title = "Eliminar";
        btnX.style.position = "absolute";
        btnX.style.top = "0";
        btnX.style.right = "0";
        btnX.style.cursor = "pointer";
        btnX.style.background = "#fff";
        btnX.style.border = "1px solid #ccc";
        btnX.style.borderRadius = "50%";
        btnX.style.fontSize = "14px";
        btnX.style.padding = "0 4px";

        btnX.onclick = function () {
          selectedFiles.splice(index, 1);
          renderPreview();
        };

        imgContainer.appendChild(img);
        imgContainer.appendChild(btnX);
        preview.appendChild(imgContainer);
      };

      reader.readAsDataURL(file);
    });

    // reconstruir input
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => dataTransfer.items.add(file));
    inputFile.files = dataTransfer.files;
  }
});


campo = document.getElementById('campo_detalles');

// ---------CARGAR ASIATENCIA  CURSO -------------------------------

// ---------CARGAR ASIATENCIA  AULA -------------------------------

function CargarAsistencia(id_sesion) {
    const rol = parseInt(document.getElementById("rol_usuario")?.value || "6");
    const id_cargalectiva = document.getElementById("id_cargalectiva")?.value || "";

    console.log("ROL DETECTADO:", rol);
    console.log("ID_CARGA:", id_cargalectiva);

    let urlSesion = "../controlador/tutor_aula/controlador_lista_sesiones.php";
    let urlAsistencia = "../controlador/tutor_aula/controlador_lista_asistencia_alumnos.php";

    if (rol === 2) {
        urlSesion = "../controlador/docente/controlador_lista_sesiones.php";
        urlAsistencia = "../controlador/docente/controlador_lista_asistencia_alumnos.php";
    }

    // Obtener datos de la sesi�n
    // Obtener datos de la sesi�n
    $.ajax({
        url: urlSesion,
        type: 'POST',
        data: { id: id_sesion }
    }).done(function (resp) {
        let data;
        try {
            data = JSON.parse(resp);
        } catch (err) {
            Swal.fire("Error", "Error al cargar la sesión", "error");
            return;
        }

        if (!data || !data[0]) {
            Swal.fire("Error", "No se encontró la sesión.", "error");
            return;
        }

        const sesion = data[0];
        $('#comp_tuto').val(sesion.comp);
        $('#tipo_session').val(sesion.tipo);
        $('#obs_tuto').val(sesion.obs);
        $('#start_date').val(sesion.fecha);
        $('#start_time').val(sesion.ini);
        $('#end_time').val(sesion.fin);


        let set_campo = "";
        if (sesion.tipo == 4) {
            set_campo = `
                <div class="row">
                    <div class="col-md-8">
                        <label>Link de la sesión:</label>
                        <input class="form-control" id="link" value="${sesion.link}" type="text" style="border-radius: 5px; font-weight: 500;">
                    </div>
                    <div class="col-md-4">
                        <label>Google Meet</label><br>
                        <button type="button" class="btn btn-success" onclick="window.open('${sesion.link}', '_blank')">
                            <i class="fa fa-video-camera"></i>&nbsp; Ir a la sesión
                        </button>
                    </div>
                </div>`;
        } else if (sesion.tipo == 5) {
            set_campo = `
                <label>Especifique:</label>
                <input class="form-control" id="detalles" value="${sesion.otro}" type="text" style="border-radius: 5px; font-weight: 500;">`;
        }

        document.getElementById('campo_detalles').innerHTML = set_campo;
        document.getElementById('modasis').style = `background-color: ${sesion.color}; border: none; border-radius: 5px 5px 0 0;`;

        // Mostrar evidencias ya guardadas si existen
        const contenedor = document.getElementById("vista_evidencias_existentes");
        contenedor.innerHTML = ""; // Limpiar primero

        if (sesion.evi1) {
            contenedor.innerHTML += `
                <div class="mb-2">
                    <a href="${sesion.evi1}" target="_blank">Ver evidencia 1</a><br>
                    <img src="${sesion.evi1}" style="max-width: 120px; border: 1px solid #ccc; margin-top: 5px;">
                </div>`;
        }

        if (sesion.evi2) {
            contenedor.innerHTML += `
                <div class="mb-2">
                    <a href="${sesion.evi2}" target="_blank">Ver evidencia 2</a><br>
                    <img src="${sesion.evi2}" style="max-width: 120px; border: 1px solid #ccc; margin-top: 5px;">
                </div>`;
        }
    });

    // Obtener alumnos asistentes
    $.ajax({
        url: urlAsistencia,  // Ahora se usa correctamente la variable urlAsistencia
        type: 'POST',
        dataType: 'json',
        data: {
            id: id_sesion,
            id_carga: id_cargalectiva
        }
    }).done(function (data) {
      console.log("Respuesta alumnos:", data);
        if (!Array.isArray(data)) {
            Swal.fire("Error", "Datos de alumnos inválidos.", "error");
            return;
        }

        const list_alumnos = document.getElementById('alumnos_asis_list');
        let vista_config = "";

        data.forEach(alumno => {
            /* const check = alumno.asis == 1 ? "checked disabled" : ""; */
            const check = alumno.asis == 1 ? "checked" : "";
            vista_config += `
                <tr>
                    <td hidden class="id_det_tuto">${alumno.id}</td>
                    <td style="font-weight: 500; max-width: 25em;">
                        <i style="color: #f39c12; font-size: 20px;" class="fa fa-angle-double-right"></i>
                        &nbsp; ${alumno.nombres}
                    </td>
                    <td><input type="checkbox" class="asistencia_check" ${check}></td>
                </tr>`;
        });

        list_alumnos.innerHTML = vista_config;

        // Habilita / deshabilita bot�n eliminar
        const activos = $(".asistencia_check:not(:disabled)").length;
        $(".marcar_todo").prop("disabled", activos === 0);

        mod_footer.innerHTML = btn_action;

        if (!data.find(a => a.asis == 1)) {
            mod_footer.innerHTML += `<button type="button" class="btn btn-warning" id="btn_delete" onclick="EliminarCalendario(${id_sesion})">Eliminar</button>`;
        }
    });
    // Limpia el preview de im�genes seleccionadas
    const preview = document.getElementById("preview_evidencias");
    if (preview) preview.innerHTML = "";

    // Limpia el input de archivo seleccionado
    const inputFile = document.getElementById("evidencias");
    if (inputFile) inputFile.value = "";

}
// ---------------------------------------------

//--------------------------------------------------------------------------------------------------------------------------

function listar_tipo_session() {
    $.ajax({
        "url": "../controlador/docente/controlador_combo_session.php",
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

function EliminarCalendario(id_sesion){
     limpiarModales();    // <<--- NUEVO
    /* $("#modal-event").modal("hide");  */
/*     $("#modal-event").removeClass("show");
    $("#modal-event").attr("aria-hidden", "true"); */

    Swal.fire({
          title: "Estas seguro de continuar?",
          text: " ",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "S&iacute, estoy seguro",
          cancelButtonText: "No, cancelar",
        }).then((confirmar) => {
          if (confirmar.value) {
               $.ajax({
                    "url": "../controlador/docente/controlador_verificar_del_calendario.php",
                    type: 'POST',
                    data: {
                        id: id_sesion
                    }
                }).done(function(resp) {
                    if (resp == 127) {
                        Swal.fire("Mensaje De Error", "No se pudo Eliminar", "error");
                    }else if (resp == 1) {
                        Swal.fire("Mensaje De Confirmación", "Elimnado correctamente", "success"); 
                        cargar_contenido('contenido_principal','fullcalendar'); 
                    }else {
                        Swal.fire("Mensaje De Alerta", "No es posible eliminar", "warning");
                    }
                });
                
          } else {
             return null;
          }
    });
    QuitarPadding();
} 


function QuitarPadding(){
    const body_general = document.getElementById("body-general").style = 'padding-right: 0px';
}


$(document).ready(function () {
    CargarHorario();
    listar_tipo_session();
    const body_general = document.getElementById("body-general");
    if (body_general) body_general.style = 'padding-right: 0px';

    setTimeout(ejecutarDespuesDeTresSegundos, 1000);
});
function ejecutarDespuesDeTresSegundos() {
    const body_general = document.getElementById("body-general").style = 'padding-right: 0px';
}

setTimeout(ejecutarDespuesDeTresSegundos, 1000);

// Activar/desactivar todos los checks de asistencia
// Al hacer clic en "marcar a todos"
$(document).on("click", "#btn_guardar_asistencia", function () {
    console.log("Ejecutando ActualizarCalendario()");
    ActualizarCalendario();
});
// Activar/desactivar todos los checks de asistencia
// Al hacer clic en "marcar a todos"
$(document).on("change", ".marcar_todo", function () {
    const marcar = this.checked;
    $(".asistencia_check").each(function () {
        if (!$(this).is(":disabled")) {
            $(this).prop("checked", marcar);
        }
    });
});
