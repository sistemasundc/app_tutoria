<header class="main-header">
  <style type="text/css">
 
  .logo:hover {

    transform: scale(1.05);
    transition: .3s;
  }
  .boxicontuto {
    position: absolute;
  }
  .icontuto {
    position: relative;
    top: .3em;
    left: -.5em;
  }
  .dropdown-menu.settings-menu {
    min-width: 180px;
    padding: 10px;
    border-radius: 6px;
    background: white;
    position: absolute;
    right: 0;
    top: 100%;
    z-index: 999;
    
  }

  .dropdown-menu.settings-menu li {
    list-style: none;
    margin: 8px 0;
  }

  .dropdown-menu.settings-menu a,
  .dropdown-menu.settings-menu i {
    color: #333;
  }

  .dropdown-menu.settings-menu .text-danger {
    color: white !important;
  }


  </style>
  
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>

      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          <!-- Messages: style can be found in dropdown.less-->
        
        <li class="dropdown user user-menu">
            <a href="../index.php" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-home"></i>Inicio
            </a>
           
        </li>
          <!-- Notificaciones: style can be found in dropdown.less -->
          <li class="dropdown notifications-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-bell-o"></i>
              <span class="label label-"></span>
              
            </a>
            <ul class="dropdown-menu" style="border-radius: 5px">
              <li>
                <!-- inner menu: contains the actual data -->
                <ul class="menu">
                  <li>
                    <a href="#">
                       <center><li class="header">No tienes Notificaciones</li></center>
                    </a>
                  
                </ul>
             
              <li class="footer"><a href="#">...</a>
              </li>
            </ul>
          </li>
          
           
          <li class="dropdown user user-menu"><a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Open Profile Menu"><i class="fa fa-user fa-lg"></i>
           </a>
          <ul class="dropdown-menu settings-menu dropdown-menu-right" style="width: 180px; border-radius: 6px; position: absolute; top: 100%; right: 0; z-index: 999; padding: 10px;">
            
            <center>
               <li class="dropdown-item" onclick="" style="cursor: pointer;"><i class="fa fa-user fa-lg" >&nbsp;&nbsp; Perfil</i> 
            </li>
            <!--<img  class="img-circle" alt="User Image"style="width: 50px;height:50px;" id="veticalfotouser"><br>-->
                 <p>
                  <?php echo $_SESSION['S_ROL']; ?>  
                </p>
            </center>
            <!------------CONFIGURCION DE DE PERFIL :::::: ESTADO PENDIENTE ------------------------>
            <!--<div class="container">
 
                 <li class="dropdown-item" style="width:100%;cursor: pointer;" onclick=""><i class="fa fa-cog fa-lg">&nbsp;&nbsp;Configuración</i>
            </li>
            </div>-->
            <div class="col-lg-12">
                <li class="dropdown-item">
                  <a class="text-danger btn btn-block btn-sm" style="border-radius: 5px;background:#05ccc4;width: 100px;cursor: pointer" href="../controlador/usuario/controlador_cerrar_session.php"><i class="fa fa-sign-out"></i>&nbsp;Salir</a>
                </li>
            </div>

          </ul>
        </li>


          <!-- Control Sidebar Toggle Button -->
          <li>
            <a href="#" data-toggle="control-sidebar"></a>
          </li>
        </ul>
      </div>
    </nav>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style type="text/css">
      body {
        font-family: "Roboto", sans-serif;
      }
    </style>
  </header>


  