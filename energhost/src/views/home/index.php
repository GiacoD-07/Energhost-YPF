<?php
// Presentación pública de Voltec Ergon / Energhost.
// Los números (factor de CO2, tarifa, umbral) vienen del .env a través del controller,
// así la presentación y el panel siempre muestran los mismos valores.

$numero = fn (float $valor, int $decimales) => number_format($valor, $decimales, ",", ".");
$heladeraKwhDia = 1.5;   // consumo típico de una heladera por día
$licuadoraKwhHora = 0.3; // una hora de licuadora
?>
<header>
  <div class="nav">
    <a class="brand" href="<?= $base ?>/">
      <div class="mark"></div>
      <div>VOLTEC ERGON<small>Consultora de energía IoT</small></div>
    </a>
    <nav class="nav-links">
      <a href="#producto">Producto</a>
      <a href="#funcionamiento">Cómo funciona</a>
      <a href="#impacto">Impacto ambiental</a>
      <a href="#equipo">Equipo</a>
      <a href="#cronograma">Cronograma</a>
    </nav>
    <div class="nav-cta">
      <?php if ($usuario): ?>
        <a href="<?= $base ?>/panel" class="btn btn-primary">Mi panel</a>
      <?php else: ?>
        <a href="<?= $base ?>/login" class="btn btn-ghost">Ingresar</a>
        <a href="<?= $base ?>/registro" class="btn btn-primary">Crear cuenta</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow">Eco Smart Grid + App Energhost</span>
      <h1>La energía que se<br>escapa, ahora <em>se ve</em>.</h1>
      <p class="lead">Voltec Ergon desarrolló un sistema de monitoreo eléctrico en tiempo real que detecta consumo vampiro, calcula tu huella de carbono y te deja apagar tus artefactos desde el celular — antes de que llegue la factura.</p>
      <div class="hero-actions">
        <?php if ($usuario): ?>
          <a href="<?= $base ?>/panel" class="btn btn-primary">Ir a mi panel</a>
        <?php else: ?>
          <a href="<?= $base ?>/registro" class="btn btn-primary">Crear mi cuenta</a>
        <?php endif; ?>
        <a href="#funcionamiento" class="btn btn-ghost">Cómo funciona →</a>
      </div>
      <div class="hero-stats">
        <div><div class="num">≤15 W</div><div class="lbl">Consumo en espera detectado</div></div>
        <div><div class="num"><?= $numero($factorCo2, 3) ?></div><div class="lbl">kg CO₂ por kWh (AR, oficial)</div></div>
        <div><div class="num">2 s</div><div class="lbl">Actualización de datos</div></div>
      </div>
    </div>

    <div class="dash-card">
      <div class="dash-top">
        <div class="name">EJEMPLO DE PROPUESTA · Cocina — Heladera</div>
        <div class="live"><span class="dot"></span>VISTA DEMO</div>
      </div>
      <div class="dash-metrics">
        <div class="metric"><div class="l">Voltaje</div><div class="v">219.6 <span>V</span></div></div>
        <div class="metric"><div class="l">Corriente</div><div class="v">0.68 <span>A</span></div></div>
        <div class="metric"><div class="l">Potencia</div><div class="v">148 <span>W</span></div></div>
        <div class="metric"><div class="l">Hoy</div><div class="v">1.5 <span>kWh</span></div></div>
      </div>
      <div class="dash-chart">
        <div class="l">Consumo — últimas 12 h</div>
        <svg viewBox="0 0 300 60" width="100%" height="60" preserveAspectRatio="none" aria-hidden="true">
          <polyline points="0,45 25,40 50,42 75,20 100,25 125,15 150,30 175,18 200,22 225,10 250,16 275,8 300,12"
            fill="none" stroke="#0FB88A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="dash-alert">
        <span>⚠️</span>
        <div><b>Ejemplo de alerta.</b> Esta visualización es una demostración y no está conectada a dispositivos reales.</div>
      </div>
    </div>
  </div>
</section>

<section class="bg-white">
  <div class="wrap problem-grid">
    <div>
      <div class="section-head" style="margin-bottom:34px;">
        <span class="eyebrow">El problema</span>
        <h2>Tu factura te dice cuánto pagaste. No te dice por qué.</h2>
        <p>Un número acumulado a fin de mes no permite identificar qué artefacto gasta de más, ni cuánto se pierde en aparatos que "están apagados" pero siguen consumiendo.</p>
      </div>
      <div class="factura">
        <div class="row"><span>Factura eléctrica — Agosto</span><span class="tag">Sin detalle</span></div>
        <div class="row"><span>Consumo total</span><span>312 kWh</span></div>
        <div class="row"><span>¿Qué artefacto consumió más?</span><span>—</span></div>
        <div class="row"><span>¿Cuánto se perdió en standby?</span><span>—</span></div>
        <div class="row total"><span>Total a pagar</span><span>$ 84.300</span></div>
      </div>
    </div>
    <div class="problem-list">
      <div class="problem-item">
        <div class="ico">01</div>
        <div><h4>Consumo vampiro invisible</h4><p>Dispositivos "apagados" que siguen tomando corriente de la red durante horas, todos los días del mes.</p></div>
      </div>
      <div class="problem-item">
        <div class="ico">02</div>
        <div><h4>Cero trazabilidad por artefacto</h4><p>La factura mensual agrupa todo el consumo del hogar en un solo número, sin desglose posible.</p></div>
      </div>
      <div class="problem-item">
        <div class="ico">03</div>
        <div><h4>Impacto ambiental invisible</h4><p>Nadie ve, en el momento, cuánto CO₂ genera dejar un electrodoméstico enchufado de más.</p></div>
      </div>
    </div>
  </div>
</section>

<section id="funcionamiento">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Arquitectura del sistema</span>
      <h2>Del enchufe a tu bolsillo, en segundos</h2>
      <p>El sensor mide, el ESP32 transmite, la nube sincroniza y Energhost se actualiza solo — sin que el usuario tenga que recargar nada.</p>
    </div>
    <div class="flow">
      <div class="flow-step">
        <div class="n">1</div>
        <h4>Sensor PZEM-004T</h4>
        <p>Mide voltaje, corriente, potencia y energía acumulada de lo que está enchufado en las 3 tomas, con aislamiento galvánico.</p>
      </div>
      <div class="flow-step">
        <div class="n">2</div>
        <h4>Microcontrolador ESP32</h4>
        <p>Procesa las lecturas y las sube por Wi-Fi a Firebase cada 2 segundos. Si el consumo supera <?= $numero($umbralSobreconsumo, 0) ?> W, corta las 3 tomas por su cuenta.</p>
      </div>
      <div class="flow-step">
        <div class="n">3</div>
        <h4>Firebase</h4>
        <p>Guarda la lectura actual, el historial minuto a minuto y el estado de cada equipo, sincronizados en tiempo real.</p>
      </div>
      <div class="flow-step">
        <div class="n">4</div>
        <h4>Panel Energhost</h4>
        <p>Cada usuario ve solo sus equipos. El panel se actualiza solo y se puede instalar en el celular.</p>
      </div>
      <div class="flow-step">
        <div class="n">5</div>
        <h4>Control remoto</h4>
        <p>El usuario apaga cualquiera de las 3 tomas desde el panel; el ESP32 recibe la orden y corta esa toma en segundos.</p>
      </div>
    </div>
  </div>
</section>

<section id="producto" class="bg-white">
  <div class="wrap product-grid">
    <div class="device-visual">
      <div class="ring r1"></div>
      <div class="ring r2"></div>
      <div class="core"><span>ESP32</span></div>
      <div class="float-tag t1">PZEM-004T</div>
      <div class="float-tag t2">Relé 220V</div>
      <div class="float-tag t3">OLED 0.96"</div>
    </div>
    <div>
      <span class="eyebrow">Hardware</span>
      <h2 style="font-size:32px;margin:14px 0 14px;">Eco Smart Grid</h2>
      <p style="color:var(--ink-soft);font-size:15.5px;">Un módulo inteligente con 3 tomas controladas: mide, decide y corta el suministro, con carcasa propia impresa en 3D y pantalla local de diagnóstico.</p>
      <div class="spec-list">
        <div class="spec"><div class="k">Microcontrolador</div><div class="v">ESP32, Wi-Fi nativo</div></div>
        <div class="spec"><div class="k">Sensor</div><div class="v">PZEM-004T V3.0</div></div>
        <div class="spec"><div class="k">Actuador</div><div class="v">Módulo de 3 relés (uno por toma)</div></div>
        <div class="spec"><div class="k">Pantalla local</div><div class="v">OLED 0.96"</div></div>
        <div class="spec"><div class="k">Carcasa</div><div class="v">Diseño CAD, impresión 3D</div></div>
        <div class="spec"><div class="k">Identificación</div><div class="v">Fichas intercambiables multicolor</div></div>
        <div class="spec"><div class="k">Vinculación</div><div class="v">Código único con QR en la etiqueta</div></div>
        <div class="spec"><div class="k">Protección</div><div class="v">Corte automático sobre <?= $numero($umbralSobreconsumo, 0) ?> W</div></div>
      </div>
    </div>
  </div>
</section>

<section id="impacto" class="bg-navy">
  <div class="wrap impact-grid">
    <div class="impact-copy">
      <span class="eyebrow">Huella de carbono</span>
      <h2 style="font-size:32px;margin:14px 0 14px;">Cada kWh que ahorrás, se traduce en CO₂ que no emitís</h2>
      <p>Energhost calcula tu huella de carbono con el factor de emisión oficial del consumo de electricidad de la red argentina: <?= $numero($factorCo2, 3) ?> kg de CO₂ por cada kWh consumido.</p>
      <div class="stat-row">
        <div><div class="num"><?= $numero($heladeraKwhDia * $factorCo2, 2) ?> kg</div><div class="lbl">CO₂/día — heladera típica</div></div>
        <div><div class="num"><?= $numero($licuadoraKwhHora * $factorCo2, 2) ?> kg</div><div class="lbl">CO₂ — 1h de licuadora</div></div>
      </div>
      <p class="fuente">Fuente: <a href="https://inventariogei.ambiente.gob.ar/files/Factores_de_Emision_SNIGEIAR_para_HC_2025.pdf" target="_blank" rel="noopener">Factores de Emisión SNIGEI-AR</a>, Inventario Nacional de Gases de Efecto Invernadero.</p>
    </div>
    <div class="calc" id="calculadora" data-tarifa="<?= htmlspecialchars((string) $tarifaKwh) ?>" data-factor="<?= htmlspecialchars((string) $factorCo2) ?>">
      <label for="kwh">Simulá tu consumo (kWh este mes)</label>
      <input type="range" id="kwh" min="10" max="500" value="150">
      <div class="val-row"><span>Consumo estimado</span><b id="kwhVal">150 kWh</b></div>
      <div class="calc-result">
        <div class="box"><div class="l">Huella de carbono</div><div class="v" id="co2Val">—</div></div>
        <div class="box amber"><div class="l">Costo estimado</div><div class="v" id="costVal">—</div></div>
      </div>
      <p class="nota">Costo calculado con una tarifa de $ <?= $numero($tarifaKwh, 2) ?> por kWh.</p>
    </div>
  </div>
</section>

<section id="empezar">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Cómo empezar</span>
      <h2>Tres pasos para ver tu consumo</h2>
    </div>
    <div class="steps">
      <div class="step-card">
        <div class="n">01</div>
        <h3>Creá tu cuenta</h3>
        <p>Registrate con tu nombre y tu email. Así cada persona ve y controla solo sus equipos.</p>
      </div>
      <div class="step-card">
        <div class="n">02</div>
        <h3>Enchufá el equipo</h3>
        <p>La primera vez crea la red Wi-Fi "Energhost-Config": conectate desde el celular y elegí la red del lugar.</p>
      </div>
      <div class="step-card">
        <div class="n">03</div>
        <h3>Vinculalo a tu cuenta</h3>
        <p>Escaneá el QR de la etiqueta o escribí el código del equipo, y anotá qué artefacto enchufaste en cada toma.</p>
      </div>
    </div>
  </div>
</section>

<section id="equipo" class="bg-white">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Organización</span>
      <h2>Tres comisiones, un mismo sistema</h2>
      <p>Cuatro integrantes divididos por especialidad técnica, coordinados desde la consultora Voltec Ergon.</p>
    </div>
    <div class="team-grid">
      <div class="team-card">
        <span class="tag">Comisión</span>
        <h3>Software</h3>
        <p style="color:var(--ink-soft);font-size:14px;">Programación del ESP32, configuración de Firebase y desarrollo del panel Energhost.</p>
        <ul>
          <li>Firmware del ESP32 conectado a Firebase</li>
          <li>Panel web en PHP (Slim), instalable en el celular</li>
          <li>Simulador de datos para pruebas sin hardware</li>
        </ul>
      </div>
      <div class="team-card">
        <span class="tag">Comisión</span>
        <h3>Hardware</h3>
        <p style="color:var(--ink-soft);font-size:14px;">Montaje físico, conexiones eléctricas, soldadura y verificaciones de seguridad.</p>
        <ul>
          <li>Ensamblaje ESP32 + PZEM-004T + 3 relés y 3 tomas</li>
          <li>Verificación con tester y multímetro</li>
          <li>Cableado definitivo del sistema</li>
        </ul>
      </div>
      <div class="team-card">
        <span class="tag">Comisión</span>
        <h3>Diseño y presentación</h3>
        <p style="color:var(--ink-soft);font-size:14px;">Modelado e impresión 3D, estética del stand y preparación de la exposición.</p>
        <ul>
          <li>Carcasas en Tinkercad / Fusion 360</li>
          <li>Impresión multicolor con Kuttercraft</li>
          <li>Banner, etiquetas con QR y defensa oral</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section id="cronograma">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Cronograma</span>
      <h2>Junio a diciembre</h2>
    </div>
    <div class="timeline">
      <div class="t-item">
        <div class="when">Junio — Julio</div>
        <h4>Investigación y primeras pruebas</h4>
        <p>Configuración de entornos y primeras pruebas de comunicación entre sensores y microcontroladores.</p>
      </div>
      <div class="t-item">
        <div class="when">Agosto — Septiembre</div>
        <h4>Fabricación y ensamblaje</h4>
        <p>Impresión de piezas, ensamblaje físico y cableado definitivo del sistema.</p>
      </div>
      <div class="t-item">
        <div class="when">Octubre</div>
        <h4>Auditoría energética real</h4>
        <p>Medición de consumos en oficinas, laboratorios y espacios institucionales de la escuela.</p>
      </div>
      <div class="t-item">
        <div class="when">Noviembre — Diciembre</div>
        <h4>Puesta en escena</h4>
        <p>Preparación estética del stand, banner, simulacros de defensa oral y optimización final del prototipo.</p>
      </div>
    </div>
  </div>
</section>

<section id="contacto">
  <div class="cta-band">
    <div>
      <h2>¿Listos para ver cuánto se está escapando?</h2>
      <p>Creá tu cuenta, vinculá tu Eco Smart Grid y empezá a monitorear en tiempo real desde Energhost.</p>
    </div>
    <?php if ($usuario): ?>
      <a href="<?= $base ?>/panel" class="btn btn-primary">Ir a mi panel</a>
    <?php else: ?>
      <a href="<?= $base ?>/registro" class="btn btn-primary">Crear mi cuenta</a>
    <?php endif; ?>
  </div>
</section>

<footer>
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <div class="brand" style="margin-bottom:14px;">
          <div class="mark"></div>
          <div>VOLTEC ERGON<small>Consultora de energía IoT</small></div>
        </div>
        <p style="font-size:13.5px;color:var(--ink-soft);max-width:280px;">Monitoreo, análisis y optimización del consumo energético residencial e institucional.</p>
      </div>
      <div>
        <h5>Producto</h5>
        <a href="#producto">Eco Smart Grid</a>
        <a href="#funcionamiento">Cómo funciona</a>
        <a href="#impacto">Huella de carbono</a>
      </div>
      <div>
        <h5>Consultora</h5>
        <a href="#equipo">Equipo</a>
        <a href="#cronograma">Cronograma</a>
        <a href="#empezar">Cómo empezar</a>
      </div>
      <div>
        <h5>App</h5>
        <a href="<?= $base ?>/panel">Energhost — Mi panel</a>
        <a href="<?= $base ?>/vincular">Vincular un equipo</a>
        <a href="<?= $base ?>/registro">Crear cuenta</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date("Y") ?> Voltec Ergon. Proyecto técnico — Eco Smart Grid / Energhost. E.E.S.T. Nº 4 de Berazategui.</span>
      <span>Factor de emisión: <?= $numero($factorCo2, 3) ?> kg CO₂/kWh (SNIGEI-AR)</span>
    </div>
  </div>
</footer>

<script src="<?= $base ?>/js/landing.js"></script>
