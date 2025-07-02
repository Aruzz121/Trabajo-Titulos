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
        'autorizacionReconocimiento':carrera.get('autorizacionReconocimiento'),
        'modalidadTitulacion':expedicion.get('modalidadTitulacion'),
        'cumplioServicioSocial':expedicion.get('cumplioServicioSocial'),
        'fundamentoLegalServicioSocial':expedicion.get('fundamentoLegalServicioSocial'),
        'entidadFederativa':expedicion.get('entidadFederativa'),
        'folio': root.get('folioControl')
    }

if __name__ == "__main__":
    datos = xml_a_diccionario("230032.xml")
    generar_pdf(datos, "TituloDigital.pdf")
    """archivo_xml = input("🔍 Ingresa el nombre del archivo XML (ej. archivo.xml): ").strip()
    
    if not os.path.exists(archivo_xml):
        print("❌ Archivo XML no encontrado.")
    else:
        datos = xml_a_diccionario(archivo_xml)
        generar_pdf(datos, "TituloDigital.pdf")
        print("✅ PDF generado correctamente como 'TituloDigital.pdf'")"""
