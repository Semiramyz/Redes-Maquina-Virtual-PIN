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
</style>
</head>
<body>
<div class="container">
<h1>Calculadora de IP (PHP)</h1>
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

    function validar_ip($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    function to_int($ip) {
        $parts = explode('.', $ip);
        return ($parts[0] << 24) | ($parts[1] << 16) | ($parts[2] << 8) | $parts[3];
    }

    function to_str($int) {
        return (($int >> 24) & 255) . "." . (($int >> 16) & 255) . "." . (($int >> 8) & 255) . "." . ($int & 255);
    }

    function contar_bits($maskInt) {
        $maskInt = $maskInt & 0xFFFFFFFF;
        $count = 0;
        while ($maskInt) {
            $count += $maskInt & 1;
            $maskInt >>= 1;
        }
        return $count;
    }

    function clase_ip($ip) {
        $oct1 = intval(explode('.', $ip)[0]);
        if ($oct1 >= 1 && $oct1 <= 126) return "A";
        if ($oct1 == 127) return "Loopback";
        if ($oct1 >= 128 && $oct1 <= 191) return "B";
        if ($oct1 >= 192 && $oct1 <= 223) return "C";
        if ($oct1 >= 224 && $oct1 <= 239) return "D (Multicast)";
        return "E (Experimental)";
    }

    function es_privada($ip) {
        return (
            preg_match('/^10\\./', $ip) ||
            preg_match('/^192\\.168\\./', $ip) ||
            preg_match('/^172\\.(1[6-9]|2[0-9]|3[0-1])\\./', $ip)
        );
    }

    if (!validar_ip($ipStr) || !validar_ip($maskStr)) {
        echo "<p class='strong'>❌ IP o máscara no válidas.</p>";
    } else {
        $ipInt = to_int($ipStr);
        $maskInt = to_int($maskStr);

        // Validar máscara contigua
        $maskBin = str_pad(decbin($maskInt), 32, "0", STR_PAD_LEFT);
        if (!preg_match('/^1*0*$/', $maskBin)) {
            echo "<p class='strong'>❌ Máscara no válida (debe tener bits contiguos 1's seguidos de 0's).</p>";
        } else {
            $ones = contar_bits($maskInt);
            $hostBits = 32 - $ones;

            $networkInt = $ipInt & $maskInt;
            $broadcastInt = $networkInt | (~$maskInt & 0xFFFFFFFF);

            if ($hostBits == 0) $usable = 1;
            elseif ($hostBits == 1) $usable = 0;
            else $usable = pow(2, $hostBits) - 2;

            $firstUsable = ($hostBits > 1) ? $networkInt + 1 : null;
            $lastUsable = ($hostBits > 1) ? $broadcastInt - 1 : null;

            $clase = clase_ip($ipStr);
            $priv = es_privada($ipStr) ? "Privada" : "Pública";

            echo "<div class='result'>";
            echo "<h3>Resultados</h3>";
            echo "<p><strong>IP de red:</strong> " . to_str($networkInt) . "</p>";
            echo "<p><strong>IP de broadcast:</strong> " . to_str($broadcastInt) . "</p>";
            echo "<p><strong>Hosts útiles:</strong> $usable</p>";
            echo "<p><strong>Rango de IPs útiles:</strong> " . 
                 ($firstUsable ? to_str($firstUsable)." - ".to_str($lastUsable) : "N/A") . "</p>";
            echo "<p><strong>Clase:</strong> $clase</p>";
            echo "<p><strong>Tipo:</strong> $priv</p>";

            $ipBin = str_pad(decbin($ipInt), 32, "0", STR_PAD_LEFT);
            $maskBin = str_pad(decbin($maskInt), 32, "0", STR_PAD_LEFT);
            $netPart = substr($ipBin, 0, $ones);
            $hostPart = substr($ipBin, $ones);

            echo "<h3>Porciones en binario</h3>";
            echo "<p><strong>IP:</strong> <br>";
            echo "<code>" . implode(" ", str_split($netPart,8)) . " <span class='strong'>" . implode(" ", str_split($hostPart,8)) . "</span></code></p>";
            echo "<p><strong>Máscara:</strong><br><code>" . chunk_split($maskBin, 8, " ") . "</code></p>";

            echo "<h3>Detalle por octeto</h3><pre>";
            for ($i=0; $i<4; $i++) {
                $oct = substr($ipBin, $i*8, 8);
                $markers = "";
                for ($j=0;$j<8;$j++){
                    $markers .= ($i*8+$j<$ones) ? "N" : "H";
                }
                echo "Octeto ".($i+1).": $oct ($markers)\n";
            }
            echo "\nIP original: $ipStr\nMáscara: $maskStr (/$ones)\n";
            echo "Network: " . to_str($networkInt) . "\nBroadcast: " . to_str($broadcastInt) . "\n";
            echo "</pre></div>";
        }
    }
}
?>
</div>
</body>
</html>
