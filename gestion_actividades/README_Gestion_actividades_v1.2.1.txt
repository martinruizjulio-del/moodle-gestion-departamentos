Gestion_actividades v1.2.1-alpha

Corrección sobre v1.2.0:
- Restaura el método can_manage_workshop(), necesario para abrir la vista pública del taller.
- Mantiene la simulación completa de v1.2.0:
  * gestores autorizados filtrados por profesores/gestores del curso,
  * alumnos manuales filtrados por rol estudiante,
  * materiales con subida de archivo y URL,
  * lista de asistencia de inscritos,
  * acceso a tarea/cuestionario vinculado.
- Añade protección en la zona de materiales para que un fallo de archivo no rompa toda la vista pública.

Esta versión corrige el error general al entrar en “Ver taller”.
