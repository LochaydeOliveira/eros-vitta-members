/**
 * Password Toggle Functionality
 * Permite mostrar/ocultar senhas nos formulários
 */

// Função para alternar visibilidade da senha
function togglePasswordVisibility(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    const icon = button.querySelector('i');
    
    if (!input || !button || !icon) {
        console.warn('Elementos não encontrados para toggle de senha:', { inputId, buttonId });
        return;
    }
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        button.setAttribute('title', 'Ocultar senha');
        button.setAttribute('aria-label', 'Ocultar senha');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        button.setAttribute('title', 'Mostrar senha');
        button.setAttribute('aria-label', 'Mostrar senha');
    }
}

// Função para inicializar todos os toggles de senha na página
function initializePasswordToggles() {
    const toggleButtons = document.querySelectorAll('.password-toggle-btn');
    
    toggleButtons.forEach(button => {
        // Adicionar tooltip inicial
        button.setAttribute('title', 'Mostrar senha');
        button.setAttribute('aria-label', 'Mostrar senha');
        
        // Adicionar evento de teclado para acessibilidade
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const inputId = this.getAttribute('onclick').match(/togglePasswordVisibility\('([^']+)'/)[1];
                togglePasswordVisibility(inputId, this.id);
            }
        });
    });
}

// Função para validação de senhas em tempo real
function initializePasswordValidation() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        // Adicionar validação de força da senha (opcional)
        input.addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = this.parentElement.querySelector('.password-strength');
            
            if (strengthIndicator) {
                const strength = calculatePasswordStrength(password);
                updatePasswordStrengthIndicator(strengthIndicator, strength);
            }
        });
    });
}

// Função para calcular força da senha
function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    if (score <= 2) return 'weak';
    if (score <= 3) return 'medium';
    return 'strong';
}

// Função para atualizar indicador de força
function updatePasswordStrengthIndicator(indicator, strength) {
    indicator.className = `password-strength ${strength}`;
    
    const messages = {
        weak: 'Senha fraca',
        medium: 'Senha média',
        strong: 'Senha forte'
    };
    
    indicator.textContent = messages[strength];
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    initializePasswordToggles();
    initializePasswordValidation();
});

// Exportar funções para uso global
window.togglePasswordVisibility = togglePasswordVisibility;
window.initializePasswordToggles = initializePasswordToggles; 