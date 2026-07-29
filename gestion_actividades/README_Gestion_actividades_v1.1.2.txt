Gestion_actividades v1.1.2-alpha

Corrección:
- La vista pública del taller vuelve a mostrar el botón “Vista docente del taller” solo a quien corresponda:
  * administrador,
  * profesor con edición del curso,
  * usuario con capacidad local/gestion_actividades:manage,
  * usuario autorizado desde el selector.
- El botón sigue oculto para alumnos.
- Si se usa “Cambiar rol a estudiante”, el botón también se oculta.
- teacher_view.php usa la misma comprobación de acceso robusta.

Objetivo:
- Alumno: vista limpia sin herramientas docentes.
- Profesor/admin/gestor: acceso claro a la vista docente desde el taller.
