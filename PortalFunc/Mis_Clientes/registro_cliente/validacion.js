function verificarDNI(dni) {
    fetch('verificar_dni.php?dni=' + encodeURIComponent(dni))
        .then(response => response.text())
        .then(data => {
            if (data === "exists") {
                if (confirm("Ya existe un cliente con este DNI. ¿Desea intentar con otro DNI?")) {
                    document.forms["registroForm"]["dni"].value = "";
                    document.forms["registroForm"]["dni"].focus();
                }
            }
        });
}

document.querySelectorAll('input[type=number]').forEach(input => {
    // Evitar que el scroll del mouse cambie el valor del input
    input.addEventListener('wheel', function (e) {
        if (document.activeElement === input) {
            input.blur(); // quita el foco del input
        }
    });

    // Bloquear teclas de flecha ↑ ↓ solo si el input está enfocado
    input.addEventListener('keydown', function (e) {
        if ((e.key === "ArrowUp" || e.key === "ArrowDown") && document.activeElement === input) {
            e.preventDefault();
        }
    });
});


function validarFormulario() {
    // datos cliente
    let nombre = document.forms["registroForm"]["nombre"].value;
    let apellidos = document.forms["registroForm"]["apellidos"].value;
    let dni = document.forms["registroForm"]["dni"].value;
    let telefono = document.forms["registroForm"]["telefono"].value;
    let fecha_nacimiento = document.forms["registroForm"]["fecha_nacimiento"].value;
    let domicilio1 = document.forms["registroForm"]["domicilio1"].value;
    let referencia1 = document.forms["registroForm"]["referencia1"].value;
    let domicilio2 = document.forms["registroForm"]["domicilio2"].value;
    let referencia2 = document.forms["registroForm"]["referencia2"].value;
    let ocupacion = document.forms["registroForm"]["ocupacion"].value;
    let clasificacion_riesgo = document.forms["registroForm"]["clasificacion_riesgo"].value;
    let agencia = document.forms["registroForm"]["agencia"].value;
    let tipo_credito = document.forms["registroForm"]["tipo_credito"].value;
    let estado = document.forms["registroForm"]["estado"].value;
    let fecha_desembolso = document.forms["registroForm"]["fecha_desembolso"].value;
    let fecha_vencimiento = document.forms["registroForm"]["fecha_vencimiento"].value;
    let monto = document.forms["registroForm"]["monto"].value;
    let saldo = document.forms["registroForm"]["saldo"].value;
    // dato garante
    let nombre_garante = document.forms["registroForm"]["nombre_garante"].value;
    let apellidos_garante = document.forms["registroForm"]["apellidos_garante"].value;
    let dni_garante = document.forms["registroForm"]["dni_garante"].value;
    let telefono_garante = document.forms["registroForm"]["telefono_garante"].value;
    let fecha_nacimiento_garante = document.forms["registroForm"]["fecha_nacimiento_garante"].value;
    let domicilio1_garante = document.forms["registroForm"]["domicilio1_garante"].value;
    let referencia1_garante = document.forms["registroForm"]["referencia1_garante"].value;
    let domicilio2_garante = document.forms["registroForm"]["domicilio2_garante"].value;
    let referencia2_garante = document.forms["registroForm"]["referencia2_garante"].value;
    let ocupacion_garante = document.forms["registroForm"]["ocupacion_garante"].value;
    let clasificacion_riesgo_garante = document.forms["registroForm"]["clasificacion_riesgo_garante"].value;
    // dato garante 2
    let nombre_garante_2 = document.forms["registroForm"]["nombre_garante_2"].value;
    let apellidos_garante_2 = document.forms["registroForm"]["apellidos_garante_2"].value;
    let dni_garante_2 = document.forms["registroForm"]["dni_garante_2"].value;
    let telefono_garante_2 = document.forms["registroForm"]["telefono_garante_2"].value;
    let fecha_nacimiento_garante_2 = document.forms["registroForm"]["fecha_nacimiento_garante_2"].value;
    let domicilio1_garante_2 = document.forms["registroForm"]["domicilio1_garante_2"].value;
    let referencia1_garante_2 = document.forms["registroForm"]["referencia1_garante_2"].value;
    let domicilio2_garante_2 = document.forms["registroForm"]["domicilio2_garante_2"].value;
    let referencia2_garante_2 = document.forms["registroForm"]["referencia2_garante_2"].value;
    let ocupacion_garante_2 = document.forms["registroForm"]["ocupacion_garante_2"].value;
    let clasificacion_riesgo_garante_2 = document.forms["registroForm"]["clasificacion_riesgo_garante_2"].value;
    // dato garante 3
    let nombre_garante_3 = document.forms["registroForm"]["nombre_garante_3"].value;
    let apellidos_garante_3 = document.forms["registroForm"]["apellidos_garante_3"].value;
    let dni_garante_3 = document.forms["registroForm"]["dni_garante_3"].value;
    let telefono_garante_3 = document.forms["registroForm"]["telefono_garante_3"].value;
    let fecha_nacimiento_garante_3 = document.forms["registroForm"]["fecha_nacimiento_garante_3"].value;
    let domicilio1_garante_3 = document.forms["registroForm"]["domicilio1_garante_3"].value;
    let referencia1_garante_3 = document.forms["registroForm"]["referencia1_garante_3"].value;
    let domicilio2_garante_3 = document.forms["registroForm"]["domicilio2_garante_3"].value;
    let referencia2_garante_3 = document.forms["registroForm"]["referencia2_garante_3"].value;
    let ocupacion_garante_3 = document.forms["registroForm"]["ocupacion_garante_3"].value;
    let clasificacion_riesgo_garante_3 = document.forms["registroForm"]["clasificacion_riesgo_garante_3"].value;

    // Fecha Programada
    let fecha_clave = document.forms["registroForm"]["fecha_clave"].value;
    let accion_fecha_clave = document.forms["registroForm"]["accion_fecha_clave"].value;
    // dato personal asignado
    let analista = document.forms["registroForm"]["analista"].value;
    let gestor = document.forms["registroForm"]["gestor"].value;
    let supervisor = document.forms["registroForm"]["supervisor"].value;
    let administrador = document.forms["registroForm"]["administrador"].value;

    // Expresiones regulares
    let regexTexto = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/;
    let regexDNI = /^\d{8}$/;
    let regexTelefono = /^9\d{8}$/;
    let regexDireccion = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ][a-zA-ZáéíóúÁÉÍÓÚñÑ0-9 .,"'() ]*$/;
    let regexFechaNacimiento = /^\d{4}-\d{2}-\d{2}$/;
    let regexNumeral = /^\d{1}$/;

    // Validaciones del cliente
    if (!regexTexto.test(nombre)) {
        alert("Nombre del cliente inválido. Debe contener solo letras y espacios."); return false;
    }
    if (!regexTexto.test(apellidos)) {
        alert("Apellidos del cliente inválido. Debe contener solo letras y espacios."); return false;
    }
    if (!regexDNI.test(dni)) {
        alert("DNI del cliente inválido"); return false;
    }
    if (!regexTelefono.test(telefono)) {
        alert("Teléfono del cliente inválido solo se aseptan numeros de 0 al 9"); return false;
    }
    let fechaNa = new Date(fecha_nacimiento);
    let hoyy = new Date();
    let edadCliente = hoyy.getFullYear() - fechaNa.getFullYear();
    let n = hoyy.getMonth() - fechaNa.getMonth();
    if (n < 0 || (n === 0 && hoyy.getDate() < fechaNa.getDate())) {
        edadCliente--;
    }
    if (edadCliente < 18) {
        alert("El cliente es menor de edad"); return false;
    }
    if (!regexFechaNacimiento.test(fecha_nacimiento)) {
        alert("Fecha de Nacimiento inválida del cliente"); return false;
    }
    if (!regexDireccion.test(domicilio1)) {
        alert("Domicilio 1 del cliente inválido. No comenzar con numero o algún caracter"); return false;
    }
    if (!regexDireccion.test(referencia1)) {
        alert("Referencia 1 del cliente inválido. NNo comenzar con numero o algún caracter"); return false;
    }
    if (domicilio2 && !regexDireccion.test(domicilio2)) {
        alert("Domicilio 2 del cliente inválido. No comenzar con numero o algún caracter"); return false;
    }
    if (referencia2 && !regexDireccion.test(referencia2)) {
        alert("Referencia 2 del cliente inválido. No comenzar con numero o algún caracter"); return false;
    }
    if (!regexTexto.test(ocupacion)) {
        alert("Ocupación del cliente inválida. Debe contener solo letras y espacios."); return false;
    }
    if (!regexTexto.test(agencia)) {
        alert("Agencia dato inválido. Debe contener solo letras y espacios."); return false;
    }
    if (!regexTexto.test(tipo_credito)) {
        alert("Tipo de credito dato inválido. Debe contener solo letras y espacios."); return false;
    }
    if (!regexTexto.test(estado)) {
        alert("Estado dato inválido. Debe contener solo letras y espacios."); return false;
    }
    if (isNaN(monto) || parseFloat(monto) <= 0) {
        alert("Monto inválido"); return false;
    }
    if (isNaN(saldo) || parseFloat(saldo) <= 0) {
        alert("Saldo inválido"); return false;
    }

    // Validaciones del garante 1
    if (nombre_garante && !regexTexto.test(nombre_garante)) {
        alert("Nombre del garante inválido. Debe contener solo letras y espacios."); return false;
    }
    if (apellidos_garante && !regexTexto.test(apellidos_garante)) {
        alert("Apellidos del garante inválido. Debe contener solo letras y espacios."); return false;
    }
    if (dni_garante && !regexDNI.test(dni_garante)) {
        alert("DNI del garante inválido"); return false;
    }
    if (telefono_garante && !regexTelefono.test(telefono_garante)) {
        alert("Teléfono del garante inválido, solo se aseptan números de 0 al 9"); return false;
    }

    // Validación opcional para fecha de nacimiento
    if (fecha_nacimiento_garante) {
        if (!regexFechaNacimiento.test(fecha_nacimiento_garante)) {
            alert("Fecha de Nacimiento inválida del garante"); return false;
        }
        let fechaNacimiento = new Date(fecha_nacimiento_garante);
        let hoy = new Date();
        let edadGarante = hoy.getFullYear() - fechaNacimiento.getFullYear();
        let m = hoy.getMonth() - fechaNacimiento.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
            edadGarante--;
        }
        if (edadGarante < 18) {
            alert("El garante es menor de edad"); return false;
        }
    }

    if (domicilio1_garante && !regexDireccion.test(domicilio1_garante)) {
        alert("Domicilio 1 del garante inválido. No comenzar con número o algún carácter"); return false;
    }
    if (referencia1_garante && !regexDireccion.test(referencia1_garante)) {
        alert("Referencia 1 del garante inválido. No comenzar con número o algún carácter"); return false;
    }
    if (domicilio2_garante && !regexDireccion.test(domicilio2_garante)) {
        alert("Domicilio 2 del garante inválido. No comenzar con número o algún carácter"); return false;
    }
    if (referencia2_garante && !regexDireccion.test(referencia2_garante)) {
        alert("Referencia 2 del garante inválido. No comenzar con número o algún carácter"); return false;
    }
    if (ocupacion_garante && !regexTexto.test(ocupacion_garante)) {
        alert("Ocupación del garante inválida. Debe contener solo letras y espacios."); return false;
    }

    // Validaciones del garante 2
    if (nombre_garante_2 && !regexTexto.test(nombre_garante_2)) {
        alert("Nombre del garante 2 inválido. Debe contener solo letras y espacios."); return false;
    }
    if (apellidos_garante_2 && !regexTexto.test(apellidos_garante_2)) {
        alert("Apellidos del garante 2 inválido. Debe contener solo letras y espacios."); return false;
    }
    if (dni_garante_2 && !regexDNI.test(dni_garante_2)) {
        alert("DNI del garante 2 inválido"); return false;
    }
    if (telefono_garante_2 && !regexTelefono.test(telefono_garante_2)) {
        alert("Teléfono del garante 2 inválido, solo se aceptan números de 0 al 9"); return false;
    }

    if (fecha_nacimiento_garante_2) {
        if (!regexFechaNacimiento.test(fecha_nacimiento_garante_2)) {
            alert("Fecha de Nacimiento inválida del garante 2"); return false;
        }
        let fechaNacimiento2 = new Date(fecha_nacimiento_garante_2);
        let hoy2 = new Date();
        let edadGarante2 = hoy2.getFullYear() - fechaNacimiento2.getFullYear();
        let m2 = hoy2.getMonth() - fechaNacimiento2.getMonth();
        if (m2 < 0 || (m2 === 0 && hoy2.getDate() < fechaNacimiento2.getDate())) {
            edadGarante2--;
        }
        if (edadGarante2 < 18) {
            alert("El garante 2 es menor de edad"); return false;
        }
    }

    if (domicilio1_garante_2 && !regexDireccion.test(domicilio1_garante_2)) {
        alert("Domicilio 1 del garante 2 inválido. No comenzar con número o carácter especial"); return false;
    }
    if (referencia1_garante_2 && !regexDireccion.test(referencia1_garante_2)) {
        alert("Referencia 1 del garante 2 inválida. No comenzar con número o carácter especial"); return false;
    }
    if (domicilio2_garante_2 && !regexDireccion.test(domicilio2_garante_2)) {
        alert("Domicilio 2 del garante 2 inválido. No comenzar con número o carácter especial"); return false;
    }
    if (referencia2_garante_2 && !regexDireccion.test(referencia2_garante_2)) {
        alert("Referencia 2 del garante 2 inválida. No comenzar con número o carácter especial"); return false;
    }
    if (ocupacion_garante_2 && !regexTexto.test(ocupacion_garante_2)) {
        alert("Ocupación del garante 2 inválida. Debe contener solo letras y espacios."); return false;
    }

    // Validaciones del garante 3
    if (nombre_garante_3 && !regexTexto.test(nombre_garante_3)) {
        alert("Nombre del garante 3 inválido. Debe contener solo letras y espacios."); return false;
    }
    if (apellidos_garante_3 && !regexTexto.test(apellidos_garante_3)) {
        alert("Apellidos del garante 3 inválido. Debe contener solo letras y espacios."); return false;
    }
    if (dni_garante_3 && !regexDNI.test(dni_garante_3)) {
        alert("DNI del garante 3 inválido"); return false;
    }
    if (telefono_garante_3 && !regexTelefono.test(telefono_garante_3)) {
        alert("Teléfono del garante 3 inválido, solo se aceptan números de 0 al 9"); return false;
    }

    if (fecha_nacimiento_garante_3) {
        if (!regexFechaNacimiento.test(fecha_nacimiento_garante_3)) {
            alert("Fecha de Nacimiento inválida del garante 3"); return false;
        }
        let fechaNacimiento3 = new Date(fecha_nacimiento_garante_3);
        let hoy3 = new Date();
        let edadGarante3 = hoy3.getFullYear() - fechaNacimiento3.getFullYear();
        let m3 = hoy3.getMonth() - fechaNacimiento3.getMonth();
        if (m3 < 0 || (m3 === 0 && hoy3.getDate() < fechaNacimiento3.getDate())) {
            edadGarante3--;
        }
        if (edadGarante3 < 18) {
            alert("El garante 3 es menor de edad"); return false;
        }
    }

    if (domicilio1_garante_3 && !regexDireccion.test(domicilio1_garante_3)) {
        alert("Domicilio 1 del garante 3 inválido. No comenzar con número o carácter especial"); return false;
    }
    if (referencia1_garante_3 && !regexDireccion.test(referencia1_garante_3)) {
        alert("Referencia 1 del garante 3 inválida. No comenzar con número o carácter especial"); return false;
    }
    if (domicilio2_garante_3 && !regexDireccion.test(domicilio2_garante_3)) {
        alert("Domicilio 2 del garante 3 inválido. No comenzar con número o carácter especial"); return false;
    }
    if (referencia2_garante_3 && !regexDireccion.test(referencia2_garante_3)) {
        alert("Referencia 2 del garante 3 inválida. No comenzar con número o carácter especial"); return false;
    }
    if (ocupacion_garante_3 && !regexTexto.test(ocupacion_garante_3)) {
        alert("Ocupación del garante 3 inválida. Debe contener solo letras y espacios."); return false;
    }

    // Fecha Programada
    if (!regexTexto.test(accion_fecha_clave)) {
        alert("Accion de fecha clave inválida. Debe contener solo letras y espacios."); return false;
    }

    // Valores para personal Asignado
    if (!regexNumeral.test(analista)) {
        alert("Máximo hasta 9"); return false;
    }
    if (!regexNumeral.test(gestor)) {
        alert("Máximo hasta 9"); return false;
    }
    if (!regexNumeral.test(supervisor)) {
        alert("Máximo hasta 9"); return false;
    }
    if (!regexNumeral.test(administrador)) {
        alert("Máximo hasta 9"); return false;
    }

    return true;
}
function mostrarGarante(numero) {
    const divActual = document.getElementById("garante" + numero);
    const divAnterior = numero > 1 ? document.getElementById("garante" + (numero - 1)) : null;

    if (divActual) {
        divActual.style.display = "block";
    }

    // Si estamos mostrando el primer garante, ocultamos el botón inicial
    if (numero === 1) {
        const botonInicial = document.getElementById("boton-agregar-garante1");
        if (botonInicial) {
            botonInicial.style.display = "none";
        }
    }

    // Ocultar botones del garante anterior (si existe)
    if (divAnterior) {
        const botonAgregarAnterior = divAnterior.querySelector(".btn.btn-primarya");
        if (botonAgregarAnterior) {
            botonAgregarAnterior.style.display = "none";
        }

        const botonCancelarAnterior = divAnterior.querySelector(".btn.btn-danger");
        if (botonCancelarAnterior) {
            botonCancelarAnterior.style.display = "none";
        }
    }
}

function cancelarGarante(numero) {
    const divActual = document.getElementById("garante" + numero);
    const divAnterior = numero > 1 ? document.getElementById("garante" + (numero - 1)) : null;

    if (divActual) {
        divActual.style.display = "none";

        // Limpiar los campos del garante cancelado
        const inputs = divActual.querySelectorAll("input, select");
        inputs.forEach(input => {
            if (input.tagName === "SELECT") {
                input.selectedIndex = 0;
            } else {
                input.value = "";
            }
        });
    }

    // Si estamos cancelando el primer garante, volver a mostrar el botón inicial
    if (numero === 1) {
        const botonInicial = document.getElementById("boton-agregar-garante1");
        if (botonInicial) {
            botonInicial.style.display = "block";
        }
    }

    // Mostrar botones del garante anterior (si existe)
    if (divAnterior) {
        const botonAgregarAnterior = divAnterior.querySelector(".btn.btn-primarya");
        if (botonAgregarAnterior) {
            botonAgregarAnterior.style.display = "inline-block";
        }

        const botonCancelarAnterior = divAnterior.querySelector(".btn.btn-danger");
        if (botonCancelarAnterior) {
            botonCancelarAnterior.style.display = "inline-block";
        }
    }
}

/* de aqui para departamento provincia  */
let provincias = {};
let distritos = {};

// Cargar departamentos
fetch('ubigeo_api/data/departamentos.json')
    .then(res => res.json())
    .then(data => {
        const departamentoSelect = document.getElementById('departamento');
        data.forEach(dep => {
            departamentoSelect.innerHTML += `<option value="${dep.id}">${dep.nombre}</option>`;
        });
    });

// Cargar provincias y distritos
fetch('ubigeo_api/data/provincias.json')
    .then(res => res.json())
    .then(data => provincias = data);

fetch('ubigeo_api/data/distritos.json')
    .then(res => res.json())
    .then(data => distritos = data);

// Evento para provincias
document.getElementById('departamento').addEventListener('change', function () {
    const provSelect = document.getElementById('provincia');
    const distSelect = document.getElementById('distrito');
    const depID = this.value;

    // Limpiar selects
    provSelect.innerHTML = '<option value="" disabled selected>Seleccione una provincia</option>';
    distSelect.innerHTML = '<option value="" disabled selected>Seleccione un distrito</option>';
    distSelect.disabled = true;

    // Llenar provincias
    if (provincias[depID]) {
        provincias[depID].forEach(prov => {
            provSelect.innerHTML += `<option value="${prov.id}">${prov.nombre}</option>`;
        });
        provSelect.disabled = false;
    } else {
        provSelect.disabled = true;
    }
});

// Evento para distritos
document.getElementById('provincia').addEventListener('change', function () {
    const distSelect = document.getElementById('distrito');
    const provID = this.value;

    distSelect.innerHTML = '<option value="" disabled selected>Seleccione un distrito</option>';

    if (distritos[provID]) {
        distritos[provID].forEach(dist => {
            distSelect.innerHTML += `<option value="${dist.id}">${dist.nombre}</option>`;
        });
        distSelect.disabled = false;
    } else {
        distSelect.disabled = true;
    }
});

/* esta parte ultimo */
function cerrarRegistro() {
    document.getElementById('registroCliente').style.display = 'none';
    document.getElementById('registroContent').innerHTML = '';

    window.location.href = "../../../InterfazLogin/home.php";
}