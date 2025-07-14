<?php
// Aumentar límites de ejecución y memoria para manejar archivos grandes
ini_set('max_execution_time', 300); // 5 minutos
ini_set('memory_limit', '512M');

// Función para extraer año de un archivo XML
function extraerAnioXML($filePath) {
    $xml = @simplexml_load_file($filePath);
    if ($xml === false) return false;
    
    // Buscar en diferentes nodos posibles
    $nodos_fecha = [
        '//*[local-name()="Carrera"]/@fechaInicio',
        '//*[local-name()="Expedicion"]/@fechaExpedicion',
        '//*[local-name()="Expedicion"]/@fechaExencionExamenProfesional'
    ];
    
    foreach ($nodos_fecha as $nodo) {
        $result = $xml->xpath($nodo);
        if (!empty($result) && isset($result[0])) {
            $fecha = (string)$result[0];
            if (preg_match('/(\d{4})/', $fecha, $matches)) {
                $anio = (int)$matches[1];
                if ($anio >= 2000 && $anio <= (int)date('Y') + 1) {
                    return $anio;
                }
            }
        }
    }
    return false;
}

// Procesar la subida de archivos si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES)) {
    // Directorio donde se guardarán los archivos
    $uploadDir = __DIR__ . '/recursos/';
    $tempDir = $uploadDir . 'temp_processing/';
    
    // Crear directorios necesarios
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);
    
    $response = ['success' => true, 'message' => 'Procesando archivos...', 'files' => []];
    $archivosProcesados = [];
    $errores = [];
    
    // Primero mover todos los archivos a una carpeta temporal
    foreach ($_FILES as $file) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $tempFile = $tempDir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $tempFile)) {
                $archivosProcesados[] = [
                    'ruta' => $tempFile,
                    'nombre' => $file['name'],
                    'tipo' => strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))
                ];
            }
        }
    }
    
    if (empty($archivosProcesados)) {
        $response = ['success' => false, 'message' => 'No se pudo subir ningún archivo.'];
    } else {
        // Primero procesar todos los XML
        $xmlFiles = [];
        $matriculas = [];
        
        foreach ($archivosProcesados as $key => $archivo) {
            if ($archivo['tipo'] === 'xml') {
                // Extraer año del XML
                $anio = extraerAnioXML($archivo['ruta']);
                
                if ($anio !== false) {
                    // Extraer matrícula del nombre del archivo
                    preg_match('/([A-Z]+-\d+)/', $archivo['nombre'], $matches);
                    $matricula = !empty($matches[1]) ? $matches[1] : '';
                    
                    // Crear directorio del año si no existe
                    $yearDir = $uploadDir . 'Gen-' . $anio . '/';
                    if (!file_exists($yearDir)) {
                        mkdir($yearDir, 0777, true);
                    }
                    
                    // Mover el XML a la carpeta correspondiente
                    $nuevoNombre = $matricula . '_' . uniqid() . '.xml';
                    $nuevaRuta = $yearDir . $nuevoNombre;
                    
                    if (rename($archivo['ruta'], $nuevaRuta)) {
                        $xmlFiles[$matricula] = [
                            'ruta' => $nuevaRuta,
                            'anio' => $anio,
                            'carpeta' => $yearDir
                        ];
                        $matriculas[] = $matricula;
                        
                        $response['files'][] = [
                            'name' => basename($nuevaRuta),
                            'size' => filesize($nuevaRuta),
                            'type' => 'text/xml',
                            'path' => str_replace('\\', '/', str_replace(__DIR__, '', $nuevaRuta))
                        ];
                    }
                }
                // Eliminar del array para no procesarlo de nuevo
                unset($archivosProcesados[$key]);
            }
        }
        
        // Luego procesar las imágenes
        foreach ($archivosProcesados as $archivo) {
            if (in_array($archivo['tipo'], ['jpg', 'jpeg', 'png'])) {
                // Extraer matrícula del nombre del archivo
                preg_match('/([A-Z]+-\d+)/', $archivo['nombre'], $matches);
                $matricula = !empty($matches[1]) ? $matches[1] : '';
                
                if (!empty($matricula) && isset($xmlFiles[$matricula])) {
                    // Mover a la carpeta del XML correspondiente
                    $nuevaRuta = $xmlFiles[$matricula]['carpeta'] . $matricula . '_' . uniqid() . '.' . $archivo['tipo'];
                } else {
                    // Si no hay XML correspondiente, mover a carpeta temporal
                    $tempDir = $uploadDir . 'temp_images/';
                    if (!file_exists($tempDir)) {
                        mkdir($tempDir, 0777, true);
                    }
                    $nuevaRuta = $tempDir . $archivo['nombre'];
                }
                
                if (rename($archivo['ruta'], $nuevaRuta)) {
                    $response['files'][] = [
                        'name' => basename($nuevaRuta),
                        'size' => filesize($nuevaRuta),
                        'type' => 'image/' . $archivo['tipo'],
                        'path' => str_replace('\\', '/', str_replace(__DIR__, '', $nuevaRuta))
                    ];
                }
            }
        }
        // Limpiar archivos temporales
        array_map('unlink', glob($tempDir . '*'));
        
        if (!empty($errores)) {
            $response['success'] = false;
            $response['message'] = 'Algunos archivos no se pudieron procesar correctamente.';
            $response['errores'] = $errores;
        } else {
            $response['message'] = 'Archivos procesados correctamente';
        }
    }
    
    // Limpiar directorio temporal
    if (is_dir($tempDir)) {
        array_map('unlink', glob($tempDir . '*'));
        @rmdir($tempDir);
    }
    
    // Devolver JSON
    header('Content-Type: application/json');
    if (isset($response['success']) && !$response['success']) {
        http_response_code(400);
    }
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso de Datos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-container {
            background-color: white;
            padding: 25px 35px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            width: 350px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 12px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        
        select {
            width: 100%;
            padding: 10px;
            margin: 12px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #3f51b5;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
        }
        button:hover {
            background-color: #303f9f;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Buscar Alumno</h2>
        <form action="procesar.php" method="post">
            <label for="matricula">Matrícula (6 dígitos):</label>
            <input type="text" id="matricula" name="matricula" pattern="\d{6}" maxlength="6" required>

            <label for="carrera">Carrera:</label>
            <select id="carrera" name="carrera" required>
                <option value="">Selecciona tu carrera</option>
                <option value="I-AGB">INGENIERÍA EN AGROBIOTECNOLOGÍA - I-AGB</option>
                <option value="I-AGRT">INGENIERÍA EN AGROTECNOLOGÍA - I-AGRT</option>
                <option value="I-PA">INGENIERÍA EN PRODUCCIÓN ANIMAL - I-PA</option>
                <option value="I-SISC">INGENIERÍA EN SISTEMAS COMPUTACIONALES - I-SISC</option>
                <option value="I-SOFT">INGENIERÍA EN SOFTWARE - I-SOFT</option>
                <option value="I-TIID">INGENIERÍA EN TECNOLOGÍAS DE LA INFORMACIÓN E INNOVACIÓN DIGITAL - I-TIID</option>
                <option value="L-NA">LICENCIATURA EN NEGOCIOS Y ADMINISTRACIÓN - L-NA</option>
                <option value="L-MC">LICENCIATURA EN MÉDICO-CIRUJANO Y PARTERO - L-MC</option>
                <option value="L-NM">LICENCIATURA EN NEGOCIOS Y MERCADOTÉCNICA - L-NM</option>
            </select>

            <button type="submit">Ingresar</button>
            
            <div style="text-align: center; margin-top: 20px;">
                <button type="button" id="toggleUpload" style="background-color: #4CAF50; width: auto; padding: 8px 20px;">
                    Subir Documentos
                </button>
            </div>
            
            <!-- Sección de subida de archivos (inicialmente oculta) -->
            <div id="uploadSection" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                <p>Arrastra y suelta tus archivos aquí o haz clic para seleccionarlos.</p>
                
                <input type="file" id="fileInput" multiple accept=".xml,.jpg,.jpeg,.png,.pdf" style="display: none;">
                
                <div style="border: 2px dashed #ccc; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer;" id="dropZone">
                    <p>Arrastra archivos aquí o haz clic para seleccionar</p>
                    <p><small>Formatos aceptados: .xml, .jpg, .jpeg, .png, .pdf</small></p>
                </div>
                
                <div id="fileList" style="margin: 10px 0; max-height: 150px; overflow-y: auto;">
                    <!-- Los archivos seleccionados aparecerán aquí -->
                </div>
                
                <button type="button" id="uploadBtn" style="background-color: #4CAF50;" disabled>Subir archivos</button>
            </div>
    </div>
    <script>
        // Mostrar/ocultar la sección de subida de archivos
        document.getElementById('toggleUpload').addEventListener('click', function() {
            const uploadSection = document.getElementById('uploadSection');
            if (uploadSection.style.display === 'none') {
                uploadSection.style.display = 'block';
                this.textContent = 'Ocultar Subida de Documentos';
            } else {
                uploadSection.style.display = 'none';
                this.textContent = 'Subir Documentos';
            }
        });

        // Funcionalidad de arrastrar y soltar archivos
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const uploadBtn = document.getElementById('uploadBtn');
        
        let files = [];
        
        // Prevenir el comportamiento por defecto para los eventos de arrastrar
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Resaltar la zona de soltar
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropZone.classList.add('highlight');
        }
        
        function unhighlight() {
            dropZone.classList.remove('highlight');
        }
        
        // Manejar archivos soltados
        dropZone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const newFiles = dt.files;
            handleFiles(newFiles);
        }
        
        // Manejar clic en la zona de soltar
        dropZone.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Manejar selección de archivos
        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
            this.value = '';
        });
        
        // Procesar archivos
        function handleFiles(newFiles) {
            const validTypes = ['application/xml', 'text/xml', 'image/jpeg', 'image/png', 'application/pdf'];
            
            for (let i = 0; i < newFiles.length; i++) {
                const file = newFiles[i];
                
                // Verificar tipo de archivo
                if (!validTypes.includes(file.type) && !file.name.match(/\.(xml|jpg|jpeg|png|pdf)$/i)) {
                    alert(`El archivo ${file.name} no tiene un formato válido. Se permiten solo XML, JPG, PNG y PDF.`);
                    continue;
                }
                
                // Verificar si el archivo ya fue agregado
                const isDuplicate = files.some(existingFile => 
                    existingFile.name === file.name && existingFile.size === file.size
                );
                
                if (!isDuplicate) {
                    files.push(file);
                }
            }
            
            updateFileList();
        }
        
        // Actualizar la lista de archivos en la interfaz
        function updateFileList() {
            fileList.innerHTML = '';
            
            if (files.length === 0) {
                uploadBtn.disabled = true;
                return;
            }
            
            uploadBtn.disabled = false;
            
            files.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                
                const fileInfo = document.createElement('div');
                fileInfo.className = 'file-info';
                
                const fileIcon = document.createElement('span');
                fileIcon.innerHTML = getFileIcon(file);
                
                const fileName = document.createElement('span');
                fileName.textContent = file.name;
                
                const fileSize = document.createElement('span');
                fileSize.textContent = ` (${formatFileSize(file.size)})`;
                fileSize.style.color = '#666';
                
                fileInfo.appendChild(fileIcon);
                fileInfo.appendChild(fileName);
                fileInfo.appendChild(fileSize);
                
                const removeBtn = document.createElement('span');
                removeBtn.className = 'remove-file';
                removeBtn.textContent = '×';
                removeBtn.onclick = () => removeFile(index);
                
                fileItem.appendChild(fileInfo);
                fileItem.appendChild(removeBtn);
                
                fileList.appendChild(fileItem);
            });
        }
        
        // Obtener ícono según el tipo de archivo
        function getFileIcon(file) {
            if (file.type.includes('image/')) {
                return '🖼️ ';
            } else if (file.type === 'application/pdf') {
                return '📄 ';
            } else if (file.type.includes('xml')) {
                return '📋 ';
            }
            return '📁 ';
        }
        
        // Formatear tamaño del archivo
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Eliminar archivo de la lista
        function removeFile(index) {
            files.splice(index, 1);
            updateFileList();
        }
        
        // Manejar envío de archivos
        uploadBtn.addEventListener('click', async () => {
            if (files.length === 0) return;
            
            const formData = new FormData();
            
            // Agregar cada archivo al FormData
            files.forEach((file, index) => {
                formData.append(`file${index}`, file);
            });
            
            try {
                uploadBtn.disabled = true;
                uploadBtn.textContent = 'Subiendo...';
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    alert('Archivos subidos exitosamente');
                    files = [];
                    updateFileList();
                    // Ocultar la sección de subida después de subir
                    document.getElementById('uploadSection').style.display = 'none';
                    document.getElementById('toggleUpload').textContent = 'Subir Documentos';
                } else {
                    throw new Error(result.message || 'Error al subir los archivos');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Subir archivos';
            }
        });
    </script>
</body>
</html>
