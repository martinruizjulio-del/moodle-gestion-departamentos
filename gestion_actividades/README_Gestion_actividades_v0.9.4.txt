Gestion_actividades v0.9.4-alpha

Corrección de estabilidad:
- Se desactiva temporalmente la modificación automática de la página del curso.
- El botón ya no crea recursos Página/URL ni modifica secciones.
- Esto evita el error generalexceptionmessage al actualizar la vista del curso.
- La gestión de talleres queda centralizada en el panel del plugin.

Motivo:
- El formato/API del curso en esta instalación está rechazando la creación automática de recursos desde el plugin.
- Antes de reactivarlo conviene saber qué formato de curso se usa y qué módulos están habilitados.

Siguiente paso recomendado:
- Mantener el curso limpio y trabajar desde el panel del plugin.
- Más adelante, crear un bloque lateral o una página propia del plugin en lugar de insertar recursos en el curso.
