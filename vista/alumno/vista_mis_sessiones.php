<script type="text/javascript" src="../js/alumno.js?rev=<?php echo time();?>"></script>
	<div class="col-md-7" >
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
	        	<div class="col-xs-7">
	        	</div>
		        <div class="col-xs-5">
		            <div class="input-group">
		            	 <label for="">Filtrar búsqueda:</label>
		                   <input type="text" class="global_filter form-control" id="global_filter" placeholder="Ingresar dato a buscar" style="border-radius: 5px;">
		               </div>
		        </div>
	        </div>

	         <table id="table_sessiones_alumno" class="display responsive nowrap" style="width:100%">
	                <thead>
	                    <tr>
	                        <th>N° id</th>
	                        <th>Horas</th>

	                        <th>Tipo de Sesi&oacute;n</th>
							<th>Asistencia</th>
	                    </tr> 
	                </thead>
	                <tfoot>
	                    <tr>
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

<style type="text/css">
:root {
    --color-inactivo: #5f5050;
    --color-hover: #ffa400;
}
.valoracion {
    display: flex;
    flex-direction: row-reverse;
	position:relative; 
	left: 20%;
}
.valoracion .btn-star {
    background-color: initial;
    border: 0;
    color: var(--color-inactivo);
    transition: 1s all;
}
.valoracion .btn-star:hover {
    cursor: pointer;
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star:nth-child(1):hover ~ .btn-star {
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star:nth-child(2):hover ~ .btn-star {
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star:nth-child(3):hover ~ .btn-star {
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star:nth-child(4):hover ~ .btn-star {
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star:nth-child(5):hover ~ .btn-star {
    color: var(--color-hover);
    transform: rotate(360deg);
}
.btn-star i {
	font-size: 30px;
}
.box-star {
	position: absolute;
}
</style>

<div class="col-md-5" > 
	<div class="box box-success box-solid"> 
		<div class="box-header with-border"> 
			<h3 class="box-title" style="text-align:center;"><center>Valoracion Tutoria</center></h3> 
		</div>

	   
		<div class="box-body">
            <div class="box box-widget widget-user-2">
                         <div class="widget-user-header bg-widget">
                            <div class="widget-user-image">
                             <img class="img-circle" src="../Plantilla/dist/img/images.png" alt="User Avatar">
                          </div>
                          <div>
                           <h3 class="widget-user-username" id="ApellidoAlumno">&nbsp;<b></b>...</h3>
                           <h5 class="widget-user-desc" id="telefono" style="margin-top: .6em;">...</h5>
                           <h5 class="widget-user-desc" id="gradoAlumno">...</h5>
                          
                         </div>
                       </div>
                     
            </div>
            <div class="box-star" id="box_valoracion" hidden>

            	<div class="valoracion">

				    <button class="btn-star" name="5">
				        <i class="fa fa-star iconstar"></i>
				    </button>

				    <button class="btn-star" name="4">
				        <i class="fa fa-star iconstar"></i>
				    </button>

				    <button class="btn-star" name="3">
				        <i class="fa fa-star iconstar"></i>
				    </button>

				    <button class="btn-star" name="2">
				        <i class="fa fa-star iconstar"></i>
				    </button>

				    <button class="btn-star" name="1">
				        <i class="fa fa-star iconstar"></i>
				    </button>

				</div>
			</div>
			<br>
			<br>
         </div>
	</div>
</div>
	<script>
$(document).ready(function() { 
    listar_mis_sessiones_alumno();
    Docente_a_Cargo();
    //listar_tipo_session();
    //envio id_estu=0 y tipo 1. solo para inicializar la tabla (No sale ningun dato).
    //listar_asistencia_estudiante(0,  1, 0); 
});
</script>