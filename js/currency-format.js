/**
 * Currency Format - Formateo de moneda RD$ en tiempo real
 * Sistema Concilio - Módulo Finanzas
 * 
 * Uso: Agregar clase 'currency-input' a cualquier input de monto
 *      El valor se formatea automáticamente con comas mientras se escribe
 *      Al enviar el formulario, se limpia a número puro
 */

(function() {
    'use strict';

    /**
     * Formatea un número con comas como separador de miles
     * @param {string|number} value - Valor a formatear
     * @returns {string} Valor formateado (ej: 1,500.00)
     */
    function formatCurrency(value) {
        // Remover todo excepto números y punto decimal
        let cleanValue = String(value).replace(/[^\d.]/g, '');
        
        // Manejar múltiples puntos decimales (quedarse con el primero)
        const parts = cleanValue.split('.');
        if (parts.length > 2) {
            cleanValue = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Separar parte entera y decimal
        let integerPart = parts[0] || '0';
        let decimalPart = parts[1] !== undefined ? parts[1] : '';
        
        // Limitar decimales a 2
        if (decimalPart.length > 2) {
            decimalPart = decimalPart.substring(0, 2);
        }
        
        // Agregar comas a la parte entera
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        
        // Combinar resultado
        if (parts[1] !== undefined) {
            return integerPart + '.' + decimalPart;
        }
        return integerPart;
    }

    /**
     * Limpia el valor formateado para enviar al servidor
     * @param {string} value - Valor formateado (ej: 1,500.00)
     * @returns {string} Valor limpio (ej: 1500.00)
     */
    function cleanCurrency(value) {
        return String(value).replace(/,/g, '');
    }

    /**
     * Inicializa el formateo en un input
     * @param {HTMLElement} input - Elemento input
     */
    function initCurrencyInput(input) {
        // Formatear valor inicial si existe
        if (input.value) {
            input.value = formatCurrency(input.value);
        }

        // Evento: Formatear mientras escribe
        input.addEventListener('input', function(e) {
            const cursorPos = this.selectionStart;
            const oldLength = this.value.length;
            const oldValue = this.value;
            
            // Formatear el valor
            this.value = formatCurrency(this.value);
            
            // Ajustar posición del cursor
            const newLength = this.value.length;
            const diff = newLength - oldLength;
            
            // Calcular nueva posición del cursor
            let newPos = cursorPos + diff;
            
            // Si el usuario está borrando, mantener posición
            if (e.inputType === 'deleteContentBackward') {
                newPos = cursorPos;
            }
            
            // Asegurar que el cursor no se salga de rango
            newPos = Math.max(0, Math.min(newPos, this.value.length));
            
            this.setSelectionRange(newPos, newPos);
        });

        // Evento: Permitir solo números, punto, coma y teclas de control
        input.addEventListener('keydown', function(e) {
            const allowedKeys = [
                'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End'
            ];
            
            // Permitir teclas de control
            if (allowedKeys.includes(e.key)) return;
            
            // Permitir Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            if (e.ctrlKey && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) return;
            
            // Permitir números
            if (/^\d$/.test(e.key)) return;
            
            // Permitir punto decimal (solo uno)
            if (e.key === '.' && !this.value.includes('.')) return;
            
            // Bloquear todo lo demás
            e.preventDefault();
        });

        // Evento: Al perder foco, asegurar formato completo con decimales
        input.addEventListener('blur', function() {
            if (this.value && this.value !== '') {
                let cleanVal = cleanCurrency(this.value);
                let numVal = parseFloat(cleanVal);
                
                if (!isNaN(numVal) && numVal > 0) {
                    // Formatear con 2 decimales
                    this.value = formatCurrency(numVal.toFixed(2));
                } else {
                    this.value = '';
                }
            }
        });
    }

    /**
     * Procesa formulario antes de enviar - limpia valores
     * @param {HTMLFormElement} form - Formulario
     */
    function processFormBeforeSubmit(form) {
        const currencyInputs = form.querySelectorAll('.currency-input');
        currencyInputs.forEach(function(input) {
            input.value = cleanCurrency(input.value);
        });
    }

    /**
     * Inicialización global cuando el DOM está listo
     */
    function init() {
        // Inicializar todos los inputs con clase 'currency-input'
        const inputs = document.querySelectorAll('.currency-input');
        inputs.forEach(initCurrencyInput);

        // Interceptar envío de formularios para limpiar valores
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function() {
                processFormBeforeSubmit(this);
            });
        });
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Exponer funciones globalmente para uso externo
    window.CurrencyFormat = {
        format: formatCurrency,
        clean: cleanCurrency,
        init: initCurrencyInput,
        processForm: processFormBeforeSubmit
    };

})();
