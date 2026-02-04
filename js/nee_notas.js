$(document).on("click", ".btnNotas", function () {
  let id_estu = $(this).data("id");

  $("#modalNotasNEE").modal("show");

  $.post("reportes/notas_nee.php", { id_estu }, function (resp) {
    if (!resp.ok) {
      Swal.fire("Error", resp.error, "error");
      return;
    }

    let tb = $("#tablaNotas tbody").empty();

    resp.data.forEach(n => {
      tb.append(`
        <tr>
          <td>${n.curso}</td>
          <td>${n.p1}</td>
          <td>${n.p2}</td>
          <td>${n.parcial}</td>
          <td>${n.p3}</td>
          <td>${n.p4}</td>
          <td>${n.final}</td>
          <td>${n.promedio}</td>
        </tr>
      `);
    });
  }, "json");
});
