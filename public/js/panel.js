// "Mis dispositivos": refresca cada 5 s el estado y la potencia de cada equipo de la lista.

const BASE_PANEL = document.body.dataset.base || "";

async function actualizarLista() {
  try {
    const respuesta = await fetch(BASE_PANEL + "/api/dispositivos", { credentials: "same-origin" });
    if (!respuesta.ok) return;
    const datos = await respuesta.json();

    datos.dispositivos.forEach((equipo) => {
      const tarjeta = document.querySelector('[data-id="' + CSS.escape(equipo.id) + '"]');
      if (!tarjeta) return;

      tarjeta.querySelector('[data-campo="estado"]').className = "estado " + (equipo.en_linea ? "estado-verde" : "estado-rojo");
      tarjeta.querySelector('[data-campo="estado-texto"]').textContent = equipo.en_linea ? "EN LÍNEA" : "DESCONECTADO";
      tarjeta.querySelector('[data-campo="potencia"]').textContent =
        equipo.potencia === null ? "--" : Number(equipo.potencia).toLocaleString("es-AR", { minimumFractionDigits: 1, maximumFractionDigits: 1 });
      tarjeta.querySelector('[data-campo="tomas"]').textContent =
        equipo.tomas_encendidas + " de " + equipo.tomas + " tomas encendidas";
      tarjeta.querySelector('[data-campo="alerta"]').textContent = equipo.sobreconsumo
        ? "Corte por sobreconsumo"
        : equipo.alerta || "Sin lecturas";
    });
  } catch (error) {
    // Sin conexión: se mantiene lo último mostrado
  }
}

setInterval(actualizarLista, 5000);
