Gestion_actividades v0.6.0-alpha

Objetivo de la versión:
- Reordenar el panel para reducir confusión.
- Mostrar el flujo secuencial:
  1) Alumnos y notas de expediente.
  2) Talleres y ediciones de taller.
- Sustituir en la interfaz el uso de "oferta" por "taller" o "edición de taller".
- Añadir una vista general de talleres actuales y pasados.

Novedades:
- Nuevo panel principal dashboard.php con dos bloques:
  * Alumnos y notas de expediente.
  * Talleres.
- Vista general de ediciones de taller:
  * estado,
  * código,
  * nombre,
  * fecha,
  * fecha límite,
  * plazas,
  * inscritos,
  * profesor/es,
  * grupo,
  * acciones.
- Estados calculados:
  * Abierto,
  * Cerrado por plazas,
  * Cerrado por fecha,
  * Pasado.

Pendiente para próximas versiones:
- Añadir alumnos manualmente a una edición de taller.
- Asociar tarea o cuestionario obligatorio.
- Generar certificado solo si hay asistencia + tarea/cuestionario completado.
