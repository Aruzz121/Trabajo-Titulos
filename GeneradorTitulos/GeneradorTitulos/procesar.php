<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Función para mostrar mensajes de depuración
function debug_log($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, __DIR__ . '/debug.log');
}

// Verificar si se enviaron los datos del formulario
if (!isset($_POST['matricula']) || !isset($_POST['carrera'])) {
    debug_log('Error: Faltan parámetros requeridos en el formulario');
    die('Error: Faltan parámetros requeridos. Asegúrate de acceder a través del formulario.');
}

// Obtener datos del formulario
$matricula = trim($_POST['matricula']);
$carrera = trim($_POST['carrera']);

debug_log("Iniciando generación de PDF para matrícula: $matricula, carrera: $carrera");

// Directorio actual
$directorio_actual = __DIR__;
debug_log("Directorio actual: $directorio_actual");

// Ruta al script de Python
$script_path = $directorio_actual . DIRECTORY_SEPARATOR . 'main.py';

// Verificar que el script existe
if (!file_exists($script_path)) {
    debug_log("Error: No se encontró el script $script_path");
    die("Error: No se pudo encontrar el script de generación de PDF.");
}

// Crear directorio temporal si no existe
$temp_dir = sys_get_temp_dir();
if (!is_writable($temp_dir)) {
    debug_log("Error: No se puede escribir en el directorio temporal: $temp_dir");
    die("Error: No se puede escribir en el directorio temporal del sistema.");
}

// Archivo temporal para el PDF
$temp_pdf = tempnam($temp_dir, 'pdf_');
if ($temp_pdf === false) {
    debug_log("Error: No se pudo crear el archivo temporal en $temp_dir");
    die("Error: No se pudo crear un archivo temporal para el PDF.");
}

debug_log("Archivo temporal creado: $temp_pdf");

// Construir el comando
$command = 'python ' . escapeshellarg($script_path) . ' ' . 
           escapeshellarg($matricula) . ' ' . 
           escapeshellarg($carrera) . ' > ' . escapeshellarg($temp_pdf) . ' 2>&1';

debug_log("Ejecutando comando: $command");

// Ejecutar el comando y capturar la salida
$output = [];
exec($command . ' 2>&1', $output, $return_var);
$output_str = implode("\n", $output);

debug_log("Código de salida del comando: $return_var");
debug_log("Salida del comando: " . $output_str);
debug_log("Tamaño del archivo generado: " . (file_exists($temp_pdf) ? filesize($temp_pdf) : 0) . " bytes");

// Función para mostrar un mensaje de error con estilo
function mostrar_error($titulo, $mensaje, $debug_info = '') {
    $debug_info_escaped = htmlspecialchars($debug_info, ENT_QUOTES, 'UTF-8');
    $titulo_escaped = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $mensaje_escaped = nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'));
    
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - ' . $titulo_escaped . '</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                background-color: #f8f9fa; 
                margin: 0;
                padding: 20px;
                color: #333;
            }
            .error-container { 
                max-width: 800px; 
                margin: 50px auto; 
                padding: 30px; 
                background-color: white; 
                border-radius: 8px;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
                border-left: 5px solid #dc3545;
            }
            .error-title { 
                color: #dc3545; 
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            .btn-volver {
                display: inline-block;
                background-color: #007bff;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 20px;
            }
            .btn-volver:hover {
                background-color: #0056b3;
            }
            .debug-info {
                margin-top: 20px;
                padding: 15px;
                background-color: #f8f9fa;
                border-radius: 4px;
                font-family: monospace;
                font-size: 0.9em;
                white-space: pre-wrap;
                display: none;
            }
            .show-debug {
                color: #6c757d;
                font-size: 0.9em;
                cursor: pointer;
                margin-top: 15px;
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1 class="error-title">' . $titulo_escaped . '</h1>
            <p>' . $mensaje_escaped . '</p>
            <a href="javascript:history.back()" class="btn-volver">Volver al formulario</a>
            
            <div class="show-debug" onclick="document.getElementById(\'debug-info\').style.display=\'block\'; this.style.display=\'none\';">
                Mostrar detalles tÃ©cnicos
            </div>
            
            <div id="debug-info" class="debug-info">' . $debug_info_escaped . '</div>
            
            <script>
                // Si hay un error en la consola, mostrar el panel de depuraciÃ³n
                window.onerror = function() {
                    document.getElementById("debug-info").style.display = "block";
                    return true;
                };
            </script>
        </div>
    </body>
    </html>';
    exit;
}

// Verificar si el archivo PDF se generó correctamente
if (file_exists($temp_pdf) && filesize($temp_pdf) > 0) {
    $pdf_content = file_get_contents($temp_pdf);
    
    // Verificar si el contenido es un PDF válido
    if (strpos($pdf_content, '%PDF-') === 0) {
        // Limpiar cualquier salida previa
        if (ob_get_level()) ob_end_clean();
        
        // Enviar encabezados para mostrar el PDF en el navegador
        header("Content-type: application/pdf");
        header("Content-Disposition: inline; filename=Titulo_{$carrera}-{$matricula}.pdf");
        header("Content-Length: " . strlen($pdf_content));
        
        // Enviar el contenido del PDF
        echo $pdf_content;
        
        // Eliminar el archivo temporal
        @unlink($temp_pdf);
        exit;
    } else {
        // Verificar si hay un mensaje de error en la salida
        if (stripos($output_str, 'XML no encontrado') !== false) {
            mostrar_error(
                'Alumno no encontrado', 
                'No se encontró información para el alumno con matrícula ' . htmlspecialchars($matricula) . ' y carrera ' . htmlspecialchars($carrera) . '.',
                "Información técnica:\n" . htmlspecialchars($output_str)
            );
        } else {
            mostrar_error(
                'No se encontro informacion del alumno.', 
                'Error en el formato del archivo',
                "Información técnica:\n" . htmlspecialchars(substr($pdf_content, 0, 1000))
            );
        }
    }
} else {
    // Mostrar mensaje de error personalizado
    if (stripos($output_str, 'XML no encontrado') !== false) {
        mostrar_error(
            'Alumno no encontrado', 
            'No se encontró información para el alumno con matrícula ' . htmlspecialchars($matricula) . ' y carrera ' . htmlspecialchars($carrera) . '.',
            "Información técnica:\n" . htmlspecialchars($output_str)
        );
    } else {
        // Mostrar error genérico
        $debug_info = "Comando ejecutado: " . htmlspecialchars($command) . "\n";
        $debug_info .= "Código de salida: " . $return_var . "\n";
        $debug_info .= "Salida del comando: " . htmlspecialchars($output_str);
        
        mostrar_error(
            'Error al generar el PDF', 
            'Ocurrió un error al intentar generar el documento. Por favor, inténtalo de nuevo más tarde.',
            $debug_info
        );
    };
    
    // Mostrar el contenido del directorio de recursos
    $recursos_dir = $directorio_actual . DIRECTORY_SEPARATOR . 'recursos';
    echo "<h3>Contenido del directorio de recursos:</h3><pre>";
    if (is_dir($recursos_dir)) {
        $files = scandir($recursos_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $file_path = $recursos_dir . DIRECTORY_SEPARATOR . $file;
                $file_type = is_dir($file_path) ? '[DIR] ' : '[FILE]';
                echo htmlspecialchars($file_type . ' ' . $file) . "\n";
            }
        }
    } else {
        echo "El directorio de recursos no existe: " . htmlspecialchars($recursos_dir);
    }
    echo "</pre>";
    
    // Mostrar información del servidor
    echo "<h3>Información del servidor:</h3>";
    echo "<pre>";
    echo "PHP version: " . phpversion() . "\n";
    echo "Sistema operativo: " . php_uname() . "\n";
    echo "Python version: " . shell_exec('python --version 2>&1') . "\n";
    echo "</pre>";
}
?>
