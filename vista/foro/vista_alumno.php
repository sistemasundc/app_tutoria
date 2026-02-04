<style type="text/css">
.custom-btn-primary {
  background-color: #00bd9d;
  color: #ffffff;
}

.custom-btn-secondary {
  background-color: #00bd9d;
  color: #ffffff;
}

.custom-btn-primary.active,
.custom-btn-secondary.active {
  background-color: #4a6fa5;
  color: white;
}

.btnforo {
	font-size: 16px;
}
.btnforo:hover {
	color: white;
	background-color: #4a6fa5;
}
.btnforo:focus,
.btnforo:active {
  outline: none !important;
  box-shadow: none !important;
}
</style>
<div class="col-md-12">
	<div class="box box-warning " style="border-radius: 5px">
		<div class="box-header with-border">
			<h3 class="box-title">Bienvenido al Foro Comunitario</h3>
		</div>
			
		<div class="container-fluid">
			<div class="row justify-content-between" style="margin-top: 1em; margin-bottom: 1em;">
		    <div class="col-md-8">
		      <button id="verTodosBtn" class="btn custom-btn-primary btnforo"><i class="fa fa-comments" aria-hidden="true"></i> &nbsp; Ver todos</button>
		    </div>
		  
		  </div>
		</div>
		<div style="width: 100%; height: 2px; background-color: #e2e7ea; margin-top: 5px; margin-bottom: 10px;"></div>

		<div class="container-fluid" id="contenido_foro" >
			<!-- contenid foro-->
		</div>
	</div>
</div>

<script type="text/javascript" src="../js/foro.js"></script>
<script type="text/javascript"> 
	document.getElementById("verTodosBtn").classList.add("active");
	cargar_foro('contenido_foro','foro/vista_foro_alumno.php');

	document.getElementById("verTodosBtn").addEventListener("click", function() {
	if (!this.classList.contains("active")) {
		this.classList.add("active");

		cargar_foro('contenido_foro','foro/vista_foro_alumno.php');
		}
	});

</script>