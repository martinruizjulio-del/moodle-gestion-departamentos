Gestion_actividades v1.1.1-alpha

Correcciones sobre v1.1.0:

1) Usuarios autorizados
- El panel de gestión muestra ahora un botón visible:
  “Seleccionar usuarios autorizados”.
- Ese botón abre authorized_users.php, donde se busca y se añade/quita gestores.

2) Vista docente y rol estudiante
- La vista pública del taller ya no muestra botón de vista docente.
- teacher_view.php bloquea el acceso cuando se está usando el cambio de rol a estudiante.
- La vista docente exige permiso de gestión del plugin o capacidad de edición del curso.

3) Lista del curso
- Se elimina el enlace a la lista completa del curso desde la vista docente.
- Se sustituye por “Inscritos del taller / asistencia”, que abre la lista propia de inscritos de la edición.
- Evita desplegar todos los participantes del curso cuando lo que se necesita es la lista del taller.
