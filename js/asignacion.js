//ASIGNACION
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

/* function DerivarEstudiante(id_asig, id_estu) {
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
} */
function DerivarEstudiante(id_asig, id_estu) {  
    estu_der = id_estu;
    id_asig_der = id_asig;

    // Limpiar motivo
    document.getElementById('motivo_der').value = '';

    // Cargar áreas
    listar_areas_apoyo();

    // Aplicar estilos a la cabecera del modal
    $("#modal_derivar .modal-header").css({
        "background-color": "#066da7",
        "color": "white"
    });

    // Mostrar el modal sin sombreado de fondo
    $("#modal_derivar").modal({
        backdrop: false,   // Sin fondo oscuro
        keyboard: false    // No se cierra con ESC
    }).modal('show');
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

            $('#tipo_session').html(cadena);////llamndo en crer horario
        } else {
            cadena += "<option value=''>NO SE ENCONTRARON REGISTROS</option>";
            $("#tipo_session").html(cadena);
        } 
    })
}
 
function CrearSesion() {
    const list_check = document.getElementsByClassName('checkasis');
    const list_id_estu = document.getElementsByClassName('id_estu_hora');
    const list_alumnos = document.getElementById('alumnos_list');

    let vista_config = "";
    let existe = false;

    for (let i = 0; i < list_check.length; i++) {
        if (list_check[i].checked && list_id_estu[i]) {
            const id_asignacion = list_check[i].id;
            const id_estudiante = list_id_estu[i].value;
            const nombre = list_check[i].getAttribute("name");

            vista_config += `
                <tr>
                    <td hidden>${id_asignacion}</td>
                    <td hidden><input type="hidden" value="${id_estudiante}"></td>
                    <td style="font-weight: 500; max-width: 25em;">
                        <i style="color: #6ed5b4;" class="fa fa-angle-double-right"></i>&nbsp; ${nombre}
                    </td>
                    <td>
                        <button type="button" class="btn btn-warning" onclick="this.closest('tr').remove()">Quitar</button>
                    </td>
                </tr>
            `;
            existe = true;
        }
    }

    if (!existe) {
        Swal.fire("Mensaje De Alerta", "Seleccione estudiantes para continuar.", "info");
        return;
    }

    list_alumnos.innerHTML = vista_config;
    $("#calendarModal").modal("show");
}
table_horario = document.getElementById('table_alumnos_horario');
function QuitarAlumno(button) {
  var rowIndex = button.closest('tr').rowIndex;
  table_horario.deleteRow(rowIndex);
}

campo = document.getElementById('campo_detalles');
var set_campo = "";
$('#tipo_session').change(function () {
    let campo = document.getElementById('campo_detalles');
    let set_campo = "";

    if (this.value == 4) {
        set_campo = `
            <label>Link de la sesión: </label>
            <input class="form-control" id="link_sesion" value="" type="text" style="border-radius: 5px; font-weight: 500;">
        `;
    } else if (this.value == 5) {
        set_campo = `
            <label>Especifique: </label>
            <input class="form-control" id="detalle_sesion" value="" type="text" style="border-radius: 5px; font-weight: 500;">
        `;
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


 //DOCENTE POR CURSO - -------------------------------------------------------------------CORREGIR LA VISTA----------
function CrearSesionCurso() {
    const idCarga = $('#id_cargalectiva_hidden').val();   // ? se obtiene correctamente
    $('#id_cargalectiva').val(idCarga);                   // ? se asigna al input oculto

    // Limpiar campos base del modal
    $('#start_date').val('');
    $('#start_time').val('');
    $('#end_time').val('');
    $('#tema_tuto').val('');
    $('#comp_tuto').val('');
    $('#alumnos_list').empty();
    $('#campo_detalles').html('');

    let checkboxes = document.getElementsByClassName('checkasis');
    let id_estudiantes = document.getElementsByClassName('id_estu_hora');

    let haySeleccion = false;

    for (let i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked && id_estudiantes[i]) {
            let id_asig = checkboxes[i].id;
            let id_estu = id_estudiantes[i].value;
            let nombre = checkboxes[i].getAttribute('name');

            let fila = `
                <tr class="tr-alum">
                    <td hidden class="alumnos_tuto">${id_asig}</td>
                    <td hidden><input type="hidden" class="id_alum_tuto" value="${id_estu}"></td>
                    <td style="font-weight: 500; max-width: 25em;">
                        ${id_estu}&nbsp;&nbsp;${nombre}
                    </td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#alumnos_list').append(fila);
            haySeleccion = true;
        }
    }

    if (!haySeleccion) {
        Swal.fire('Mensaje de Alerta', 'Seleccione al menos un estudiante para programar la sesión.', 'info');
        return;
    }

    // Mostrar modal solo si hay estudiantes seleccionados
    $('#calendarModal').modal('show');
}
// GUARDAR el tipo de asignaci�n
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
            Swal.fire("Mensaje De Error", "No se completó.", "error");
        }
    });
});

// LISTAR tipos de sesii�n
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
            Swal.fire("Mensaje De Error", "No se completo la derivaci&oacute;n.", "error");
        }
    }); 
}

//-----TUTOR DE CURSO-----
function GuardarCitas_TC() {
    const form = document.getElementById('modal-form-body');
    const formData = new FormData(form);

    const tema = $('#tema_tuto').val().trim();
    const comp = $('#comp_tuto').val() || '';
    const tipo = $('#tipo_session').val();
    const fecha = $('#start_date').val();
    const horaIni = $('#start_time').val();
    const horaFin = $('#end_time').val();
    const link = $('#link_sesion').val() || '';
    const id_carga = $('#id_cargalectiva').val();
    const docente = $('#textId').val();

    if (!tema || !fecha || !horaIni || !horaFin) {
        Swal.fire("Mensaje De Alerta", "Complete los campos requeridos", "info");
        return;
    }

    if (horaIni >= horaFin) {
        Swal.fire("Mensaje De Alerta", "La hora de inicio debe ser menor que la hora final", "info");
        return;
    }

    const filas = document.querySelectorAll('#alumnos_list tr');
    let array_alumnos = [];
    let array_id_alumnos = [];

    filas.forEach((fila) => {
        const id_asignacion = fila.children[0]?.textContent?.trim();
        const id_estudiante = fila.querySelector('input[type="hidden"]')?.value?.trim();

        if (id_asignacion && id_estudiante) {
            array_alumnos.push(id_asignacion);
            array_id_alumnos.push(id_estudiante);
        }
    });

    if (array_id_alumnos.length === 0) {
        Swal.fire("Mensaje De Alerta", "Seleccione al menos un estudiante", "info");
        return;
    }

    // Validar im�genes
    const evidencias = document.getElementById("evidencias");
    if (evidencias && evidencias.files.length > 2) {
        Swal.fire("Advertencia", "Solo puedes subir un máximo de 2 imágenes", "warning");
        return;
    }

    let totalSize = 0;
    if (evidencias && evidencias.files.length > 0) {
        for (let file of evidencias.files) {
            if (!['image/jpeg', 'image/png'].includes(file.type)) {
                Swal.fire("Archivo inválido", "Solo se permiten imágenes JPG o PNG", "warning");
                return;
            }
            totalSize += file.size;
        }

        if (totalSize > 20 * 1024 * 1024) {
            Swal.fire("Tamaño excedido", "Las imágenes no deben superar los 20MB en total", "warning");
            return;
        }

        for (let i = 0; i < evidencias.files.length; i++) {
            formData.append('evidencias[]', evidencias.files[i]);
        }
    }

    // Agregar dem�s campos
    formData.append("tem", tema);
    formData.append("com", comp);
    formData.append("tip", tipo);
    formData.append("fec", fecha);
    formData.append("ini", horaIni);
    formData.append("fin", horaFin);
    formData.append("lik", link);
    formData.append("doc", docente);
    formData.append("carga", id_carga);
    formData.append("alu", array_alumnos.join(','));
    formData.append("ida", array_id_alumnos.join(','));

    $.ajax({
        url: '../controlador/docente/controlador_subir_sesion_tuto.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (resp) {
            try {
                const data = typeof resp === "string" ? JSON.parse(resp) : resp;
                if (data.status === "success") {
                    Swal.fire("¡Registrado!", data.message, "success").then(() => {
                        $('#calendarModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire("Error", data.message || "No se pudo registrar la sesión", "error");
                }
            } catch (e) {
                console.error("Error al parsear respuesta:", e, resp);
                Swal.fire("Error", "Respuesta inválida del servidor", "error");
            }
        },
        error: function (xhr, status, error) {
            console.error("ERROR AJAX:", error);
            Swal.fire("Error", "Error del servidor (500)", "error");
        }
    });
}




/* TUTOR DE AULA */

function Derivar_estudiante() {
  const id_doce = $("#textId").val();
  const mot_der = $("#motivo_der").val().trim();
  const id_area = $("#areas_apoyo").val();

  if (!mot_der || !estu_der) {
    Swal.fire("Mensaje De Alerta", "Hay campos sin completar.", "warning");
    return;
  }
  if (!id_area || id_area == 0) {
    Swal.fire("Mensaje De Alerta", "Ningún área seleccionada.", "warning");
    return;
  }

  // Evitar doble click
  const $btn = $("#btn-derivar"); // ponle id="btn-derivar" a tu botón Derivar
  $btn.prop("disabled", true);

  Swal.fire({
    title: "Registrando derivación...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  $.ajax({
    url: '../controlador/tutor_aula/controlador_derivar_alumno.php',
    type: 'POST',
    dataType: 'json',                    // <-- IMPORTANTE
    data: {
      motivo: mot_der,
      area: id_area,
      estu: estu_der,
      doce: id_doce,
      asig: id_asig_der
    }
  })
  .done(function(resp) {
    Swal.close();
    // console.log(resp); // te ayuda a ver el JSON en consola

    if (resp && resp.ok) {
      Swal.fire("Éxito", resp.msg || "Derivación registrada.", "success");

      $("#modal_derivar").modal('hide');

      // Si tienes DataTable en el historial:
      if (window.tabla_historial_derivaciones) {
        // recarga sin perder la página actual
        window.tabla_historial_derivaciones.ajax.reload(null, false);
      }
      // O emite un evento por si el historial está en otra vista
      window.dispatchEvent(new CustomEvent('derivacion-registrada'));
    } else {
      Swal.fire("Mensaje De Error", (resp && resp.msg) ? resp.msg : "No se completó la derivación.", "error");
    }
  })
  .fail(function() {
    Swal.close();
    Swal.fire("Error", "Error de comunicación con el servidor.", "error");
  })
  .always(function() {
    $btn.prop("disabled", false);
  });
}

function GuardarCitas() {
    const form = document.getElementById('modal-form-body');
    const formData = new FormData(form);

    const tema = document.getElementById('tema_tuto')?.value.trim() || '';
    const fecha = document.getElementById('start_date')?.value || '';
    const horaIni = document.getElementById('start_time')?.value || '';
    const horaFin = document.getElementById('end_time')?.value || '';
    const tipo = document.getElementById('tipo_session')?.value || '';
    const link = document.getElementById('link_sesion')?.value || '';
    const detalle = document.getElementById('detalle_sesion')?.value || '';
    const docente = document.getElementById('textId')?.value || '';
    const id_carga = document.getElementById('id_cargalectiva')?.value || '';

    // Validaciones b�sicas
    if (!tema || !fecha || !horaIni || !horaFin || !tipo) {
        Swal.fire("Mensaje De Alerta", "Complete todos los campos requeridos (Tema, Fecha y Horarios)", "info");
        return;
    }

    if (horaIni >= horaFin) {
        Swal.fire("Mensaje De Alerta", "La hora de inicio debe ser menor que la hora final", "info");
        return;
    }

    // Obtener estudiantes desde #alumnos_list
    const filas = document.querySelectorAll('#alumnos_list tr');

    let array_alumnos = [];
    let array_id_alumnos = [];

    filas.forEach((fila, i) => {
        const id_asignacion = fila.children[0]?.textContent?.trim();
        const id_estudiante = fila.querySelector('input[type="hidden"]')?.value?.trim();

        if (id_asignacion && id_estudiante) {
            array_alumnos.push(id_asignacion);
            array_id_alumnos.push(id_estudiante);
        } else {
            console.warn(`?? Fila ${i} inválida: asignacion=${id_asignacion}, estudiante=${id_estudiante}`);
        }
    });

    // Validar que existan estudiantes correctos
    if (array_alumnos.length === 0 || array_id_alumnos.length === 0 || array_alumnos.length !== array_id_alumnos.length) {
        Swal.fire("Error", "Los datos de los estudiantes no están completos o coinciden incorrectamente", "error");
        return;
    }

    // Validar im�genes
    const evidencias = document.getElementById("evidencias");
    if (evidencias && evidencias.files.length > 2) {
        Swal.fire("Advertencia", "Solo puedes subir un máximo de 2 imágenes", "warning");
        return;
    }

    let totalSize = 0;
    if (evidencias && evidencias.files.length > 0) {
        for (let file of evidencias.files) {
            if (!['image/jpeg', 'image/png'].includes(file.type)) {
                Swal.fire("Archivo inválido", "Solo se permiten imágenes JPG o PNG", "warning");
                return;
            }
            totalSize += file.size;
        }

        if (totalSize > 20 * 1024 * 1024) {
            Swal.fire("Tamaño excedido", "Las imágenes no deben superar los 20MB en total", "warning");
            return;
        }

        for (let i = 0; i < evidencias.files.length; i++) {
            formData.append('evidencias[]', evidencias.files[i]);
        }
    }

    // Agregar campos al FormData
    formData.append("tem", tema);
    formData.append("fec", fecha);
    formData.append("ini", horaIni);
    formData.append("fin", horaFin);
    formData.append("tip", tipo);
    formData.append("lik", link);
    formData.append("det", detalle);
    formData.append("doc", docente);
    formData.append("carga", id_carga);
    formData.append("alu", array_alumnos.join(','));
    formData.append("ida", array_id_alumnos.join(','));

    // Enviar al servidor
    $.ajax({
        url: "../controlador/tutor_aula/controlador_subir_sesion_tuto.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (resp) {
            try {
                const data = typeof resp === "string" ? JSON.parse(resp) : resp;
                if (data.status === "success") {
                    Swal.fire("Registrado!", data.message, "success").then(() => {
                        $('#calendarModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire("Error", data.message || "No se pudo registrar la sesión", "error");
                }
            } catch (e) {
                console.error("Error al parsear respuesta:", e, resp);
                Swal.fire("Error", "Respuesta inválida del servidor", "error");
            }
        },
        error: function (xhr, status, error) {
            console.error("ERROR AJAX:", error);
            Swal.fire("Error", "Error del servidor (500)", "error");
        }
    });
}
