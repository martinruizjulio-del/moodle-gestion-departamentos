Gestion_actividades v1.1.0-alpha

Integra las tareas 1, 2 y 3.

Tarea 1: archivos/materiales del taller
- Nueva tabla local_ga_materials.
- Nueva pantalla material_edit.php.
- La vista pública del taller muestra materiales visibles.
- La vista docente permite añadir, editar y borrar materiales mediante nombre, descripción, URL y visibilidad.

Tarea 2: usuarios autorizados
- Nueva tabla local_ga_authorized.
- Nueva pantalla authorized_users.php.
- Permite buscar usuarios, añadirlos como gestores autorizados, quitarlos y ver la lista actual.
- Se añade enlace desde el panel de gestión si la versión del dashboard lo permite.

Tarea 3: vista docente del taller
- Nueva pantalla teacher_view.php.
- Permite gestionar materiales, abrir tarea/cuestionario vinculado, abrir edición completa, acceder a inscritos/estado, acceder a lista del curso, abrir asistencia vinculada y marcar “Taller terminado”.
- La vista pública del alumno sigue limpia; solo muestra un botón de acceso a vista docente si el usuario tiene permisos.

Nota:
- El botón “Taller terminado” marca la edición como completed. La comprobación automática asistencia + tarea/cuestionario + certificado queda preparada como siguiente paso.
