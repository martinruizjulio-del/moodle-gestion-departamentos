Gestion_actividades v0.5.0-alpha

Nueva estructura de talleres:
- Taller base: por ejemplo T04 - Emprender en deporte.
- Ediciones/ofertas del taller: un mismo taller puede ofertarse varias veces en el año.
- Cada edición puede tener:
  * fecha,
  * fecha límite de inscripción,
  * plazas máximas,
  * uno o varios profesores responsables,
  * grupo Moodle asociado,
  * ID de Attendance,
  * ID de certificado.

Regla implementada:
- Un alumno que ya haya realizado un taller base no podrá repetirlo en otra edición del mismo taller.
- La exclusión se hace por código de taller base.

Nueva navegación:
- En la página principal aparece botón "Talleres".
- Desde Talleres se crean talleres base.
- Dentro de cada taller se crean ediciones/ofertas.
- Cada edición puede sincronizar inscritos desde su grupo Moodle.
- Si hay más miembros que plazas, los sobrantes quedan como over_places.
- Si el alumno ya realizó el taller base, queda como blocked_repeat.
- Si se alcanzan plazas, la edición se marca como closed_full.

Pendiente:
- Crear automáticamente grupo/Attendance/certificado desde la edición.
- Sincronizar certificado real con Custom certificate.
- Mostrar certificados en My certificates.
- Sustituir campos ID manuales por selectores visuales de módulos.
