import os
import qrcode
from fpdf import FPDF
from datetime import datetime
from fpdf.enums import Align

class PDF(FPDF):
    def header(self):
        pass

    def footer(self):
        pass

def generar_pdf(datos, output_file, foto_path=None):
    """
    Genera un PDF y lo guarda en el objeto de archivo proporcionado.
    
    Args:
        datos: Diccionario con los datos del alumno
        output_file: Objeto de archo donde se escribirá el PDF
        foto_path: Ruta opcional a la foto del alumno
    """
    pdf = PDF()
    pdf.add_page()

    # Obtener la ruta del directorio actual de manera confiable
    base_dir = os.path.dirname(os.path.abspath(__file__))
    recursos_dir = os.path.join(base_dir, "recursos")
    qr_dir = os.path.join(recursos_dir, "QR")
    os.makedirs(qr_dir, exist_ok=True)
    
    # Verificar que el directorio de recursos existe
    if not os.path.exists(recursos_dir):
        os.makedirs(recursos_dir, exist_ok=True)
    
    # Obtener la matrícula del alumno para usar en el nombre del archivo
    matricula = datos.get('matricula', '').strip()
    
    # Generar nombre único para el QR usando la matrícula
    qr_filename = f"QR-{matricula}.png"
    qr_path = os.path.join(qr_dir, qr_filename)
    
    # Generar nombre único para el HTML usando la matrícula
    html_filename = f"{matricula}.html"
    # Usar la ruta absoluta al directorio de verificación en htdocs
    verificacion_dir = os.path.normpath(os.path.join(base_dir, "..", "verificacion"))
    os.makedirs(verificacion_dir, exist_ok=True)  # Asegurar que el directorio exista
    html_path = os.path.join(verificacion_dir, html_filename)

    # Fondo - verificar la ruta del archivo
    fondo_path = os.path.join(recursos_dir, "fondo_hoja.png")
    
    # Verificar si el archivo de fondo existe
    if not os.path.exists(fondo_path):
        print(f"[ADVERTENCIA] No se encontró el archivo de fondo en: {fondo_path}")
        # Crear un fondo blanco simple
        pdf.set_fill_color(255, 255, 255)
        pdf.rect(0, 0, 210, 297, 'F')
    else:
        try:
            pdf.image(fondo_path, x=0, y=0, w=210, h=297)
        except Exception as e:
            print(f"[ADVERTENCIA] Error al cargar el fondo: {str(e)}")
            # Crear un fondo blanco simple si hay un error al cargar la imagen
            pdf.set_fill_color(255, 255, 255)
            pdf.rect(0, 0, 210, 297, 'F')

    # Foto
    if foto_path and os.path.exists(foto_path):
        pdf.image(foto_path, x=15, y=60, w=35, h=45)

    # Datos del alumno
    pdf.set_xy(51, 76.5)
    pdf.set_font("Arial", "B", 12)
    pdf.multi_cell(120, 6, datos['nombre'])

    pdf.set_xy(51, 93)
    pdf.multi_cell(120, 6, datos['carrera'])

    # Texto legal
    pdf.set_xy(25, 110)
    pdf.set_font("Arial", "", 10.5)
    texto_legal = (
        f"En virtud de haber acreditado el Programa Educativo en vigor y cumplir con los requisitos de ley correspondientes, "
        f"según constancias que obran en los archivos de esta institución. Se expide el acta de exención de examen profesional "
        f"el día {datos['fecha_examen']}."
    )
    pdf.multi_cell(160, 6, texto_legal)
    pdf.ln(3)
    pdf.set_x(25)
    pdf.multi_cell(160, 6, f"El título fue expedido en Tepic, Nayarit, México, el día {datos['fecha_expedicion']}.")

    # Datos administrativos
    pdf.set_xy(65, 155)
    pdf.cell(70, 6, f"{datos['clave_institucion']} ")
    pdf.set_xy(110, 155)
    pdf.cell(70, 6, f"{datos['clave_carrera']}")
    pdf.set_xy(150, 155)
    pdf.cell(70, 6, f"{datos['fecha_terminacion']}")

    pdf.set_xy(75, 169)
    pdf.cell(70, 6, f"{datos['autorizacionReconocimiento']} ")
    pdf.set_xy(155, 169)
    pdf.cell(70, 6, f"{datos['modalidadTitulacion']}")

    pdf.set_xy(75, 182.5)
    pdf.cell(70, 6, f"{datos['cumplioServicioSocial']} ")
    pdf.set_xy(100, 182.5)
    pdf.cell(70, 6, f"{datos['fundamentoLegalServicioSocial']}")
    pdf.set_xy(160, 182.5)
    pdf.cell(70, 6, f"{datos['entidadFederativa']}")

    # URL para el QR - usar la matrícula en la URL
    url_qr = f"http://localhost/TRABAJO_DE_ESTANCIAS-2/verificacion/{matricula}.html"
    
    # Verificar si ya existe el archivo HTML para este alumno
    if not os.path.exists(html_path):
        # Generar y guardar el código QR solo si no existe el HTML
        qr = qrcode.make(url_qr)
        qr.save(qr_path)
        
        # Crear HTML de verificación
        generar_html_verificacion(datos, html_path, recursos_dir, qr_filename, foto_path)
    else:
        # Si el HTML ya existe, solo generar el QR si no existe
        if not os.path.exists(qr_path):
            qr = qrcode.make(url_qr)
            qr.save(qr_path)
    
    # Agregar el QR al PDF (siempre usar el QR existente o recién generado)
    if os.path.exists(qr_path):
        pdf.image(qr_path, x=20.5, y=166, w=30)
    else:
        print("[ADVERTENCIA] No se pudo encontrar el archivo QR")

    # Guardar el PDF en el objeto de archivo
    try:
        if hasattr(output_file, 'write'):
            # Si es un objeto de archivo, escribir directamente
            pdf_content = pdf.output(dest='S').encode('latin1')
            output_file.write(pdf_content)
        else:
            # Si es una ruta, guardar normalmente
            pdf.output(output_file)
    except Exception as e:
        print(f"[ERROR] Error al guardar el PDF: {str(e)}")
        import traceback
        print(traceback.format_exc())
        raise

def generar_html_verificacion(datos, html_path, recursos_dir, qr_filename, foto_file=None):
    # Obtener la ruta base para las imágenes
    base_url = "/TRABAJO_DE_ESTANCIAS-2"
    
    # Inicializar la ruta de la foto
    foto_path = ""
    
    try:
        # Si se proporcionó una ruta de foto, usarla
        if foto_file and os.path.exists(foto_file):
            # Convertir la ruta a formato URL
            foto_path = foto_file.replace('\\', '/')  # Convertir a formato URL
            foto_path = foto_path.split('GeneradorTitulos/')[-1]  # Obtener solo la parte relativa
            foto_path = f"{base_url}/GeneradorTitulos/{foto_path}"
            print(f"[DEBUG] Usando foto proporcionada: {foto_path}")
        else:
            print("[ADVERTENCIA] No se proporcionó una ruta de foto válida")
    except Exception as e:
        print(f"[ERROR] Error al procesar la ruta de la foto: {e}")
        foto_path = ""
    
    # Crear el HTML con la plantilla original
    html = f"""<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Título Digital - UNIVERSIDAD POLITÉCNICA DEL ESTADO DE NAYARIT</title>
    <style>
        body {{ 
            font-family: Arial, sans-serif; 
            background-color: #f5f5f5; 
            color: #333; 
            margin: 0;
            padding: 0;
        }}
        .container {{ 
            max-width: 800px; 
            margin: 20px auto; 
            padding: 20px; 
            background-color: white; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }}
        .header {{ 
            text-align: center; 
            margin-bottom: 20px; 
            padding-bottom: 15px;
            border-bottom: 2px solid #006837;
        }}
        .header img {{ 
            max-width: 200px; 
            margin-bottom: 10px; 
        }}
        .header h1 {{
            color: #006837;
            margin: 10px 0;
            font-size: 24px;
        }}
        .panel {{ 
            border: 1px solid #ddd; 
            margin-bottom: 20px; 
            border-radius: 5px; 
            overflow: hidden; 
        }}
        .panel-header {{ 
            background-color: #006837; 
            color: white; 
            padding: 12px 15px; 
            font-weight: bold; 
            text-align: center; 
            font-size: 18px; 
        }}
        .panel-content {{ 
            padding: 25px; 
        }}
        table {{ 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
        }}
        table td {{ 
            padding: 10px; 
            vertical-align: middle; 
            border-bottom: 1px solid #eee; 
        }}
        .text-right {{ 
            text-align: right; 
            width: 35%; 
            font-weight: bold; 
            color: #444; 
            padding-right: 15px;
        }}
        .text-center {{ 
            text-align: center; 
        }}
        .student-photo {{ 
            display: block; 
            margin: 0 auto; 
            width: 180px; 
            height: 220px;
            object-fit: cover;
            border: 1px solid #ddd; 
            padding: 5px; 
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }}
        .document-info {{ 
            margin: 30px 0; 
            padding: 20px; 
            background-color: #f9f9f9; 
            border-radius: 5px; 
            font-style: italic;
            border-left: 4px solid #006837;
        }}
        .footer {{ 
            text-align: center; 
            margin-top: 30px; 
            padding-top: 15px; 
            border-top: 1px solid #eee; 
            font-size: 12px; 
            color: #666; 
        }}
        .qr-code {{ 
            margin: 25px 0; 
            text-align: center; 
        }}
        .qr-code img {{ 
            max-width: 150px; 
            border: 1px solid #ddd;
            padding: 10px;
            background: white;
        }}
        .student-info {{
            padding: 20px 0;
        }}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="{base_url}/GeneradorTitulos/recursos/logoupen.png" alt="Logo UPEN">
        <h1>UNIVERSIDAD POLITÉCNICA DEL ESTADO DE NAYARIT</h1>
    </div>

    <div class="panel">
        <div class="panel-header">INFORMACIÓN DEL TITULADO</div>
        <div class="panel-content">
            <div class="student-info">
                <table>
                    <tr>
                        <td rowspan="7" class="text-center" style="width: 40%;">
                            {'<img src="' + foto_path + '" alt="Foto del estudiante" class="student-photo">' if foto_path else '<div style="height: 220px; display: flex; align-items: center; justify-content: center; color: #999;">Sin foto</div>'}
                        </td>
                        <td class="text-right">Nombre:</td>
                        <td><strong>{datos['nombre']}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-right">Carrera:</td>
                        <td><strong>{datos['carrera']}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-right">Matrícula:</td>
                        <td><strong>{datos['matricula']}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-right">Clave Institución:</td>
                        <td>{datos['clave_institucion']}</td>
                    </tr>
                    <tr>
                        <td class="text-right">Clave Carrera:</td>
                        <td>{datos['clave_carrera']}</td>
                    </tr>
                    <tr>
                        <td class="text-right">Término de Estudios:</td>
                        <td>{datos['fecha_terminacion']}</td>
                    </tr>
                    <tr>
                        <td class="text-right">Examen Profesional:</td>
                        <td>{datos['fecha_examen']}</td>
                    </tr>
                </table>
            </div>

            <div class="document-info">
                <p style="margin: 0; text-align: center;">
                    El presente documento acredita que <strong>{datos['nombre']}</strong> ha cumplido 
                    satisfactoriamente con todos los requisitos académicos establecidos en el plan de estudios 
                    de la carrera de <strong>{datos['carrera']}</strong>.
                </p>
            </div>

            <div class="qr-code">
                <p style="margin-bottom: 10px;">
                    <strong>VERIFICA ESTE DOCUMENTO ESCANEANDO EL CÓDIGO QR</strong>
                </p>
                <img src="{base_url}/GeneradorTitulos/recursos/QR/{qr_filename}" alt="Código QR de verificación">
                <p style="margin-top: 10px; font-size: 12px; color: #666;">
                    Fecha de expedición: {datos['fecha_expedicion']}
                </p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>© {datetime.now().year} UNIVERSIDAD POLITÉCNICA DEL ESTADO DE NAYARIT</p>
        <p>Todos los derechos reservados</p>
    </div>
</div>
</body>
</html>
"""
    # Asegurarse de que el directorio de verificación existe
    os.makedirs(os.path.dirname(html_path), exist_ok=True)
    
    # Escribir el archivo HTML
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html)
