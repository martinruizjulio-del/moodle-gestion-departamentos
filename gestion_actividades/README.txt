

## v1.5.50-alpha
- Corrige la importación de reconocimiento institucional para no depender de la extensión PHP ZipArchive.
- La lectura de .xlsx usa el desempaquetador de Moodle (`get_file_packer('application/zip')`), más compatible con servidores Moodle 4.x/PHP 8.2.
- Mantiene previsualización sin guardar, cruce por email y guardado solo tras confirmación.


v1.5.54-alpha: La nota numérica de la tarea aparece en el certificado de Taller Tipo A cuando el taller requiere tarea.


v1.5.57-alpha: Reordena el panel de talleres: 5 pasa a ser Talleres Tipo B, elimina del panel la Validación de Talleres Tipo B externos y añade distinción Tipo A/Tipo B en talleres archivados.


v1.5.58-alpha: añade traspasos de horas Tipo A a Tipo B con texto obligatorio, listado de gestión y ajuste de cómputo sin duplicar horas.


v1.5.59-alpha: Unifica la vista del alumno como Mi portafolio HEE, elimina del portafolio el flujo antiguo de Talleres Tipo B externos y deshabilita la subida directa de certificados externos.

v1.5.62-alpha: añade criterio automático de 54 horas para ocultar la autoevaluación, acceso a notas de ediciones archivadas, filtros y ordenación en listados, y sincronización masiva del cuaderno al guardar varias notas.

v1.5.63-alpha: auditoría integral de estabilidad y eficiencia; oculta también la sección del cuestionario hasta 54 horas y conserva las restricciones previas.

v1.5.64-alpha: corrige la ocultación completa de la sección de autoevaluación mediante la API de Moodle y publica/restaura automáticamente la tarjeta del taller tras guardar una edición.
