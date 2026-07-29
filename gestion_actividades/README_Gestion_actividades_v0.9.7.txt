Gestion_actividades v0.9.7-alpha

Generación de talleres con diagnóstico:
- Sigue usando etiquetas visibles dentro de TALLERES TIPO A.
- Ahora usa add_course_module + course_add_cm_to_section cuando están disponibles.
- Si no puede crear el taller, muestra una pantalla con el detalle:
  * módulo label no instalado,
  * tabla label no existe,
  * error de escritura concreto,
  * ya existía, etc.
- No crea secciones por taller.
- No limpia secciones antiguas automáticamente.

Objetivo:
- Saber exactamente por qué en esta instalación Moodle devolvía 0 sin mostrar nada en el curso.
