var sem = $('#anio').val();
var id_doce = $('#textId').val();

var foro_carga = 0;
var tit = 0;
var msj = 0;
var likes = 0;
var resp = 0;
var fecha = "";
var nom = "";
var inicial = "";

function cargar_foro(contenedor,contenido){
      $("#"+contenedor).load(contenido);
}
function cargar_foro_one(contenedor,contenido, id_foro, con_foro, re, fe, nombre, ini){
    document.getElementById("verTodosBtn").classList.remove("active");

    foro_carga = id_foro;
    tit = document.getElementById("titu"+con_foro).innerHTML;
    msj = document.getElementById("mesa"+con_foro).innerHTML;

    resp = re;
    fecha = fe;
    nom = nombre;
    inicial = ini;

    $("#"+contenedor).load(contenido);
}
function listar_combo_ciclos() {
    $.ajax({
        "url": "../controlador/foro/controlador_carga_lectiva_asignado.php",
         type: 'POST',
        data: {
            doce: id_doce,
            anio: sem
        }
    }).done(function(resp) {
        var data = JSON.parse(resp);

        var cadena = "<option value='1'>Todos</option>";
        if (data.length > 0) {
	        for (var i = 0; i < data.length; i++) {
	            cadena += "<option class='cargas_lec' value='" + data[i][0] + "'>" + data[i][1] + "</option>";
	        } 

	        $('#id_carga').html(cadena);////lamndo en vista matricula 
        } else {
            cadena += "<option value=''>NO SE ENCONTRARON REGISTROS</option>";
            $("#id_carga").html(cadena);
        }
    });
}

function PublicarForo() {
	var titulo = $('#asunto').val();
	var mensaje = $('#mensaje').val();

	if (titulo.length <= 0 || mensaje.length <= 0) {
    	Swal.fire("Mensaje De Advertencia", "Complete los todos los campos.", "warning");
    	return false;
	}

 	var cargas = document.getElementsByClassName('cargas_lec');
 	var car = document.getElementById('id_carga').value;
 
 	var array_carga = new Array();

 	if (car == '1'){
 		for (var i = 0;i<cargas.length;i++) {
 			array_carga.push(cargas[i].value);
 		}
 	}else {
 		array_carga.push(car);
 	}

    var carga = array_carga.toString();

	$.ajax({
        "url": "../controlador/foro/controlador_publicar_foro.php",
         type: 'POST',
        data: {
            doce: id_doce,
            titu: titulo,
            des: mensaje,
            car: carga,
            anio: sem
        }	
    }).done(function(resp) { 
    	if (resp == 1){
    		Swal.fire("Mensaje De Confirmacion", "Publicado.", "success");

    		$('#asunto').val("");
			$('#mensaje').val("");

			document.getElementById("nuevoForoBtn").classList.remove("active");
            document.getElementById("verTodosBtn").classList.add("active");
            cargar_foro('contenido_foro','foro/vista_foro_all.php');
    	}else {
    		Swal.fire("Mensaje De Advertencia", "No se pudo completar.", "error");
    	}
    });
}

var pagina = 5;
function Paginacion() {
    pagina += 3;
    ListarForosAll();
}
function ListarForosAll() { 
    var filtro = $('#search').val(); 

	$.ajax({
        "url": "../controlador/foro/controlador_ver_foros.php",
         type: 'POST',
        data: {
            doce: id_doce,
            anio: sem,
            fil: filtro,
            pag: pagina
        }
    }).done(function(resp) {
    	var cadena = "";

    	if (resp.length >= 3) {
    		var data = JSON.parse(resp); 
            
            var len_foros = data.length;
            if (len_foros+1 <= pagina){
                var sinmas = "Fin de contenido";
                pagina = len_foros+1;
            }else {
                var sinmas = "Cargar mas...";
            }
            $('#btnpag').html(sinmas);
            var likes = 0;
    		for (var i = 0;i<data.length;i++) { 
                var mostrar_dia; 
                var color = "12";
                var nombre = data[i][5];
                //console.log(nombre);
                var ini = nombre.charAt(0);

                if (data[i][2] >= 1 && data[i][2] < 7){
                    mostrar_dia = data[i][2] + "dia";
                }else if (data[i][2] >= 7) {
                    let actividad = data[i][2] / 7;
                    let entero = parseInt(actividad);
                    let decimal = Math.floor(( parseFloat(actividad) - entero) * 10);

                    mostrar_dia = entero + "sem";
                } else {
                    mostrar_dia = "<span class=\"tt-color19 tt-badge\">New</span>";
                }

                if (data[i][4] != 'Todos') {
                    color = "08";
                }else {
                    color = "12";
                }
          var nres = data[i][3];
          if ( parseInt(nres) < 1){
            var delfor = `<button type="button" class="btn btn-danger" onclick="DeleteForo(${data[i][6]})"><i class="fa fa-trash" aria-hidden="true"></i></button>`;
          }else {
            var delfor = `<span class="tt-color16 tt-badge">No permitido</span>`;
          }
    			cadena += `
                    <div class="tt-item">
                <div class="box-avatar">
                    <div class="icon-avatar">
                        <div class="nom-avatar">
                            ${ini}
                        </div>
                    </div>
                </div>
                <div class="tt-col-description">
                    <span id="titu${i}" hidden>${data[i][0]}</span>
                    <span id="mesa${i}" hidden>${data[i][1]}</span>
                    <h6 class="tt-title">
                        <a href="#" onclick="cargar_foro_one('contenido_foro','foro/vista_respuesta_foro.php', 
                        '${data[i][6]}', '${i}', '${data[i][3]}', '${data[i][7]}', '${nombre}', '${ini}');">
                        ${data[i][0]}
                        </a>
                    </h6>
                </div>
            
                <div class="tt-col-value tt-color-select  hide-mobile"> ${data[i][3]}</div>
                <div class="tt-col-value  hide-mobile">${mostrar_dia}</div>
                <div class="tt-col-value hide-mobile">
                    <button type="button" class="btn btn-primary" onclick="cargar_foro_one('contenido_foro','foro/vista_respuesta_foro.php', 
                        '${data[i][6]}', '${i}', '${data[i][3]}', '${data[i][7]}', '${nombre}', '${ini}');">
                        <i class="fa fa-eye" aria-hidden="true"></i>
                    </button>
                    ${delfor}
                </div>
            </div>
                `;
    		}
	       
	        $("#contenidoForoAll").html(cadena);

    	}else {

    		cadena += "<h5>NO SE ENCONTRARON REGISTROS</h5>";
	        $("#contenidoForoAll").html(cadena);
    	}
        
    });
}
function ListarForosAlumno() { 
    var filtro = $('#search').val(); 

    $.ajax({
        "url": "../controlador/foro/controlador_ver_foros_alumno.php",
         type: 'POST',
        data: {
            doce: id_doce,
            anio: sem,
            fil: filtro,
            pag: pagina
        }
    }).done(function(resp) {
        var cadena = "";
       
        if (resp.length >= 3) {
            var data = JSON.parse(resp); 
            
            var len_foros = data.length;
            if (len_foros+1 <= pagina){
                var sinmas = "Fin de contenido";
                pagina = len_foros+1;
            }else {
                var sinmas = "Cargar mas...";
            }
            $('#btnpag').html(sinmas);
            var likes = 0;
            for (var i = 0;i<data.length;i++) { 
                var mostrar_dia; 
                var color = "12";
                var nombre = data[i][4];
                //console.log(nombre);
                var ini = nombre.charAt(0);

                if (data[i][2] >= 1 && data[i][2] < 7){
                    mostrar_dia = data[i][2] + "dia";
                }else if (data[i][2] >= 7) {
                    let actividad = data[i][2] / 7;
                    let entero = parseInt(actividad);
                    let decimal = Math.floor(( parseFloat(actividad) - entero) * 10);

                    mostrar_dia = entero + "sem";
                } else {
                    mostrar_dia = "<span class=\"tt-color19 tt-badge\">New</span>";
                }

                if (data[i][4] != 'Todos') {
                    color = "08";
                }else {
                    color = "12";
                }

                cadena += `
                    <div class="tt-item">
                <div class="box-avatar">
                    <div class="icon-avatar">
                        <div class="nom-avatar">
                            ${ini}
                        </div>
                    </div>
                </div>
                <div class="tt-col-description">
                    <span id="titu${i}" hidden>${data[i][0]}</span>
                    <span id="mesa${i}" hidden>${data[i][1]}</span>
                    <h6 class="tt-title">
                        <a href="#" onclick="cargar_foro_one('contenido_foro','foro/vista_respuesta_foro.php', 
                        '${data[i][5]}', '${i}', '${data[i][3]}', '${data[i][6]}', '${nombre}', '${ini}');">
                        ${data[i][0]}
                        </a>
                    </h6>
                </div>
                

                <div class="tt-col-value tt-color-select  hide-mobile"> ${data[i][3]}</div>
                <div class="tt-col-value  hide-mobile">${mostrar_dia}</div>
                
            </div>
                `;
            }
           
            $("#contenidoForoAlumno").html(cadena);

        }else {

            cadena += "<h5>NO SE ENCONTRARON REGISTROS</h5>";
            $("#contenidoForoAlumno").html(cadena);
        }
        
    });
}
function DeleteForo(id_foro) {
	Swal.fire({
          title: "¿Está seguro de continuar?",
          text: " ",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Sí, eliminar",
          cancelButtonText: "No, cancelar",
        }).then((confirmarDelete) => {
          if (confirmarDelete.value) {
             $.ajax({
		        "url": "../controlador/foro/controlador_eliminar_foro.php",
		         type: 'POST',
		        data: {
		            foro: id_foro
		        }   
		    }).done(function(resp) {
		        if (resp == 1){
		            Swal.fire("Mensaje De Confirmacion", "Eliminado.", "success");

		            ListarForosAll();
		        }else {
		            Swal.fire("Mensaje De Advertencia", "Este foro ya tiene respuestas.", "info");
		        }
		    });
          } else {
             return null;
          }
    });
}

function CargarForo() {
    $('#respuesta').val("");
    var encabezado = `
        <div class="tt-item-header snipcss0-4-4-5">
          <div class="tt-item-info info-top snipcss0-5-5-6">
            
            <div class="tt-avatar-icon snipcss0-6-6-7">
             
                
                  <div class="box-avatar">
                    <div class="icon-avatar">
                        <div class="nom-avatar">
                            ${inicial}
                        </div>
                    </div>
                </div>
                
             
            </div>

            <div class="tt-avatar-title snipcss0-6-6-11">
              <a href="#" class="snipcss0-7-11-12">
                ${nom}
              </a>
            </div>
            <a href="#" class="tt-info-time snipcss0-6-6-13">
              <i class="tt-icon snipcss0-7-13-14">
                <svg class="snipcss0-8-14-15">
                  <use xlink:href="#icon-time" class="snipcss0-9-15-16">
                  </use>
                </svg>
              </i>
              ${fecha}
            </a>
          </div>
          <h3 class="tt-item-title snipcss0-5-5-17">
            <a href="#" class="snipcss0-6-17-18">
              ${tit}
            </a>
          </h3>
        </div>
        <div class="tt-item-description snipcss0-4-4-30">
          <p class="snipcss0-5-30-35">
            ${msj}
          </p>
        </div>
    `;

    $('#veroneforo').html(encabezado);

    $.ajax({
        "url": "../controlador/foro/controlador_listar_respuestas.php",
         type: 'POST',
        data: {
            for: foro_carga
        }
    }).done(function(resp) {
        
        if (resp.length >= 0) {
            var data = JSON.parse(resp); 
            
            if (data.length > 0){

            var respsss = "";
            for (var i=0;i<data.length;i++) {
                respsss += `
<div style="width: 100%; height: 2px; background-color: #e2e7ea; margin-top: 10px; margin-bottom: 10px;"></div>

                <div class="tt-single-topic snipcss0-3-161-162">
                    <div class="tt-item-header pt-noborder snipcss0-4-162-163">
                      <div class="tt-item-info info-top snipcss0-5-163-164">
                        
                        <div class="tt-avatar-title snipcss0-6-164-169">
                          <a href="#" class="snipcss0-7-169-170">
                            <span class=\"tt-color03 tt-badge\">${data[i][2]}</span>
                          </a>
                        </div>
                        <a href="#" class="tt-info-time snipcss0-6-164-171">
                          <i class="tt-icon snipcss0-7-171-172">
                            <svg class="snipcss0-8-172-173">
                              <use xlink:href="#icon-time" class="snipcss0-9-173-174">
                              </use>
                            </svg>
                          </i>
                          ${data[i][3]}
                        </a>
                      </div>
                    </div>
                    <div class="tt-item-description snipcss0-4-162-175">
                      ${data[i][1]}
                    </div>
                  </div>
                </div>
                </div>

                `;
      
            }
            respsss += `<div style="width: 100%; height: 2px; background-color: #e2e7ea; margin-top: 10px; margin-bottom: 10px;"></div>`;
            $("#respuestasfor").html(respsss);
            }else {
                respsss = "<span class=\"tt-color03 tt-badge\">Sin respuestas</span>";
                $("#respuestasfor").html(respsss);
            }
            
        }
    });
}

function PublicarRespuesta() {

    var mensaje = $('#respuesta').val();

    if (mensaje.length <= 0) {
        Swal.fire("Mensaje De Advertencia", "Complete los todos los campos.", "warning");
        return false;
    }

    $.ajax({
        "url": "../controlador/foro/controlador_publicar_respuesta.php",
         type: 'POST',
        data: {
            for: foro_carga,
            res: mensaje,
            usu: id_doce
        }
    }).done(function(resp) {
        if (resp == 1){
            Swal.fire("Mensaje De Confirmacion", "Publicado.", "success");
            CargarForo();
            $('#respuesta').val("");
        }else {
            Swal.fire("Mensaje De Advertencia", "No se pudo completar.", "error");
        }
    });
}