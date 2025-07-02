import os
from fpdf import FPDF
from fpdf.enums import Align
import qrcode

class PDF(FPDF):
    def header(self):
        # No agregamos encabezado si ya viene en fondo_hoja.png
        pass

    def footer(self):
        #self.set_y(-15)
        #self.set_font("Arial", "I", 8)
        #self.cell(0, 10, f"Página {self.page_no()}", align=Align.C)
        pass

def generar_pdf(datos, salida="TituloDigital.pdf"):
    pdf = PDF()
    pdf.add_page()

    base_dir = os.path.dirname(__file__)
    recursos_dir = os.path.join(base_dir, "recursos")

    # FONDO
    fondo_path = os.path.join(recursos_dir, "fondo_hoja.png")
    if os.path.exists(fondo_path):
        pdf.image(fondo_path, x=0, y=0, w=210, h=297)

    # FOTO DEL ALUMNO
    foto_path = os.path.join(recursos_dir, "foto_alumno.jpg")
    if os.path.exists(foto_path):
        pdf.image(foto_path, x=20, y=48, w=35, h=45)

    # DATOS DEL ALUMNO
    pdf.set_xy(70, 50)
    pdf.set_font("Arial", "", 11)
    pdf.cell(0, 6, "Se expide a:", ln=True)
    
    pdf.set_font("Arial", "B", 12)
    pdf.set_x(70)
    pdf.multi_cell(120, 6, datos['nombre'])

    pdf.set_x(70)
    pdf.set_font("Arial", "", 11)
    pdf.cell(0, 6, "El título de:", ln=True)

    pdf.set_x(70)
    pdf.set_font("Arial", "B", 12)
    pdf.multi_cell(120, 6, datos['carrera'])

    # TEXTO LEGAL
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

    # LÍNEA DIVISORIA
    pdf.line(25, 165, 185, 165)

    # DATOS ADMINISTRATIVOS EN DOS LÍNEAS
    pdf.set_font("Arial", "", 10)
    pdf.set_xy(25, 170)
    pdf.cell(70, 6, f"Clave de Institución: {datos['clave_institucion']}", ln=0)
    pdf.cell(70, 6, f"Clave de Carrera: {datos['clave_carrera']}", ln=1)

    pdf.set_x(25)
    pdf.cell(70, 6, f"Fecha de Terminación: {datos['fecha_terminacion']}", ln=0)
    pdf.cell(70, 6, f"Folio: {datos['folio']}", ln=1)

    # LÍNEA DIVISORIA INFERIOR
    pdf.line(25, 185, 185, 185)

    # QR
    qr = qrcode.make(f"https://proyecta.upen.edu.mx/valida?folio={datos['folio']}")
    qr_path = os.path.join(recursos_dir, "qr_temp.png")
    qr.save(qr_path)
    pdf.image(qr_path, x=28, y=190, w=28)

    # TEXTO QR
    pdf.set_font("Arial", "", 7)
    pdf.set_xy(25, 220)
    pdf.cell(60, 4, "QR para validar la información", ln=1)
    pdf.set_x(25)
    pdf.cell(60, 4, "en proyecta.upen.edu.mx", ln=1)

    # SELLO DIGITAL
    sello_path = os.path.join(recursos_dir, "sello_digital.png")
    if os.path.exists(sello_path):
        pdf.image(sello_path, x=150, y=190, w=40)

    # SELLOS Y CADENA ORIGINAL
    pdf.set_xy(25, 235)
    pdf.set_font("Arial", "", 9)
    pdf.cell(0, 6, "Sello Digital Rector:", ln=1)
    pdf.cell(0, 6, "Sello Digital Servicios Escolares:", ln=1)
    pdf.cell(0, 6, "Cadena Original:", ln=1)

    # GUARDAR PDF
    pdf.output(salida)
