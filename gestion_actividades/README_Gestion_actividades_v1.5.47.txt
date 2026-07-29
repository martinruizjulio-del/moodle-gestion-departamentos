Gestion_actividades v1.5.47-alpha
=================================

Cambios de integración con block_gestion_hee v1.0.7-alpha:

- Añadida invalidación defensiva de la caché del bloque Gestión HEE cuando cambian datos que afectan a las horas del alumno.
- La invalidación se llama de forma segura: si el bloque no está instalado, está desactivado o su lib.php no está disponible, local_gestion_actividades sigue funcionando.
- Se invalidan horas tras:
  - subir certificado Tipo B;
  - validar/rechazar certificado Tipo B;
  - eliminar certificado Tipo B mediante el método delete_upload();
  - generar certificado Tipo A;
  - regenerar certificado Tipo A tras borrar el anterior;
  - crear historial de horas completadas;
  - modificar horas de un taller desde ficha básica o edición completa.

Requisito recomendado:
- Instalar/actualizar también block_gestion_hee v1.0.7-alpha para que la función block_gestion_hee_invalidate_user_cache() esté disponible.

Compatibilidad:
- Moodle 4.x.
- PHP 8.2.
