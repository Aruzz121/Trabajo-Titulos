<?php
require_once 'vendor/autoload.php'; // mPDF
require_once 'lib/funciones.php';
require_once 'lib/pdf.php';

$matricula = $_GET['matricula'] ?? '';
$fecha = $_GET['fecha'] ?? '';

$xml_path = "datos/$matricula/$fecha.xml";
$foto_path = "datos/$matricula/$fecha.jpg";

if (!file_exists($xml_path)) {
    exit("❌ Archivo XML no encontrado.");
}

$datos = leer_xml($xml_path);
$datos['foto'] = file_exists($foto_path) ? $foto_path : null;

generar_pdf($datos); // esto genera y muestra el PDF
