document.querySelector('form').onsubmit = function(e){
    const password = document.querySelector('input[name="nueva_password"]').value;
    const confirm = document.querySelector('input[name="confirmar_password"]').value;

    if(password !== confirm){
        alert('Las contraseñas no coinciden');
        e.preventDefault();
    }


}