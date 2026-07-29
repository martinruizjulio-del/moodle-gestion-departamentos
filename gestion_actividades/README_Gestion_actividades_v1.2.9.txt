Gestion_actividades v1.2.9-alpha

Corrección de tarea/cuestionario:
- Se elimina el intento de crear automáticamente la tarea/cuestionario con add_moduleinfo().
- En esta instalación estaba provocando “Error escribiendo a la base de datos”.
- Ahora task_activity.php abre el formulario nativo de Moodle para crear la actividad seleccionada:
  * Tarea si está seleccionado tarea o si no se detecta el tipo.
  * Cuestionario si se detecta cuestionario.
- No abre la configuración completa del taller.
- Tras crear la actividad en Moodle, queda pendiente vincularla como actividad requerida desde la edición.

Objetivo:
- Evitar el error de base de datos y permitir continuar la simulación con un flujo seguro.
