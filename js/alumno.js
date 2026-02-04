function Docente_a_Cargo(){
    var id_alumno  = $("#textId").val();
    var mistutores = document.getElementById('mis-tutores-asignados');
    var data_tutores = "";

    $.ajax({
          "url": "../controlador/alumno/controlador_listar_mi_tutor.php",
            type: 'POST',
            data:{
              idalumno: id_alumno
            }
          }).done(function(resp) {
              if (resp == 0) {
                data_tutores = `
                <div class="box box-widget widget-user-2">
                  <div class="widget-user-header bg-widget">
                    <div>
                      <h4 class="widget-user-desc"> No tienes tutores asignados.</h4>
                    </div>
                  </div>
                </div>`;
              }else{
                var datos = JSON.parse(resp); 
        
                for (var i=0;i<datos.length;i++) {
                  data_tutores += `
                  <div class="box box-widget widget-user-2">
                    <div class="widget-user-header bg-widget">
                      <div class="widget-user-image">
                        <img class="img-circle" src="../Plantilla/dist/img/images.png" alt="User Avatar">
                      </div>
                      <div>
                        <h3 class="widget-user-username">${datos[i]['nombres']}</h3>
                        <h5 class="widget-user-desc" style="margin-top: .6em;">${datos[i]['telefono']}</h5>
                        <h5 class="widget-user-desc">${datos[i]['correo']}</h5>

                      </div>
                    </div>
                  </div>`;
                }
              }
              mistutores.innerHTML = data_tutores;
    });
}

function filterUnoGlobal() {
  $('#table_sessiones_alumno').DataTable().search($('#global_filter').val()).draw();
}

var table_sessiones_alumno;
function listar_mis_sessiones_alumno() {
    var idestu = document.getElementById("textId").value;
    var hoy = document.getElementById("ftoday").value;

    table_sessiones_alumno = $("#table_sessiones_alumno").DataTable({
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
            url: "../controlador/alumno/controlador_sessiones_tutoria.php",
            type: 'POST',
            data: {
                estu: idestu,
                fecha: hoy
            }
        },
        "columns": [
            {
                "data": "id_tuto"
            },
            {
                "data": "horas"
            },
            {
                "data": "tipo"
            },
            {
                "defaultContent": "<button style='font-size:13px;' type='button' class='sesiones btn btn-primary' \
                    title='Sesiones'><i class=\"fa fa-star\" aria-hidden=\"true\" ></i>&nbsp; Valorar tutoria </button>"
            }
        ],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("table_sessiones_alumno_filter").style.display = "none";

    $('input.global_filter').on('keyup click', function () {
        filterUnoGlobal();
    });
}

var id_sesion = 0;
var valoracion = 0;
var iconstar = document.getElementsByClassName('iconstar');

$('#table_sessiones_alumno').on('click', '.sesiones', function() {
    var tabla = table_sessiones_alumno;
    var data = tabla.row($(this).parents('tr')).data();
   
    if (tabla.row(this).child.isShown()) {
        var data = tabla.row(this).data();
    }

    id_sesion = data.id_tuto;
    valoracion = 0;

    var boxval = document.getElementById('box_valoracion');
    boxval.hidden = false;

    ValorarTutoria(id_sesion, valoracion);
});

$('.btn-star').on('click', function() {
    valoracion = this.name;

    ValorarTutoria(id_sesion, valoracion);
});

function ValorarTutoria(id_sesion, valoracion) {
  $.ajax({
          "url": "../controlador/alumno/controlador_valorar_tutoria.php",
            type: 'POST',
            data:{
              idsesion: id_sesion,
              val: valoracion
            }
          }).done(function(resp) {

              if (resp == 0){
                ValorarTutoria(id_sesion, 0); //recursivo solo para actulizar la valoracion
              }else {
                var datos = JSON.parse(resp);

                var valo = parseInt(datos[0]['valoracion']);

                if (valo >= 1 && valo <= 5){

                  var nopinta = Math.abs(valo-5);

                  for (var i=0;i<=4;i++){
                    if (i<nopinta){
                      iconstar[i].style = 'color: ;';
                    }else {
                      iconstar[i].style = 'color: #ffa400;';
                    }
                  }
                }else {
                  for (var i=0;i<=4;i++){
                    iconstar[i].style = 'color: ;';
                  }
                }
              }
    });
}