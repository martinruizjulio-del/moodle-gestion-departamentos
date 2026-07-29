Gestion_actividades v0.5.2-alpha

Mejoras:
- Opción para crear automáticamente un grupo Moodle para cada edición/oferta de taller.
- El grupo se crea dentro del curso Moodle asociado al taller base.
- La edición queda vinculada automáticamente al grupo creado.
- La pantalla muestra claramente el curso Moodle donde se crea/usa el grupo.
- Al crear/editar taller base, el curso se elige mediante selector, no escribiendo solo un ID.

Concepto:
- El grupo creado para una edición es el grupo donde los alumnos se apuntarán a esa edición concreta.
- Ejemplo:
  Taller base: ABD - Abdominales siempre
  Edición: ABD_E1
  Grupo creado: ABD_E1 - Abdominales siempre
- Ese grupo debe usarse después para restringir recursos, Attendance y certificado.
