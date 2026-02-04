function Generar(id_historial) {
	var id_his = document.getElementById('id_history');
	var id_form = document.getElementById('formHis');

	id_his.value = id_historial;
	id_form.submit();
}
function HistorialFormatoDerivado(id_der) {
    var semestre = new Date().getFullYear(); // o valor real si lo tienes
    window.open('/pdf_ge/referencia.php?id=' + id_der + '&semestre=' + semestre, '_blank');
}