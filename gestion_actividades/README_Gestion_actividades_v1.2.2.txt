Gestion_actividades v1.2.2-alpha

Corrección:
- Reescribe edition_students.php de forma segura.
- La lista “Inscritos del taller / asistencia” ya no debe lanzar dmlreadexception.
- Añade lectura defensiva con mensaje técnico dentro del plugin si vuelve a fallar.
- La lista de inscritos usa un SELECT con ID único de inscripción para evitar errores de registros duplicados.
- Mantiene búsqueda manual de alumnos filtrada por rol estudiante del curso.
