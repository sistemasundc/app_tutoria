

 <script type="text/javascript" src="../js/reportep.js?rev=<?php echo time();?>"></script>
	<div class="col-md-12" >
	  <div class="box box-warning ">
	    <div class="box-header with-border">
	      <h3 class="box-title" style="text-align: center;"><center><strong>Lista de Estudiante</strong></center></h3>
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
	    <!-- /.box-header -->
	    <div class="box-body">
	      <div class="box-body">
	        <div class="row">
	          	<div class="col-xs-3">
	            	<label for="">Fecha Inicio</label>
	            	<input type="date" class="form-control" id="reportFechainicio" placeholder="" style="border-radius: 5px;"><br>
	          	</div>
	          	<div class="col-xs-3">
	            	<label for="">Fecha Final</label>
	            	<div class="selecturno">
		            	<input type="date" class="form-control" id="reportFechafin" placeholder="" style="border-radius: 5px;" >&nbsp;
		            	<button onclick=" " class="btn" type="submit" name="search" id="butsearch" class="btn btn-flat">
		                	<i class="fa fa-search" style="color: white;font-size: 15px;"></i>
		                </button>
	                </div>
	            	<br>
	          	</div>
	          	<div class="col-xs-3">
	          		

<label for="">Ciclo</label>
<br>
  				<select class="cmbColumn" id="ciclo_estu" >
    						<option value="" selected disabled>Todos</option>
    						<option value="I">I</option>
					    	<option value="II">II</option>
					    	<option value="III">III</option>
					    	<option value="IV">IV</option>
					    	<option value="V">V</option>
					    	<option value="VI">VI</option>
					    	<option value="VII">VII</option>
					    	<option value="VIII">VIII</option>
					    	<option value="IX">IX</option>
					    	<option value="X">X</option>
  				</select>

	          	</div>
		        <div class="col-xs-3">
		            <div class="input-group">
		            	 <label for="">Filtrar búsqueda</label>
		                   <input type="text" class="global_filter form-control" id="global_filter" placeholder="Ingresar dato a buscar" style="border-radius: 5px;">
		               </div>
		        </div>
	        </div>

	         <table id="table_alumnox" class="display responsive nowrap" style="width:100%">
	                <thead>
	                    <tr>
	                        <th>N° id estudiante</th>
	                        <th>Apellidos</th>
	                        <th>Nombres</th>
	                        <th>Tel&eacute;fono</th>
	                        <th>Ciclo</th>
							<th>Dni</th>
	                        <th>Correo</th>
	                        <th>Semestre</th>
	                        <th>Opciones</th>
	                    </tr> 
	                </thead>
	                <tfoot>
	                    <tr>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th> 
	                        <th></th> 
	                        <th></th> 
	                    </tr>
	                </tfoot>
	            </table>
	       
<div class="modal fade" id="cambiarTutorModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content" style="border-radius: 5px;">
			<div class="modal-header" id="dff"
				style="background-color: #3c8dbc; border: none; border-radius: 5px 5px 0 0;">
				<h4 class="modal-title" style="color: white; float: left;">Cambiar Docente Tutor</h4>
				<div style="position: absolute; left: 90%; ">
					<button type="button" class="btn" data-dismiss="modal"
						style="position: relative; background-color: transparent; color: white;">
						<i class="fa fa-times" aria-hidden="true"></i>
					</button>
				</div>
			</div> <!-- close modal -->

		<div class="modal-body">
			<div class="loadingDiv"></div>
			<!-- QuickSave/Edit FORM -->
			<div id="modal-form-body" style="margin-left: 1em; margin-right: 1em;">	
				<div class="row">
					<h4><i class="fa fa-address-book" aria-hidden="true" style="color: #3c8dbc;"></i>&nbsp;Tutores asignados:</h4>
					<div class="col-md-12" id="tutores-asignados" style="margin-bottom: 1em;">
						<label for="">Docente:</label> <span>No tiene docentes asignados</span>
					</div>
					<hr>
					<h4><i class="fa fa-address-card" aria-hidden="true" style="color: #3c8dbc;"></i>&nbsp;Cambiar de tutor:</h4>
					<div class="col-lg-12" id="combotutores">
	                   <label for="">Docentes:</label>
	                    <input type="text" id="textId_es" value="0" hidden >
						<!-- NUEVO INPUT OCULTO -->
   						<input type="hidden" id="textId" value="<?php echo $_SESSION['S_IDUSUARIO']; ?>">
	                    <select class="js-example-basic-single" name="state" id="docentes-tutores" style="width:100%;">
	                    	<option>Espere un momento...</option>
	                    </select>
	                </div>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<hr>
			
			<button type="button" id="btn-aginarr-tutor" class="btn btn-primary"
					onclick="CambiarTutor()">Asignar tutor</button>
			<button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-danger">Cancelar</button>
		</div>
	</div>
</div>
</div>


	       
	    </div>
	     <div class="modal-footer">
	         
	     </div>
	  </div>
	</div>
	</div>


	<script>
$(document).ready(function() {
    listar_Alumnos();//esta en index.js==> cargar semestre actual
} );
</script>