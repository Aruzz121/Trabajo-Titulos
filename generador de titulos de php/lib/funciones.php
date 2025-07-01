<?php
function leer_xml($xml_path) {
    $xml = simplexml_load_file($xml_path);
    $ns = $xml->getNamespaces(true);
    $xml->registerXPathNamespace('ns', $ns['']);

    $institucion = $xml->xpath('//ns:Institucion')[0];
    $carrera = $xml->xpath('//ns:Carrera')[0];
    $alumno = $xml->xpath('//ns:Profesionista')[0];
    $expedicion = $xml->xpath('//ns:Expedicion')[0];

    return [
        'nombre' => "{$alumno['nombre']} {$alumno['primerApellido']} {$alumno['segundoApellido']}",
        'carrera' => (string) $carrera['nombreCarrera'],
        'clave_institucion' => (string) $institucion['cveInstitucion'],
        'clave_carrera' => (string) $carrera['cveCarrera'],
        'fecha_examen' => formatear_fecha($expedicion['fechaExencionExamenProfesional']),
        'fecha_expedicion' => formatear_fecha($expedicion['fechaExpedicion']),
        'fecha_terminacion' => formatear_fecha_invertida($carrera['fechaTerminacion']),
        'folio' => (string) $xml['folioControl']
    ];
}

function formatear_fecha($fecha) {
    $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio",
              "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    $f = DateTime::createFromFormat("Y-m-d", $fecha);
    return $f->format('j') . ' de ' . $meses[$f->format('n') - 1] . ' de ' . $f->format('Y');
}

function formatear_fecha_invertida($fecha) {
    $f = DateTime::createFromFormat("Y-m-d", $fecha);
    return $f->format("d-m-Y");
}
