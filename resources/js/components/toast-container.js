/**
 * Toast Container Alpine Component
 * Usage: <x-toast-container position="top-right" />
 * 
 * Trigger from anywhere:
 *   window.dispatchEvent(new CustomEvent('toast', { 
 *     detail: { 
 *       type: 'success', 
 *       title: 'Berhasil', 
 *       message: 'Data tersimpan',
 *       duration: 5000,
 *       action: { label: 'Lihat', onClick: () => {} }
 *     } 
 *   }));
 */

function toastContainer() {
    return {
        // Configuration
        position: 'top-right',
        maxToasts: 5,
        
        // State
        toasts: [],
        
        // Types configuration
        typeConfig: {
            success: { 
                icon: 'check_circle', 
                iconColor: 'text-success', 
                typeClasses: 'bg-canvas border-success/20',
                progressColor: 'bg-success',
                actionColor: 'text-success border-success/30'
            },
            error: { 
                icon: 'error', 
                iconColor: 'text-error', 
                typeClasses: 'bg-canvas border-error/20',
                progressColor: 'bg-error',
                actionColor: 'text-error border-error/30'
            },
            warning: { 
                icon: 'warning', 
                iconColor: 'text-warning', 
                typeClasses: 'bg-canvas border-warning/20',
                progressColor: 'bg-warning',
                actionColor: 'text-warning border-warning/30'
            },
            info: { 
                icon: 'info', 
                iconColor: 'text-info', 
                typeClasses: 'bg-canvas border-info/20',
                progressColor: 'bg-info',
                actionColor: 'text-info border-info/30'
            },
            default: { 
                icon: 'notifications', 
                iconColor: 'text-on-surface-variant', 
                typeClasses: 'bg-canvas border-outline-variant/20',
                progressColor: 'bg-primary',
                actionColor: 'text-primary border-primary/30'
            },
        },

        // Initialize from persisted storage
        restore() {
            try {
                const saved = JSON.parse(localStorage.getItem('hrconnect-toasts') || '[]');
                const now = Date.now();
                this.toasts = saved
                    .filter(t => t.expiresAt > now)
                    .map(t => ({
                        ...t,
                        visible: true,
                        progress: ((t.expiresAt - now) / (t.duration || 5000)) * 100,
                        _timer: null,
                    }))
                    .slice(0, this.maxToasts);
                this.toasts.forEach(t => this.startTimer(t));
            } catch (e) {
                console.warn('[Toast] Failed to restore:', e);
            }
        },

        // Persist to localStorage
        persist() {
            const toPersist = this.toasts.map(t => ({
                id: t.id,
                type: t.type,
                title: t.title,
                message: t.message,
                duration: t.duration,
                action: t.action,
                expiresAt: t.expiresAt,
            }));
            localStorage.setItem('hrconnect-toasts', JSON.stringify(toPersist));
        },

        // Add new toast
        add(config) {
            const type = config.type || 'default';
            const cfg = this.typeConfig[type] || this.typeConfig.default;
            const duration = config.duration ?? 5000;
            const now = Date.now();
            
            const toast = {
                id: crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(36).slice(2)}`,
                type,
                title: config.title || '',
                message: config.message || '',
                duration,
                action: config.action || null,
                visible: true,
                progress: 100,
                expiresAt: duration > 0 ? now + duration : 0,
                icon: cfg.icon,
                iconColor: cfg.iconColor,
                typeClasses: cfg.typeClasses,
                progressColor: cfg.progressColor,
                actionColor: cfg.actionColor,
                _timer: null,
            };

            // Add to front
            this.toasts.unshift(toast);
            
            // Limit max toasts
            if (this.toasts.length > this.maxToasts) {
                const removed = this.toasts.pop();
                this.clearTimer(removed);
            }

            // Start timer
            this.startTimer(toast);
            this.persist();
        },

        // Remove toast
        remove(id) {
            const idx = this.toasts.findIndex(t => t.id === id);
            if (idx === -1) return;
            
            const toast = this.toasts[idx];
            toast.visible = false;
            
            // Wait for transition then remove from array
            setTimeout(() => {
                this.toasts.splice(idx, 1);
                this.persist();
            }, 250); // match transition-toast-exit duration
            
            this.clearTimer(toast);
        },

        // Handle action click
        handleAction(toast) {
            if (toast.action?.onClick) {
                toast.action.onClick();
            }
            if (!toast.action?.persist) {
                this.remove(toast.id);
            }
        },

        // Timer management
        startTimer(toast) {
            if (toast.duration <= 0) return;
            
            const interval = 100; // update progress every 100ms
            const totalSteps = toast.duration / interval;
            let step = 0;
            
            toast._timer = setInterval(() => {
                step++;
                toast.progress = Math.max(0, 100 - (step / totalSteps) * 100);
                
                if (step >= totalSteps) {
                    this.remove(toast.id);
                }
            }, interval);
        },

        clearTimer(toast) {
            if (toast._timer) {
                clearInterval(toast._timer);
                toast._timer = null;
            }
        },

        pauseTimer(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (toast) this.clearTimer(toast);
        },

        resumeTimer(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (toast && toast.duration > 0 && toast.visible) {
                this.startTimer(toast);
            }
        },
    };
}

// Global helper
window.toast = function(config) {
    window.dispatchEvent(new CustomEvent('toast', { detail: config }));
};

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { toastContainer };
}