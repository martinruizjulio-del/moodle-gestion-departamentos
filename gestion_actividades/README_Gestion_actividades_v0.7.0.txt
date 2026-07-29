Gestion_actividades v0.7.0-alpha

Nuevas funciones:
1) Alumnos manuales:
- Desde una edición de taller aparece "Alumnos manuales y estado".
- Permite buscar alumnos del curso por nombre, email, username o idnumber.
- Añade al alumno al grupo Moodle asociado a la edición.
- Lo registra internamente como inscripción manual.
- Respeta el límite de plazas y la regla de no repetir taller, salvo que se use "Forzar".

2) Tarea/cuestionario obligatorio:
- En la edición de taller se puede seleccionar una actividad obligatoria del curso.
- Puede ser una Tarea (assign) o un Cuestionario (quiz).
- Para certificado, el alumno debe:
  a) estar marcado como asistente,
  b) tener completada la tarea/cuestionario asociado.
- La pantalla "Alumnos manuales y estado" muestra:
  * asistió,
  * tarea/cuestionario completado,
  * puede generar certificado.

Notas:
- Para que Moodle detecte la actividad completada, la finalización de actividad debe estar activada en la tarea/cuestionario.
- La generación real del certificado con Custom certificate queda para una fase posterior.
