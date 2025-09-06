// API JavaScript para simular funcionalidades PHP
class MemberAPI {
    constructor() {
        this.baseURL = window.location.origin + '/area-membros/';
        this.users = [
            { email: 'admin@exemplo.com', password: '123456', name: 'Administrador', role: 'admin' },
            { email: 'veramdssoares@gmail.com', password: 'Jw5$Gp8ews', name: 'Vera Soares', role: 'client' }
        ];
        this.currentUser = null;
        this.purchases = [
            { user_id: 1, product: 'libido-renovado', approved_at: '2024-01-01', status: 'approved' }
        ];
    }

    // Simular login
    async login(email, password) {
        return new Promise((resolve) => {
            setTimeout(() => {
                const user = this.users.find(u => u.email === email && u.password === password);
                if (user) {
                    this.currentUser = user;
                    localStorage.setItem('member_user', JSON.stringify(user));
                    resolve({ success: true, user: user });
                } else {
                    resolve({ success: false, error: 'Email ou senha incorretos' });
                }
            }, 500);
        });
    }

    // Simular logout
    logout() {
        this.currentUser = null;
        localStorage.removeItem('member_user');
    }

    // Verificar se está logado
    isLoggedIn() {
        if (this.currentUser) return true;
        const stored = localStorage.getItem('member_user');
        if (stored) {
            this.currentUser = JSON.parse(stored);
            return true;
        }
        return false;
    }

    // Simular verificação de compra
    async checkPurchase(userId) {
        return new Promise((resolve) => {
            setTimeout(() => {
                const purchase = this.purchases.find(p => p.user_id === userId);
                if (purchase) {
                    const approvedDate = new Date(purchase.approved_at);
                    const now = new Date();
                    const daysDiff = Math.floor((now - approvedDate) / (1000 * 60 * 60 * 24));
                    resolve({
                        hasPurchase: true,
                        canDownload: daysDiff >= 7,
                        daysLeft: Math.max(0, 7 - daysDiff),
                        approvedAt: purchase.approved_at
                    });
                } else {
                    resolve({ hasPurchase: false });
                }
            }, 300);
        });
    }

    // Simular listagem de e-books
    async getEbooks() {
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve([
                    { name: 'Guia Completo - Libido Renovado', file: 'guia-completo.pdf', size: '2.5 MB' },
                    { name: 'Exercícios Práticos', file: 'exercicios-praticos.pdf', size: '1.8 MB' },
                    { name: 'Meditações Guiadas', file: 'meditacoes-guiadas.pdf', size: '3.2 MB' }
                ]);
            }, 200);
        });
    }

    // Simular listagem de áudios
    async getAudios() {
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve([
                    { name: 'Meditação Guiada - Relaxamento', file: 'meditacao-relaxamento.mp3', size: '15.2 MB' },
                    { name: 'Exercícios de Respiração', file: 'exercicios-respiracao.mp3', size: '12.8 MB' },
                    { name: 'Técnicas de Conexão', file: 'tecnicas-conexao.mp3', size: '18.5 MB' }
                ]);
            }, 200);
        });
    }

    // Simular download
    async downloadFile(type, filename) {
        return new Promise((resolve) => {
            setTimeout(() => {
                // Simular download
                const link = document.createElement('a');
                link.href = `#download-${type}-${filename}`;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                resolve({ success: true, message: 'Download iniciado' });
            }, 100);
        });
    }

    // Simular visualização
    async viewFile(type, filename) {
        return new Promise((resolve) => {
            setTimeout(() => {
                // Simular visualização
                window.open(`#view-${type}-${filename}`, '_blank');
                resolve({ success: true, message: 'Visualização aberta' });
            }, 100);
        });
    }

    // Simular webhook
    async processWebhook(data) {
        return new Promise((resolve) => {
            setTimeout(() => {
                console.log('Webhook processado:', data);
                resolve({ success: true, message: 'Webhook processado com sucesso' });
            }, 500);
        });
    }

    // Simular log
    log(message, level = 'info') {
        const timestamp = new Date().toISOString();
        const logEntry = `[${timestamp}] [${level.toUpperCase()}] ${message}`;
        console.log(logEntry);
        
        // Salvar no localStorage para debug
        const logs = JSON.parse(localStorage.getItem('member_logs') || '[]');
        logs.push(logEntry);
        if (logs.length > 100) logs.shift(); // Manter apenas os últimos 100 logs
        localStorage.setItem('member_logs', JSON.stringify(logs));
    }

    // Obter logs
    getLogs() {
        return JSON.parse(localStorage.getItem('member_logs') || '[]');
    }

    // Limpar logs
    clearLogs() {
        localStorage.removeItem('member_logs');
    }
}

// Instância global da API
window.memberAPI = new MemberAPI();
