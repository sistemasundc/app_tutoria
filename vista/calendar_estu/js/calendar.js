function addZero(i) {
    if (i < 10) {
        i = '0' + i;
    }
    return i;
}

function hoyYMD() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

var fecha_actual = hoyYMD();  // ejemplo: "2025-09-08"


var data_horario = [];
var id_ses = "";
var fecha_session = "";
var id_user = document.getElementById('textId').value;
var iconstar = document.getElementsByClassName('iconstar');

function CargarHorario() {
    $.ajax({
        "url": "../controlador/horario/controlador_horario_alumno.php",
            type: 'POST',
            data: {
                estu: id_user
            }
    }).done(function(resp) {

        var data = JSON.parse(resp);

        for (var i = 0; i < data.length; i++) {
            var event = {};

            event.title = data[i][0];
            event.description = data[i][1];

            if (data[i][2].length > 0 && data[i][2] != 0) {
                //event.url = data[i][2];
            }

            event.start = data[i][3];
            event.end = data[i][4];
           
            event.color = data[i][5];
            event.textColor = "#ffffff";

            event.id = data[i][6];
            data_horario.push(event);
        } 
        Calendar();
    })
}

var id_sesion_actual = "";
function Calendar() {
    $('#calendar').fullCalendar({
        timeZone: 'local',
        initialView: 'dayGridMonth',
        locale: 'es',
        header: {
            left: 'prev,next',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        defaultDate: fecha_actual,
        buttonIcons: true, // show the prev/next text
        weekNumbers: false,
        editable: false,
        eventLimit: true, // allow "more" link when too many events 
        events: data_horario,
        dayClick: function (date, jsEvent, view) {
            //alert('Has hecho dayClickick en: '+ date.format());
            //NuevoCalendar(date.format());
        }, 
        eventClick: function (calEvent, jsEvent, view) {
            $('#event-title').text(calEvent.title);
            $('#tema_tuto').html(calEvent.title);

            id_sesion_actual = calEvent.id;
            CargarAsistencia(calEvent.id);

            $('#modal-event').modal({
            backdrop: false,   // sin capa oscura (no bloquea el calendario)
            keyboard: false
            }); 
        }, 
    });
} 
 
function CargarAsistencia(id_sesion) {
    console.log("carga asistencia");
    var id_estu = $('#textId').val();

    $.ajax({
        "url": "../controlador/docente/controlador_lista_sesiones_estu.php",
        type: 'POST',
        data: {
            id: id_sesion,
            es: id_estu
        }
    }).done(function(resp) { 
        console.log(resp);
        id_ses = id_sesion

        var data = JSON.parse(resp); 

        $('#tipo_session').html(data[0]['tipo']);
        $('#obs_tuto').html(data[0]['obs']);

        if (data[0]['tipo'] == 4) {
            set_campo = `
                    <button type="button" class="btn btn-success" onclick="window.open('${data[0]['link']}', '_blank')">
                        <i class="fa fa-video-camera" aria-hidden="true"></i>&nbsp; Ir a google meet
                    </button>`;
        }else if (data[0]['tipo'] == 5) {
            set_campo = `<span class="campoasis" >${data[0]['otro']}</span>`;
        }else {
            set_campo = data[0]['des_tipo']
        }

        $('#tipo_session').html(set_campo);

        $('#start_date').html(data[0]['fecha']);
        fecha_session = String(data[0]['fecha'] || '').trim().slice(0, 10);  // Solo YYYY-MM-DD
        $('#start_time').html(data[0]['ini']);
        $('#end_time').html(data[0]['fin']);

        $('#doce_tuto').html(data[0]['nombres']);

        var mod = document.getElementById('modasis');
        var color_d = data[0]['color'];
        
        
        var boxstar = document.getElementById('box_valoracion');
        var boxstar = document.getElementById('box_valoracion');
        var valora = "";
        var comenta = "";
        if (data[0]['asis'] == 1) {
            valora = `
                <label>
                    <i class="fa fa-star" aria-hidden="true" style="color: #3c8dbc;"></i>&nbsp; 
                    Valoración Tutoria:
                    <span class="label label-success" style="font-size: 0.9em;">Habilitado</span>
                </label><br>`;
            comenta = `
                <label class="rellenar" >
                    <i class="fa fa-comments-o" aria-hidden="true" style="color: #3c8dbc;"></i>
                    &nbsp; Comentario:
                    <span class="label label-success" style="font-size: 0.9em;">Habilitado</span>
                </label>
                <br>
                <textarea class="campoasis" 
                          id="comentario_estu" 
                          style="text-align: justify; max-width: 100%; width: 100%; height: 4em; overflow: hidden;" 
                          placeholder="Comentario">${data[0]['coment']}</textarea>`;

            boxstar.hidden = false;
    
            var valo = parseInt(data[0]['valo']);
            var nopinta = Math.abs(valo-5);

            for (var i=0;i<=4;i++){
                if (i<nopinta){
                    iconstar[i].style = 'color: ;';
                }else {
                    iconstar[i].style = 'color: #ffa400;';
                }
            }
        }else {
            valora = `
                <label>
                    <i class="fa fa-star" aria-hidden="true" style="color: #3c8dbc;"></i>&nbsp; 
                    Valoración Tutoria:
                    <span class="label label-warning" style="font-size: 0.9em;">Asistencia pendiente</span>
                </label><br>`;
            
            comenta = `
                <label class="rellenar">
                    <i class="fa fa-comments-o" aria-hidden="true" style="color: #3c8dbc;"></i>
                    &nbsp; Comentario:
                    <span class="label label-warning" style="font-size: 0.9em;">Comentario pendiente</span>
                </label>
            `;

            boxstar.hidden = true;
        }

        mod.style = `background-color: ${color_d}; border: none; border-radius: 5px 5px 0 0;`;

        $('#valo_tuto').html(valora);
        $('#box_comentario').html(comenta);
    });
}

var valoracion = "";

$('.btn-star').on('click', function() { 
    valoracion = this.name;

    if (fecha_session === fecha_actual) {
        ValorarTutoria(id_ses, valoracion);
    } else {
        Swal.fire("Mensaje De Alerta", "No se aceptan calificaciones fuera de la fecha de la sesión.", "info");
    }
});

function ValorarTutoria(id_sesion, valoracion) {
    var id_estu = $('#textId').val();
    var comentario = $('#comentario_estu').val();
   
    $.ajax({
        "url": "../controlador/alumno/controlador_valorar_tutoria.php",
            type: 'POST',
            data:{
                idsesion: id_sesion,
                val: valoracion,
                coment: comentario,
                estu: id_estu
            }
        }).done(function(resp) {
            console.log(resp);
            if (resp == 1) {
                var valo = parseInt(valoracion);

                if (valo >= 1 && valo <= 5){

                  var nopinta = Math.abs(valo-5);

                  for (var i=0;i<=4;i++){
                    if (i<nopinta){
                      iconstar[i].style = 'color: ;';
                    }else {
                      iconstar[i].style = 'color: #ffa400;';
                    }
                  }
                }
            }else {
               Swal.fire("Mensaje De Error", "Error", "error");
            }
    });
}

function GuardarComentario() {
  var valo_actual = 0;
  for (var i=0;i<=4;i++){
    if (iconstar[i].style.color != ''){ 
      valo_actual++;
    }
  }
  ValorarTutoria(id_ses, valo_actual);   // sin restricción de fecha
  Swal.fire("Mensaje De Confirmación", "Guardado correctamente.", "success");
}

$(document).ready(function() {
    CargarHorario(); 
});   