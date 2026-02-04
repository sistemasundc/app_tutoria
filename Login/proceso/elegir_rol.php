<?php 
date_default_timezone_set('America/Lima');
session_start();
if (!isset($_SESSION['ROLES_MULTIPLES']) || empty($_SESSION['ROLES_MULTIPLES'])) {
  header("Location: ../index.php");
  exit();
}
$numero_roles = count($_SESSION['ROLES_MULTIPLES']);
$tiene_scroll = $numero_roles >= 3;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="../../img/favicon.png" />
  <title>SISTECU - UNDC | Selección de Rol</title>

  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    :root {  --azul-titulo:#1a2647;  --rojo:#b52a1e;
      --gris:#444;
      --muted:#6b7280;
      --shadow:0 24px 60px rgba(0,0,0,.08);
      --border:#dcdcdc;
      --radius:8px;
      --panel-bg:#fff;
    }

    *{
      box-sizing:border-box;
      margin:0;
      padding:0;
      font-family:-apple-system,BlinkMacSystemFont,"Inter",system-ui,Roboto,"Helvetica Neue",Arial,sans-serif;
    }

    html,body{
      height:100%;
      width:100%;
      background:#fff;
      margin:0;
    }

    body{
      display:flex;
      flex-direction:column;  /* footer al final */
      min-height:100vh;
    }

    main{
      display:flex;
      height:100vh;
      align-items:center;
      justify-content:flex-start;
      padding-left:2rem;
      gap:2rem;
    }

    /* Panel izquierdo */
    .col-left{
      flex:0 0 auto;
      width:100%;
      max-width:600px;
      background:var(--panel-bg);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      padding:2rem;
      border:1px solid rgba(0,0,0,.05);
    }

    .logo{
      display:block;
      margin:0 auto 1rem;
      max-width:200px;
    }

    .divider{
      width:100%;
      max-width:340px;
      height:1px;
      background:var(--border);
      margin:0 auto 1rem;
    }

    .subtitulo{
      text-align:center;
      font-size:.85rem;
      color:var(--gris);
      margin-bottom:1.5rem;
    }

    /* Tarjetas de roles */
    .roles-scroll{
      display:flex;
      flex-direction:column;
      align-items:center;
      width:100%;
      <?php if ($tiene_scroll): ?>max-height:240px;overflow-y:auto;<?php endif; ?>
      gap:0.8rem;
    }

    .card{
      width:90%;
      border:1.5px solid #999;
      border-radius:8px;
      background:#fff;
      text-align:center;
      cursor:pointer;
      padding:.8rem;
      transition:.3s;
      box-shadow:0 2px 6px rgba(0,0,0,.1);
    }
    .card:hover, .card.active{
      background:#1a73e8;
      color:#fff;
      border:1.5px solid #1a73e8;
      transform:translateY(-2px);
    }

    .card h5{
      margin:.4rem 0 .2rem;
      font-size:.95rem;
      font-weight:500;
    }
    .card p{
      font-size:.85rem;
      color:inherit;
    }

    .resumen{
      text-align:center;
      margin-top:1rem;
      color:var(--muted);
      font-size:.8rem;
    }

    .footer{
      margin-top:1.5rem;
      text-align:center;
      font-size:.75rem;
      color:var(--muted);
      line-height:1.5;
    }
    .footer a{color:var(--rojo);text-decoration:none;font-weight:500;}

    .btn-exit{
      display:inline-block;
      background:var(--rojo);
      color:#fff;
      font-weight:600;
      border:none;
      border-radius:6px;
      padding:.65rem 3rem;
      margin-top:1rem;
      text-decoration:none;
      transition:.3s;
    }
    .btn-exit:hover{background:#8b1d15;}

    /* Imagen derecha */

    .col-right{
      flex:1;
      height:100%;
      /* 👇 aquí van JUNTOS: primero el degradado, luego la imagen */
      background:
        linear-gradient(rgba(5, 71, 163, 0.55), rgba(5, 71, 163, 0.25)),
        url('../images/portal.png') center center / cover no-repeat;
    }

    @media(max-width:1100px){
      main{flex-direction:column;height:auto;padding:1.5rem;}
      .col-left{max-width:480px;}
      .col-right{width:100%;height:220px;border-radius:8px;}
    }
  </style>
</head>
<body>

<main>
  <section class="col-left">
    <img src="../images/logo_sistecu.png" class="logo" alt="SISTECU - UNDC">
    <!-- <img src="../images/logo_navidad.png" class="logo" alt="SISTECU - UNDC"> -->
    <div class="divider"></div>
    <div class="subtitulo"><i class="fa-solid fa-user-gear"></i> &nbsp;Seleccione con qué usuario desea acceder al Sistema.</div>

    <form method="POST" action="establecer_sesion.php" id="rolForm">
      <input type="hidden" name="rol_seleccionado" id="rolSeleccionadoInput" />
      <div class="roles-scroll" id="rolesContainer">
        <?php
        foreach ($_SESSION['ROLES_MULTIPLES'] as $rol) {
          $data = base64_encode(json_encode($rol));
          echo '<div class="card" data-rol="' . $data . '">';
          echo '<h5> <strong>'. htmlspecialchars($rol['rol']) . '</strong> </h5>';
          echo '<p><i class="fa-regular fa-envelope"></i> ' . htmlspecialchars($rol['correo']) . '</p>';
          echo '</div>';
        }
        ?>
      </div>

      <div class="resumen">
        Usted tiene <?php echo $numero_roles; ?> usuario<?php echo $numero_roles > 1 ? 's' : ''; ?> asociado<?php echo $numero_roles > 1 ? 's' : ''; ?> a este correo.
      </div>

      <div style="text-align:center;">
        <a href="../index.php" class="btn-exit"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
      </div>
    </form>

    <div class="footer">
      Consultas y observaciones del sistema:<br>
      <b>974 612 338 — Natali Postillón</b><br>
      Soporte del Sistema de Tutoría<br><br>
      Al hacer clic en “Acceder”, aceptas nuestros 
      <a href="https://web.undc.edu.pe/terminos-y-condiciones/" target="_blank">Términos y Condiciones</a> y la 
      <a href="https://web.undc.edu.pe/pdatospersonales/" target="_blank">Política de Privacidad</a>.
    </div>
  </section>

  <section class="col-right"></section>
</main>
<footer style="
  background:#0547a3;
  color:white;
  text-align:center;
  padding:10px 0;
  font-size:0.85rem;
  width:100%;
  flex-shrink:0;">
  © 2026 Universidad Nacional de Cañete - Sistema de Tutoría y Consejería Universitaria
</footer>
<script>
  const cards = document.querySelectorAll('.card');
  const inputRol = document.getElementById('rolSeleccionadoInput');
  const rolForm = document.getElementById('rolForm');
  cards.forEach(card=>{
    card.addEventListener('click',()=>{
      inputRol.value = card.getAttribute('data-rol');
      rolForm.submit(); // selecciona y envía directo
    });
  });
</script>

</body>
</html>
