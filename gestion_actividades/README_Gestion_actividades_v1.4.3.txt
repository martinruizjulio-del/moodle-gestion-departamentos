Gestion_actividades v1.4.3-alpha

Primera fase del Portafolio de certificados.

Añadido:
- /local/gestion_actividades/portfolio.php
  Vista del alumno: portafolio personal con certificados Tipo A generados por el sistema y bloque reservado para Tipo B.

- /local/gestion_actividades/portfolio_admin.php
  Vista del gestor: búsqueda de alumnos, resumen de certificados Tipo A, horas acumuladas y acceso al detalle de cada portafolio.

- /local/gestion_actividades/mycertificates.php
  Ahora redirige al nuevo portafolio.

- dashboard.php
  Añade acceso directo al Portafolio gestor, Mi portafolio y Plantilla única de certificado.

Concepto:
- Tipo A: certificados generados por el sistema tras completar taller, asistencia y actividad requerida.
- Tipo B: espacio preparado para fase posterior, donde el alumno podrá subir certificados y el gestor validarlos/rechazarlos.

Nota:
Esta versión no cambia todavía la base de datos para Tipo B. Usa la tabla de certificados existente para mostrar y organizar los certificados Tipo A ya generados.
