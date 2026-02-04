<script type="text/javascript" src="../js/reportep.js?rev=<?php echo time();?>"></script>
	<div class="col-md-6" >
	  <div class="box box-warning box-solid">
	    <div class="box-header with-border">
	      <h3 class="box-title" style="text-align: center;"><center>Sesión del día</center></h3>
	    </div>

	    <style>
	    .selecturno {
	        display: flex;
	        justify-content: end;
	    }

	    #butsearch{
	    	border-radius: 5px;
	    	margin-top: -2px;
	    	font-size: 10px;
	    	background-color: #05ccc4; 
	    }
	    .cmbColumn {
		  
		  border-radius: 3px;
		  margin-boton: 8px;
		 
		  border: 1px solid gray;
		  font-size: 14px;
		  height: 30px;
		  padding: 5px;
		  width: 100px;
		}

		.cmbColumn .option {
		  height: 30px;
		  border-button: 8px;
		}
	
</style>

	   <?php 
              date_default_timezone_set('America/Lima');
              $n_today = date('N');
              $f_today = date('Y-m-d');
            ?>
            <input type="text" id="ntoday" value="<?php echo $n_today ?>" hidden >
            <input type="text" id="ftoday" value="<?php echo $f_today ?>" hidden >
	    <!-- /.box-header -->
	    <div class="box-body">
	      <div class="box-body">
	        <div class="row">
	        	<div class="col-md-7">
	        	</div>
		        <div class="col-md-5">
		            <div class="input-group">
		            	 <label for="">Filtrar búsqueda:</label>
		                   <input type="text" class="global_filter form-control" id="global_filter" placeholder="Ingresar dato a buscar" style="border-radius: 5px;">
		               </div>
		        </div>
	        </div>

	         <table id="table_sessiones" class="display responsive nowrap" style="width:100%">
	                <thead>
	                    <tr>
	                        <th>N° id</th>
	                        <th>Hora incio</th>
	                        <th>Hora final</th>
	                        <th>Tipo</th>
							<th>Asistencia</th>
	                    </tr> 
	                </thead>
	                <tfoot>
	                    <tr>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                    </tr>
	                </tfoot>
	            </table>
	       
	    </div>
	     <div class="modal-footer">
	         
	     </div>
	  </div>



	</div>
</div>

<div class="col-md-6" > <div class="box box-success box-solid"> <div
class="box-header with-border"> <h3 class="box-title" style="text-align:
center;"><center>Registrar Sesión y Marcar Asistencia</center></h3> </div>

	    <!-- /.box-header -->
	    <div class="box-body">
	      <div class="box-body">
	      	<div class="row">
				<div class="col-md-6" >
				   
					<label for="">Tema:</label>
					<br>
					<textarea type="text" class="areat campoasis form-control" id="tema_asig" style="border-radius: 5px; max-width:100%;height: 100px;"></textarea>
					
				</div>
				<div class="col-md-6" style="margin: 0px 0px 2em 0px">
				   
					<label for="">Compromiso:</label>
		            <textarea type="text" class="areat campoasis form-control" id="compr_asig" style="border-radius: 5px; max-width: 100%;height: 100px;"></textarea>
				
				</div>
				
				<div class="col-md-6">
				   
					<label for="">Observaciones / Problemas:</label>
		            <textarea type="text" class="areat campoasis form-control" id="obser" style="border-radius: 5px; max-width: 100%;height: 100px;"></textarea>
					
				</div>

				<div class="col-md-6">
		            <div class="box-body">
		              	<div class="btn-group" style="width: 100%; margin-bottom: 10px;">
		                    <label for=""><b>Tipo de sisión:<b></label>
		                    <select class="global_filter form-control campoasis" id="tipo_session" style="width:100%; padding: 7px" > 
		                    </select>
		              </div>
		            </div>	
		        </div>
	      		<div class="col-md-12" id="esp_otros" hidden>
				   
					<label for="">Especifique:</label>
		            <textarea type="text" class="areat form-control campoasis" id="espes" style="border-radius: 5px; max-width: 100%;height: 100px; font-weight: normal;"></textarea>
					
				</div>
	      	</div>
	      	
	        <div class="row" style="margin-top: 50px; ">
	        	
				<style>
				  
					:root {
					  --scale-factor: .6; /* Puedes ajustar el valor de escala aquí */
					}

					.link {
					  text-align: center;
					  color: #278fb2;
					}

					.ext-cross:before,
					.checkbox__checker:before,
					.checkbox__cross:before,
					.checkbox__ok:before,
					.ext-cross:after,
					.checkbox__checker:after,
					.checkbox__cross:after,
					.checkbox__ok:after {
					  content: "";
					  display: block;
					  position: absolute;
					  width: calc(14px * var(--scale-factor));
					  height: calc(2px * var(--scale-factor));
					  margin: 0 auto;
					  top: calc(20px * var(--scale-factor));
					  left: 0;
					  right: 0;
					  background-color: #bf1e1e;
					  border-radius: calc(5px * var(--scale-factor));
					  transition-duration: 0.3s;
					}

					.ext-cross:before,
					.checkbox__checker:before,
					.checkbox__cross:before,
					.checkbox__ok:before {
					  transform: rotateZ(45deg);
					}

					.ext-cross:after,
					.checkbox__checker:after,
					.checkbox__cross:after,
					.checkbox__ok:after {
					  transform: rotateZ(-45deg);
					}

					.ext-ok:before,
					.checkbox__ok:before,
					.checkbox__toggle:checked + .checkbox__checker:before,
					.ext-ok:after,
					.checkbox__ok:after,
					.checkbox__toggle:checked + .checkbox__checker:after {
					  background-color: #0cb018;
					}

					.ext-ok:before,
					.checkbox__ok:before,
					.checkbox__toggle:checked + .checkbox__checker:before {
					  width: calc(6px * var(--scale-factor));
					  top: calc(23px * var(--scale-factor));
					  left: calc(-7px * var(--scale-factor));
					}

					.ext-ok:after,
					.checkbox__ok:after,
					.checkbox__toggle:checked + .checkbox__checker:after {
					  width: calc(12px * var(--scale-factor));
					  left: calc(5px * var(--scale-factor));
					}

					.checkbox {
					  width: calc(100px * var(--scale-factor));
					  margin: 0 auto calc(30px * var(--scale-factor)) auto;
					}

					.checkbox__container {
					  display: block;
					  position: relative;
					  height: calc(42px * var(--scale-factor));
					  cursor: pointer;
					}

					.checkbox__toggle {
					  display: none;
					}

					.checkbox__toggle:checked + .checkbox__checker {
					  left: calc((100% - 0px) * var(--scale-factor));
					  transform: rotateZ(360deg);
					}

					.checkbox__checker,
					.checkbox__cross,
					.checkbox__ok {
					  display: block;
					  position: absolute;
					  height: calc(43px * var(--scale-factor));
					  width: calc(43px * var(--scale-factor));
					  top: -1px;
					  left: 0px;
					  z-index: 1;
					}

					.checkbox__checker {
					  border-radius: calc(100% * var(--scale-factor));
					  background-color: #fff;
					  box-shadow: 0px calc(2px * var(--scale-factor)) calc(6px * var(--scale-factor)) rgba(0, 0, 0, 0.5);
					  transition: 0.3s;
					  z-index: 2;
					}

					.checkbox__checker:before,
					.checkbox__checker:after {
					  transition-duration: 0.3s;
					}

					.checkbox__cross:before,
					.checkbox__cross:after,
					.checkbox__ok:before,
					.checkbox__ok:after {
					  background-color: #ddd;
					}

					.checkbox__ok {
					  left: calc((100% - 43px) * var(--scale-factor));
					}

					.checkbox__txt-left,
					.checkbox__txt-right {
					  display: block;
					  position: absolute;
					  width: calc(42px * var(--scale-factor));
					  top: calc(15px * var(--scale-factor));
					  text-align: center;
					  color: #fff;
					  font-size: calc(12px * var(--scale-factor));
					  z-index: 1;
					}

					.checkbox__txt-right {
					  right: 0px;
					}

					.checkbox__bg {
					  position: absolute;
					  top: 0;
					  left: 0;
					  fill: #aaa;
					  width: 100%;
					  height: 100%;
					}

				  .box-body table td {
				    font-weight: normal;
				  }

				</style>




		        <div class="col-xs-5">
		            <div class="input-group">
		            	 <label for="">Filtrar búsqueda:</label>
		                   <input type="text" class="global_filter form-control" id="global_filter2" placeholder="Ingresar dato a buscar" style="border-radius: 5px;">
		              </div>
		        </div>
	        	
		        <div class="col-xs-5" id="box_todos" hidden>
		            <div class="input-group" >
		            	 <label for="">Marcar a todos:</label>
		            	 <br>
		                 
							<div class='checkbox' >
							  <label class='checkbox__container'>
							    <input class='checkbox__toggle' id="checkbox_todos" type='checkbox'>
							    <span class='checkbox__checker'></span>
							    
							    <svg class='checkbox__bg' space='preserve' style='enable-background:new 0 0 110 43.76;' version='1.1' viewbox='0 0 110 43.76'>
							      <path class='shape' d='M88.256,43.76c12.188,0,21.88-9.796,21.88-21.88S100.247,0,88.256,0c-15.745,0-20.67,12.281-33.257,12.281,S38.16,0,21.731,0C9.622,0-0.149,9.796-0.149,21.88s9.672,21.88,21.88,21.88c17.519,0,20.67-13.384,33.263-13.384,S72.784,43.76,88.256,43.76z'></path>
							    </svg>
							  </label>
							</div>
		              </div>
		        </div>
	        </div>

	         <table id="table_asistencia_estudiante" class="display responsive nowrap" style="width:100%">
	                <thead>
	                    <tr>
	                        <th>N° id</th>
	                        <th>Estudiante</th>
	                        <th>Asistencia</th>
	                    </tr> 
	                </thead>
	                <tfoot>
	                    <tr >
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                    </tr>
	                </tfoot>
	            </table>
	       
	    </div>
	    <div class="modal-footer">
            <button class="btn btn-success" onclick="Registrar_session()" ><i class="fa fa-check"><b>&nbsp;Guardar asistencia</b></i></button>
        </div> 
	  </div> 
	</div>
</div>
	<script>
$(document).ready(function() {
    listar_mis_sessiones();
    listar_tipo_session();
    //envio id_estu=0 y tipo 1. solo para inicializar la tabla (No sale ningun dato).
    listar_asistencia_estudiante(0,  1, 0); 
});
</script>