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
        input[type="text"], select {
            width: 100%;
            padding: 10px;
            margin: 12px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
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

            <label for="fecha">Fecha de nacimiento (DDMMAAAA):</label>
            <input type="text" id="fecha" name="fecha" pattern="\d{8}" maxlength="8" required>

            <label for="carrera">Carrera:</label>
            <select id="carrera" name="carrera" required>
                <option value="">Selecciona tu carrera</option>
                <option>INGENIERÍA EN AGROBIOTECNOLOGÍA</option>
                <option>INGENIERÍA EN AGROTECNOLOGÍA</option>
                <option>INGENIERÍA EN PRODUCCIÓN ANIMAL</option>
                <option>INGENIERÍA EN SISTEMAS COMPUTACIONALES</option>
                <option>INGENIERÍA EN SOFTWARE</option>
                <option>INGENIERÍA EN TECNOLOGÍAS DE LA INFORMACIÓN E INNOVACIÓN DIGITAL</option>
                <option>LICENCIATURA EN ADMINISTRACIÓN</option>
                <option>LICENCIATURA EN MÉDICO-CIRUJANO Y PARTERO</option>
                <option>LICENCIATURA EN NEGOCIOS Y ADMINISTRACIÓN</option>
                <option>LICENCIATURA EN NEGOCIOS Y MERCADOTÉCNICA</option>
            </select>

            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
