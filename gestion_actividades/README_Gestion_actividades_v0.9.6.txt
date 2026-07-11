Gestion_actividades v0.9.6-alpha

Generación de talleres en el curso:
- Cambia la generación a etiquetas visibles de Moodle dentro de TALLERES TIPO A.
- No usa add_moduleinfo ni recurso URL.
- Crea una entrada visible con:
  * código y nombre del taller,
  * horas,
  * enlace a las ediciones.
- No crea secciones por taller.
- No limpia secciones antiguas automáticamente en esta versión para evitar errores de formato de curso.
- Si falla, intenta mostrar el detalle técnico dentro del plugin en lugar de enviar a MoodleDocs.

Objetivo:
- Conseguir que el taller aparezca dentro de la única sección TALLERES TIPO A de forma compatible.
