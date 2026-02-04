/* alert("nee.js cargado"); */
var NEE_API = "reportes/save_nee.php";

(function () {

  var dtNEE = null;

  // ✅ la exponemos para que pueda llamarse desde cualquier acción
  window.cargarNEE = function(){};

  function initNEE() {
    if (!document.getElementById("tablaNEE")) return;

    function cargarNEE() {
      $.post(NEE_API, { accion: "listar" }, function (resp) {

        if (!resp || resp.ok !== true) {
          Swal.fire("Error", (resp && resp.error) ? resp.error : "No se pudo cargar", "error");
          return;
        }

        if (dtNEE) { dtNEE.clear().destroy(); dtNEE = null; }

        var tb = $("#tablaNEE tbody").empty();
        (resp.data || []).forEach(function (r) {
          tb.append(
            `<tr>
              <td>${r.id_estu}</td>
              <td>${r.estudiante}</td>
              <td>${r.carrera || ""}</td>
              <td>${r.email_estu || ""}</td>
              <td>${r.ciclo || ""}</td>
              <td>${r.id_semestre || ""}</td>
              <td><button class="btn btn-info btn-xs btnNotas" data-id="${r.id_estu}">NOTAS</botton></td>
              <td><button class="btn btn-success btn-xs btnAsis" data-id="${r.id_estu}">ASISTENCIA</button></td>
              <td><button class="btn btn-danger btn-xs btnDesactivar" data-id="${r.id_estu}">Desactivar</button></td>
            </tr>`
          );
        });

        dtNEE = $("#tablaNEE").DataTable({
          language: (window.idioma_espanol || {}),
          responsive: true,
          destroy: true
        });

      }, "json").fail(function (xhr) {
        Swal.fire("Error", "Fallo AJAX listar NEE (revisa consola / red).", "error");
        console.error("listar NEE error:", xhr.responseText);
      });
    }

    // Expone el refresco para usarlo en desactivar/guardar
    window.cargarNEE = cargarNEE;

    function renderBusqueda(items) {
      var tb = $("#tablaBusqueda tbody").empty();

      if (!items || items.length === 0) {
        tb.html(`<tr><td colspan="9" class="text-center text-muted">Sin resultados</td></tr>`);
        return;
      }

      items.forEach(function (x) {
        var json = encodeURIComponent(JSON.stringify(x));
        tb.append(
          `<tr>
            <td>${x.id_estu}</td>
            <td>${x.estudiante}</td>
            <td>${x.dni_estu || ""}</td>
            <td>${x.cod_estu || ""}</td>
            <td>${x.email_estu || ""}</td>
            <td>${x.ciclo || ""}</td>
            <td>${x.id_semestre || ""}</td>
            <td>${x.carrera || ""}</td>
            <td><button class="btn btn-primary btn-xs btnSelect" data-json="${json}">Seleccionar</button></td>
          </tr>`
        );
      });
    }

    // evitar doble binding
    $(document).off("click.nee", "#btnBuscar");
    $(document).off("submit.nee", "#formNEE");
    $(document).off("click.nee", ".btnSelect");
    $(document).off("click.nee", ".btnDesactivar");
    $(document).off("click.nee", ".btnNotas");
    $(document).off("click.nee", ".btnAsis");

    // Buscar
    $(document).on("click.nee", "#btnBuscar", function () {
      var q = $("#q").val().trim();
      if (q.length < 2) {
        Swal.fire("Atención", "Escribe al menos 2 caracteres.", "warning");
        return;
      }

      $.post(NEE_API, { accion: "buscar_estudiante", q: q }, function (resp) {
        if (!resp || resp.ok !== true) {
          Swal.fire("Error", (resp && resp.error) ? resp.error : "No se pudo buscar", "error");
          return;
        }
        renderBusqueda(resp.data);
      }, "json").fail(function (xhr) {
        Swal.fire("Error", "Fallo AJAX buscar (revisa consola / red).", "error");
        console.error("buscar_estudiante error:", xhr.responseText);
      });
    });
    // Seleccionar
    $(document).on("click.nee", ".btnSelect", function () {
      var x = JSON.parse(decodeURIComponent($(this).attr("data-json")));

      $("#id_estu").val(x.id_estu);
      $("#dni").val(x.dni_estu || "");
      $("#codigo").val(x.cod_estu || "");
      $("#email").val(x.email_estu || "");
      $("#programa").val(x.carrera || "");
      $("#ape_paterno").val(x.apepa_estu || "");
      $("#ape_materno").val(x.apema_estu || "");
      $("#nombres").val(x.nom_estu || "");

      $("#btnGuardar").prop("disabled", false);

      $("#seleccionInfo").show().html(
        "<b>Seleccionado:</b> " + x.estudiante +
        " | <b>Ciclo:</b> " + (x.ciclo || "") +
        " | <b>Semestre:</b> " + (x.id_semestre || "") +
        " | <b>Carrera:</b> " + (x.carrera || "")
      );
    });

    // Guardar (ya refresca con cargarNEE)
    $(document).on("submit.nee", "#formNEE", function (e) {
      e.preventDefault();

      if (!$("#id_estu").val()) {
        Swal.fire("Atención", "Selecciona un estudiante.", "warning");
        return;
      }

      var data = $(this).serializeArray();
      data.push({ name: "accion", value: "guardar" });

      $.post(NEE_API, data, function (resp) {
        if (resp && resp.ok === true) {
          Swal.fire("¡Registrado!", "Se agregó el estudiante a NEE.", "success");

          $("#modalNEE").modal("hide");
          $("#formNEE")[0].reset();
          $("#btnGuardar").prop("disabled", true);
          $("#seleccionInfo").hide().html("");
          $("#tablaBusqueda tbody").html(`<tr><td colspan="9" class="text-center text-muted">Realiza una búsqueda…</td></tr>`);

          // ✅ refresca igual que quieres
          cargarNEE();

        } else {
          Swal.fire("Error", (resp && resp.error) ? resp.error : "No se pudo guardar", "error");
        }
      }, "json").fail(function (xhr) {
        Swal.fire("Error", "Fallo AJAX guardar (revisa consola / red).", "error");
        console.error("guardar error:", xhr.responseText);
      });
    });

    // ✅ Desactivar (MISMO comportamiento: refresca con cargarNEE)
    $(document).on("click.nee", ".btnDesactivar", function () {
      var id = $(this).data("id");

      Swal.fire({
        title: "¿Desactivar estudiante NEE?",
        text: "El registro quedará almacenado, pero no aparecerá como activo.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, desactivar",
        cancelButtonText: "Cancelar"
      }).then(function (r) {

        // compatibilidad con versiones viejas/nuevas de SweetAlert2
        var confirmed = (r && (r.isConfirmed === true || r.value === true));
        if (!confirmed) return;

        $.post(NEE_API, { accion: "desactivar", id_estu: id }, function (resp) {

          if (resp && resp.ok === true) {
            Swal.fire("Listo", "Estudiante desactivado.", "success");

            // ✅ refresca la tabla sin recargar página
            cargarNEE();

          } else {
            Swal.fire("Error", (resp && resp.error) ? resp.error : "No se pudo desactivar", "error");
          }

        }, "json").fail(function (xhr) {
          Swal.fire("Error", "Fallo AJAX desactivar (revisa consola / red).", "error");
          console.error("desactivar error:", xhr.responseText);
        });

      });
    });

    $(document).on("click.nee", ".btnNotas", function () {
      Swal.fire("Pendiente", "Aquí irá el modal de NOTAS.", "info");
    });

    $(document).on("click.nee", ".btnAsis", function () {
      Swal.fire("Pendiente", "Aquí irá el modal de ASISTENCIA.", "info");
    });

    // carga inicial
    cargarNEE();
  }

  window.initNEE = initNEE;

  $(document).ready(function () {
    initNEE();
  });

  // Si tu vista entra por load() a #contenido_principal
  var obs = new MutationObserver(function () {
    if (document.getElementById("tablaNEE")) initNEE();
  });
  obs.observe(document.body, { childList: true, subtree: true });

})();
