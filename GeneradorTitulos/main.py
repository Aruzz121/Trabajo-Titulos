import os
import xml.etree.ElementTree as ET
from plantilla import generar_pdf
from datetime import datetime

def xml_a_diccionario(xml_path):
    tree = ET.parse(xml_path)
    root = tree.getroot()
    
    ns = {'ns': 'https://www.siged.sep.gob.mx/titulos/'}
    
    institucion = root.find('ns:Institucion', ns)
    carrera = root.find('ns:Carrera', ns)
    alumno = root.find('ns:Profesionista', ns)
    expedicion = root.find('ns:Expedicion', ns)

    def formatear_fecha(fecha_str):
        meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio",
                 "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"]
        fecha = datetime.strptime(fecha_str, "%Y-%m-%d")
        return f"{fecha.day} de {meses[fecha.month-1]} de {fecha.year}"
    
    return {
        'nombre': f"{alumno.get('nombre')} {alumno.get('primerApellido')} {alumno.get('segundoApellido')}",
        'carrera': carrera.get('nombreCarrera'),
        'fecha_examen': formatear_fecha(expedicion.get('fechaExencionExamenProfesional')),
        'fecha_expedicion': formatear_fecha(expedicion.get('fechaExpedicion')),
        'clave_institucion': institucion.get('cveInstitucion'),
        'clave_carrera': carrera.get('cveCarrera'),
        'fecha_terminacion': datetime.strptime(carrera.get('fechaTerminacion'), "%Y-%m-%d").strftime("%d-%m-%Y"),
        'autorizacionReconocimiento': carrera.get('autorizacionReconocimiento'),
        'modalidadTitulacion': expedicion.get('modalidadTitulacion'),
        'cumplioServicioSocial': expedicion.get('cumplioServicioSocial'),
        'fundamentoLegalServicioSocial': expedicion.get('fundamentoLegalServicioSocial'),
        'entidadFederativa': expedicion.get('entidadFederativa'),
        'folio': root.get('folioControl')
    }

if __name__ == "__main__":
    # Obtener ruta del script actual
    BASE_DIR = os.path.dirname(os.path.abspath(__file__))

    # Ruta completa al XML
    xml_file = os.path.join(BASE_DIR, "230032.xml")

    # Leer y procesar el XML
    datos = xml_a_diccionario(xml_file)

    # Generar PDF en la misma carpeta
    output_pdf = os.path.join(BASE_DIR, "TituloDigital.pdf")
    generar_pdf(datos, output_pdf)
    print("✅ PDF generado correctamente.")
