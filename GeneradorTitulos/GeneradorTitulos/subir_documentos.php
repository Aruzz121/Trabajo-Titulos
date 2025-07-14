<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Documentos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .drop-zone {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .drop-zone.highlight {
            border-color: #4CAF50;
            background-color: #f0fff0;
        }
        #fileInput {
            display: none;
        }
        .file-list {
            margin-top: 20px;
        }
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            background: white;
        }
        .file-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .file-icon {
            width: 24px;
            height: 24px;
        }
        .remove-file {
            color: #f44336;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
        }
        .upload-btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }
        .upload-btn:hover {
            background-color: #45a049;
        }
        .upload-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .back-btn {
            display: inline-block;
            margin-top: 15px;
            color: #2196F3;
            text-decoration: none;
        }
        .back-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Subir Documentos</h1>
        <p>Arrastra y suelta tus archivos aquí o haz clic para seleccionarlos.</p>
        
        <input type="file" id="fileInput" multiple accept=".xml,.jpg,.jpeg,.png,.pdf">
        
        <div class="drop-zone" id="dropZone">
            <p>Arrastra archivos aquí o haz clic para seleccionar</p>
            <p><small>Formatos aceptados: .xml, .jpg, .jpeg, .png, .pdf</small></p>
        </div>
        
        <div class="file-list" id="fileList">
            <!-- Los archivos seleccionados aparecerán aquí -->
        </div>
        
        <button id="uploadBtn" class="upload-btn" disabled>Subir archivos a la plataforma</button>
        <a href="FORMULARIO.php" class="back-btn">← Volver al formulario</a>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const uploadBtn = document.getElementById('uploadBtn');
        
        // Almacenar archivos seleccionados
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
            // Limpiar el input para permitir seleccionar el mismo archivo de nuevo
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
                
                const fileIcon = document.createElement('div');
                fileIcon.className = 'file-icon';
                fileIcon.innerHTML = getFileIcon(file);
                
                const fileName = document.createElement('span');
                fileName.textContent = file.name;
                
                const fileSize = document.createElement('span');
                fileSize.textContent = formatFileSize(file.size);
                fileSize.style.color = '#666';
                fileSize.style.fontSize = '0.9em';
                
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
                return '🖼️';
            } else if (file.type === 'application/pdf') {
                return '📄';
            } else if (file.type.includes('xml')) {
                return '📋';
            }
            return '📁';
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
                
                const response = await fetch('procesar_archivos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.text();
                
                if (response.ok) {
                    alert('Archivos subidos exitosamente');
                    window.location.href = 'FORMULARIO.php';
                } else {
                    throw new Error(result || 'Error al subir los archivos');
                }
            } catch (error) {
                alert('Error: ' + error.message);
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Subir archivos a la plataforma';
            }
        });
    </script>
</body>
</html>
