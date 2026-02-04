<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../modelo/modelo_conexion.php';
require_once __DIR__ . '/../../modelo/modelo_docente.php';

$CN = new conexion();
$CN->conectar();
$MD = new Docente();

$id_doc   = $_SESSION['S_IDUSUARIO'];
$semestre = $_SESSION['S_SEMESTRE'];

// Trae hasta 2 aulas para saber si hay 1 o más
$sql = "
  SELECT DISTINCT cl.id_cargalectiva AS id_carga
  FROM tutoria_asignacion_tutoria ta
  JOIN carga_lectiva cl ON cl.id_cargalectiva = ta.id_carga
  WHERE ta.id_docente = ? AND ta.id_semestre = ?
  LIMIT 2
";
$stmt = $CN->conexion->prepare($sql);
$stmt->bind_param('ii', $id_doc, $semestre);
$stmt->execute();
$res  = $stmt->get_result();

$ids = [];
while ($r = $res->fetch_assoc()) { $ids[] = (int)$r['id_carga']; }
$stmt->close();

if (count($ids) === 1) {
  // solo un aula => ir directo a la lista de estudiantes
  $linkMisTutorados = "tutor_aula/vista_alumnos_asignados.php?id_cargalectiva=".$ids[0];
} else {
  // 0 o 2+ aulas => ir a la vista intermedia de aulas
  $linkMisTutorados = "tutor_aula/aulas.php";
}
?>

<head>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<aside class="main-sidebar" > 
   <style type="text/css">
    #boxmarcatuto {
      padding: 6px 10px;
      margin: 0;
      height: 60px;              /* controla el alto del header */
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: visible;         /* por si hay sombras del logo */
      border-bottom: 1px solid rgba(255,255,255,.08);
    }

    .logo-mini{
      max-height: 100px;          /* 56px header - paddings ≈ 44px de imagen */
      height: 80px;     /* tamaño REAL del logo */
      width: auto;
      display: block;
      margin: 0;
      object-fit: contain;       /* evita cualquier recorte */
      position: static !important;
      z-index: auto !important;
      transform: none !important;
    }

    /* Evita que otro contenedor recorte */
    .main-sidebar .sidebar,
    .main-sidebar{
      overflow: visible;         /* AdminLTE trae auto; aquí lo normalizamos */
    }

    .logo-mini:hover {
      transform: scale(1.05);
      transition: 0.3s ease;
    }

    /*.user-name {
      color: white;
      font-weight: 600;
      font-size: 14px;
      margin-top: 5px;
    }*/

    @media (max-width: 768px) {
      .logo-mini {
        max-width: 100px;
      }

      /*.user-name {
        font-size: 13px;
      }*/
    }
    .sidebar-collapse #boxmarcatuto {
      display: none;
    }
  </style>
    <div id="boxmarcatuto">
    <a href="../index.php">
     <img src="../Login/images/logo_sistecu2.png" alt="Logo SISEDE" class="logo-mini">
       <!-- <img src="../Login/images/logo_navidad2.png" alt="Logo SISEDE" class="logo-mini"> -->
    </a>
    </div>
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar" >
      <!-- Sidebar user panel -->
       <hr>
      <div class="user-panel" style="height: 3.7em; margin-top:-20px;">
        <div class="pull-left info"> 
        <p><?php echo $_SESSION['S_USER']; ?></p>
       <!-- <p>/* echo $_SESSION['S_ROL'];*/</p> -- Apertura y cierre del php-->
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div>
      <hr>
      <!-- search form -->
      <!-- <form action="#" method="get" class="sidebar-form" >
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Search...">
          <span class="input-group-btn">
                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
        </div>
      </form> -->
      <!-- /.search form -->
      <!-- sidebar menu: : style can be found in sidebar.less -->
      <ul class="sidebar-menu" data-widget="tree">
       <!-- <li class="header">MAIN NAVIGATION</li>-->
       <?php 
  		
        if ($_SESSION['S_ROL'] =='COORDINADOR GENERAL DE TUTORIA') {
          ?>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_usuarios.php')">
            <i class="fa fa-user-cog"></i> <span style="cursor: pointer;">Usuarios</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_informe_resultado_mensual.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Informe de Resultados</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
         <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_porcentaje_estudiantes.php')">
            <i class="glyphicon glyphicon-stats"></i> <span style="cursor: pointer;">Participación Estudiantes</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_porcentaje_tutores.php')">
            <i class="glyphicon glyphicon-th-list"></i> <span style="cursor: pointer;">Participación Tutores</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_tendencia_cumplimiento.php')">
            <i class="fas fa-chart-line"></i> <span style="cursor: pointer;">Tendencia Cumplimiento</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/derivaciones_atenciones.php')">
            <i class="fa fa-file-pdf-o"></i> <span style="cursor: pointer;">Reporte de Derivaciones</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_nee.php')">
            <i class="fas fa-user-friends"> </i> <span style="cursor: pointer;">  Estudiantes NEE</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/reporte_estadistico_encuesta.php')">
            <i class="fa fa-bar-chart"></i> <span style="cursor: pointer;">Reporte Indicadores</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
          <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_planes_tutoria.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte de Planes</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_planes_tutor_aula.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Planes de tutoría</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_informe_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte Informes|Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_informe_aula.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Informe mensual|Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_informe_final_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte Informe Final</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_informe_final_aula.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Informe Final|Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <!-- <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_informe_mensual_curso.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte Informes|Cursos</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> -->
        <!-- <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_informe_mensual_cursos.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Informe mensual|Cursos</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> -->
        <li class="treeview">
        <a href="https://www.google.com" onclick="window.open('https://drive.google.com/file/d/1vFxhcN9OOTJb0SWriZgYIA7H6NssZB63/view', '_blank');">
          <i class="	fa fa-file-movie-o"></i> <span style="cursor: pointer;">Manual de Usuario</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
      </li>
<!-- 		    <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/vista_reportes_alumnos_escu_pro.php')">
            <i class="glyphicon glyphicon-user"></i> <span style="cursor: pointer;">Estudiantes</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> 

        <li class="treeview">
          <a onclick="cargar_contenido('reportes/vista_reportes_alumnos_escu_pro.php')">
            <i class="glyphicon glyphicon-user"></i> <span style="cursor: pointer;">Tutores</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> --> 

        <!-- <li class="treeview">
           <a onclick="cargar_contenido('contenido_principal','reportes/vista_reportes_alumnos_escu_pro.php')">
            <i class="glyphicon glyphicon-user"></i> <span style="cursor: pointer;">Alumnos</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> -->   
		  
		  
	    <!--  <li class=" treeview">
          <a > ----- > FALTA ARCHIVO
            <i class="glyphicon glyphicon-equalizer"></i> <span style="cursor: pointer;">Oficina de Apoyo</span>
            <span class="pull-right-container">
              <i class="fa fa-labtop"></i>
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
       </li> -->
		
		
	   <?php
          }
        ?>
		  

		<?php 
     if ($_SESSION['S_ROL'] =='APOYO') {
    ?>
      
        <script>
            // Simular un clic en el elemento <li> con el id "cargar" cuando la página se carga
            document.addEventListener("DOMContentLoaded", function() {
              const liCargar = document.getElementById("carga3");
              if (liCargar) {
                const link = liCargar.querySelector("a");
                link.click();
              }
            });
        </script>
  
		<li id="carga3" class=" treeview">
          <a onclick="cargar_contenido('contenido_principal','apoyo/vista_tutorados_referidos.php')">
            <i class="fa  fa-users"></i> <span style="cursor: pointer;">Tutorados referido</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> 


        
     <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','apoyo/vista_historial_referidos.php')">
            <i class=" glyphicon glyphicon-list-alt"></i> <span style="cursor: pointer;">Historial de atención</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
        </li>
 
		  
		 <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','apoyo/vista_generar_informe.php')">
            <i class=" glyphicon glyphicon-duplicate"></i> <span style="cursor: pointer;">Generar Informe</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
        </li>
      <li class="treeview">
        <a href="https://www.google.com" onclick="window.open('https://drive.google.com/file/d/10EAyZhjAD0bisX0mdPgGDZzCki7wTmtT/view', '_blank');">
          <i class="	fa fa-file-movie-o"></i> <span style="cursor: pointer;">Manual de Usuario</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
      </li>
		  
     
      <?php
          }
         ?>

        <?php 

        if ($_SESSION['S_ROL'] =='TUTOR DE CURSO') {
        ?>

            <script>
            // Simular un clic en el elemento <li> con el id "cargar" cuando la página se carga
            document.addEventListener("DOMContentLoaded", function() {
              const liCargar = document.getElementById("cargar");
              if (liCargar) {
                const link = liCargar.querySelector("a");
                link.click();
              }
            });

            // Simular un clic en el elemento <li> con el id "cargar" cuando la página se carga
            function cargarHorario1() {
                document.addEventListener("DOMContentLoaded", function() {
                  const liCargar = document.getElementById("horario");
                  if (liCargar) {
                    const link = liCargar.querySelector("a");
                    link.click();
                  }
                });
              }

            function mostrarPopup(id_estu) {
                    var url = 'docente/boleta_notas.php?id_est=' + id_estu;

                    // Tamaño fijo de la ventana emergente
                    var anchoVentana = 500;
                    var altoVentana = 500;

                    // Calcular la posición centrada en la pantalla
                    var izquierda = (screen.width - anchoVentana) / 2;
                    var arriba = (screen.height - altoVentana) / 2;

                    var ventanaPopup = window.open(url, '_blank', 'width=' + anchoVentana + ',height=' + altoVentana + ',left=' + izquierda + ',top=' + arriba);
                    ventanaPopup.focus();
                }
          </script>
        <li id="horario" class="treeview">
          <a id="horaaa" onclick="cargar_contenido('contenido_principal', 'docente/asig_plan_tutoria.php')" >
          <i class="fas fa-file-pdf "></i> <span style="cursor: pointer;">Informe de Tutoría</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
          </a>
        </li>
        <li id="horario" class="treeview">
          <a id="horaaa" onclick="cargar_contenido('contenido_principal', 'docente/asignaturas_tutoradas.php')" >
          <i class="fa fa-users"></i> <span style="cursor: pointer;">Mis Tutorados</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
          </a>
        </li>

        <li id="horario" class="treeview">
          <a id="horaaa" onclick="cargar_contenido('contenido_principal','fullcalendar')" >
          <i class=" fa  fa-calendar"></i> <span style="cursor: pointer;">Gestionar Horario</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
          </a>
        </li>
         

        <li class="treeview">
          <a  onclick="cargar_contenido('contenido_principal','docente/vista_historial_sesiones.php')">
            <i class="glyphicon glyphicon-calendar"></i> <span style="cursor: pointer;">Historial de sesiones</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>

         <li class="treeview">
          <a  onclick="cargar_contenido('contenido_principal','docente/vista_historial_derivaciones.php')">
            <i class="fa fa-calendar-o" aria-hidden="true"></i> <span style="cursor: pointer;">Historial de derivaciones</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
        </li>

        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','folder/listar_Folder.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Material de Apoyo</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
        </li>

         <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','foro/vista_foro.php')">
            <i class="fa fa-comments"></i> <span style="cursor: pointer;">Foro</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
        </li>

      <li class="treeview">
        <a onclick="cargar_contenido('contenido_principal','../vista/tutoriales_docentes.php')">
          <i class="	fa fa-file-movie-o"></i> <span style="cursor: pointer;">Manual - Tutoriales</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
      </li>
      <?php }

       ?>

       <!-- ===========================================    REGULARIZACION    ============================================================ -->
      <!-- ?php if (($_SESSION['S_ROL'] ?? '') === 'TUTOR DE AULA' && (int)($_SESSION['S_IDUSUARIO'] ?? 0) === 400): ?>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','tutor_aula/informe_final_excepcion.php')" style="cursor:pointer;">
            <i class="fa fa-exclamation-circle"></i>
            <span>Regularización (2025-I)</span>
          </a>
        </li>
      ?php endif; ?> -->
      <!-- ================================================================================================================================ -->
      <?php
      if ($_SESSION['S_ROL'] == 'TUTOR DE AULA') {
        ?>
          <script>
          document.addEventListener("DOMContentLoaded", function() {
            const liCargar = document.getElementById("cargar_tutoraula");
            if (liCargar) {
              const link = liCargar.querySelector("a");
              link.click();
            }
          });
          </script>
          <script>
            // Simular un clic en el elemento <li> con el id "cargar" cuando la pÃ¡gina se carga
            document.addEventListener("DOMContentLoaded", function() {
              const liCargar = document.getElementById("cargar");
              if (liCargar) {
                const link = liCargar.querySelector("a");
                link.click();
              }
            });

            // Simular un clic en el elemento <li> con el id "cargar" cuando la pÃ¡gina se carga
            function cargarHorario1() {
                document.addEventListener("DOMContentLoaded", function() {
                  const liCargar = document.getElementById("horario");
                  if (liCargar) {
                    const link = liCargar.querySelector("a");
                    link.click();
                  }
                });
              }

            function mostrarPopup(id_estu) {
                    var url = 'tutor_aula/boleta_notas.php?id_est=' + id_estu;

                    // TamaÃ±o fijo de la ventana emergente
                    var anchoVentana = 500;
                    var altoVentana = 500;

                    // Calcular la posiciÃ³n centrada en la pantalla
                    var izquierda = (screen.width - anchoVentana) / 2;
                    var arriba = (screen.height - altoVentana) / 2;

                    var ventanaPopup = window.open(url, '_blank', 'width=' + anchoVentana + ',height=' + altoVentana + ',left=' + izquierda + ',top=' + arriba);
                    ventanaPopup.focus();
                }
          </script>
          <li id="treeview" class="treeview">
          <a  onclick="cargar_contenido('contenido_principal', 'tutor_aula/salon_asig_tutoria.php')" >
          <i class="fas fa-file-pdf "></i> <span style="cursor: pointer;">Gestión de Documentos</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
          </a>
        </li>
        <li id="treeview" class="treeview">
          <a onclick="cargar_contenido('contenido_principal','<?= $linkMisTutorados ?>')">
            <i class="fa fa-users"></i>
            <span style="cursor: pointer;">Mis Tutorados</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
          </a>
        </li>
          
        <li id="horario" class="treeview">
        <a id="horaaa" onclick="cargar_contenido('contenido_principal','fullcalendar')" >
        <i class=" fa  fa-calendar"></i> <span style="cursor: pointer;">Gestionar Horario</span>
        <span class="pull-right-container">
        <i class="fa fa-angle-left pull-right"></i>
        </span>
        </a>
      </li>
      <li class="treeview">
        <a  onclick="cargar_contenido('contenido_principal','tutor_aula/vista_historial_sesiones.php')">
          <i class="glyphicon glyphicon-calendar"></i> <span style="cursor: pointer;">Historial de sesiones</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
      </li>
      <li class="treeview">
        <a  onclick="cargar_contenido('contenido_principal','tutor_aula/vista_historial_derivaciones.php')">
          <i class="fa fa-calendar-o" aria-hidden="true"></i> <span style="cursor: pointer;">Historial de derivaciones</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>

          </span>
        </a>
      </li>
      <li class="treeview">
        <a onclick="cargar_contenido('contenido_principal','folder/listar_Folder.php')">
          <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Material de Apoyo</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>

          </span>
        </a>
      </li>
       <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','foro/vista_foro.php')">
            <i class="fa fa-comments"></i> <span style="cursor: pointer;">Foro</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
        </li>

      <li class="treeview">
        <a onclick="cargar_contenido('contenido_principal','../vista/tutoriales_docentes.php')">
          <i class="	fa fa-file-movie-o"></i> <span style="cursor: pointer;">Manual - Tutoriales</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
      </li>
      <?php }

       ?>
      
      <?php
        if ($_SESSION['S_IDUSUARIO'] == '33') { ?>

          <!--<hr >
            <span style="color: white; font-size: 15px; display: block; text-align: center; margin-left: -20px;">Módulo Coordinador</span>
          </hr>

          <li id="cargar4" class="treeview">
            <a onclick="cargar_contenido('contenido_principal','coordinador/vista_tutorados.php')">
            <i class=" fa  fa-users"></i> <span style="cursor: pointer;">Docente Tutor</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
            </a>
          </li>
 
      <li class="treeview">
        <a onclick="cargar_contenido('contenido_principal','reportes/vista_reportes_alumnos_escu_pro.php')">
        <i class=" fa  fa-users"></i> <span style="cursor: pointer;">Tutorados</span>
        <span class="pull-right-container">
        <i class="fa fa-angle-left pull-right"></i>

        </span>
        </a>
           </li>-->

      <?php }


       ?>


         <?php 

        if ($_SESSION['S_ROL'] =='DIRECTOR DE DEPARTAMENTO ACADEMICO') {//---
          ?>

          <script>
            // Simular un clic en el elemento <li> con el id "cargar" cuando la página se carga
            document.addEventListener("DOMContentLoaded", function() {
              const liCargar = document.getElementById("cargar4");
              if (liCargar) {
                const link = liCargar.querySelector("a");
                link.click();
              }
            });
        </script>

		   <li class="treeview">
  			  <a onclick="cargar_contenido('contenido_principal','coordinador/vista_tutorados.php')">
  				<i class=" fa  fa-users"></i> <span style="cursor: pointer;">Asignación de tutor</span>
  				<span class="pull-right-container">
  				<i class="fa fa-angle-left pull-right"></i>
  				</span>
  			  </a>
       </li>
 
		  <li class="treeview">
			  <a onclick="cargar_contenido('contenido_principal','reportes/vista_reportes_alumnos_escu_pro.php')">
				<i class=" fa  fa-users"></i> <span style="cursor: pointer;">Tutorados</span>
				<span class="pull-right-container">
				<i class="fa fa-angle-left pull-right"></i>
				</span>
			  </a>
      </li>

       <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/direccion_porcentaje_estudiantes.php')">
            <i class="glyphicon glyphicon-stats"></i> <span style="cursor: pointer;">Participación Estudiantes</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/direccion_porcentaje_tutores.php')">
            <i class="glyphicon glyphicon-th-list"></i> <span style="cursor: pointer;">Participación Tutores</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/tendencia_cumplimiento.php')">
            <i class="fas fa-chart-line"></i> <span style="cursor: pointer;">Tendencia de Cumplimiento</span>
            <span class="pull-right-container">
            <i class=""></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/derivaciones_atenciones.php')">
            <i class="fa fa-file-pdf-o"></i> <span style="cursor: pointer;">Reporte de Derivaciones</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
         <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','coordinador/vista_planes_tutor_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Planes de tutoría</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','coordinador/vista_informe_mensual_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Informe mensual Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
       <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','coordinador/vista_informe_final_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Informe Final Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> 
     <!--    <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','coordinador/vista_informe_mensual_cursos.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Informe mensual Cursos</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> -->
        <li class="treeview">
        <a href="https://www.google.com" onclick="window.open('https://drive.google.com/file/d/1SyTfVnlc_FQWfrI6oegUxrpHgIM2T5hS/view', '_blank');">
          <i class="	fa fa-file-movie-o"></i> <span style="cursor: pointer;">Manual de Usuario</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
      </li>
	 <!-- <li class="treeview">
          <a >
            <span class="pull-right text-muted">
                  <b class="badge bg-info pull-right"></b>
                  </span>
             <i class="glyphicon glyphicon-calendar "></i> <span style="cursor: pointer;">Comunicado</span><span class="pull-right-container">
              <i class="fa fa-labtop"></i>
              <i class="fa fa-angle-left pull-right"></i>
            </span>
           
          </a>         
      </li>-->

       <?php
          }
         ?>

          <?php 

        if ($_SESSION['S_ROL'] =='ALUMNO') {
          ?>  
		  

          <script>
            // Simular un clic en el elemento <li> con el id "cargar2" cuando la página se carga
            document.addEventListener("DOMContentLoaded", function() {
              const liCargar = document.getElementById("cargar2");
              if (liCargar) {
                const link = liCargar.querySelector("a");
                link.click();
              }
            });
          </script>
<!--
         <li class="treeview">
          <a  onclick="cargar_contenido('contenido_principal','alumno/vista_mis_sessiones.php')">
            <i class=" fa fa-calendar-times-o"></i> <span style="cursor: pointer;">Sesiones del día</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
          </li>
-->
        <li id="cargar2" class=" treeview">
          <a  onclick="cargar_contenido('contenido_principal','calendar_estu/index.php')">
            <i class="glyphicon glyphicon-time"></i> <span style="cursor: pointer;">Horario</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
		  
          <li  class="treeview">
          <a  onclick="cargar_contenido('contenido_principal','alumno/vista_mi_tutor.php')">
            <i class=" fa  fa-edit"></i> <span style="cursor: pointer;">Mi Tutor</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
        </li>

         <!-- <li class="treeview">
          <a >
            <i class=" fa  fa-calendar-check-o"></i> <span style="cursor: pointer;">Tutoría Finalizada</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
        </li> -->

          <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','alumno/listar_foders.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Materiales de Apoyo</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','foro/vista_alumno.php')">
            <i class="fa fa-comments"></i> <span style="cursor: pointer;">Foro</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>

            </span>
          </a>
        </li>

       <!-- <li class="treeview">
        <a href="https://www.google.com" onclick="window.open('https://docs.google.com/forms/d/e/1FAIpQLSfm3PEaPZ4Sc_rcPny9nsoKE5MLLI7FiXoeW0z1M8gDZezPpA/viewform', '_blank');">
          <i class="glyphicon glyphicon-file"></i> <span style="cursor: pointer;">Prueba de Diagnóstico</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
      </li> -->
      <li class="treeview">
        <a href="https://www.google.com" onclick="window.open('https://drive.google.com/file/d/1n7ZdaK07rTHr_O6bgHjyzCBKi3SHk_uN/view', '_blank');">
          <i class="	fa fa-file-movie-o"></i> <span style="cursor: pointer;">Manual de Usuario</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
      </li>
      
        <?php
          }
         ?>
    
    <?php
      if ($_SESSION['S_ROL'] == 'DIRECCION DE ESCUELA') {
        ?>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/direccion_porcentaje_estudiantes.php')">
            <i class="glyphicon glyphicon-stats"></i> <span style="cursor: pointer;">Participación Estudiantes</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/direccion_porcentaje_tutores.php')">
            <i class="glyphicon glyphicon-th-list"></i> <span style="cursor: pointer;">Participación Tutores</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/tendencia_cumplimiento.php')">
            <i class="fas fa-chart-line"></i> <span style="cursor: pointer;">Tendencia de Cumplimiento</span>
            <span class="pull-right-container">
            <i class=""></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/derivaciones_atenciones.php')">
            <i class="fa fa-file-pdf-o"></i> <span style="cursor: pointer;">Reporte de Derivaciones</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/direccion_planes_tutor_aula.php')">
            <i class="glyphicon glyphicon-file"></i> <span style="cursor: pointer;">Planes de tutoría</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/direccion_informe_mensual_aula.php')">
            <i class="glyphicon glyphicon-file"></i> <span style="cursor: pointer;" title="Informe mensual Tutores de Aula">Informe Mensual Aula</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/direccion_informe_final_aula.php')">
            <i class="glyphicon glyphicon-file"></i> <span style="cursor: pointer;" title="Informe mensual Tutores de Aula">Informe Final Aula</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <!-- <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/direccion_informe_mensual_cursos.php')">
            <i class="glyphicon glyphicon-file"></i> <span style="cursor: pointer;" title="Informe mensual Tutores de Curso">Informe Mensual Cursos</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> -->
        <li class="treeview">
        <a href="https://www.google.com" onclick="window.open('https://drive.google.com/file/d/1Cqql5wSQcPj01rBeV8LyaSIjcKcvKwR3/view', '_blank');">
          <i class="	fa fa-file-movie-o"></i> <span style="cursor: pointer;">Manual de Usuario</span>
          <span class="pull-right-container">
          <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
      </li>

    <?php
          }
         ?>
    <?php
      if ($_SESSION['S_ROL'] == 'SUPERVISION') {
      ?>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_usuarios.php')">
            <i class="fa fa-user-cog"></i> <span style="cursor: pointer;">Usuarios</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
       <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_porcentaje_tutores.php')">
            <i class="glyphicon glyphicon-th-list"></i> <span style="cursor: pointer;">Participación Tutores</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_porcentaje_estudiantes.php')">
            <i class="glyphicon glyphicon-stats"></i> <span style="cursor: pointer;">Participación Estudiantes</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_tendencia_cumplimiento.php')">
            <i class="fas fa-chart-line"></i> <span style="cursor: pointer;">Tendencia de Cumplimiento</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/derivaciones_atenciones.php')">
            <i class="fa fa-file-pdf-o"></i> <span style="cursor: pointer;">Reporte de Derivaciones</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_nee.php')">
            <i class="fas fa-user-friends"> </i> <span style="cursor: pointer;">  Estudiantes NEE</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
      <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_planes_tutoria.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte de Planes</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_planes_tutor_aula.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Planes de tutoría</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_informe_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte de Informes Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_informe_aula.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Informe mensual Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_informe_final_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte Informe Final</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
         <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_informe_final_aula.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Informe Final Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
    <!--     <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_informe_mensual_curso.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte de Informes Cursos</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_informe_mensual_cursos.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Informe mensual Cursos</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> -->
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/reporte_estadistico_encuesta.php')">
            <i class="fa fa-bar-chart"></i> <span style="cursor: pointer;">Reporte Indicadores</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/cumplimiento_no_lectivas.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Reporte Horas no lectivas</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
      <?php
      }
      ?>
      <?php 

        if ($_SESSION['S_ROL'] =='VICEPRESIDENCIA ACADEMICA') {
          ?>
          <li class="treeview">
            <a onclick="cargar_contenido('contenido_principal','reportes/admin_porcentaje_tutores.php')">
              <i class="glyphicon glyphicon-th-list"></i> <span style="cursor: pointer;">Participación Tutores</span>
              <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
          </li> 
          <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_porcentaje_estudiantes.php')">
            <i class="glyphicon glyphicon-stats"></i> <span style="cursor: pointer;">Participación Estudiantes</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_tendencia_cumplimiento.php')">
            <i class="fas fa-chart-line"></i> <span style="cursor: pointer;">Tendencia de Cumplimiento</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/derivaciones_atenciones.php')">
            <i class="fa fa-file-pdf-o"></i> <span style="cursor: pointer;">Reporte de Derivaciones</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes/admin_nee.php')">
            <i class="fas fa-user-friends"> </i> <span style="cursor: pointer;">  Estudiantes NEE</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
         <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/reporte_estadistico_encuesta.php')">
            <i class="fa fa-bar-chart"></i> <span style="cursor: pointer;">Reporte Indicadores</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
          <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_planes_tutoria.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte de Planes</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_planes_tutor_aula.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Planes de tutoría</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_informe_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte de Informes Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_informe_aula.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Informe mensual Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_informe_final_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte Informe Final</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_informe_final_aula.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Informe Final Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/cumplimiento_no_lectivas.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Reporte Horas no lectivas</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
  <!--       <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/report_informe_mensual_curso.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Reporte de Informes Cursos</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/vista_general_informe_mensual_cursos.php')">
            <i class="glyphicon glyphicon-folder-open"></i> <span style="cursor: pointer;">Informe mensual Cursos</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> -->
        
      <?php
      }
      ?>
      <?php 

        if ($_SESSION['S_ROL'] =='COMITÉ - SUPERVISIÓN') {//---
          ?>

          <script>
            // Simular un clic en el elemento <li> con el id "cargar" cuando la página se carga
            document.addEventListener("DOMContentLoaded", function() {
              const liCargar = document.getElementById("cargar4");
              if (liCargar) {
                const link = liCargar.querySelector("a");
                link.click();
              }
            });
        </script>

       <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/direccion_porcentaje_estudiantes.php')">
            <i class="glyphicon glyphicon-stats"></i> <span style="cursor: pointer;">Participación Estudiantes</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','direccion_escuela/direccion_porcentaje_tutores.php')">
            <i class="glyphicon glyphicon-th-list"></i> <span style="cursor: pointer;">Participación Tutores</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>

         <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','coordinador/vista_planes_tutor_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Planes de tutoría</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','coordinador/vista_informe_mensual_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Informe mensual Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','coordinador/vista_informe_final_aula.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Informe Final Aula</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> 
      <!--   <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','coordinador/vista_informe_mensual_cursos.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Informe mensual Cursos</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li> -->
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','reportes_generales/s_reg_no_lectivas.php')">
            <i class="fa fa-list-alt"></i> 
            <span style="cursor: pointer;">Control Horas no lectivas</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
	 <!-- <li class="treeview">
          <a >
            <span class="pull-right text-muted">
                  <b class="badge bg-info pull-right"></b>
                  </span>
             <i class="glyphicon glyphicon-calendar "></i> <span style="cursor: pointer;">Comunicado</span><span class="pull-right-container">
              <i class="fa fa-labtop"></i>
              <i class="fa fa-angle-left pull-right"></i>
            </span>
           
          </a>         
      </li>-->

       <?php
          }
         ?>
                  <?php 

        if ($_SESSION['S_ROL'] =='DEPARTAMENTO ESTUDIOS GENERALES') {//---
          ?>

          <script>
            // Simular un clic en el elemento <li> con el id "cargar" cuando la página se carga
            document.addEventListener("DOMContentLoaded", function() {
              const liCargar = document.getElementById("cargar4");
              if (liCargar) {
                const link = liCargar.querySelector("a");
                link.click();
              }
            });
        </script>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','estudios_generales/eg_porcentaje_tutores.php')">
            <i class="glyphicon glyphicon-th-list"></i> <span style="cursor: pointer;">Participación Tutores</span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','estudios_generales/eg_report_general_informe_mensual.php')">
            <i class="glyphicon glyphicon-book"></i> <span style="cursor: pointer;">Estado de Informes</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>
        <li class="treeview">
          <a onclick="cargar_contenido('contenido_principal','estudios_generales/eg_informe_mensual_cursos.php')">
            <i class="fa fa-file-pdf-o"></i> <span style="cursor: pointer;">Informe mensual</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
        </li>


       <?php
          }
         ?>
    </ul>
    </section>
    <!-- /.sidebar -->
</aside>