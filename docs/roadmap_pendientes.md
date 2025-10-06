# Ruta de desarrollo para completar módulos pendientes

Este documento consolida los frentes de trabajo necesarios para cubrir las brechas detectadas entre la base de datos `sistema_academico_final_MFA.sql` y la implementación actual. Se propone abordar los temas en fases, priorizando los flujos críticos para usuarios finales.

## 1. Autenticación y flujo de acceso
- **Formulario de inicio de sesión**: conectar `index.php` con `login.php` mediante `method="POST"` y `action="login.php"`. Validar campos vacíos en frontend.
- **Verificación en `login.php`**: revisar lógica de sesiones, manejo de errores y redirecciones por rol (`role_id`). Implementar cierre de sesión consistente.
- **Registro y alta inicial**: en `register.php`/`guardar_estudiante.php`, mapear correctamente `role_id`, generar matrícula inicial (tabla `matriculas`) y disparar correo de verificación (`verify_email.php`).

## 2. Autoservicio del estudiante
- **Dashboard dinámico**: reemplazar datos de ejemplo por consultas reales a `matriculas`, `calificaciones`, `asistencia` y `tareas` según el estudiante logueado.
- **Gestión de tareas**:
  - Listar tareas (`tareas`) filtradas por cursos en los que está matriculado.
  - Implementar subida de entregas y almacenamiento en `tarea_entregas` (y archivos en servidor).
  - Mostrar estado de entregas, retroalimentación y calificaciones.
- **Módulos adicionales**: crear vistas funcionales para perfil (`estudiante_perfil.php`) y configuración (`estudiante_configuracion.php`) que permitan actualizar datos personales (tabla `usuarios`) y preferencias.

## 3. Gestión académica y administrativa
- **Materias y horarios**:
  - Construir CRUD para `materias` y `horarios` accesible para director/administrador.
  - Validar integridad con `cursos`, `profesores` y evitar solapamientos de horario.
- **Matrículas**: desarrollar flujo para asignar estudiantes a cursos (`matriculas`), con validaciones de cupos y prerequisitos.
- **Materiales de clase**: convertir la vista estática en un módulo que permita a profesores subir recursos (`materiales`) y a estudiantes descargarlos.

## 4. Evaluaciones y seguimiento
- **Calificaciones**: interfaz para que profesores registren calificaciones (`calificaciones`) ligadas a tareas o evaluaciones.
- **Asistencia**: implementar captura de asistencia por clase (`asistencia`) con reportes por estudiante y por curso.
- **Reportes y analítica**: añadir tableros para director con KPIs (promedios, inasistencias, entregas pendientes) usando consultas agregadas.

## 5. Configuración y preferencias de usuario
- **Perfil del profesor/director**: completar formularios de `pages/profesor_configuracion.php` (y equivalentes) con acciones POST que actualicen `usuarios`.
- **Gestión de contraseñas**: permitir actualización segura y recuperación (tokens temporales, caducidad).
- **Configuraciones adicionales**: notificaciones (email/MFA) y preferencia de idioma si aplica.

## 6. Infraestructura y calidad
- **Validaciones y sanitización**: aplicar filtros a entradas, protección CSRF y prepared statements en todas las operaciones.
- **Pruebas**: preparar dataset de prueba e incorporar pruebas funcionales básicas (PHPUnit o integración manual documentada).
- **Documentación**: mantener README y manuales de uso actualizados conforme se completen los módulos.

## Fases sugeridas
1. **Fase 1**: Autenticación, matrícula inicial y dashboard estudiantil mínimo viable.
2. **Fase 2**: Gestión académica (materias, horarios, materiales) y flujos de tareas.
3. **Fase 3**: Evaluaciones, asistencia y reportes.
4. **Fase 4**: Configuración avanzada y endurecimiento de seguridad.

Cada fase debe cerrarse con pruebas, revisión de código y actualización de documentación para asegurar coherencia con la base de datos existente.
