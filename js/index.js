// obtener modales
const modalRegistro = document.getElementById('miModalRegistro');
const modalRecuperar = document.getElementById('miModalRecuperar');

// obtener boton que abre la modal
const btnRegistro = document.querySelector('.abrir-modal-registro');
const btnRecuperar = document.querySelector('.abrir-modal-recuperar');

// obtener el elemento <span> que cierra la modal
const spanRegistro = document.querySelector('.cerrarRegistro');
const spanRecuperar = document.querySelector('.cerrarRecuperar');

// abrir modal registro
btnRegistro.onclick = function(){
    modalRegistro.style.display = "flex";
}

// abrir modal recuperar
btnRecuperar.onclick = function(){
    modalRecuperar.style.display = "flex";
}

// cerrar modales cuando se hace click en el <span>
spanRegistro.onclick = function(){
    modalRegistro.style.display = "none";
}

spanRecuperar.onclick = function(){
    modalRecuperar.style.display = "none";
}

// cerrar modal cuando el usuario hace click fuera del modal
window.onclick = function(event){
    if(event.target == modalRegistro){
        modalRegistro.style.display = "none";
    }
    if(event.target == modalRecuperar){
        modalRecuperar.style.display = "none";
    }
}