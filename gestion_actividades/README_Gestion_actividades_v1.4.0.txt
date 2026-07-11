Gestion_actividades v1.4.0-alpha

Certificados Talleres Tipo A:
- Añade plantilla única editable de certificado.
- Incluye estética base del Word UCV subido: pix/certificate_ucv_bg.jpg y templates/UCV_WORD_GENERAL.dotx.
- Genera PDF con fondo UCV, título, texto editable y variables.
- Variables disponibles:
  {alumno}, {taller}, {codigo_taller}, {fecha}, {horas}, {curso_academico}, {fecha_emision}, {codigo_certificado}
- Genera certificados solo para alumnos inscritos con:
  * asistencia confirmada,
  * tarea/cuestionario completado o entregado, si existe actividad requerida.
- Guarda PDF en el área de archivos del plugin y lo lista para alumno/gestor.

Rutas:
- Plantilla: /local/gestion_actividades/certificate_template.php
- Generar certificados de edición: /local/gestion_actividades/generate_certificates.php?id=ID_EDICION&sesskey=...
- Gestor: /local/gestion_actividades/certificates.php?editionid=ID_EDICION
- Alumno: /local/gestion_actividades/mycertificates.php
