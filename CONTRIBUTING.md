# Contributing to Omni-Agent

¡Primero que nada, gracias por tomarte el tiempo de contribuir a **Omni-Agent**! 🚀

Este proyecto es Open Source y fomenta la colaboración comunitaria para ayudar a crear la mejor herramienta todo-en-uno para agencias de viajes. Tu ayuda es vital.

## 🤝 ¿Cómo puedo contribuir?

### Reporte de Bugs
Si encuentras un error o bug:
- Revisa si el bug ya ha sido reportado en la pestaña "Issues".
- Si no está reportado, abre un "Issue" detallando:
  - Comportamiento esperado.
  - Comportamiento actual.
  - Pasos para reproducir el bug.
  - Entorno (Versión PHP, Navegador, OS).

### Proponiendo Mejoras o Nuevas Funcionalidades
Si tienes una idea increíble para sumar al ERP o al CRM:
- Abre un "Issue" etiquetado como `enhancement` o `feature request` antes de escribir código.
- De debatirá la viabilidad de la idea.

### Realizando Cambios (Pull Requests)
1. Haz un "Fork" de este repositorio.
2. Crea una rama (`branch`) con tu nueva funcionalidad: `git checkout -b feature/MiNuevaFuncionalidad` o `fix/BugEnFacturacion`.
3. Sigue los estándares de código de Laravel (usamos [Laravel Pint](https://laravel.com/docs/pint) para formateo de código). Antes de hacer commit, ejecuta:
   ```bash
   ./vendor/bin/pint
   ```
4. Asegúrate que los tests de PHPUnit pasen exitosamente:
   ```bash
   php artisan test
   ```
5. Haz commit de tus cambios: `git commit -m "feat: Agregado módulo X"`.
6. Haz push a tu fork: `git push origin feature/MiNuevaFuncionalidad`.
7. Abre un Pull Request describiendo exhaustivamente qué cambios introdujiste.

## 🛠️ Estack Tecnológico
- **Backend:** Laravel 12, PHP 8.3+
- **Frontend Panel:** Filament v4, Livewire v3, Tailwind CSS
- **Frontend Cliente:** Blade, Alpine.js, Tailwind v4
- **Testing:** Pest PHP

¡Feliz desarrollo de código libre! 🌍
