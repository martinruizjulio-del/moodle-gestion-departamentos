Gestion_actividades v1.4.5-alpha

Portafolio PDF:
- El alumno puede descargar su portafolio completo en PDF desde /local/gestion_actividades/portfolio.php.
- El PDF incluye portada con nombre del alumno, curso, horas Tipo A, horas Tipo B validadas y total reconocido.
- La portada es editable por gestores desde /local/gestion_actividades/portfolio_cover_template.php.
- Los gestores pueden descargar el portafolio PDF de un alumno desde /local/gestion_actividades/portfolio_admin.php.
- Los gestores pueden descargar todos los portafolios en un ZIP desde /local/gestion_actividades/portfolio_pdf_all.php?sesskey=...

Variables de portada:
{alumno}
{curso}
{horas_tipo_a}
{horas_tipo_b}
{horas_total}
{fecha_emision}

Notas:
- El PDF resume Tipo A generado por sistema y Tipo B subido por el alumno.
- En el total solo suman las horas Tipo B validadas.
- Los PDF originales de certificados siguen descargándose aparte desde las tablas del portafolio.
