<?php session_start(); ?>

<link rel="stylesheet" href="../Plantilla/dist/css/skins/_foro_resp.css">

<style type="text/css">
  .box-avatar {
  width: 50px;
  height: 50px;
}
.icon-avatar {
  position: absolute;

  width: 50px;
  height: 50px;

  border-radius: 50%;

  background-color: #4a6fa5;
}
.nom-avatar {
  position: relative;
  top: 13%;
  left: 35%;

  font-size: 25px;
  font-weight: 500;

  color: white;
}
.tt-badge {
  font-size: 14px;
  padding: 1px 7px 2px;
  line-height: 1;
  font-weight: 500;
  height: 25px;
  border: none;
  outline: 0;
  position: relative;
  display: -webkit-inline-box;
  display: -ms-inline-flexbox;
  display: inline-flex;
  -webkit-box-pack: center;
  -ms-flex-pack: center;
  justify-content: center;
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  text-align: center;
  cursor: pointer;
  white-space: nowrap;
  border-radius: 3px;
  color: #fff
}
.tt-badge.tt-color03 {
  background-color: #ff6868
}
</style>
<div class="container snipcss0-0-0-1 snipcss-nLdr1">
  <div class="tt-single-topic-list snipcss0-1-1-2">


    <div class="tt-item snipcss0-2-2-3">
      <div class="tt-single-topic snipcss0-3-3-4" id="veroneforo">
        <!-- Mensajes -->
      </div>
    </div>
<!--
    <div class="tt-item snipcss0-2-2-65">
       Info 
    </div>
    -->
    <div class="tt-item snipcss0-2-2-161">
      <div class="tt-single-topic snipcss0-3-161-162" id="respuestasfor">
         <!-- Respuestas -->
         <span class="tt-color03 tt-badge">Sin respuestas</span>
      </div>

    </div>
  </div>
  
  <div class="tt-wrapper-inner snipcss0-1-1-494">
    <h4 class="tt-title-separator snipcss0-2-494-495">
      <span class="snipcss0-3-495-496">
        Has llegado al final de las respuestas.  
      </span>
    </h4>
  </div>

  <?php 
    
    if ($_SESSION['S_ROL'] =='ALUMNO') {
      echo '
  <div class="tt-wrapper-inner snipcss0-1-1-509">
    <div class="pt-editor form-default snipcss0-2-509-510">
      <h6 class="pt-title snipcss0-3-510-511">
        Publica tu respuesta
      </h6>
      <div class="pt-row snipcss0-3-510-512">
        
        <div class="col-right tt-hidden-mobile snipcss0-4-512-568">
          <a href="#" class="btn btn-primary snipcss0-5-568-569">
            Ver contenido
          </a>
        </div>
      </div>
      <div class="form-group snipcss0-3-510-570">
        <textarea id="respuesta" class="form-control snipcss0-4-570-571" rows="5" placeholder="Escribe tu respuesta">
        </textarea>
      </div>
      <div class="pt-row snipcss0-3-510-572">
        
        <div class="col-auto snipcss0-4-572-580">
          <button onclick="PublicarRespuesta()" class="btn btn-secondary btn-width-lg snipcss0-5-580-581">
            Responder
          </button>
        </div>
      </div>
    </div>
  </div>';
    }
  ?>
  
</div>


<script type="text/javascript">
  $(document).ready(function() {
    CargarForo();
  });
</script>
