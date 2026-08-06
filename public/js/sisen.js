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

    document.querySelectorAll('[data-payroll-calc]').forEach((scope) => {
        const fields = ['sueldo_base', 'bonos', 'horas_extra', 'deducciones', 'isr', 'imss'];
        const total = scope.querySelector('[name="total_preview"]');
        const calc = () => {
            const value = (name) => Number(scope.querySelector(`[name="${name}"]`)?.value || 0);
            const result = value('sueldo_base') + value('bonos') + value('horas_extra') - value('deducciones') - value('isr') - value('imss');
            if (total) total.value = result.toFixed(2);
        };
        fields.forEach((field) => scope.querySelector(`[name="${field}"]`)?.addEventListener('input', calc));
        calc();
    });
});
