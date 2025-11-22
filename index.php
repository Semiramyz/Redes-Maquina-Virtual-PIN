<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Calculadora IP en PHP</title>

<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f2f4f8;color:#111;margin:0;padding:0}
.container{max-width:900px;margin:30px auto;background:white;padding:25px;border-radius:10px;box-shadow:0 4px 14px rgba(0,0,0,0.1)}
h1{margin-bottom:15px;color:#1e40af}
input[type=text]{width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;font-size:14px}
button{background:#2563eb;color:white;padding:10px 15px;border:0;border-radius:6px;cursor:pointer;font-weight:bold}
.result{margin-top:20px;padding:15px;background:#eef3ff;border-radius:8px}
pre{background:#111;color:#eee;padding:10px;border-radius:6px;overflow-x:auto}
.strong{font-weight:bold;color:#dc2626}

/* HOJA DE VIDA */
.cv-container{
    display:none;
    margin-top:25px;
    padding:20px;
    background:#eef3ff;
    border-radius:10px;
}

.cv-title{
    font-size:22px;
    color:#1e40af;
    margin-bottom:10px;
}

.cv-section{
    margin-bottom:18px;
}

.cv-section-title{
    font-size:18px;
    color:#2563eb;
    margin-bottom:6px;
    border-left:5px solid #2563eb;
    padding-left:8px;
}

.skill-box{
    background:white;
    padding:8px;
    border-radius:6px;
    margin-bottom:5px;
    border:1px solid #d0d7ff;
}
</style>

<script>
function toggleCV() {
    const cv = document.getElementById("cv");
    cv.style.display = (cv.style.display === "none" || cv.style.display === "") ? "block" : "none";
}
</script>

</head>
<body>

<div class="container">

<h1>Calculadora de IP (PHP)</h1>

<!-- BOTÓN HOJA DE VIDA -->
<button onclick="toggleCV()" style="background:#16a34a;margin-bottom:15px">
    Mostrar / Ocultar Hoja de Vida
</button>

<!-- HOJA DE VIDA -->
<div id="cv" class="cv-container">

    <div class="cv-title">Hoja de Vida - Juan Sebastián Pineda Santafé</div>

    <div class="cv-section">
        <div class="cv-section-title">Perfil</div>
        <p>
            Estudiante de Ingeniería de Sistemas con conocimientos en backend, bases de datos,
            sistemas embebidos e inteligencia artificial.
        </p>
        <p>
            Enfoque en software escalable, eficiente y organizado. Interés en avionica y sistemas médicos.
        </p>
    </div>

    <div class="cv-section">
        <div class="cv-section-title">Proyecto Destacado</div>
        <div class="skill-box">
            <strong>ESP32 IA de Detección de Personas</strong><br>
            - TensorFlowLite, C++, ESP32-CAM<br>
            - Interfaz web integrada<br>
            - Visión artificial en sistema embebido<br>
        </div>
    </div>

    <div class="cv-section">
        <div class="cv-section-title">Educación</div>
        <ul>
            <li>Ingeniería de Sistemas – Universidad El Bosque</li>
            <li>Colegio La Presentación Girardot</li>
        </ul>
    </div>

    <div class="cv-section">
        <div class="cv-section-title">Lenguajes</div>
        <div class="skill-box">Python</div><div class="skill-box">Java</div>
        <div class="skill-box">TypeScript</div><div class="skill-box">JavaScript</div>
        <div class="skill-box">C / C++</div><div class="skill-box">C#</div>
        <div class="skill-box">SQL / PL-SQL</div><div class="skill-box">Node.js</div>
        <div class="skill-box">Go</div>
    </div>

    <div class="cv-section">
        <div class="cv-section-title">Frameworks</div>
        <div class="skill-box">Angular</div><div class="skill-box">React</div>
        <div class="skill-box">Spring Boot</div><div class="skill-box">Flask</div>
        <div class="skill-box">FastAPI</div><div class="skill-box">Git / GitHub</div>
    </div>

</div>

<!-- FORMULARIO CALCULADORA -->
<form method="POST">
  <label>Dirección IP:</label>
  <input type="text" name="ip" placeholder="Ej: 192.168.0.10" required value="<?php echo isset($_POST['ip']) ? htmlspecialchars($_POST['ip']) : '';?>">

  <label>Máscara de Subred:</label>
  <input type="text" name="mask" placeholder="Ej: 255.255.255.0" required value="<?php echo isset($_POST['mask']) ? htmlspecialchars($_POST['mask']) : '';?>">

  <button type="submit">Calcular</button>
</form>

<?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $ipStr = $_POST["ip"];
    $maskStr = $_POST["mask"];

    function validar_ip($ip){ return filter_var($ip, FILTER_VALIDATE_IP); }
    function to_int($ip){ $p = explode('.', $ip); return ($p[0]<<24)|($p[1]<<16)|($p[2]<<8)|$p[3]; }
    function to_str($int){ return (($int>>24)&255).".".(($int>>16)&255).".".(($int>>8)&255).".".($int&255); }

    function contar_bits($mask){
        $mask &= 0xFFFFFFFF;
        $c = 0;
        while($mask){ $c += $mask & 1; $mask >>= 1; }
        return $c;
    }

    function clase_ip($ip){
        $o = intval(explode('.', $ip)[0]);
        if($o>=1 && $o<=126) return "A";
        if($o==127) return "Loopback";
        if($o>=128 && $o<=191) return "B";
        if($o>=192 && $o<=223) return "C";
        if($o>=224 && $o<=239) return "D (Multicast)";
        return "E (Experimental)";
    }

    function es_privada($ip){
        return (
            preg_match('/^10\\./',$ip) ||
            preg_match('/^192\\.168\\./',$ip) ||
            preg_match('/^172\\.(1[6-9]|2[0-9]|3[0-1])\\./',$ip)
        );
    }

    if(!validar_ip($ipStr) || !validar_ip($maskStr)){
        echo "<p class='strong'>❌ IP o máscara no válidas.</p>";
    } else {

        $ip = to_int($ipStr);
        $mask = to_int($maskStr);

        $maskBin = str_pad(decbin($mask),32,"0",STR_PAD_LEFT);
        if(!preg_match('/^1*0*$/',$maskBin)){
            echo "<p class='strong'>❌ Máscara no válida.</p>";
        } else {

            $ones = contar_bits($mask);
            $hostBits = 32 - $ones;

            $net = $ip & $mask;
            $bcast = $net | (~$mask & 0xFFFFFFFF);

            if($hostBits == 0) $usable = 1;
            elseif($hostBits == 1) $usable = 0;
            else $usable = pow(2,$hostBits)-2;

            $first = $hostBits>1 ? $net+1 : null;
            $last  = $hostBits>1 ? $bcast-1 : null;

            $clase = clase_ip($ipStr);
            $priv  = es_privada($ipStr) ? "Privada" : "Pública";

            $ipBin = str_pad(decbin($ip),32,"0",STR_PAD_LEFT);

            $netPart = substr($ipBin,0,$ones);
            $hostPart = substr($ipBin,$ones);

            echo "<div class='result'>";
            echo "<h3>Resultados</h3>";

            echo "<p><strong>IP de red:</strong> ".to_str($net)."</p>";
            echo "<p><strong>IP de broadcast:</strong> ".to_str($bcast)."</p>";
            echo "<p><strong>Hosts útiles:</strong> $usable</p>";
            echo "<p><strong>Rango de IPs útiles:</strong> ".($first? to_str($first)." - ".to_str($last) : "N/A")."</p>";

            echo "<hr>";

            echo "<p><strong>Clase de la IP:</strong> $clase</p>";
            echo "<p><strong>Tipo:</strong> $priv</p>";

            echo "<hr>";

            echo "<h3>Fragmento de Red / Trama</h3>";
            echo "<p><strong>Binario (N = Network, H = Host):</strong></p>";

            echo "<pre>";
            for($i=0;$i<32;$i++){
                echo $ipBin[$i];
                echo (($i+1)%8==0) ? " " : "";
            }
            echo "\n\nMarcado:\n";

            for($i=0;$i<32;$i++){
                echo ($i<$ones?"N":"H");
                echo (($i+1)%8==0)?" ":"";
            }

            echo "\n\nNetwork Bits: $ones\nHost Bits: $hostBits\n";
            echo "</pre>";

            echo "</div>";
        }
    }
}

?>

</div>
</body>
</html>
