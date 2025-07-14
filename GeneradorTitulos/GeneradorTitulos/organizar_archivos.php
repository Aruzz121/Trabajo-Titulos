<?php
// Directorio base de recursos
$recursos_dir = __DIR__ . '/recursos';

// Función para obtener el año de generación desde el XML
function obtenerAnioGeneracion($archivo) {
    $contenido = file_get_contents($archivo);
    
    // Intentar extraer del campo fechaExpedicion
    if (preg_match('/fechaExpedicion=[\'"](\d{4})/', $contenido, $matches)) {
        $anio = $matches[1];
        if ($anio >= 2000 && $anio <= (int)date('Y') + 1) {
            return $anio;
        }
    }
    
    // Si no se encuentra, intentar con fechaExencionExamenProfesional
    if (preg_match('/fechaExencionExamenProfesional=[\'"](\d{4})/', $contenido, $matches)) {
        $anio = $matches[1];
        if ($anio >= 2000 && $anio <= (int)date('Y') + 1) {
            return $anio;
        }
    }
    
    // Si no se pudo obtener del XML, intentar del nombre del archivo
    $nombre_archivo = basename($archivo);
    if (preg_match('/(\d{2})(\d{6})/', $nombre_archivo, $matches)) {
        $anio = '20' . $matches[1]; // Asumir que los dos primeros dígitos son el año
        if ($anio >= 2000 && $anio <= (int)date('Y') + 1) {
            return $anio;
        }
    }
    
    return false;
}

// Función para mover un archivo a su destino final
function moverArchivo($archivo, $destino_dir) {
    if (!is_dir($destino_dir)) {
        if (!mkdir($destino_dir, 0777, true)) {
            return ['estado' => 'error', 'mensaje' => 'No se pudo crear el directorio de destino'];
        }
    }
    
    $nombre_archivo = basename($archivo);
    $destino = $destino_dir . '/' . $nombre_archivo;
    
    // Si el archivo ya existe en el destino, agregar un sufijo numérico
    $contador = 1;
    while (file_exists($destino)) {
        $info = pathinfo($nombre_archivo);
        $nuevo_nombre = $info['filename'] . '_' . $contador . '.' . $info['extension'];
        $destino = $destino_dir . '/' . $nuevo_nombre;
        $contador++;
    }
    
    if (rename($archivo, $destino)) {
        return [
            'estado' => 'ok',
            'mensaje' => 'Archivo movido correctamente',
            'destino' => $destino
        ];
    } else {
        return [
            'estado' => 'error',
            'mensaje' => 'Error al mover el archivo',
            'destino' => $destino
        ];
    }
}

// Función para procesar un archivo
function procesarArchivo($archivo, $recursos_dir) {
    $info = pathinfo($archivo);
    $nombre = $info['filename'];
    $extension = strtolower($info['extension']);
    
    // Obtener año de generación
    $anio = obtenerAnioGeneracion($archivo);
    
    if ($anio === false) {
        return [
            'archivo' => basename($archivo),
            'estado' => 'error',
            'mensaje' => 'No se pudo determinar el año de generación',
            'tipo' => $extension
        ];
    }
    
    // Crear carpeta del año si no existe
    $carpeta_anio = "$recursos_dir/Gen-$anio";
    
    // Mover el archivo
    $resultado = moverArchivo($archivo, $carpeta_anio);
    $resultado['archivo'] = basename($archivo);
    $resultado['tipo'] = $extension;
    
    return $resultado;
}

// Iniciar el procesamiento
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizador de Archivos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .resultado { margin: 5px 0; padding: 5px; border-left: 3px solid #ccc; }
        .success-message { 
            background-color: #dff0d8; 
            border: 1px solid #d6e9c6; 
            color: #3c763d; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 4px; 
        }
        .button-container { margin: 20px 0; }
        .button { 
            display: inline-block; 
            padding: 10px 15px; 
            background-color: #5cb85c; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
        }
        .button:hover { background-color: #4cae4c; }
    </style>
</head>
<body>
    <h1>Organizador de Archivos</h1>
    
    <?php
    // Obtener lista de archivos (XML, JPG, JPEG, PNG) en el directorio de recursos
    $archivos = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($recursos_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $path) {
        if ($path->isFile() && 
            in_array(strtolower($path->getExtension()), ['xml', 'jpg', 'jpeg', 'png']) &&
            !preg_match('/Gen-\d{4}/', $path->getPath())) {
            $archivos[] = $path->getPathname();
        }
    }

    // Procesar cada archivo
    $resultados = [];
    foreach ($archivos as $archivo) {
        $resultado = procesarArchivo($archivo, $recursos_dir);
        $resultados[] = $resultado;
        
        // Mostrar progreso en tiempo real
        echo "<div class='resultado {$resultado['estado']}'>";
        echo "<strong>" . htmlspecialchars($resultado['archivo']) . "</strong> - ";
        echo "<span class='{$resultado['estado']}'>" . htmlspecialchars($resultado['mensaje']) . "</span>";
        echo "</div>";
        flush();
        ob_flush();
    }

    // Eliminar directorios vacíos
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($recursos_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $path) {
        if ($path->isDir() && !preg_match('/Gen-\d{4}$/', $path->getPathname())) {
            @rmdir($path->getPathname());
        }
    }
    
    if (empty($archivos)) {
        echo "<p>No se encontraron archivos para organizar.</p>";
    } else {
        echo "<div class='success-message'>";
        echo "<p>¡Proceso completado!</p>";
        echo "<p>Los archivos han sido organizados en carpetas por año de generación.</p>";
        echo "</div>";
    }
    ?>
    
    <div class="button-container">
        <a href="FORMULARIO.php" class="button">Volver al inicio</a>
    </div>
</body>
</html>
