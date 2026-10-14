// Calculadora de la presentación.
// La tarifa y el factor de CO2 vienen del servidor (.env), los mismos que usa el panel.

const calculadora = document.getElementById("calculadora");
const entradaKwh = document.getElementById("kwh");

if (calculadora && entradaKwh) {
  const tarifa = parseFloat(calculadora.dataset.tarifa) || 0;
  const factor = parseFloat(calculadora.dataset.factor) || 0;

  const actualizar = () => {
    const kwh = parseFloat(entradaKwh.value);
    document.getElementById("kwhVal").textContent = kwh + " kWh";
    document.getElementById("co2Val").textContent =
      (kwh * factor).toLocaleString("es-AR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " kg";
    document.getElementById("costVal").textContent =
      tarifa > 0 ? "$ " + Math.round(kwh * tarifa).toLocaleString("es-AR") : "Tarifa sin configurar";
  };

  entradaKwh.addEventListener("input", actualizar);
  actualizar();
}
