function AbrirModalCambCont() {
    var id_usu=$("#textId").val();
    var rol_usu =$("#Userrol").val();
      Extraer_contracena(id_usu,rol_usu);
    $("#modal_Camb_contra").modal({
       
        backdrop: 'static',
        keyboard: false
    })
    $(".modal-header").css("background-color", "#05ccc4");
    $(".modal-header").css("color", "white");
    $("#modal_Camb_contra").modal('show');
   }

function Extraer_contracena(id_usu,rol_usu){//FUNCION TRAER CONTRAXE�A Y FOTO
     $('.loader').show();////prende

    $.ajax({
        url:'../controlador/usuario/controlador_extraer_contracena.php',
        type:'POST',
        data:{
            id_usu:id_usu,
            rol_usu: rol_usu
        }
    }).done(function(resp) {
        $('.loader').hide();
        var data = JSON.parse(resp);
    var contracena = data[0]?.clave || '';
    var fot = data[0]['foto'] || '';

    $("#fotoActual").val(fot);
          //cada perfil puede tener otra ruta de foto para mayor personalizacion
          //segun el rol puedo, por ahora esta trabajando en la misma carpeta todos.
          if(rol_usu=='COORDINADOR GENERAL DE TUTORIA'){
             $("#fotouserhorz").attr("src","../imagenes/admin.png");//NAV HORIZONTAL
            $("#veticalfotouser").attr("src","../imagenes/admin.png");//NAV VERTICALL
            $("#mostrarimagen").attr("src","../imagenes/admin.png");//MODAL EDITAT FOTO
            $("#contra_bd").val(contracena);//MODAL EDITAR PASSWORD
           }

           if(rol_usu=='DIRECCION DE ESCUELA'){
             $("#fotouserhorz").attr("src","../imagenes/escu.png");//NAV HORIZONTAL
            $("#veticalfotouser").attr("src","../imagenes/escu.png");//NAV VERTICALL
            $("#mostrarimagen").attr("src","../imagenes/escu.png");//MODAL EDITAT FOTO
            $("#contra_bd").val(contracena);//MODAL EDITAR PASSWORD
           }

           if(rol_usu=='SUPERVISION'){
             $("#fotouserhorz").attr("src","../imagenes/esu.png");//NAV HORIZONTAL
            $("#veticalfotouser").attr("src","../imagenes/esu.png");//NAV VERTICALL
            $("#mostrarimagen").attr("src","../imagenes/esu.png");//MODAL EDITAT FOTO
            $("#contra_bd").val(contracena);//MODAL EDITAR PASSWORD
           } 

          if(rol_usu=='DIRECTOR DE DEPARTAMENTO'){
             $("#fotouserhorz").attr("src","../imagenes/dep.png");//NAV HORIZONTAL
            $("#veticalfotouser").attr("src","../imagenes/dep.png");//NAV VERTICALL
            $("#mostrarimagen").attr("src","../imagenes/dep.png");//MODAL EDITAT FOTO
            $("#contra_bd").val(contracena);//MODAL EDITAR PASSWORD
           }

           if(rol_usu=='TUTOR DE CURSO' || rol_usu=='APOYO'){
            $("#fotouserhorz").attr("src","../imagenes/docente.png");//NAV HORIZONTAL
            $("#veticalfotouser").attr("src","../imagenes/docente.png");//NAV VERTICALL
            $("#mostrarimagen").attr("src","../imagenes/docente.png");//MODAL EDITAT FOTO
            $("#contra_bd").val(contracena);//MODAL EDITAR PASSWORD
           }

           if(rol_usu=='VICEPRESIDENCIA ACADEMICA'){
            $("#fotouserhorz").attr("src","../imagenes/aula.png");//NAV HORIZONTAL
            $("#veticalfotouser").attr("src","../imagenes/aula.png");//NAV VERTICALL
            $("#mostrarimagen").attr("src","../imagenes/aula.png");//MODAL EDITAT FOTO
            $("#contra_bd").val(contracena);//MODAL EDITAR PASSWORD
           }

           if(rol_usu=='TUTOR DE AULA' || rol_usu=='APOYO'){
            $("#fotouserhorz").attr("src","../imagenes/aula.png");//NAV HORIZONTAL
            $("#veticalfotouser").attr("src","../imagenes/aula.png");//NAV VERTICALL
            $("#mostrarimagen").attr("src","../imagenes/aula.png");//MODAL EDITAT FOTO
            $("#contra_bd").val(contracena);//MODAL EDITAR PASSWORD
           }

           if(rol_usu=='ALUMNO'){
            $("#fotouserhorz").attr("src","../imagenes/alumno.png");//NAV HORIZONTAL
            $("#veticalfotouser").attr("src","../imagenes/alumno.png");//NAV VERTICALL
            $("#mostrarimagen").attr("src","../imagenes/alumno.png");//MODAL EDITAT FOTO
            $("#contra_bd").val(contracena);//MODAL EDITAR PASSWORD
           }          
    })
}
function Modificar_Contrasena(){
    var rol_usu =$("#Userrol").val();
        
     var f = new Date();
     var idusu=$("#textId").val();
     var bdcont=$("#contra_bd").val();
     var contrAct=$("#txt_cont_act").val();
     var contrnew=$("#txt_cont_nuw").val();
     var contrep=$("#repcontra").val();
     var fotoactual=$("#fotoActual").val();
     //alert(fotoactual);

     var archivo = $("#seleccionararchivo").val();
     var formato = archivo.split('.').pop();//formato png
     var nombreArchivo ="IMG"+f.getDate()+""+(f.getMonth()+1)+""+f.getFullYear()+""+f.getHours()+""+f.getMinutes()+""+f.getSeconds()+"."+formato;
    
///hola
//alert(idusu+'---'+bdcont+'---'+contrAct+'---'+contrnew+'--'+contrep);
if (contrAct.length == 0 ) {
      $("#notif").hide();
      $("#noexiste").hide();
     $("#llenecamp").show();
     return;
       // return Swal.fire("Mensaje De Advertencia", "Llene los campos vacios", "warning");
    }

    if (contrnew.length == 0 || contrep.length == 0  ) {
       //$("#notif").hide();
       // $("#llenecamp").show();
       var contrnew=contrAct;
       var contrep=contrAct;
    }

if (contrnew != contrep) {
    $("#llenecamp").hide();
    $("#noexiste").hide();
     $("#notif").show();
     return;
    }
           var formData= new FormData();
          var foto = $("#seleccionararchivo")[0].files[0];
          
            formData.append('f',foto);
            formData.append('idusu',idusu);
            formData.append('bdcont',bdcont);
            formData.append('contrAct',contrAct);
            formData.append('contrnew',contrnew);
            formData.append('r',contrep );
            formData.append('nombreArchivo',nombreArchivo);
            formData.append('fotAct',fotoactual);

           
                $.ajax({
                url:'../controlador/usuario/controlador_modificar_contra.php',
                type:'post',
                data:formData,
                contentType:false,
                processData:false,
                success: function(respuesta){
                     // alert(respuesta);
                       $("#notif").hide();
                          $("#notif").hide(); 
                         if (respuesta > 0) {
                             if (respuesta == 1) {
                                 $("#modal_Camb_contra").modal('hide');
                                 limpiarModalContra();
                                 Swal.fire("Mensaje De Confirmacion", "Datos correctamente,", "success").then((value) => {
                   
                                 });

                        Swal.fire({
                         title: 'DESEAR CERRAR LA SECTION?',
                         text: "Ingrese con la contraceña nueva",
                         icon: 'warning',
                         showCancelButton: true,
                         confirmButtonColor: '#3085d6',
                         cancelButtonColor: '#d33',
                         confirmButtonText: 'Si'
                             }).then((result) => {
                               if (result.value) {
                                 window.open('../controlador/usuario/controlador_cerrar_session.php');
                                 //window.location.reload('../controlador/usuario/controlador_cerrar_session.php');
                              }
                               location.reload();
                           })                 
            } else {
                $("#llenecamp").hide();
                $("#notif").hide();
                  $("#noexiste").show();
                return;// Swal.fire("Mensaje De Advertencia", "la contra cenña no pertecenes al usuario", "warning");
            }
        } else {
            Swal.fire("Mensaje De Error", "Lo sentimos, no se pudo completar el registro", "error");
        }

                }
            });
    return false;  
}

function limpiarModalContra(){
$("#txt_cont_act").val("");
$("#txt_cont_nuw").val("");
$("#repcontra").val("");
$("#seleccionararchivo").attr("");
}

function addcontranew(){
    $("#cambiarcontratambien").show();
     $("#botonaddcontra").hide(); 
} 
 