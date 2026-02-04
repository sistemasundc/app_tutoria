$(document).on("click", ".btnAsis", function () {
  let id_estu = $(this).data("id");

  $("#modalAsistenciaNEE").modal("show");

  $.post("reportes/asistencia_nee.php", { id_estu }, function (resp) {
    if (!resp.ok) {
      Swal.fire("Error", resp.error, "error");
      return;
    }

    let tb = $("#tablaAsistencia tbody").empty();

    resp.data.forEach(a => {
      tb.append(`
        <tr>
          <td>${a.semana}</td>
          <td>${a.condicion}</td>
          <td>${a.porcentaje}%</td>
        </tr>
      `);
    });
  }, "json");
});
