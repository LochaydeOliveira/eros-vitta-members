/**
 * Meta Conversions API - Cliente JavaScript
 * Envia eventos de conversão para a Meta via API do servidor
 */

class MetaConversions {
    constructor() {
        this.apiBase = '/api';
    }

    /**
     * Envia evento de conversão
     */
    async sendEvent(eventName, userData = {}, customData = {}) {
        try {
            const response = await fetch(`${this.apiBase}/meta/conversion`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    event_name: eventName,
                    user_data: userData,
                    custom_data: customData,
                    event_time: Math.floor(Date.now() / 1000),
                    source_url: window.location.href
                })
            });

            const result = await response.json();
            
            if (result.success) {
                console.log('Meta event sent:', eventName, result);
                return true;
            } else {
                console.error('Meta event failed:', result.error);
                return false;
            }
        } catch (error) {
            console.error('Meta event error:', error);
            return false;
        }
    }

    /**
     * Evento de visualização de página
     */
    async trackPageView(userEmail = null) {
        const userData = userEmail ? { email: userEmail } : {};
        return await this.sendEvent('PageView', userData);
    }

    /**
     * Evento de visualização de conteúdo
     */
    async trackViewContent(contentName, contentCategory = 'Digital Product', value = 0) {
        return await this.sendEvent('ViewContent', {}, {
            content_name: contentName,
            content_category: contentCategory,
            value: value,
            currency: 'BRL'
        });
    }

    /**
     * Evento de adicionar ao carrinho (para produtos bloqueados)
     */
    async trackAddToCart(productName, productId, value = 0) {
        return await this.sendEvent('AddToCart', {}, {
            content_name: productName,
            content_ids: [productId],
            value: value,
            currency: 'BRL'
        });
    }

    /**
     * Evento de início de checkout
     */
    async trackInitiateCheckout(productName, productId, value = 0) {
        return await this.sendEvent('InitiateCheckout', {}, {
            content_name: productName,
            content_ids: [productId],
            value: value,
            currency: 'BRL'
        });
    }

    /**
     * Evento de lead (cadastro/contato)
     */
    async trackLead(userEmail, leadType = 'Newsletter') {
        return await this.sendEvent('Lead', {
            email: userEmail
        }, {
            content_name: leadType,
            content_category: 'Lead'
        });
    }

    /**
     * Evento de compra (chamado pelo webhook, mas pode ser usado para testes)
     */
    async trackPurchase(userEmail, productName, productId, value, currency = 'BRL') {
        return await this.sendEvent('Purchase', {
            email: userEmail
        }, {
            content_name: productName,
            content_ids: [productId],
            value: value,
            currency: currency,
            num_items: 1
        });
    }
}

// Instância global
window.metaConversions = new MetaConversions();

// Auto-track page view
document.addEventListener('DOMContentLoaded', () => {
    // Verificar se há email do usuário logado
    const userEmail = localStorage.getItem('user_email') || null;
    window.metaConversions.trackPageView(userEmail);
});

// Funções de conveniência para uso nas páginas
window.trackMetaEvent = (eventName, userData, customData) => {
    return window.metaConversions.sendEvent(eventName, userData, customData);
};

window.trackMetaPageView = (userEmail) => {
    return window.metaConversions.trackPageView(userEmail);
};

window.trackMetaViewContent = (contentName, contentCategory, value) => {
    return window.metaConversions.trackViewContent(contentName, contentCategory, value);
};

window.trackMetaAddToCart = (productName, productId, value) => {
    return window.metaConversions.trackAddToCart(productName, productId, value);
};

window.trackMetaInitiateCheckout = (productName, productId, value) => {
    return window.metaConversions.trackInitiateCheckout(productName, productId, value);
};

window.trackMetaLead = (userEmail, leadType) => {
    return window.metaConversions.trackLead(userEmail, leadType);
};
