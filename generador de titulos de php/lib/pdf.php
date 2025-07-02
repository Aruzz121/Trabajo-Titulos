<?php
use Mpdf\Mpdf;

function generar_pdf($datos) {
    $mpdf = new Mpdf();
    $html = '<html><body>';
    
    $html .= '<h1>Título Digital</h1>';
    $html .= '<p>Nombre: ' . $datos['nombre'] . '</p>';
    $html .= '<p>Carrera: ' . $datos['carrera'] . '</p>';
    $html .= '<p>Expedido: ' . $datos['fecha_expedicion'] . '</p>';
    $html .= '<p>Folio: ' . $datos['folio'] . '</p>';

    // Agrega imagen de fondo, QR, etc. si quieres aquí con CSS/HTML
    if ($datos['foto']) {
        $html .= '<img src="' . $datos['foto'] . '" width="100">';
    }

    $html .= '</body></html>';

    $mpdf->WriteHTML($html);
    $mpdf->Output("TituloDigital.pdf", "I"); // I = inline, D = descarga
}
