<script type="text/javascript" src="../../js/reportep.js?rev=<?php echo time();?>"></script>
<script type="text/javascript" src="../../js/pdf.js?rev=<?php echo time();?>"></script>
	<div class="col-md-12" >
	  <div class="box box-primary box-solid">
	    <div class="box-header with-border">
	      <h3 class="box-title" style="text-align: center;"><center>Historial de Sesiones</center></h3>
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
		  margin-botton: 8px;
		 
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

		.text-truncate {
		  overflow: hidden;
		  text-overflow: ellipsis;
		  white-space: nowrap;
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
		        <div class="col-md-9">
	        	</div>
				<div class="col-md-3 d-flex align-items-left">
				    <label for="" style="margin-right: 10px;">Filtrar búsqueda</label>
				    <input type="text" class="global_filter form-control" id="global_filter" placeholder="Ingresar dato a buscar" style="border-radius: 5px;">
				</div>

	        </div>
	       
	         <table id="tabla_historial_sesiones_ta" class="display responsive nowrap" style="width:100%">
	                <thead>
	                    <tr>
	                        <th>N° id</th>
	                        <th>Tema</th>
	                        <th>Fecha</th>
	                        <th>Hora</th>
	                        <th>Modalidad</th>
	                        <th>Tipo</th>
							<th>Formato</th>
	                    </tr> 
	                </thead>
	                <tfoot>
	                    <tr>
	                        <th class="text-truncate"></th>
	                        <th></th>
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

	  <form method="post" action="../pdf_ge/index.php" id="formHis" target="_blank">
	  	<input type="text" name="id_his" id="id_history" hidden>
	  </form>

	</div>
</div>

	<script>
$(document).ready(function() { 
    listar_historial_sesiones_TA();
} );
</script>