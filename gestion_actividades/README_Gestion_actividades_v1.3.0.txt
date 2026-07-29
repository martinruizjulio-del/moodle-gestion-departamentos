Gestion_actividades v1.3.0-alpha

Cambios:
1) Tarea/cuestionario:
- El mensaje de advertencia se limpia en la vista docente.
- El botón pasa a “Gestionar tarea/cuestionario”.
- task_activity.php permite:
  a) abrir Moodle para crear la tarea/cuestionario en la sección TALLERES TIPO A,
  b) volver a task_activity.php y vincular una actividad ya creada,
  c) si la actividad ya está vinculada, abrirla directamente.

2) Vinculación:
- Nueva detección de tareas/cuestionarios candidatos en el curso.
- Botón “Vincular esta actividad”.
- Guarda requiredcmid en la edición.

3) Pantalla dentro del taller:
- La actividad se crea en la sección TALLERES TIPO A, no en General, usando el formulario nativo de Moodle.
- La vista del taller puede abrir la actividad cuando esté vinculada.

Nota:
- Moodle no permite controlar totalmente el “volver” del formulario nativo sin tocar core; por eso se añade una pantalla de vinculación limpia.
