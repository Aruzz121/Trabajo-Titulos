import os
import sys
import xml.etree.ElementTree as ET
from plantilla import generar_pdf
from datetime import datetime
import re

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
        'matricula': alumno.get('curp'),  # Asumiendo que la matrícula está en el campo CURP
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

def buscar_archivo(base_dir, search_pattern, matricula, extension):
    """
    Busca un archivo que coincida con el patrón de búsqueda y la extensión.
    
    Args:
        base_dir: Directorio base para buscar
        search_pattern: Patrón de búsqueda (puede ser el nombre completo o parte)
        matricula: Número de matrícula (opcional, para compatibilidad)
        extension: Extensión del archivo a buscar (sin el punto)
    """
    # Primero intentar con búsqueda exacta
    exact_pattern = re.compile(rf"^{search_pattern}\.{extension}$", re.IGNORECASE)
    
    # Luego intentar con búsqueda flexible
    flex_pattern = re.compile(rf".*{search_pattern}.*\.{extension}$", re.IGNORECASE)
    
    # Buscar en el directorio base
    for fname in os.listdir(base_dir):
        if exact_pattern.match(fname):
            return os.path.join(base_dir, fname)
    
    # Si no se encontró con búsqueda exacta, intentar con búsqueda flexible
    for fname in os.listdir(base_dir):
        if flex_pattern.match(fname):
            return os.path.join(base_dir, fname)
    
    # Buscar en subcarpetas Gen-AAAA
    for item in os.listdir(base_dir):
        item_path = os.path.join(base_dir, item)
        if os.path.isdir(item_path) and item.startswith('Gen-'):
            # Primero búsqueda exacta
            for fname in os.listdir(item_path):
                if exact_pattern.match(fname):
                    return os.path.join(item_path, fname)
            # Luego búsqueda flexible
            for fname in os.listdir(item_path):
                if flex_pattern.match(fname):
                    return os.path.join(item_path, fname)
    
    return None

def obtener_siguiente_folio():
    """Obtiene el siguiente número de folio secuencial"""
    contador_path = os.path.join(os.path.dirname(__file__), "contador_folio.txt")
    try:
        with open(contador_path, 'r') as f:
            folio = int(f.read().strip()) + 1
    except (FileNotFoundError, ValueError):
        folio = 1
    
    # Guardar el siguiente folio
    with open(contador_path, 'w') as f:
        f.write(str(folio))
    
    return folio

# Importar generar_pdf desde plantilla
from plantilla import generar_pdf as generar_pdf_desde_plantilla

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Faltan argumentos: matrícula, carrera")
        sys.exit(1)

    matricula = sys.argv[1]
    carrera = sys.argv[2]

    base_dir = os.path.dirname(os.path.abspath(__file__))
    recursos_dir = os.path.join(base_dir, "recursos")

    # Obtener el siguiente folio secuencial
    folio = obtener_siguiente_folio()
    
    # Buscar archivos - manejar tanto el formato antiguo como el nuevo
    # Primero intentar con el formato nuevo (I-SOFT-170005)
    xml_file = buscar_archivo(recursos_dir, f"{carrera}-{matricula}", "", "xml")
    foto_file = buscar_archivo(recursos_dir, f"{carrera}-{matricula}", "", "jpg")
    
    # Si no se encuentra con el formato nuevo, intentar con el formato antiguo
    if not xml_file:
        # Extraer solo el código de carrera (ej: I-SOFT -> SOFT)
        carrera_codigo = carrera.split('-')[-1] if '-' in carrera else carrera
        xml_file = buscar_archivo(recursos_dir, f"{carrera_codigo}-{matricula}", "", "xml")
    if not foto_file:
        carrera_codigo = carrera.split('-')[-1] if '-' in carrera else carrera
        foto_file = buscar_archivo(recursos_dir, f"{carrera_codigo}-{matricula}", "", "jpg")

    if not xml_file:
        print("[ERROR] XML no encontrado")
        sys.exit(1)

    datos = xml_a_diccionario(xml_file)
    # Usar el folio secuencial en lugar del folio del XML
    datos['folio'] = str(folio)
    
    # Configurar la salida para evitar errores de codificación
    import sys
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    
    if not foto_file:
        print("[ADVERTENCIA] Foto no encontrada, se generará sin foto")

    # Generar el PDF
    try:
        # Usar sys.stdout.buffer para salida binaria segura
        if hasattr(sys.stdout, 'buffer'):
            output = sys.stdout.buffer
        else:
            output = sys.stdout
        
        # Importar la función de generación de PDF
        from plantilla import generar_pdf
        
        # Generar el PDF directamente a la salida estándar
        temp_output = io.BytesIO()
        generar_pdf(datos, temp_output, foto_path=foto_file)
        
        # Escribir el contenido del PDF en la salida estándar
        pdf_content = temp_output.getvalue()
        
        # Verificar que el PDF se generó correctamente
        if pdf_content and pdf_content.startswith(b'%PDF-'):
            output.write(pdf_content)
            output.flush()
        else:
            print("[ERROR] El archivo generado no es un PDF válido")
            if not pdf_content:
                print("El contenido del PDF está vacío")
            sys.exit(1)
            
    except Exception as e:
        print(f"[ERROR] Error al generar el PDF: {str(e)}")
        import traceback
        print(traceback.format_exc())
        sys.exit(1)
