<?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  session_start();
/* ========= Aviso encuesta (solo ciertos roles) ========= */
$mostrarAvisoEncuesta = false;

$correo_login = $_SESSION['email'] ?? null;
$rol_sesion   = $_SESSION['S_ROL'] ?? '';
$id_semestre_objetivo = (int)($_SESSION['S_SEMESTRE'] ?? 0);
/* Normalizador simple de rol (opcional pero recomendable) */
/* Normalizador simple de rol */
$rol_normalizado = strtoupper(trim(preg_replace('/\s+/', ' ', $rol_sesion)));

/* Lista blanca de roles que SÍ deben ver la encuesta */
$roles_permitidos = [
  'TUTOR DE AULA',
  'TUTOR DE CURSO',
  'DIRECCION DE ESCUELA',
  'DIRECTOR DE DEPARTAMENTO ACADEMICO',
  'COORDINADOR GENERAL DE TUTORIA',
  'DEPARTAMENTO ESTUDIOS GENERALES',
];

if ($correo_login && $id_semestre_objetivo > 0 && in_array($rol_normalizado, $roles_permitidos, true)) {
    require_once(__DIR__ . '/../modelo/modelo_conexion.php');
    $conexion = new conexion();
    $conexion->conectar();

    if (!empty($conexion->conexion)) {

        //  SOLO valida si ya respondió EN EL SEMESTRE ACTUAL (sesión)
        $sql = "SELECT 1
                FROM tutoria_encuesta_docentes
                WHERE LOWER(email)=LOWER(?)
                  AND id_semestre=?
                LIMIT 1";

        if ($stmt = $conexion->conexion->prepare($sql)) {
            $stmt->bind_param("si", $correo_login, $id_semestre_objetivo);
            $stmt->execute();
            $stmt->store_result();

            // Mostrar aviso solo si NO existe respuesta en el semestre actual
            $mostrarAvisoEncuesta = ($stmt->num_rows === 0);

            $stmt->free_result();
            $stmt->close();
        }
    }
}
/* ========= /Aviso encuesta ========= */

  // Validación: solo permitir acceso con sesión válida
  if (!isset($_SESSION['S_IDUSUARIO']) || !isset($_SESSION['S_USER']) || !isset($_SESSION['S_ROL'])) {
      header('Location: ../Login/index.php');
      exit;
  }
 // Redirigir automáticamente solo si es TUTOR DE AULA y no se ha especificado página
  if ($_SESSION['S_ROL'] === 'TUTOR DE AULA' && !isset($_GET['pagina'])) {
      header("Location: index.php?pagina=tutor_aula/salon_asig_tutoria.php");
      exit();
  }
  if ($_SESSION['S_ROL'] === 'TUTOR DE CURSO' && !isset($_GET['pagina'])) {
      header("Location: index.php?pagina=docente/asig_plan_tutoria.php");
      exit();
  }
  if ($_SESSION['S_ROL'] === 'DIRECTOR DE DEPARTAMENTO ACADEMICO' && !isset($_GET['pagina'])) {
      header("Location: index.php?pagina=coordinador/vista_tutorados.php");
      exit();
  }
  if ($_SESSION['S_ROL'] === 'COMITÉ - SUPERVISIÓN' && !isset($_GET['pagina'])) {
      header("Location: index.php?pagina=direccion_escuela/direccion_porcentaje_estudiantes.php");
      exit();
  }
  // Redirigir automáticamente solo si es DIRECTOR DE ESCUELA y no se ha especificado página
  if ($_SESSION['S_ROL'] === 'DIRECCION DE ESCUELA' && !isset($_GET['pagina'])) {
      header("Location: index.php?pagina=direccion_escuela/direccion_porcentaje_estudiantes.php");
      exit();
  }
   // Redirigir automáticamente solo si es SUERVISOR y no se ha especificado página
  if (
      (isset($_SESSION['S_ROL']) && (
          $_SESSION['S_ROL'] === 'SUPERVISION' || 
          $_SESSION['S_ROL'] === 'VICEPRESIDENCIA ACADEMICA' || 
          $_SESSION['S_ROL'] === 'COORDINADOR GENERAL DE TUTORIA'
      )) && 
      !isset($_GET['pagina'])
  ) {
      header("Location: index.php?pagina=reportes/admin_porcentaje_tutores.php");
      exit();
  }
  //ESTUFIOS GENERALES
  if ($_SESSION['S_ROL'] === 'DEPARTAMENTO ESTUDIOS GENERALES' && !isset($_GET['pagina'])) {
      header("Location: index.php?pagina=estudios_generales/eg_porcentaje_tutores.php");
      exit();
  }
  // Mostrar mensaje según origen del usuario
  $mensaje = "";
  if ($_SESSION['S_ORIGEN'] === 'docente') {
      $mensaje = "Bienvenido";
  } else {
      $mensaje = "Acceso como usuario del sistema con rol: " . $_SESSION['S_ROL'];
  }
?>
 
<!DOCTYPE html>
<html>
<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Sistema | Tutoría UNDC</title>
  <!-- Tell the browser to be responsive to screen width -->


  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
   
  <!-- Bootstrap 3.3.7 -->

 <link rel="stylesheet" href="../Plantilla/bower_components/bootstrap/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../Plantilla/bower_components/font-awesome/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="../Plantilla/bower_components/Ionicons/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../Plantilla/dist/css/AdminLTE.min.css">
   <!-- color de la navegacion navV navH -->
  <link rel="stylesheet" href="../Plantilla/dist/css/skins/_all-skins.min.css">

  <!-- Morris chart -->
  
  <!-- Daterange picker -->
  
  <!-- bootstrap wysihtml5 - text editor -->

  <link rel="stylesheet" href="../Plantilla/plugins/DataTables/datatables.min.css">

<!--booton imprimir-->

  <link rel="stylesheet" href="../Plantilla/plugins/select2/select2.min.css">
           
<link rel="shortcut icon" type="image/png" href="../img/favicon.png"/>

<script type="text/javascript" src="../js/index.js?rev=<?php echo time();?>"></script>
  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic"> 
  <link rel="stylesheet" type="text/css" href="../Plantilla/dist/css/view_asistencia.css"> 

  <style>
  .swal2-popup { font-size: 1.3rem !important; }
  
  .loader { margin-top: 20px; text-align: center; }

  html, body {
    height: 100%;
    background-color: #f4f6f9; /* color de fondo de AdminLTE */
  }
  .wrapper { min-height: 100%; }  /* SIN FLEX */
  .content-wrapper { background: #fff; }
  .banner-encuesta{
  background:#0f6efd11;
  border:2px dashed #0f6efd;
  border-radius:10px;
  padding:14px 16px;
  margin:0 0 18px 0;
}
.banner-encuesta h3{
  margin:0 0 6px 0;
  font-size:1.05rem;
  color:#0f6efd;
}
.banner-encuesta p{ margin:0 0 10px 0; color:#333; }
.btn-encuesta{
  display:inline-block;
  padding:8px 14px;
  background:#0f6efd;
  color:#fff;
  text-decoration:none;
  border-radius:6px;
  font-weight:bold;
}
.btn-encuesta:hover{ background:#0b5ed7; }
/* Centrado vertical simple para Bootstrap 3 */
#modalEncuesta .modal-dialog {
  margin-top: 10vh;   /* baja un poco desde arriba */
  margin-bottom: 10vh;
}
/* Asegura opacidad del backdrop (ya lo hace BS3, pero por si acaso) */
.modal-backdrop.in { opacity: 0.6; }
  </style>
</head>
<?php if (!empty($mostrarAvisoEncuesta) && $mostrarAvisoEncuesta): ?>
    <script>
    // Ejecutar cuando todo esté cargado y Bootstrap ya disponible
    $(function(){
      $('#modalEncuesta').modal({
        backdrop: 'static', // no permite cerrar haciendo clic fuera
        keyboard: false     // no permite cerrar con ESC
      }).modal('show');
    });
    </script>
<?php endif; ?>
<body class="hold-transition skin-blue sidebar-mini" id="body-general">

<div class="wrapper">

<?php 
    include ('menu/navV.php');
    include ('menu/navH.php');

     ?>


<!-- =====================ENCUESTA ========================== -->
<!--  <div class="modal fade" id="modalEncuesta" tabindex="-1" role="dialog" aria-hidden="true"> 
  <div class="modal-dialog" role="document" style="max-width:600px;">
    <div class="modal-content" style="border-radius:12px; box-shadow:0 5px 18px rgba(0,0,0,0.3); border-top:5px solid #0f6efd;">
      
      
      <div class="modal-header" style="background:#0f6efd;color:white; border-top-left-radius:12px; border-top-right-radius:12px;">
        <h4 class="modal-title" style="margin:0; font-size:20px; font-weight:bold; text-align:center;">
          SEGUNDA ENCUESTA DE SATISFACCIÓN DE LOS DOCENTES SOBRE EL USO DEL SISTEMA DE TUTORÍA Y CONSEJERÍA UNIVERSITARIA (SISTECU)
        </h4>
      </div>
      
      
      <div class="modal-body" style="font-size:15px; line-height:1.6; color:#333; text-align:justify;">
        <p style="margin-bottom:12px;">
          Estimado docente, su opinión es muy importante para nosotros.  
          Le invitamos a completar una <b>breve encuesta</b> sobre su experiencia en el uso del sistema de Tutoría y Consejería Universitaria (SISTECU).
        </p>
        <p style="margin-bottom:10px;">
          <strong>Nota:</strong>
        </p>
        <ul style="margin-left:18px; margin-bottom:10px;">
          <li>
            Sus respuestas serán utilizadas exclusivamente para fines de mejora continua
            de la calidad del servicio brindado a través del sistema.
          </li>
          <li>
            Una vez enviada la encuesta, por favor <strong>actualice la página</strong>;
            este aviso no volverá a mostrarse.
          </li>
        </ul>

        <hr style="margin:15px 0;">
        <p style="margin:0; font-size:13px; color:#666; font-style:italic; text-align:center;">
          Agradecemos de antemano su participación y compromiso con la mejora académica.
        </p>
      </div>
      
      
      <div class="modal-footer" style="text-align:center; border-top:none;">
        <a href="https://forms.gle/5j9ZFViUNDRLVNWn9"
           target="_blank"
           class="btn btn-primary"
           style="background:#0f6efd; border:none; padding:10px 20px; border-radius:6px; font-weight:bold;">
           Ir a la encuesta
        </a>
      </div>

    </div>
  </div>
</div>  -->
<!-- =====================/ENCUESTA========================== -->
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
  

    <!-- Content Header (Page header) -->
    <!-- Main content -->
  <section class="content">

    <div class="row" id="contenido_principal">
    <div class="col-md-12">

          <input type="text" id="UserNom" value="<?php echo $_SESSION['S_USER'] ?>" hidden>
          <input type="text" id="Userrol" value="<?php echo $_SESSION['S_ROL'] ?>" hidden >
          <input type="text" id="Uschool" value="<?php echo $_SESSION['S_SCHOOL'] ?>" hidden >
          <input type="text" id="Unameschool" value="<?php echo $_SESSION['S_SCHOOLNAME'] ?>" hidden>

          <?php
     
            /* $pagina = $_GET['pagina'] ?? '';
            if (preg_match('/^[\w\-\/]+\.php$/', urldecode($pagina)) && file_exists(urldecode($pagina))) {
                include(urldecode($pagina));
            } */ 
        
            $pagina = $_GET['pagina'] ?? '';

            // Permite letras, números, guiones, guiones bajos, barras y .php
            if (preg_match('/^[\w\-\/]+\.php$/', $pagina) && file_exists($pagina)) {
                include($pagina);
            }
            ?>
            <!-- mas -->
            <!-- /.box-header -->
            <!-- /.box-body -->
          <!-- /.box -->
     </div>
        <center><div class="loader" hidden>
               <img src="../Login/vendor/loader.gif" alt="" style="width: 100px;height:100px;">
              </div></center>
    </div>
  </div>
<!-- /modal del index -->
<!-- /modificar contraceña -->
  <form autocomplete="false" onsubmit="return false"  method="POST" action="#" enctype="multipart/form-data" onsubmit="return false">
    <div class="modal fade" id="modal_Camb_contra" role="dialog">
        <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title"><b><i class="glyphicon glyphicon-user"></i> Mi Perfil</b></h4>
            </div>
            <div class="modal-body">
          <!--CUADRO PARA LA FOTO-->
          <div class="box box-widget widget-user-2">
            <div class="widget-user-header" >
              <div class="widget-user-image">
                <img  class="img-circle" alt="User Image" id="mostrarimagen">
                
              </div>
              <h3 class="widget-user-username"><b><?php echo $_SESSION['S_USER']; ?></b> </h3>
              <h5 class="widget-user-desc"><?php echo $_SESSION['S_ROL']; ?></h5>
            </div>
            <div class="box-footer no-padding">
              <ul class="nav nav-stacked">
                <input type="text"  id="fotoActual" hidden>
                <input type="file" class="form-control" id="seleccionararchivo" accept="image/x-png,image/gif,image/jpeg"  style="border-radius: 5px;"><br>

              </ul>
            </div>
          </div>
          <!-- FIN CUADRO-->

                <div class="col-lg-12">

                     <style type="text/css">
                       .col-lg-12 input:focus:invalid{ 
                               box-shadow: 0 0 5px #d45252;
                               border-color: #b03535
                                } 
                                .col-lg-12 input:required:valid {                                
                                   background: #fff;
                                   box-shadow: 0 0 5px #5cd053;
                                   border-color: #28921f;
                               }                          
                     </style>
                    <input type="text" id="textId" value="<?php echo $_SESSION['S_IDUSUARIO'] ?>"hidden >
                    <input type="text" id="anio" value="<?php echo $_SESSION['S_SEMESTRE'] ?>" hidden >   
                            <div id="notif" class="  " role="alert" hidden style="border-radius: 5px;background: #F5890E"><ul>las contraceñas nuevas no coisiden!</ul>
                             </div>

                             <div id="llenecamp"  role="alert" hidden style="border-radius: 5px;background: #2DD2BB"><ul>Llene los campos vacios!</ul> 
                              </div>
                              <div id="noexiste" class="" role="alert" hidden style="border-radius: 5px;background: #F52A0E"><ul>la Contraceña anterior es diferente a lo que estas ingresando!</ul>
                               </div>
                      <input type="text"  id="contra_bd" hidden>
                    
                  </div><br>
                 <div class="col-lg-12">
                    <label for="">Contrase&ntilde;a Actual</label>
                    <input type="password" class="form-control" id="txt_cont_act" placeholder="Ingrese contrase&ntilde;a Actual"><br>
                </div>
                 <div class="col-lg-12">
                    <button class="btn btn-block" style="width:100%; background:#05ccc4" onclick="addcontranew()" id="botonaddcontra"><i class=""></i>¿Deseas cambiar contrase&ntilde;a?</button>
                </div><br>


                 <div id="cambiarcontratambien" hidden>
                <div class="col-lg-12">
                    <label for="">Contrase&ntilde;a Nueva</label>
                    <input type="password" class="form-control" id="txt_cont_nuw" placeholder="Ingrese contrase&ntilde;a Nueva" ><br>
                </div>
                <div class="col-lg-12">
                    <label for="">Repita la Contrase&ntilde;a</label>
                    <input type="password" class="form-control" id="repcontra" placeholder="Repita contrase&ntilde;a Repetida" ><br>
                </div>
                </div>
            </div><br><br><br><br><br>

            <div class="modal-footer">
                <button class="btn btn-primary" onclick="Modificar_Contrasena()"><i class="fa fa-check"><b>&nbsp;Guardar</b></i></button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-close"><b>&nbsp;Cerrar</b></i></button>
            </div>
        </div>
        </div>
    </div>
</form>

  <!-- /.content-wrapper -->

  <footer class="main-footer">
    <div class="pull-right hidden-xs">
        <a href="https://wa.me/51974612338" target="_blank">
          <i class="fab fa-whatsapp" style="color: green"></i>
        <strong>974 612 338 </strong></a> |  Natali Postillón  | 
        <b>Soporte del Sistema de Tutoría</b>
    </div>
    <strong>Universidad Nacional de Cañete <a href="#">UNDC</a>.</strong>
</footer>

<!-- Control Sidebar -->
<div class="control-sidebar-bg"></div>
</div>

<!-- ./wrapper -->

<!-- === EMPIEZAN SCRIPTS === -->

<!-- jQuery (SOLO UNA VEZ) -->
<script src="../Plantilla/bower_components/jquery/dist/jquery.min.js"></script>
<!-- 🔧 Parche para evitar errores por llamadas a controlador/index.php -->
 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(document).ajaxSend(function(event, jqxhr, settings) {
    if (settings.url.includes('controlador/index.php')) {
      console.error('🚨 Alerta: Este archivo está intentando llamar a controlador/index.php:', settings);
      console.trace(); // Esto te dirá qué JS hace la llamada
      jqxhr.abort();   // Cancelar la petición
    }
  });
</script>
<?php if ($mostrarAvisoEncuesta): ?>
<script>
$(document).ready(function(){
  $("#modalEncuesta").modal({
    backdrop: 'static',
    keyboard: false
  });
  $("#modalEncuesta").modal('show');
});
</script>
<?php endif; ?>

<!-- Tu JS -->

<!-- jQuery UI -->
<script src="../Plantilla/bower_components/jquery-ui/jquery-ui.min.js"></script>

<!-- Resolver conflicto jQuery UI vs Bootstrap -->
<script>
  $.widget.bridge('uibutton', $.ui.button);
</script>

<!-- Bootstrap -->
<script src="../Plantilla/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

<!-- AdminLTE -->
<script src="../Plantilla/dist/js/adminlte.min.js"></script>

<!-- AdminLTE demo (puedes quitarlo si no quieres cosas de demo) -->
<script src="../Plantilla/dist/js/demo.js"></script>

<!-- DataTables -->
<script src="../Plantilla/plugins/DataTables/datatables.min.js"></script>

<!-- Exportar en PDF (opcional si usas DataTables export) -->
<script src="../Plantilla/plugins/DataTables/pdfmake-0.1.36/vfs_fonts.js"></script>

<!-- Select2 -->
<script src="../Plantilla/plugins/select2/select2.min.js"></script>

<!-- SweetAlert2 -->
<!-- <script src="../Plantilla/plugins/sweetalert2/sweetalert2.js"></script> -->

<!-- === TUS ARCHIVOS JS PROPIOS AQUÍ ABAJO === -->

<!-- Scripts de funcionalidades internas -->
<script src="../js/index.js?rev=<?php echo time(); ?>"></script>
<script src="../js/docente.js?rev=<?php echo time(); ?>"></script>
<script src="../js/asignacion.js?rev=<?php echo time(); ?>"></script>
<script src="../js/nee.js?rev=<?php echo time(); ?>"></script>
<script src="../js/usuarios_admin.js?rev=<?php echo time(); ?>"></script>

<!-- === SCRIPTS INLINE de funciones dinámicas === -->
<script>
var idioma_espanol = {
    select: { rows: "%d fila seleccionada" },
    "sProcessing": "<span class='fa-stack fa-lg'><i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i></span>&emsp;Procesando....",
    "sLengthMenu": "Mostrar _MENU_ registros",
    "sZeroRecords": "No se encontraron resultados",
    "sEmptyTable": "Ningún dato disponible en esta tabla",
    "sInfo": "Registros del (_START_ al _END_) total de _TOTAL_ registros",
    "sInfoEmpty": "Registros del (0 al 0) total de 0 registros",
    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
    "sSearch": "Buscar:",
    "oPaginate": {
        "sFirst": "Primero", "sLast": "Último", "sNext": "Siguiente", "sPrevious": "Anterior"
    },
    "oAria": {
        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
    }
};

function cargar_contenido(contenedor, contenido) {
    $("#" + contenedor).empty();

    $("#" + contenedor).load(contenido, function(response, status, xhr) {

        redimensionarScroll();

        // ✅ Inicializadores por módulo (cuando se navega y regresa)
        if (contenido.indexOf("reportes/admin_usuarios.php") !== -1) {
            if (typeof initUsuarios === "function") {
                initUsuarios();
            }
        }

    });
}


function redimensionarScroll() {
    $("body").css({"padding-right": "0px"});
}

// ==================== Configuraciones iniciales ====================
$(document).ready(function() {
    redimensionarScroll();

    var id_usu = $("#textId").val();
    var rol_usu = $("#Userrol").val();
    window.s_escuela = $("#Uschool").val();        // se declara como window para global
    window.s_nombre_escuela = $("#Unameschool").val();

    Extraer_contracena(id_usu, rol_usu); // Traer contraseña y foto

    $("#modal_Camb_contra").on('shown.bs.modal', function() {
        $("#txt_cont_act").focus();
    });
    $("#modal_semestre").on('shown.bs.modal', function() {
        $("#cbm_semestre").focus();
    });

    // Previsualizar imagen de perfil
    document.getElementById("seleccionararchivo").addEventListener("change", () => {
        $("#idfoto").show();
        var archivoseleccionado = document.querySelector("#seleccionararchivo");
        var archivos = archivoseleccionado.files;
        var imagenPrevisualizacion = document.querySelector("#mostrarimagen");

        if (!archivos || !archivos.length) {
            imagenPrevisualizacion.src = "";
            return;
        }

        var primerArchivo = archivos[0];
        var objectURL = URL.createObjectURL(primerArchivo);
        imagenPrevisualizacion.src = objectURL;
    });
});



</script>

</body>
</html>
