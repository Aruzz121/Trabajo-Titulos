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
        pdf.image(foto_path, x=15, y=60, w=35, h=45)

    # DATOS DEL ALUMNO
    pdf.set_xy(51, 76.5)
    pdf.set_font("Arial", "B", 12)
    pdf.multi_cell(120, 6, datos['nombre'])

    pdf.set_xy(51,93)
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
    #pdf.line(25, 165, 185, 165)

    # DATOS ADMINISTRATIVOS EN DOS LÍNEAS
   
    pdf.set_xy(65, 155)
    pdf.cell(70, 6, f"{datos['clave_institucion']} ")
    pdf.set_xy(110, 155)
    pdf.cell(70, 6, f"{datos['clave_carrera']}")
    pdf.set_xy(150,155)
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

    # QR
    qr = qrcode.make(f"https://upnay.edu.mx/")
    qr_path = os.path.join(recursos_dir, "qr_temp.png")
    qr.save(qr_path)
    pdf.image(qr_path, x=20.5, y=166, w=30)

   

    # GUARDAR PDF
    pdf.output(salida)
