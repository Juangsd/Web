/**
 * script.js - Funcionalidades principales para Certi-Celen
 * @version 1.0
 * @author Tu Nombre
 */

document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidades generales para todas las páginas
    initMobileMenu();
    initFormValidations();
    initTooltips();
    initVerificationPage();
    initAdminPages();
    initStudentPages();
    
    // Otras inicializaciones según la página
    if (document.body.classList.contains('verification-page')) {
        enhanceVerificationForm();
    }
    
    if (document.body.classList.contains('admin-dashboard')) {
        initAdminDashboard();
    }
});

/**
 * Funciones principales
 */

function initMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            this.classList.toggle('open');
        });
    }
}

function initFormValidations() {
    // Validación general para formularios
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showFormErrors(this);
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('error');
        } else {
            input.classList.remove('error');
            
            // Validación especial para emails
            if (input.type === 'email' && !isValidEmail(input.value)) {
                isValid = false;
                input.classList.add('error');
            }
        }
    });
    
    return isValid;
}

function showFormErrors(form) {
    const firstError = form.querySelector('.error');
    if (firstError) {
        firstError.focus();
        
        // Mostrar mensaje flotante
        const errorMessage = document.createElement('div');
        errorMessage.className = 'form-error-message';
        errorMessage.textContent = 'Por favor complete todos los campos requeridos';
        document.body.appendChild(errorMessage);
        
        setTimeout(() => {
            errorMessage.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            errorMessage.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(errorMessage);
            }, 300);
        }, 3000);
    }
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function initTooltips() {
    const elements = document.querySelectorAll('[data-tooltip]');
    
    elements.forEach(el => {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = el.getAttribute('data-tooltip');
        el.appendChild(tooltip);
        
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
    
    function showTooltip(e) {
        const tooltip = this.querySelector('.tooltip');
        if (tooltip) {
            tooltip.style.opacity = '1';
            tooltip.style.visibility = 'visible';
        }
    }
    
    function hideTooltip() {
        const tooltip = this.querySelector('.tooltip');
        if (tooltip) {
            tooltip.style.opacity = '0';
            tooltip.style.visibility = 'hidden';
        }
    }
}

/**
 * Funciones específicas para la página de verificación
 */
function initVerificationPage() {
    const verificationForm = document.querySelector('.verification-form');
    if (!verificationForm) return;
    
    // Auto-focus en el campo de código
    const codeInput = verificationForm.querySelector('#codigo');
    if (codeInput) {
        codeInput.focus();
        
        // Pegar desde el portapapeles si el usuario pega
        codeInput.addEventListener('paste', function(e) {
            setTimeout(() => {
                this.value = this.value.trim();
                if (this.value) {
                    this.form.submit();
                }
            }, 10);
        });
    }
    
    // Copiar código al hacer clic
    const codeDisplay = document.querySelector('.detail-value');
    if (codeDisplay) {
        codeDisplay.addEventListener('click', function() {
            const textToCopy = this.textContent.trim();
            navigator.clipboard.writeText(textToCopy).then(() => {
                showToast('Código copiado al portapapeles');
            });
        });
    }
}

function enhanceVerificationForm() {
    // Agregar funcionalidad QR si está disponible
    if (typeof QRScanner !== 'undefined') {
        initQRScanner();
    }
}

function initQRScanner() {
    const qrButton = document.createElement('button');
    qrButton.className = 'btn qr-scanner-btn';
    qrButton.innerHTML = '<i class="icon-qrcode"></i> Escanear QR';
    
    const codeInput = document.querySelector('#codigo');
    if (codeInput) {
        codeInput.parentNode.appendChild(qrButton);
        
        qrButton.addEventListener('click', function() {
            QRScanner.scan((err, result) => {
                if (err) {
                    showToast('Error al escanear QR: ' + err.message, 'error');
                    return;
                }
                if (result) {
                    codeInput.value = result;
                    codeInput.form.submit();
                }
            });
            
            QRScanner.show();
        });
    }
}

/**
 * Funciones para páginas de administrador
 */
function initAdminPages() {
    // Tablas con ordenación
    const sortableTables = document.querySelectorAll('table[data-sortable]');
    
    sortableTables.forEach(table => {
        const headers = table.querySelectorAll('th[data-sort]');
        
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const column = this.getAttribute('data-sort');
                const direction = this.getAttribute('data-sort-direction') || 'asc';
                const newDirection = direction === 'asc' ? 'desc' : 'asc';
                
                // Actualizar UI
                headers.forEach(h => h.removeAttribute('data-sort-direction'));
                this.setAttribute('data-sort-direction', newDirection);
                
                // Ordenar tabla
                sortTable(table, column, newDirection);
            });
        });
    });
    
    // Confirmación para acciones importantes
    const dangerousActions = document.querySelectorAll('.btn-danger, [data-confirm]');
    dangerousActions.forEach(action => {
        action.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || '¿Está seguro de realizar esta acción?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

function sortTable(table, column, direction) {
    // Implementación básica de ordenación de tablas
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`td[data-column="${column}"]`).textContent.trim();
        const bValue = b.querySelector(`td[data-column="${column}"]`).textContent.trim();
        
        if (direction === 'asc') {
            return aValue.localeCompare(bValue);
        } else {
            return bValue.localeCompare(aValue);
        }
    });
    
    // Limpiar y reinsertar filas ordenadas
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * Funciones para páginas de estudiante
 */
function initStudentPages() {
    // Validación especial para formulario de solicitud
    const requestForm = document.querySelector('form#solicitud-certificado');
    if (requestForm) {
        requestForm.addEventListener('submit', function(e) {
            const fileInput = this.querySelector('input[type="file"]');
            if (fileInput && !fileInput.value) {
                e.preventDefault();
                showToast('Debe seleccionar un archivo adjunto', 'error');
                fileInput.focus();
            }
        });
    }
    
    // Vista previa de documentos
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.getAttribute('data-preview');
            const preview = document.getElementById(previewId);
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
}

/**
 * Utilidades
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Exportar funciones para uso en otros scripts si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateForm,
        showToast,
        initMobileMenu
    };
}