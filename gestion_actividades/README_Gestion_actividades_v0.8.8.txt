Gestion_actividades v0.8.8-alpha

Modo seguro:
- Guarda solo datos básicos de talleres y ediciones.
- Desactiva temporalmente:
  * creación automática de grupos,
  * creación automática de secciones,
  * creación automática de tareas/cuestionarios,
  * creación automática de certificados.
- Objetivo: confirmar que el guardado básico funciona y aislar el origen del dmlwriteexception.

Campos mínimos guardados:
- Taller: curso, código, nombre, descripción, horas si existe columna.
- Edición: taller, nombre, código, fecha, límite, plazas, estado y campos opcionales solo si existen.

Cuando esta versión guarde correctamente, se reactivarán las funciones automáticas una a una.
