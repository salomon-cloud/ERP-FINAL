const ISR_TABLA = [
        { min: 0.01, max: 746.04, cuota: 0.00, porc: 0.0192 },
        { min: 746.05, max: 6332.05, cuota: 14.32, porc: 0.0640 },
        { min: 6332.06, max: 11128.01, cuota: 371.83, porc: 0.1088 },
        { min: 11128.02, max: 12935.82, cuota: 893.63, porc: 0.1600 },
        { min: 12935.83, max: 15487.71, cuota: 1182.88, porc: 0.1792 },
        { min: 15487.72, max: 31236.49, cuota: 1640.18, porc: 0.2136 },
        { min: 31236.50, max: 49233.00, cuota: 5007.79, porc: 0.2352 },
        { min: 49233.01, max: 93993.90, cuota: 9238.92, porc: 0.3000 },
        { min: 93993.91, max: 125325.20, cuota: 22666.74, porc: 0.3200 },
        { min: 125325.21, max: 375975.61, cuota: 32693.36, porc: 0.3400 },
        { min: 375975.62, max: Infinity, cuota: 117914.98, porc: 0.3500 },
    ];

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.mobile-overlay');
        const toggle = document.querySelector('[data-sidebar-toggle]');

        if (toggle) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }

        document.querySelectorAll('[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!confirm(form.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        });

        const sugerirIsr = (sueldo) => {
            const tramo = ISR_TABLA.find((t) => sueldo > t.min && sueldo <= t.max);
            if (!tramo) return 0;
            return Math.round((tramo.cuota + (sueldo - tramo.min) * tramo.porc) * 100) / 100;
        };
        const sugerirImss = (sueldo) => Math.round(sueldo * 0.02375 * 100) / 100;

        document.querySelectorAll('[data-payroll-calc]').forEach((scope) => {
            const fields = ['sueldo_base', 'bonos', 'horas_extra', 'deducciones', 'isr', 'imss'];
            const total = scope.querySelector('[name="total_preview"]');
            const empleado = scope.querySelector('[name="empleado_id"]');
            const sueldo = scope.querySelector('[name="sueldo_base"]');
            const calc = () => {
                const value = (name) => Number(scope.querySelector(`[name="${name}"]`)?.value || 0);
                const result = value('sueldo_base') + value('bonos') + value('horas_extra') - value('deducciones') - value('isr') - value('imss');
                if (total) total.value = result.toFixed(2);
            };
            const autoFillSueldo = () => {
                if (!empleado || !sueldo) return;
                const option = empleado.options[empleado.selectedIndex];
                const base = Number(option?.dataset.sueldo || 0);
                if (!Number(sueldo.value)) sueldo.value = base.toFixed(2);
                autoFillDeducciones();
            };
            const autoFillDeducciones = () => {
                if (!sueldo) return;
                const base = Number(sueldo.value || 0);
                if (base > 0) {
                    scope.querySelector('[name="isr"]').value = sugerirIsr(base).toFixed(2);
                    scope.querySelector('[name="imss"]').value = sugerirImss(base).toFixed(2);
                }
                calc();
            };
            empleado?.addEventListener('change', autoFillSueldo);
            sueldo?.addEventListener('input', autoFillDeducciones);
            fields.forEach((field) => scope.querySelector(`[name="${field}"]`)?.addEventListener('input', calc));
            autoFillSueldo();
        });
    });
