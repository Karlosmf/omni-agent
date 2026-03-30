# Plan de Implementación "Marca Blanca" (White-label)

## FASE 1: Base de Datos y Modelo de Configuración - COMPLETADO
- [x] Crear modelo `AgencySetting` y migración.
- [x] Cargar configuración en caché y compartir globalmente en `AppServiceProvider`.
- [x] Crear helper `hex_to_oklch` para inyección de estilos.

## FASE 2: Backend (Panel Filament v4) - COMPLETADO
- [x] Crear página de Filament `ManageAgencySettings`.
- [x] Implementar formulario de edición con `ColorPicker`, `FileUpload` y `Repeater`.
- [x] Soportar persistencia y limpieza de caché al guardar.

## FASE 3: Inyección Dinámica en el Frontend - COMPLETADO
- [x] Modificar layout principal y guest para inyectar CSS dinámico.
- [x] Sincronizar título, favicon y logos dinámicamente.

## FASE 4: Seeders y Testing (PestPHP) - COMPLETADO
- [x] Crear `AgencySettingSeeder` con valores por defecto (Luopan Viajes).
- [x] Implementar tests unitarios y de funcionalidad con Pest.

## FASE 5: Commit y Documentación - COMPLETADO
- [x] Actualizar `plan.md`.
- [x] Git commit de los cambios.
