// Panel de un equipo: consulta la API y actualiza la pantalla sin recargar.
//   cada 2 s  -> /api/dispositivos/{id}/ultima    (métricas, conexión, avisos)
//   cada 3 s  -> /api/dispositivos/{id}/reles     (interruptores)
//   cada 15 s -> /api/dispositivos/{id}/eventos   (actividad)
//   cada 60 s -> /api/dispositivos/{id}/historial (gráfico y consumo vampiro)

const BASE = document.body.dataset.base || "";
const ID = document.getElementById("panel-dispositivo").dataset.id;
const API = BASE + "/api/dispositivos/" + encodeURIComponent(ID);

let hayCorteActivo = false;
const ocupado = { resumen: false, reles: false, historial: false, eventos: false };
let releEnviando = false;

document.addEventListener("DOMContentLoaded", () => {
  actualizarResumen();
  actualizarReles();
  actualizarHistorial();
  actualizarEventos();

  setInterval(actualizarResumen, 2000);
  setInterval(actualizarReles, 3000);
  setInterval(actualizarEventos, 15000);
  setInterval(actualizarHistorial, 60000);

  document.querySelectorAll(".interruptor").forEach((interruptor) => {
    interruptor.addEventListener("change", () => cambiarRele(interruptor));
  });
});

// ---------- Utilidades ----------

async function pedirJson(ruta, opciones = {}) {
  const respuesta = await fetch(API + ruta, { credentials: "same-origin", ...opciones });
  if (respuesta.status === 401) {
    // La sesión venció: volver a ingresar
    window.location.href = BASE + "/login?volver=" + encodeURIComponent(window.location.pathname);
  }
  const datos = await respuesta.json().catch(() => ({}));
  return { status: respuesta.status, datos };
}

function mostrar(id, visible, texto) {
  const elemento = document.getElementById(id);
  if (!elemento) return;
  elemento.classList.toggle("oculto", !visible);
  if (texto !== undefined) elemento.textContent = texto;
}

function escribir(id, valor, decimales) {
  const elemento = document.getElementById(id);
  if (!elemento) return;
  elemento.textContent =
    valor === null || valor === undefined
      ? "--"
      : Number(valor).toLocaleString("es-AR", { minimumFractionDigits: decimales, maximumFractionDigits: decimales });
}

function horaDe(timestampMs) {
  return new Date(timestampMs).toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
}

function fechaHoraDe(timestampMs) {
  return new Date(timestampMs).toLocaleString("es-AR", { day: "2-digit", month: "2-digit", hour: "2-digit", minute: "2-digit" });
}

function textoSeguro(texto) {
  const div = document.createElement("div");
  div.textContent = texto;
  return div.innerHTML;
}

// ---------- Resumen: métricas, conexión y avisos ----------

async function actualizarResumen() {
  if (ocupado.resumen) return; // no acumular pedidos si el anterior sigue en curso
  ocupado.resumen = true;

  try {
    const { status, datos } = await pedirJson("/ultima");
    if (status !== 200 || !datos.ok) throw new Error(datos.error || "HTTP " + status);

    hayCorteActivo = datos.sobreconsumo;
    pintarConexion(datos.en_linea ? "verde" : "rojo", datos.en_linea ? "EN LÍNEA" : "DESCONECTADO");

    const lectura = datos.lectura || {};
    escribir("val-potencia", lectura.potencia, 1);
    escribir("val-voltaje", lectura.voltaje, 0);
    escribir("val-corriente", lectura.corriente, 2);
    escribir("val-energia", lectura.energia_kwh, 3);
    escribir("val-costo", datos.costo_pesos, 2);
    escribir("val-co2", datos.co2_kg, 2);

    document.getElementById("metricas").classList.toggle("desactualizado", !datos.en_linea);
    document.getElementById("tarjeta-potencia").classList.toggle("tarjeta-alerta", datos.alerta === "Sobreconsumo");

    mostrar("aviso-sobreconsumo", datos.sobreconsumo);
    mostrar("aviso-sensor", datos.error_sensor);
    mostrar("aviso-fantasma", Boolean(lectura.consumo_fantasma) && datos.en_linea);
    mostrar(
      "aviso-alerta",
      Boolean(datos.alerta) && datos.alerta !== "Normal" && datos.alerta !== "Sobreconsumo",
      "Alerta: " + datos.alerta
    );

    let mensaje = datos.mensaje;
    if (!datos.en_linea && lectura.timestamp) {
      mensaje = "El equipo está desconectado. Última lectura: " + fechaHoraDe(lectura.timestamp) + ".";
    }
    mostrar("aviso-general", Boolean(mensaje), mensaje || "");
  } catch (error) {
    pintarConexion("rojo", "SIN SERVIDOR");
    document.getElementById("metricas").classList.add("desactualizado");
  } finally {
    ocupado.resumen = false;
  }
}

function pintarConexion(color, texto) {
  document.getElementById("estado-conexion").className = "estado estado-" + color;
  document.getElementById("estado-texto").textContent = texto;
}

// ---------- Relés ----------

async function actualizarReles() {
  if (ocupado.reles || releEnviando) return;
  ocupado.reles = true;

  try {
    const { status, datos } = await pedirJson("/reles");
    if (status !== 200 || !datos.ok) throw new Error(datos.error);
    Object.entries(datos.reles).forEach(([clave, estado]) => pintarRele(clave.replace("rele_", ""), estado));
  } catch (error) {
    // Se mantiene lo último mostrado; el estado de conexión ya avisa el problema.
  } finally {
    ocupado.reles = false;
  }
}

function pintarRele(numero, estado) {
  const interruptor = document.getElementById("rele-" + numero);
  const etiqueta = document.getElementById("rele-estado-" + numero);
  if (!interruptor || !etiqueta) return;

  const encendido = Number(estado) === 1;
  interruptor.checked = encendido;
  etiqueta.textContent = encendido ? "Encendido" : "Apagado";
  etiqueta.classList.toggle("encendido", encendido);
}

async function cambiarRele(interruptor) {
  const numero = Number(interruptor.dataset.rele);
  const estado = interruptor.checked ? 1 : 0;
  const mensaje = document.getElementById("reles-mensaje");

  // Encender después de un corte por sobreconsumo pide confirmación
  if (estado === 1 && hayCorteActivo) {
    const confirma = window.confirm("Hubo un corte por sobreconsumo. ¿Confirmás que querés volver a encender?");
    if (!confirma) {
      pintarRele(numero, 0);
      return;
    }
  }

  releEnviando = true;
  interruptor.disabled = true; // evita clics dobles
  mensaje.textContent = "Enviando orden…";

  try {
    const { status, datos } = await pedirJson("/reles", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ rele: numero, estado: estado }),
    });
    if (status !== 200 || !datos.ok) throw new Error(datos.error || "HTTP " + status);

    pintarRele(numero, estado);
    if (estado === 1) hayCorteActivo = false;
    mensaje.textContent = "Orden enviada. El equipo la aplica en unos segundos.";
    actualizarEventos();
  } catch (error) {
    pintarRele(numero, estado ? 0 : 1); // vuelve al estado anterior
    mensaje.textContent = "No se pudo cambiar: " + error.message;
  } finally {
    interruptor.disabled = false;
    releEnviando = false;
  }
}

// ---------- Actividad ----------

async function actualizarEventos() {
  if (ocupado.eventos) return;
  ocupado.eventos = true;
  const tabla = document.getElementById("tabla-eventos");

  try {
    const { status, datos } = await pedirJson("/eventos");
    if (status !== 200 || !datos.ok) throw new Error(datos.error);

    if (datos.eventos.length === 0) {
      tabla.innerHTML = '<tr><td colspan="3" class="texto-suave">Todavía no hay actividad.</td></tr>';
      return;
    }
    tabla.innerHTML = datos.eventos
      .map(
        (e) =>
          '<tr><td class="mono">' + fechaHoraDe(e.timestamp) + "</td><td>" + textoSeguro(e.nombre_usuario) +
          "</td><td>" + textoSeguro(e.detalle) + "</td></tr>"
      )
      .join("");
  } catch (error) {
    tabla.innerHTML = '<tr><td colspan="3" class="texto-suave">No se pudo cargar la actividad.</td></tr>';
  } finally {
    ocupado.eventos = false;
  }
}

// ---------- Historial ----------

async function actualizarHistorial() {
  if (ocupado.historial) return;
  ocupado.historial = true;
  const contenedor = document.getElementById("grafico");

  try {
    const { status, datos } = await pedirJson("/historial?limite=120");
    if (status !== 200 || !datos.ok) throw new Error(datos.error);

    const vampiro = datos.consumo_vampiro || {};
    mostrar(
      "aviso-vampiro",
      Boolean(vampiro.detectado),
      vampiro.detectado
        ? "Consumo vampiro detectado: " + vampiro.minutos + " minutos en espera, " +
            vampiro.kwh_desperdiciados + " kWh desperdiciados."
        : ""
    );

    if (!datos.mediciones || datos.mediciones.length < 2) {
      contenedor.innerHTML =
        '<p class="texto-suave">' + (datos.mensaje || "Todavía no hay datos de historial para mostrar") + "</p>";
      return;
    }
    contenedor.innerHTML = dibujarGrafico(datos.mediciones);
  } catch (error) {
    contenedor.innerHTML = '<p class="texto-suave">No se pudo cargar el historial.</p>';
  } finally {
    ocupado.historial = false;
  }
}

// Dibuja un gráfico de líneas simple en SVG (sin librerías, funciona sin internet).
function dibujarGrafico(mediciones) {
  const ancho = 640, alto = 220, izq = 48, der = 12, arriba = 12, abajo = 28;
  const tiempos = mediciones.map((m) => m.timestamp);
  const potencias = mediciones.map((m) => m.potencia);
  const tMin = Math.min(...tiempos), tMax = Math.max(...tiempos);
  const pMax = Math.max(10, ...potencias) * 1.1;

  const x = (t) => izq + ((t - tMin) / Math.max(1, tMax - tMin)) * (ancho - izq - der);
  const y = (p) => arriba + (1 - p / pMax) * (alto - arriba - abajo);

  const puntos = mediciones.map((m) => x(m.timestamp).toFixed(1) + "," + y(m.potencia).toFixed(1)).join(" ");

  let lineasGuia = "";
  for (let i = 0; i <= 4; i++) {
    const valor = (pMax / 4) * i;
    lineasGuia +=
      '<line x1="' + izq + '" x2="' + (ancho - der) + '" y1="' + y(valor) + '" y2="' + y(valor) + '" class="guia"/>' +
      '<text x="' + (izq - 6) + '" y="' + (y(valor) + 4) + '" class="eje" text-anchor="end">' + Math.round(valor) + "</text>";
  }

  return (
    '<svg viewBox="0 0 ' + ancho + " " + alto + '" role="img" aria-label="Potencia en watts de las últimas lecturas">' +
    lineasGuia +
    '<polyline points="' + puntos + '" class="linea"/>' +
    '<text x="' + izq + '" y="' + (alto - 6) + '" class="eje">' + horaDe(tMin) + "</text>" +
    '<text x="' + (ancho - der) + '" y="' + (alto - 6) + '" class="eje" text-anchor="end">' + horaDe(tMax) + "</text>" +
    "</svg>"
  );
}
