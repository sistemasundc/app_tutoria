var tablaUsuarios = null;
const URL_CTRL = "../../controlador/administrador/controlador_usuario.php";

function initUsuarios() {

  // evita duplicar eventos al entrar/salir
  $(document).off("submit", "#formUsuario");
  $(document).off("click", ".btn-cancelar-usuario");

  // ✅ siempre renderiza tabla al entrar al módulo
  renderTablaUsuarios();

  // combos (si el modal existe en la vista)
  cargarRoles();
  cargarCarreras();

  // submit crear/editar
  $(document).on("submit", "#formUsuario", function (e) {
    e.preventDefault();

    Swal.fire({
      title: "¿Guardar cambios?",
      text: "Se guardará la información del usuario.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí, guardar",
      cancelButtonText: "Cancelar"
    }).then((result) => {
      if (!result.isConfirmed) return;

      const datos = $(this).serialize() + "&accion=guardar";

      $.ajax({
        url: URL_CTRL,
        type: "POST",
        data: datos,
        dataType: "json"
      })
      .done(function (r) {
        if (r && r.status === "ok") {
          $("#modalUsuario").modal("hide");
          $("#formUsuario")[0].reset();

          Swal.fire({
            icon: "success",
            title: "Guardado",
            text: r.msg || "Usuario guardado correctamente.",
            timer: 1300,
            showConfirmButton: false
          });

          recargarTablaUsuarios();
        } else {
          Swal.fire("Error", (r && r.msg) ? r.msg : "No se pudo guardar.", "error");
        }
      })
      .fail(function (xhr) {
        console.log(xhr.responseText);
        Swal.fire("Error", "Respuesta inválida del servidor (revisa controlador/modelo).", "error");
      });

    });
  });
}

function renderTablaUsuarios() {
  // destruir si existe (para volver al módulo sin que quede vacío)
  if ($.fn.DataTable.isDataTable("#tablaUsuarios")) {
    $("#tablaUsuarios").DataTable().clear().destroy();
    $("#tablaUsuarios tbody").empty();
  }

  tablaUsuarios = $("#tablaUsuarios").DataTable({
    destroy: true,
    responsive: true,
    processing: true,
    autoWidth: false,
    ajax: {
      url: URL_CTRL,
      type: "POST",
      data: { accion: "listar" },
      dataType: "json",
      dataSrc: function (json) {
        return (json && json.data) ? json.data : [];
      }
    },
    columns: [
      { data: "username" },
      { data: "nombre_completo" },
      { data: "cor_inst" },
      { data: "rol" },
      { data: "carrera" },
      {
        data: "estado",
        render: function (data) {
          return (data === "ACTIVO")
            ? '<span class="label label-success">ACTIVO</span>'
            : '<span class="label label-danger">INACTIVO</span>';
        }
      },
      { data: "acciones", orderable: false, searchable: false }
    ],
    order: [[0, "asc"]]
  });
}

function recargarTablaUsuarios() {
  if ($.fn.DataTable.isDataTable("#tablaUsuarios")) {
    $("#tablaUsuarios").DataTable().ajax.reload(null, false);
  } else {
    renderTablaUsuarios();
  }
}

function abrirModalUsuario() {
  $("#formUsuario")[0].reset();
  $("#id_usuario").val("");
  $("#estado").val("ACTIVO");
  $("#modalUsuario").modal("show");
}

function editarUsuario(id) {
  $.ajax({
    url: URL_CTRL,
    type: "POST",
    data: { accion: "obtener", id_usuario: id },
    dataType: "json"
  })
  .done(function (u) {
    $("#id_usuario").val(u.id_usuario);
    $("#username").val(u.username);
    $("#nombres").val(u.nombres);
    $("#apaterno").val(u.apaterno);
    $("#amaterno").val(u.amaterno);
    $("#cor_inst").val(u.cor_inst);
    $("#cel_pa").val(u.cel_pa);
    $("#rol_id").val(u.rol_id);
    $("#id_car").val(u.id_car);
    $("#estado").val(u.estado);
    $("#modalUsuario").modal("show");
  })
  .fail(function (xhr) {
    console.log(xhr.responseText);
    Swal.fire("Error", "No se pudo obtener el usuario.", "error");
  });
}

function cambiarEstado(id, nuevoEstado) {
  const txt = (nuevoEstado === "ACTIVO") ? "activar" : "desactivar";

  Swal.fire({
    title: "Confirmación",
    text: `¿Deseas ${txt} este usuario?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Sí",
    cancelButtonText: "Cancelar"
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.ajax({
      url: URL_CTRL,
      type: "POST",
      data: { accion: "estado", id_usuario: id, estado: nuevoEstado },
      dataType: "json"
    })
    .done(function (r) {
      if (r && r.status === "ok") {
        Swal.fire({
          icon: "success",
          title: "Listo",
          text: r.msg || "Estado actualizado.",
          timer: 1200,
          showConfirmButton: false
        });
        recargarTablaUsuarios();
      } else {
        Swal.fire("Error", (r && r.msg) ? r.msg : "No se pudo actualizar.", "error");
      }
    })
    .fail(function (xhr) {
      console.log(xhr.responseText);
      Swal.fire("Error", "Respuesta inválida del servidor.", "error");
    });

  });
}

function cargarRoles() {
  $.post(URL_CTRL, { accion: "roles" }, function (html) {
    $("#rol_id").html(html);
  });
}

function cargarCarreras() {
  $.post(URL_CTRL, { accion: "carreras" }, function (html) {
    $("#id_car").html(html);
  });
}
