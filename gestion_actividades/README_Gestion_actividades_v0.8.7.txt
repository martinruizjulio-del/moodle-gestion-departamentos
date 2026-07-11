Gestion_actividades v0.8.7-alpha

Corrección defensiva de guardado:
- Antes de escribir en una tabla, filtra el registro para guardar solo columnas que existan realmente en la base de datos instalada.
- Esto evita fallos cuando la instalación real tiene una tabla que no coincide exactamente con la versión del código.
- Se aplica a talleres y ediciones de taller.
- La creación del grupo ya no debería romper el guardado de la edición.
- Si aún falla, la pantalla mostrará un detalle técnico más concreto en lugar de solo “Error escribiendo a la base de datos”.
