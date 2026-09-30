// ==========================================
// CONFIGURACIÓN Y CONSTANTES
// ==========================================
const APP_CONFIG = {
    PRELOADER_DELAY: 500,
    FAKE_LOADING_INTERVAL: 100,
    FAKE_LOADING_INCREMENT: 10,
    STAR_GENERATION_INTERVAL: 20,
    STAR_LIFETIME: 20000,
    STAR_MIN_SIZE: 10,
    STAR_MAX_SIZE: 160,
    AUDIO_VOLUME: 1,
    MODAL_Z_INDEX_OFFSET: 10,
    DEBOUNCE_DELAY: 100
};

// ==========================================
// GESTORES DE RECURSOS OPTIMIZADOS
// ==========================================

// Gestor centralizado de intervalos (reutilizado del primer código)
class IntervalManager {
    constructor() {
        this.intervals = new Map();
        this.timeouts = new Map();
    }
    
    setInterval(name, callback, delay) {
        this.clearInterval(name);
        this.intervals.set(name, setInterval(callback, delay));
    }
    
    setTimeout(name, callback, delay) {
        this.clearTimeout(name);
        this.timeouts.set(name, setTimeout(() => {
            callback();
            this.timeouts.delete(name);
        }, delay));
    }
    
    clearInterval(name) {
        if (this.intervals.has(name)) {
            clearInterval(this.intervals.get(name));
            this.intervals.delete(name);
        }
    }
    
    clearTimeout(name) {
        if (this.timeouts.has(name)) {
            clearTimeout(this.timeouts.get(name));
            this.timeouts.delete(name);
        }
    }
    
    clearAll() {
        this.intervals.forEach(interval => clearInterval(interval));
        this.timeouts.forEach(timeout => clearTimeout(timeout));
        this.intervals.clear();
        this.timeouts.clear();
    }
}

// Cache de elementos DOM optimizado
class DOMCache {
    constructor() {
        this.cache = new Map();
        this.selectors = new Map();
    }
    
    get(selector) {
        if (!this.cache.has(selector)) {
            const element = document.querySelector(selector);
            if (element) {
                this.cache.set(selector, element);
            }
        }
        return this.cache.get(selector);
    }
    
    getAll(selector) {
        if (!this.selectors.has(selector)) {
            const elements = document.querySelectorAll(selector);
            this.selectors.set(selector, elements);
        }
        return this.selectors.get(selector);
    }
    
    $get(selector) {
        const cacheKey = `jquery_${selector}`;
        if (!this.cache.has(cacheKey)) {
            const $element = $(selector);
            if ($element.length) {
                this.cache.set(cacheKey, $element);
            }
        }
        return this.cache.get(cacheKey);
    }
    
    clear() {
        this.cache.clear();
        this.selectors.clear();
    }
    
    invalidate(selector) {
        this.cache.delete(selector);
        this.selectors.delete(selector);
        this.cache.delete(`jquery_${selector}`);
    }
}

// Gestor de audio optimizado
class AudioManager {
    constructor() {
        this.audioCache = new Map();
        this.soundtrack = null;
        this.audioStarted = false;
        this.isEnabled = true;
    }
    
    preload(src, key) {
        if (this.audioCache.has(key)) return;
        
        const audio = new Audio();
        audio.preload = 'auto';
        audio.src = src;
        this.audioCache.set(key, audio);
    }
    
    createSoundtrack(src) {
        if (!this.soundtrack) {
            this.soundtrack = new Audio();
            this.soundtrack.src = src;
            this.soundtrack.volume = APP_CONFIG.AUDIO_VOLUME;
            this.soundtrack.loop = true;
        }
        return this.soundtrack;
    }
    
    async startSoundtrack(src) {
        if (this.soundtrack) {
            try {
                this.soundtrack.pause();
                this.soundtrack.currentTime = 0;
                this.soundtrack = null;
            } catch (e) {}
        }
        return false;
    }
    
    toggleSoundtrack() {
        if (!this.soundtrack) return false;
        
        if (this.soundtrack.paused) {
            this.soundtrack.play();
            return true;
        } else {
            this.soundtrack.pause();
            return false;
        }
    }
    
    isSoundtrackPlaying() {
        return this.soundtrack && !this.soundtrack.paused;
    }
    
    cleanup() {
        if (this.soundtrack) {
            this.soundtrack.pause();
            this.soundtrack = null;
        }
        this.audioCache.clear();
        this.audioStarted = false;
    }
}

// Pool de elementos para efectos visuales
class StarPool {
    constructor(maxSize = 50) {
        this.pool = [];
        this.maxSize = maxSize;
        this.activeStars = new Set();
    }
    
    get() {
        if (this.pool.length > 0) {
            return this.pool.pop();
        }
        return this.createNew();
    }
    
    release(star) {
        if (this.pool.length < this.maxSize && this.activeStars.has(star)) {
            // Reset star
            star.style.cssText = '';
            star.className = 'moneda';
            this.activeStars.delete(star);
            this.pool.push(star);
        } else if (star.parentNode) {
            star.parentNode.removeChild(star);
            this.activeStars.delete(star);
        }
    }
    
    createNew() {
        const star = document.createElement('div');
        star.className = 'moneda';
        return star;
    }
    
    cleanup() {
        this.activeStars.forEach(star => {
            if (star.parentNode) {
                star.parentNode.removeChild(star);
            }
        });
        this.activeStars.clear();
        this.pool = [];
    }
    
    addActive(star) {
        this.activeStars.add(star);
    }
}

// Gestor de efectos visuales optimizado
class VisualEffectsManager {
    constructor() {
        this.starPool = new StarPool();
        this.isGenerating = false;
        this.boundCleanup = this.cleanup.bind(this);
    }
    
    startStarGeneration() {
        if (this.isGenerating) return;
        
        this.isGenerating = true;
        const modalBg = domCache.$get('.efecto-bingo');
        
        if (!modalBg || !modalBg.length) {
            this.isGenerating = false;
            return;
        }
        
        intervalManager.setInterval('starGeneration', () => {
            this.generateStar(modalBg);
        }, APP_CONFIG.STAR_GENERATION_INTERVAL);
        
        // Setup cleanup listeners
        this.setupCleanupListeners();
    }
    
    generateStar(container) {
        const star = this.starPool.get();
        this.starPool.addActive(star);
        
        const randomX = Math.floor(Math.random() * window.innerWidth);
        const randomY = Math.floor(Math.random() * window.innerHeight);
        const randomSize = Math.floor(Math.random() * (APP_CONFIG.STAR_MAX_SIZE - APP_CONFIG.STAR_MIN_SIZE)) + APP_CONFIG.STAR_MIN_SIZE;
        
        star.style.cssText = `
            top: ${randomY}px;
            left: ${randomX}px;
            width: ${randomSize}px;
            height: ${randomSize}px;
            transform: translate(${randomX}px, ${randomY}px);
        `;
        
        container.append(star);
        
        // Auto-remove star after lifetime
        intervalManager.setTimeout(`star_${Date.now()}_${Math.random()}`, () => {
            this.starPool.release(star);
        }, APP_CONFIG.STAR_LIFETIME);
    }
    
    setupCleanupListeners() {
        const loginModal = domCache.$get('#login');
        const jugarModal = domCache.$get('#jugar');
        
        if (loginModal && loginModal.length) {
            loginModal.off('hidden.bs.modal', this.boundCleanup);
            loginModal.on('hidden.bs.modal', this.boundCleanup);
        }
        
        if (jugarModal && jugarModal.length) {
            jugarModal.off('hidden.bs.modal', this.boundCleanup);
            jugarModal.on('hidden.bs.modal', this.boundCleanup);
        }
    }
    
    cleanup() {
        this.isGenerating = false;
        intervalManager.clearInterval('starGeneration');
        this.starPool.cleanup();
    }
    
    stop() {
        this.cleanup();
    }
}

// Gestor de modales optimizado
class ModalManager {
    constructor() {
        this.loadingModals = new Set();
        this.setupGlobalModalEvents();
    }
    
    async loadModal(modalId, url, showAfterLoad = true) {
        if (this.loadingModals.has(modalId)) return;
        
        this.loadingModals.add(modalId);
        const modal = domCache.$get(`#${modalId}`);
        
        if (!modal || !modal.length) {
            this.loadingModals.delete(modalId);
            return;
        }
        
        try {
            const data = await this.loadContent(url);
            modal.html(data);
            
            if (showAfterLoad) {
                modal.modal('show');
            }
            
            // Special handling for specific modals
            this.handleSpecialModals(modalId);
            
        } catch (error) {
            console.error(`Error loading modal ${modalId}:`, error);
            modal.html('<p>Error al cargar el contenido.</p>');
        } finally {
            this.loadingModals.delete(modalId);
        }
    }
    
    loadContent(url) {
        return new Promise((resolve, reject) => {
            $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                timeout: 10000,
                success: resolve,
                error: reject
            });
        });
    }
    
    handleSpecialModals(modalId) {
        if (modalId === 'awards') {
            const gameFinalized = domCache.$get('#game-finalized');
            if (gameFinalized && gameFinalized.length) {
                gameFinalized.hide();
            }
        }
    }
    
    showModal(modalId) {
        const modal = domCache.$get(`#${modalId}`);
        if (modal && modal.length) {
            modal.modal('show');
        }
    }
    
    setupGlobalModalEvents() {
        // Use event delegation for better performance
        $(document).on('hidden.bs.modal', '.modal', function(e) {
            const visibleModals = domCache.$get('.modal:visible');
            if (visibleModals && visibleModals.length) {
                const backdrop = domCache.$get('.modal-backdrop').first();
                const lastModal = visibleModals.last();
                
                if (backdrop.length && lastModal.length) {
                    backdrop.css('z-index', parseInt(lastModal.css('z-index')) - APP_CONFIG.MODAL_Z_INDEX_OFFSET);
                }
                $('body').addClass('modal-open');
            }
        });
        
        $(document).on('show.bs.modal', '.modal', function(e) {
            const visibleModals = domCache.$get('.modal:visible');
            if (visibleModals && visibleModals.length) {
                const backdrop = domCache.$get('.modal-backdrop.in').first();
                const $this = $(this);
                
                if (backdrop.length) {
                    const newZIndex = parseInt(visibleModals.last().css('z-index')) + APP_CONFIG.MODAL_Z_INDEX_OFFSET;
                    backdrop.css('z-index', newZIndex);
                    $this.css('z-index', newZIndex + APP_CONFIG.MODAL_Z_INDEX_OFFSET);
                }
            }
        });
    }
}

// Gestor de navegación SPA optimizado
class NavigationManager {
    constructor() {
        this.lastUrl = '';
        this.loadingPage = false;
        this.setupEvents();
    }
    
    setupEvents() {
        // Use event delegation
        $(document).on('click', '.linkPage', (e) => {
            e.preventDefault();
            const href = $(e.currentTarget).attr('href');
            this.checkURL(href);
        });
        
        // Handle browser back/forward
        window.addEventListener('popstate', () => {
            this.checkURL(window.location.hash);
        });
    }
    
    checkURL(hash) {
        if (!hash) hash = window.location.hash;
        if (hash === this.lastUrl || this.loadingPage) return;
        
        this.lastUrl = hash;
        this.loadPage(hash);
    }
    
    async loadPage(url) {
        if (this.loadingPage) return;
        
        this.loadingPage = true;
        const contentPage = domCache.$get('#content-page');
        
        if (!contentPage || !contentPage.length) {
            this.loadingPage = false;
            return;
        }
        
        try {
            const data = await this.loadContent(url);
            contentPage.html(data);
            
            // Update browser history if needed
            if (window.location.hash !== url) {
                history.pushState(null, null, url);
            }
            
        } catch (error) {
            console.error('Error loading page:', error);
            contentPage.html('<p>Error al cargar la página.</p>');
        } finally {
            this.loadingPage = false;
        }
    }
    
    loadContent(url) {
        return new Promise((resolve, reject) => {
            $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                timeout: 15000,
                success: resolve,
                error: reject
            });
        });
    }
}

// Gestor de UI optimizado
class UIManager {
    constructor() {
        this.slidersActive = false;
        this.boundCloseSlidersOutside = this.closeSlidersOnClickOutside.bind(this);
        this.setupEvents();
    }
    
    setupEvents() {
        // Setup slider buttons event prevention
        const sliderButtons = domCache.getAll('.btn-volume, .btn-microphone, .btn-binary, .btn-user, .btn-lock');
        sliderButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });
    }
    
    toggleSliders() {
        const hiddenButtons = domCache.getAll('.btn-volume, .btn-microphone, .btn-binary, .btn-user, .btn-lock');
        
        hiddenButtons.forEach(button => {
            button.classList.toggle('hidden');
        });
        
        if (!this.slidersActive) {
            this.slidersActive = true;
            document.body.classList.add('sliders-active');
            document.addEventListener('click', this.boundCloseSlidersOutside);
        } else {
            this.slidersActive = false;
            document.body.classList.remove('sliders-active');
            document.removeEventListener('click', this.boundCloseSlidersOutside);
        }
    }
    
    closeSlidersOnClickOutside(event) {
        const slidersButton = domCache.get('.btn-sliders');
        const hiddenButtons = domCache.getAll('.btn-volume, .btn-microphone, .btn-binary, .btn-user, .btn-lock');
        
        if (!slidersButton?.contains(event.target) && 
            ![...hiddenButtons].some(btn => btn.contains(event.target))) {
            
            hiddenButtons.forEach(button => button.classList.add('hidden'));
            this.slidersActive = false;
            document.body.classList.remove('sliders-active');
            document.removeEventListener('click', this.boundCloseSlidersOutside);
        }
    }
    
    cleanup() {
        document.removeEventListener('click', this.boundCloseSlidersOutside);
        this.slidersActive = false;
    }
}

// ==========================================
// INSTANCIAS GLOBALES
// ==========================================
const intervalManager = new IntervalManager();
const domCache = new DOMCache();
const audioManager = new AudioManager();
const visualEffects = new VisualEffectsManager();
const modalManager = new ModalManager();
const navigationManager = new NavigationManager();
const uiManager = new UIManager();

// ==========================================
// UTILIDADES
// ==========================================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ==========================================
// GESTOR DE PRELOADER OPTIMIZADO
// ==========================================
class PreloaderManager {
    constructor() {
        this.preloader = null;
        this.progressBar = null;
        this.loadingPercentage = null;
        this.fakeProgress = 0;
        this.isComplete = false;
    }
    
    init() {
        this.preloader = domCache.get('.preloader');
        this.progressBar = domCache.get('.progress');
        this.loadingPercentage = domCache.get('.loading-percentage');
        
        if (!this.preloader) return;
        
        this.setupEvents();
        this.startFakeLoading();
    }
    
    setupEvents() {
        window.addEventListener('load', () => this.removePreloader());
        
        // Listen for actual loading events if available
        document.addEventListener('progress', (event) => this.updateProgress(event));
    }
    
    updateProgress(event) {
        if (event.lengthComputable && this.progressBar && this.loadingPercentage) {
            const percentComplete = Math.round((event.loaded / event.total) * 100);
            this.setProgress(percentComplete);
        }
    }
    
    setProgress(percent) {
        if (this.progressBar) {
            this.progressBar.style.width = percent + '%';
        }
        if (this.loadingPercentage) {
            this.loadingPercentage.textContent = percent + '%';
        }
    }
    
    startFakeLoading() {
        intervalManager.setInterval('fakeLoading', () => {
            this.fakeProgress += APP_CONFIG.FAKE_LOADING_INCREMENT;
            this.setProgress(this.fakeProgress);
            
            if (this.fakeProgress >= 100) {
                intervalManager.clearInterval('fakeLoading');
                this.removePreloader();
            }
        }, APP_CONFIG.FAKE_LOADING_INTERVAL);
    }
    
    removePreloader() {
        if (this.isComplete || !this.preloader) return;
        
        this.isComplete = true;
        intervalManager.clearInterval('fakeLoading');
        
        intervalManager.setTimeout('removePreloader', () => {
            if (this.preloader) {
                this.preloader.style.display = 'none';
            }
        }, APP_CONFIG.PRELOADER_DELAY);
    }
}

// ==========================================
// APLICACIÓN PRINCIPAL OPTIMIZADA
// ==========================================
class OptimizedApp {
    constructor() {
        this.preloaderManager = new PreloaderManager();
        this.initialized = false;
        this.boundSoundtrackStarter = null;
    }
    
    init() {
        if (this.initialized) return;
        this.initialized = true;
        
        console.log('Initializing Optimized App...');
        
        // Initialize all managers
        this.preloaderManager.init();
        this.setupAudioEvents();
        this.setupGlobalEvents();
        
        // Make functions globally available
        this.exposeGlobalFunctions();
        
        console.log('Optimized App initialized successfully');
    }
    
    setupAudioEvents() {
        // Setup volume button
        $(document).on('click', '.btn-volume', () => {
            const isPlaying = audioManager.toggleSoundtrack();
            const volumeBtn = domCache.$get('.btn-volume');
            
            if (volumeBtn && volumeBtn.length) {
                const icon = isPlaying ? 
                    '<i class="fa-duotone fa-solid fa-volume"></i>' : 
                    '<i class="fa-duotone fa-solid fa-volume-slash"></i>';
                volumeBtn.html(icon);
            }
        });
        
        // Setup auto-play soundtrack
        this.setupAutoSoundtrack();
    }
    
    setupAutoSoundtrack() {
        // Música de fondo deshabilitada
        return;
    }
    
    setupGlobalEvents() {
        // Setup visual effects when document is ready
        $(document).ready(() => {
            visualEffects.startStarGeneration();
        });
        
        // Setup cleanup on page unload
        window.addEventListener('beforeunload', () => this.cleanup());
        window.addEventListener('unload', () => this.cleanup());
        
        // Handle visibility changes for performance
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                visualEffects.stop();
            } else {
                visualEffects.startStarGeneration();
            }
        });
    }
    
    exposeGlobalFunctions() {
        // Modal functions
        window.modalitiesGet = () => modalManager.showModal('modalities');
        window.boardGet = () => modalManager.showModal('board');
        window.gamesGet = () => modalManager.loadModal('games', site_url + 'games/gamesGet');
        window.awardsGet = () => modalManager.loadModal('awards', site_url + 'boards/awardsGet');
        window.awardsGameGet = () => modalManager.loadModal('awards', site_url + 'boards/awardsGameGet');
        window.gameAdd = () => modalManager.loadModal('add-game', site_url + 'games/add');
        window.modalityAdd = () => modalManager.loadModal('add-modality', site_url + 'games/addmodality');
        window.playersGet = () => modalManager.loadModal('players', site_url + 'boards/playersGet');
        window.paymentsGet = () => modalManager.loadModal('payments', site_url + 'payments/paymentsGet');
        window.rechargeGet = () => modalManager.loadModal('recharge', site_url + 'payments/rechargeGet');
        window.retireGet = () => modalManager.loadModal('retire', site_url + 'payments/retireGet');
        window.settingswalletGet = () => modalManager.loadModal('settings', site_url + 'payments/settingswalletGet');
        window.settingsGet = () => modalManager.loadModal('settings', site_url + 'home/settingsGet');
        
        // UI functions
        window.ViewSliders = () => uiManager.toggleSliders();
        
        // App reference
        window.OptimizedApp = this;
    }
    
    cleanup() {
        console.log('Cleaning up Optimized App...');
        
        intervalManager.clearAll();
        visualEffects.cleanup();
        audioManager.cleanup();
        uiManager.cleanup();
        domCache.clear();
        
        if (this.boundSoundtrackStarter) {
            document.removeEventListener('click', this.boundSoundtrackStarter);
        }
        
        console.log('Optimized App cleanup completed');
    }
    
    // Utility methods for external access
    getState() {
        return {
            initialized: this.initialized,
            audioStarted: audioManager.audioStarted,
            slidersActive: uiManager.slidersActive,
            starsGenerating: visualEffects.isGenerating,
            activeIntervals: intervalManager.intervals.size,
            activeTimeouts: intervalManager.timeouts.size,
            cachedElements: domCache.cache.size
        };
    }
}

// ==========================================
// INICIALIZACIÓN
// ==========================================
const optimizedApp = new OptimizedApp();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    optimizedApp.init();
});

// Fallback jQuery initialization
$(function() {
    optimizedApp.init();
});

// ==========================================
// DEBUGGING (solo en desarrollo)
// ==========================================
if (typeof DEBUG !== 'undefined' && DEBUG) {
    window.AppDebug = {
        getState: () => optimizedApp.getState(),
        forceCleanup: () => optimizedApp.cleanup(),
        intervalManager,
        domCache,
        audioManager,
        visualEffects,
        modalManager,
        navigationManager,
        uiManager
    };
    
    console.log('App Debug tools available in window.AppDebug');
}

console.log('Optimized App script loaded successfully');

// ==========================================
// GESTOR DE RENDIMIENTO Y MÉTRICAS
// ==========================================
class PerformanceManager {
    constructor() {
        this.metrics = new Map();
        this.observers = new Map();
        this.thresholds = {
            memoryUsage: 50 * 1024 * 1024, // 50MB
            domNodes: 1000,
            eventListeners: 100,
            ajaxRequests: 10
        };
        this.init();
    }
    
    init() {
        this.setupPerformanceObserver();
        this.setupMemoryMonitoring();
        this.setupNetworkMonitoring();
        this.startMetricsCollection();
    }
    
    setupPerformanceObserver() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    this.recordMetric(`performance_${entry.entryType}`, {
                        name: entry.name,
                        duration: entry.duration,
                        startTime: entry.startTime,
                        timestamp: Date.now()
                    });
                }
            });
            
            try {
                observer.observe({ entryTypes: ['measure', 'navigation', 'resource'] });
                this.observers.set('performance', observer);
            } catch (e) {
                console.warn('Performance Observer not fully supported:', e);
            }
        }
    }
    
    setupMemoryMonitoring() {
        if ('memory' in performance) {
            intervalManager.setInterval('memoryCheck', () => {
                const memory = performance.memory;
                this.recordMetric('memory', {
                    used: memory.usedJSHeapSize,
                    total: memory.totalJSHeapSize,
                    limit: memory.jsHeapSizeLimit,
                    timestamp: Date.now()
                });
                
                // Alert if memory usage is high
                if (memory.usedJSHeapSize > this.thresholds.memoryUsage) {
                    this.handleHighMemoryUsage();
                }
            }, 30000); // Check every 30 seconds
        }
    }
    
    setupNetworkMonitoring() {
        // Monitor AJAX requests
        const originalAjax = $.ajax;
        let activeRequests = 0;
        
        $.ajax = function(options) {
            activeRequests++;
            const startTime = performance.now();
            
            const originalSuccess = options.success || (() => {});
            const originalError = options.error || (() => {});
            const originalComplete = options.complete || (() => {});
            
            options.success = function(data, textStatus, jqXHR) {
                activeRequests--;
                performanceManager.recordMetric('ajax_success', {
                    url: options.url,
                    duration: performance.now() - startTime,
                    timestamp: Date.now()
                });
                originalSuccess.apply(this, arguments);
            };
            
            options.error = function(jqXHR, textStatus, errorThrown) {
                activeRequests--;
                performanceManager.recordMetric('ajax_error', {
                    url: options.url,
                    error: textStatus,
                    duration: performance.now() - startTime,
                    timestamp: Date.now()
                });
                originalError.apply(this, arguments);
            };
            
            options.complete = function() {
                performanceManager.recordMetric('ajax_active', activeRequests);
                originalComplete.apply(this, arguments);
            };
            
            return originalAjax.call(this, options);
        };
    }
    
    startMetricsCollection() {
        intervalManager.setInterval('metricsCollection', () => {
            this.collectDOMMetrics();
            this.collectAppMetrics();
        }, 60000); // Collect every minute
    }
    
    collectDOMMetrics() {
        this.recordMetric('dom_nodes', document.querySelectorAll('*').length);
        this.recordMetric('cached_elements', domCache.cache.size);
        this.recordMetric('active_intervals', intervalManager.intervals.size);
        this.recordMetric('active_timeouts', intervalManager.timeouts.size);
    }
    
    collectAppMetrics() {
        this.recordMetric('app_state', optimizedApp.getState());
        this.recordMetric('star_pool_size', visualEffects.starPool.pool.length);
        this.recordMetric('active_stars', visualEffects.starPool.activeStars.size);
    }
    
    recordMetric(key, value) {
        if (!this.metrics.has(key)) {
            this.metrics.set(key, []);
        }
        
        const metrics = this.metrics.get(key);
        metrics.push({
            value,
            timestamp: Date.now()
        });
        
        // Keep only last 100 entries per metric
        if (metrics.length > 100) {
            metrics.splice(0, metrics.length - 100);
        }
    }
    
    handleHighMemoryUsage() {
        console.warn('High memory usage detected, performing cleanup...');
        
        // Force garbage collection if available
        if (window.gc) {
            window.gc();
        }
        
        // Clear old metrics
        this.clearOldMetrics();
        
        // Clear DOM cache
        domCache.clear();
        
        // Reduce star pool size
        visualEffects.starPool.cleanup();
        
        // Clear audio cache
        audioManager.audioCache.clear();
    }
    
    clearOldMetrics() {
        const cutoff = Date.now() - (5 * 60 * 1000); // 5 minutes ago
        
        this.metrics.forEach((metrics, key) => {
            const filtered = metrics.filter(m => m.timestamp > cutoff);
            this.metrics.set(key, filtered);
        });
    }
    
    getMetrics(key) {
        return this.metrics.get(key) || [];
    }
    
    getAllMetrics() {
        const result = {};
        this.metrics.forEach((value, key) => {
            result[key] = value;
        });
        return result;
    }
    
    cleanup() {
        this.observers.forEach(observer => observer.disconnect());
        this.observers.clear();
        this.metrics.clear();
        intervalManager.clearInterval('memoryCheck');
        intervalManager.clearInterval('metricsCollection');
    }
}

// ==========================================
// GESTOR DE ERRORES AVANZADO
// ==========================================
class ErrorManager {
    constructor() {
        this.errors = [];
        this.maxErrors = 50;
        this.errorCounts = new Map();
        this.setupErrorHandling();
    }
    
    setupErrorHandling() {
        // Global error handler
        window.addEventListener('error', (event) => {
            this.handleError({
                type: 'javascript',
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error,
                timestamp: Date.now()
            });
        });
        
        // Promise rejection handler
        window.addEventListener('unhandledrejection', (event) => {
            this.handleError({
                type: 'promise',
                message: event.reason?.message || 'Unhandled promise rejection',
                reason: event.reason,
                timestamp: Date.now()
            });
        });
        
        // AJAX error handler
        $(document).ajaxError((event, jqXHR, ajaxSettings, thrownError) => {
            this.handleError({
                type: 'ajax',
                message: thrownError || 'AJAX request failed',
                url: ajaxSettings.url,
                status: jqXHR.status,
                statusText: jqXHR.statusText,
                timestamp: Date.now()
            });
        });
    }
    
    handleError(errorInfo) {
        // Increment error count
        const errorKey = `${errorInfo.type}_${errorInfo.message}`;
        this.errorCounts.set(errorKey, (this.errorCounts.get(errorKey) || 0) + 1);
        
        // Add to error log
        this.errors.push(errorInfo);
        
        // Keep only recent errors
        if (this.errors.length > this.maxErrors) {
            this.errors.splice(0, this.errors.length - this.maxErrors);
        }
        
        // Log error
        console.error('App Error:', errorInfo);
        
        // Handle critical errors
        if (this.isCriticalError(errorInfo)) {
            this.handleCriticalError(errorInfo);
        }
        
        // Send to analytics if available
        this.sendErrorToAnalytics(errorInfo);
    }
    
    isCriticalError(errorInfo) {
        const criticalPatterns = [
            /out of memory/i,
            /maximum call stack/i,
            /script error/i
        ];
        
        return criticalPatterns.some(pattern => 
            pattern.test(errorInfo.message)
        ) || this.errorCounts.get(`${errorInfo.type}_${errorInfo.message}`) > 5;
    }
    
    handleCriticalError(errorInfo) {
        console.error('Critical error detected:', errorInfo);
        
        // Attempt recovery
        try {
            // Clear intervals and timeouts
            intervalManager.clearAll();
            
            // Stop visual effects
            visualEffects.cleanup();
            
            // Clear caches
            domCache.clear();
            
            // Show user notification
            this.showErrorNotification('Se ha detectado un error crítico. La aplicación se está recuperando...');
            
        } catch (recoveryError) {
            console.error('Error during recovery:', recoveryError);
            this.showErrorNotification('Error crítico. Por favor, recarga la página.');
        }
    }
    
    showErrorNotification(message) {
        // Create or update error notification
        let notification = domCache.get('#error-notification');
        
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'error-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #f44336;
                color: white;
                padding: 15px;
                border-radius: 5px;
                z-index: 10000;
                max-width: 300px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            `;
            document.body.appendChild(notification);
            domCache.cache.set('#error-notification', notification);
        }
        
        notification.textContent = message;
        notification.style.display = 'block';
        
        // Auto-hide after 5 seconds
        intervalManager.setTimeout('hideErrorNotification', () => {
            notification.style.display = 'none';
        }, 5000);
    }
    
    sendErrorToAnalytics(errorInfo) {
        // Send to Google Analytics if available
        if (typeof gtag !== 'undefined') {
            gtag('event', 'exception', {
                description: errorInfo.message,
                fatal: this.isCriticalError(errorInfo)
            });
        }
        
        // Send to custom analytics endpoint if available
        if (typeof analytics_endpoint !== 'undefined') {
            fetch(analytics_endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'error',
                    data: errorInfo,
                    userAgent: navigator.userAgent,
                    url: window.location.href
                })
            }).catch(() => {
                // Silently fail analytics
            });
        }
    }
    
    getErrorReport() {
        return {
            errors: this.errors,
            errorCounts: Object.fromEntries(this.errorCounts),
            timestamp: Date.now()
        };
    }
    
    clearErrors() {
        this.errors = [];
        this.errorCounts.clear();
    }
}

// ==========================================
// GESTOR DE ESTADO AVANZADO
// ==========================================
class StateManager {
    constructor() {
        this.state = new Map();
        this.subscribers = new Map();
        this.history = [];
        this.maxHistorySize = 50;
        this.setupInitialState();
    }
    
    setupInitialState() {
        this.setState('app', {
            initialized: false,
            loading: false,
            error: null,
            lastActivity: Date.now()
        });
        
        this.setState('ui', {
            slidersOpen: false,
            activeModal: null,
            theme: 'default'
        });
        
        this.setState('audio', {
            enabled: true,
            playing: false,
            volume: 1
        });
        
        this.setState('effects', {
            starsActive: false,
            particleCount: 0
        });
    }
    
    setState(key, value, notify = true) {
        const oldValue = this.state.get(key);
        const newValue = typeof value === 'object' && value !== null ? 
            { ...oldValue, ...value } : value;
        
        this.state.set(key, newValue);
        
        // Add to history
        this.history.push({
            key,
            oldValue,
            newValue,
            timestamp: Date.now()
        });
        
        // Trim history
        if (this.history.length > this.maxHistorySize) {
            this.history.splice(0, this.history.length - this.maxHistorySize);
        }
        
        // Notify subscribers
        if (notify) {
            this.notifySubscribers(key, newValue, oldValue);
        }
        
        return newValue;
    }
    
    getState(key) {
        return this.state.get(key);
    }
    
    getAllState() {
        const result = {};
        this.state.forEach((value, key) => {
            result[key] = value;
        });
        return result;
    }
    
    subscribe(key, callback) {
        if (!this.subscribers.has(key)) {
            this.subscribers.set(key, new Set());
        }
        
        this.subscribers.get(key).add(callback);
        
        // Return unsubscribe function
        return () => {
            const subscribers = this.subscribers.get(key);
            if (subscribers) {
                subscribers.delete(callback);
                if (subscribers.size === 0) {
                    this.subscribers.delete(key);
                }
            }
        };
    }
    
    notifySubscribers(key, newValue, oldValue) {
        const subscribers = this.subscribers.get(key);
        if (subscribers) {
            subscribers.forEach(callback => {
                try {
                    callback(newValue, oldValue, key);
                } catch (error) {
                    console.error('Error in state subscriber:', error);
                }
            });
        }
    }
    
    getHistory(key) {
        return key ? 
            this.history.filter(h => h.key === key) : 
            this.history;
    }
    
    clearHistory() {
        this.history = [];
    }
    
    // Computed properties
    addComputed(key, computeFn, dependencies = []) {
        const compute = () => {
            try {
                const result = computeFn(this.getAllState());
                this.setState(`computed_${key}`, result, false);
                return result;
            } catch (error) {
                console.error(`Error computing ${key}:`, error);
                return null;
            }
        };
        
        // Initial computation
        compute();
        
        // Subscribe to dependencies
        dependencies.forEach(dep => {
            this.subscribe(dep, compute);
        });
        
        return () => this.getState(`computed_${key}`);
    }
}

// ==========================================
// GESTOR DE CACHÉ AVANZADO
// ==========================================
class CacheManager {
    constructor() {
        this.caches = new Map();
        this.defaultTTL = 5 * 60 * 1000; // 5 minutes
        this.maxSize = 100;
        this.setupCaches();
    }
    
    setupCaches() {
        this.createCache('dom', { ttl: 10 * 60 * 1000, maxSize: 50 });
        this.createCache('ajax', { ttl: 2 * 60 * 1000, maxSize: 20 });
        this.createCache('computed', { ttl: 30 * 1000, maxSize: 30 });
        this.createCache('assets', { ttl: 30 * 60 * 1000, maxSize: 100 });
        
        // Cleanup expired entries periodically
        intervalManager.setInterval('cacheCleanup', () => {
            this.cleanupExpired();
        }, 60000); // Every minute
    }
    
    createCache(name, options = {}) {
        this.caches.set(name, {
            data: new Map(),
            ttl: options.ttl || this.defaultTTL,
            maxSize: options.maxSize || this.maxSize,
            hits: 0,
            misses: 0
        });
    }
    
    set(cacheName, key, value, customTTL) {
        const cache = this.caches.get(cacheName);
        if (!cache) return false;
        
        const ttl = customTTL || cache.ttl;
        const expiresAt = Date.now() + ttl;
        
        // Remove oldest entries if cache is full
        if (cache.data.size >= cache.maxSize) {
            const oldestKey = cache.data.keys().next().value;
            cache.data.delete(oldestKey);
        }
        
        cache.data.set(key, {
            value,
            expiresAt,
            createdAt: Date.now(),
            accessCount: 0
        });
        
        return true;
    }
    
    get(cacheName, key) {
        const cache = this.caches.get(cacheName);
        if (!cache) return null;
        
        const entry = cache.data.get(key);
        if (!entry) {
            cache.misses++;
            return null;
        }
        
        // Check if expired
        if (Date.now() > entry.expiresAt) {
            cache.data.delete(key);
            cache.misses++;
            return null;
        }
        
        // Update access info
        entry.accessCount++;
        entry.lastAccessed = Date.now();
        cache.hits++;
        
        return entry.value;
    }
    
    has(cacheName, key) {
        return this.get(cacheName, key) !== null;
    }
    
    delete(cacheName, key) {
        const cache = this.caches.get(cacheName);
        if (cache) {
            return cache.data.delete(key);
        }
        return false;
    }
    
    clear(cacheName) {
        const cache = this.caches.get(cacheName);
        if (cache) {
            cache.data.clear();
            cache.hits = 0;
            cache.misses = 0;
            return true;
        }
        return false;
    }
    
    cleanupExpired() {
        const now = Date.now();
        
        this.caches.forEach((cache, cacheName) => {
            const toDelete = [];
            
            cache.data.forEach((entry, key) => {
                if (now > entry.expiresAt) {
                    toDelete.push(key);
                }
            });
            
            toDelete.forEach(key => cache.data.delete(key));
            
            if (toDelete.length > 0) {
                console.log(`Cleaned up ${toDelete.length} expired entries from ${cacheName} cache`);
            }
        });
    }
    
    getStats(cacheName) {
        const cache = this.caches.get(cacheName);
        if (!cache) return null;
        
        const hitRate = cache.hits + cache.misses > 0 ? 
            (cache.hits / (cache.hits + cache.misses) * 100).toFixed(2) : 0;
        
        return {
            size: cache.data.size,
            maxSize: cache.maxSize,
            hits: cache.hits,
            misses: cache.misses,
            hitRate: `${hitRate}%`,
            ttl: cache.ttl
        };
    }
    
    getAllStats() {
        const stats = {};
        this.caches.forEach((cache, name) => {
            stats[name] = this.getStats(name);
        });
        return stats;
    }
}

// ==========================================
// GESTOR DE LAZY LOADING
// ==========================================
class LazyLoadManager {
    constructor() {
        this.observer = null;
        this.loadedElements = new Set();
        this.loadingElements = new Set();
        this.setupIntersectionObserver();
    }
    
    setupIntersectionObserver() {
        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadElement(entry.target);
                    }
                });
            }, {
                rootMargin: '50px',
                threshold: 0.1
            });
        }
    }
    
    observe(element, loadCallback) {
        if (!this.observer || this.loadedElements.has(element)) return;
        
        element.dataset.lazyLoadCallback = loadCallback.toString();
        this.observer.observe(element);
    }
    
    async loadElement(element) {
        if (this.loadedElements.has(element) || this.loadingElements.has(element)) {
            return;
        }
        
        this.loadingElements.add(element);
        this.observer.unobserve(element);
        
        try {
            // Execute load callback if exists
            if (element.dataset.lazyLoadCallback) {
                const callback = new Function('element', element.dataset.lazyLoadCallback);
                await callback(element);
            }
            
            // Handle different element types
            if (element.dataset.src) {
                await this.loadImage(element);
            } else if (element.dataset.modalUrl) {
                await this.loadModal(element);
            } else if (element.dataset.contentUrl) {
                await this.loadContent(element);
            }
            
            this.loadedElements.add(element);
            element.classList.add('lazy-loaded');
            
        } catch (error) {
            console.error('Error lazy loading element:', error);
            element.classList.add('lazy-error');
        } finally {
            this.loadingElements.delete(element);
        }
    }
    
    loadImage(element) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                element.src = element.dataset.src;
                element.removeAttribute('data-src');
                resolve();
            };
            img.onerror = reject;
            img.src = element.dataset.src;
        });
    }
    
    async loadModal(element) {
        const url = element.dataset.modalUrl;
        const modalId = element.dataset.modalTarget;
        
        if (url && modalId) {
            await modalManager.loadModal(modalId, url, false);
        }
    }
    
    async loadContent(element) {
        const url = element.dataset.contentUrl;
        
        try {
            const response = await fetch(url);
            const content = await response.text();
            element.innerHTML = content;
        } catch (error) {
            element.innerHTML = '<p>Error loading content</p>';
            throw error;
        }
    }
    
    loadAll() {
        const lazyElements = document.querySelectorAll('[data-lazy]');
        lazyElements.forEach(element => {
            this.loadElement(element);
        });
    }
    
    cleanup() {
        if (this.observer) {
            this.observer.disconnect();
        }
        this.loadedElements.clear();
        this.loadingElements.clear();
    }
}

// ==========================================
// INSTANCIAS DE GESTORES AVANZADOS
// ==========================================
const performanceManager = new PerformanceManager();
const errorManager = new ErrorManager();
const stateManager = new StateManager();
const cacheManager = new CacheManager();
const lazyLoadManager = new LazyLoadManager();

// ==========================================
// INTEGRACIÓN AVANZADA DE GESTORES
// ==========================================

// Actualizar DOMCache para usar CacheManager
class EnhancedDOMCache extends DOMCache {
    constructor() {
        super();
        this.cacheManager = cacheManager;
    }
    
    get(selector) {
        // Try cache first
        let element = this.cacheManager.get('dom', selector);
        if (element && document.contains(element)) {
            return element;
        }
        
        // Query DOM and cache result
        element = document.querySelector(selector);
        if (element) {
            this.cacheManager.set('dom', selector, element);
        }
        return element;
    }
    
    $get(selector) {
        const cacheKey = `jquery_${selector}`;
        let $element = this.cacheManager.get('dom', cacheKey);
        
        if (!$element || !$element.length || !document.contains($element[0])) {
            $element = $(selector);
            if ($element.length) {
                this.cacheManager.set('dom', cacheKey, $element);
            }
        }
        return $element;
    }
    
    invalidate(selector) {
        this.cacheManager.delete('dom', selector);
        this.cacheManager.delete('dom', `jquery_${selector}`);
    }
}

// Actualizar AudioManager con estado
class EnhancedAudioManager extends AudioManager {
    constructor() {
        super();
        this.setupStateIntegration();
    }
    
    setupStateIntegration() {
        // Subscribe to state changes
        stateManager.subscribe('audio', (newState) => {
            this.isEnabled = newState.enabled;
            if (!newState.enabled && this.soundtrack) {
                this.soundtrack.pause();
            }
        });
    }
    
    async startSoundtrack(src) {
        const result = await super.startSoundtrack(src);
        
        stateManager.setState('audio', {
            playing: result,
            src: src
        });
        
        return result;
    }
    
    toggleSoundtrack() {
        const wasPlaying = super.toggleSoundtrack();
        
        stateManager.setState('audio', {
            playing: wasPlaying
        });
        
        return wasPlaying;
    }
}

// Actualizar VisualEffectsManager con rendimiento
class EnhancedVisualEffectsManager extends VisualEffectsManager {
    constructor() {
        super();
        this.performanceMode = false;
        this.frameRate = 60;
        this.lastFrameTime = 0;
        this.setupPerformanceMonitoring();
    }
    
    setupPerformanceMonitoring() {
        // Monitor performance and adjust accordingly
        stateManager.subscribe('performance', (perfState) => {
            if (perfState.memoryUsage > 0.8) {
                this.enablePerformanceMode();
            } else if (perfState.memoryUsage < 0.5) {
                this.disablePerformanceMode();
            }
        });
    }
    
    enablePerformanceMode() {
        if (this.performanceMode) return;
        
        this.performanceMode = true;
        this.frameRate = 30; // Reduce frame rate
        APP_CONFIG.STAR_GENERATION_INTERVAL = 40; // Slower generation
        APP_CONFIG.STAR_LIFETIME = 10000; // Shorter lifetime
        
        console.log('Performance mode enabled for visual effects');
        stateManager.setState('effects', { performanceMode: true });
    }
    
    disablePerformanceMode() {
        if (!this.performanceMode) return;
        
        this.performanceMode = false;
        this.frameRate = 60;
        APP_CONFIG.STAR_GENERATION_INTERVAL = 20;
        APP_CONFIG.STAR_LIFETIME = 20000;
        
        console.log('Performance mode disabled for visual effects');
        stateManager.setState('effects', { performanceMode: false });
    }
    
    generateStar(container) {
        // Throttle based on frame rate
        const now = performance.now();
        if (now - this.lastFrameTime < (1000 / this.frameRate)) {
            return;
        }
        this.lastFrameTime = now;
        
        // Use performance mode settings
        if (this.performanceMode && this.starPool.activeStars.size > 10) {
            return; // Limit active stars in performance mode
        }
        
        super.generateStar(container);
        
        // Update state
        stateManager.setState('effects', {
            particleCount: this.starPool.activeStars.size
        });
    }
}

// ==========================================
// GESTOR DE CONECTIVIDAD Y OFFLINE
// ==========================================
class ConnectivityManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.offlineQueue = [];
        this.retryAttempts = new Map();
        this.maxRetries = 3;
        this.setupEventListeners();
        this.setupOfflineHandling();
    }
    
    setupEventListeners() {
        window.addEventListener('online', () => {
            this.handleOnline();
        });
        
        window.addEventListener('offline', () => {
            this.handleOffline();
        });
        
        // Periodic connectivity check
        intervalManager.setInterval('connectivityCheck', () => {
            this.checkConnectivity();
        }, 30000);
    }
    
    setupOfflineHandling() {
        // Intercept AJAX requests when offline
        const originalAjax = $.ajax;
        
        $.ajax = (options) => {
            if (!this.isOnline) {
                return this.handleOfflineRequest(options);
            }
            
            return originalAjax.call($, options).fail((jqXHR) => {
                if (jqXHR.status === 0 || jqXHR.status >= 500) {
                    this.queueRequest(options);
                }
            });
        };
    }
    
    handleOnline() {
        console.log('Connection restored');
        this.isOnline = true;
        
        stateManager.setState('connectivity', {
            online: true,
            lastOnline: Date.now()
        });
        
        this.processOfflineQueue();
        this.showConnectivityNotification('Conexión restaurada', 'success');
    }
    
    handleOffline() {
        console.log('Connection lost');
        this.isOnline = false;
        
        stateManager.setState('connectivity', {
            online: false,
            lastOffline: Date.now()
        });
        
        this.showConnectivityNotification('Sin conexión a internet', 'warning');
    }
    
    async checkConnectivity() {
        try {
            const response = await fetch('/ping', { 
                method: 'HEAD',
                cache: 'no-cache',
                timeout: 5000
            });
            
            const wasOnline = this.isOnline;
            this.isOnline = response.ok;
            
            if (!wasOnline && this.isOnline) {
                this.handleOnline();
            } else if (wasOnline && !this.isOnline) {
                this.handleOffline();
            }
        } catch (error) {
            if (this.isOnline) {
                this.handleOffline();
            }
        }
    }
    
    handleOfflineRequest(options) {
        const deferred = $.Deferred();
        
        // Queue the request
        this.queueRequest(options, deferred);
        
        // Return a promise that will resolve when online
        return deferred.promise();
    }
    
    queueRequest(options, deferred = null) {
        this.offlineQueue.push({
            options,
            deferred,
            timestamp: Date.now(),
            retries: 0
        });
        
        console.log(`Queued offline request: ${options.url}`);
    }
    
    async processOfflineQueue() {
        if (this.offlineQueue.length === 0) return;
        
        console.log(`Processing ${this.offlineQueue.length} queued requests`);
        
        const queue = [...this.offlineQueue];
        this.offlineQueue = [];
        
        for (const item of queue) {
            try {
                const result = await $.ajax(item.options);
                
                if (item.deferred) {
                    item.deferred.resolve(result);
                }
                
                console.log(`Successfully processed queued request: ${item.options.url}`);
                
            } catch (error) {
                item.retries++;
                
                if (item.retries < this.maxRetries) {
                    // Re-queue with delay
                    setTimeout(() => {
                        this.offlineQueue.push(item);
                    }, Math.pow(2, item.retries) * 1000); // Exponential backoff
                } else {
                    console.error(`Failed to process queued request after ${this.maxRetries} retries:`, item.options.url);
                    
                    if (item.deferred) {
                        item.deferred.reject(error);
                    }
                }
            }
        }
    }
    
    showConnectivityNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `connectivity-notification ${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: ${type === 'success' ? '#4CAF50' : type === 'warning' ? '#FF9800' : '#2196F3'};
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            z-index: 10001;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        `;
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Auto-remove
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    getQueueStatus() {
        return {
            isOnline: this.isOnline,
            queueLength: this.offlineQueue.length,
            retryAttempts: Object.fromEntries(this.retryAttempts)
        };
    }
}

// ==========================================
// GESTOR DE ACCESIBILIDAD
// ==========================================
class AccessibilityManager {
    constructor() {
        this.preferences = {
            reducedMotion: false,
            highContrast: false,
            largeText: false,
            screenReader: false
        };
        this.init();
    }
    
    init() {
        this.detectPreferences();
        this.setupKeyboardNavigation();
        this.setupARIA();
        this.setupFocusManagement();
    }
    
    detectPreferences() {
        // Detect reduced motion preference
        if (window.matchMedia) {
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.preferences.reducedMotion = reducedMotion.matches;
            
            reducedMotion.addEventListener('change', (e) => {
                this.preferences.reducedMotion = e.matches;
                this.applyMotionPreferences();
            });
            
            // Detect high contrast
            const highContrast = window.matchMedia('(prefers-contrast: high)');
            this.preferences.highContrast = highContrast.matches;
            
            highContrast.addEventListener('change', (e) => {
                this.preferences.highContrast = e.matches;
                this.applyContrastPreferences();
            });
        }
        
        // Detect screen reader
        this.preferences.screenReader = this.detectScreenReader();
        
        this.applyPreferences();
    }
    
    detectScreenReader() {
        // Simple screen reader detection
        return window.navigator.userAgent.includes('NVDA') ||
               window.navigator.userAgent.includes('JAWS') ||
               window.speechSynthesis !== undefined;
    }
    
    applyPreferences() {
        this.applyMotionPreferences();
        this.applyContrastPreferences();
        this.applyTextPreferences();
    }
    
    applyMotionPreferences() {
        if (this.preferences.reducedMotion) {
            document.body.classList.add('reduced-motion');
            
            // Disable visual effects
            visualEffects.cleanup();
            
            // Reduce animation durations
            const style = document.createElement('style');
            style.textContent = `
                *, *::before, *::after {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    applyContrastPreferences() {
        if (this.preferences.highContrast) {
            document.body.classList.add('high-contrast');
        }
    }
    
    applyTextPreferences() {
        if (this.preferences.largeText) {
            document.body.classList.add('large-text');
        }
    }
    
    setupKeyboardNavigation() {
        // Enhanced keyboard navigation
        document.addEventListener('keydown', (e) => {
            switch (e.key) {
                case 'Escape':
                    this.handleEscapeKey();
                    break;
                case 'Tab':
                    this.handleTabKey(e);
                    break;
                case 'Enter':
                case ' ':
                    this.handleActivationKey(e);
                    break;
            }
        });
    }
    
    handleEscapeKey() {
        // Close open modals
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            $(openModal).modal('hide');
            return;
        }
        
        // Close sliders if open
        if (uiManager.slidersActive) {
            uiManager.toggleSliders();
        }
    }
    
    handleTabKey(e) {
        // Trap focus in modals
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const focusableElements = openModal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            
            if (focusableElements.length === 0) return;
            
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }
    
    handleActivationKey(e) {
        const target = e.target;
        
        // Handle custom clickable elements
        if (target.hasAttribute('data-clickable') || target.classList.contains('clickable')) {
            e.preventDefault();
            target.click();
        }
    }
    
    setupARIA() {
        // Add ARIA labels to interactive elements
        document.querySelectorAll('button:not([aria-label])').forEach(button => {
            if (!button.textContent.trim()) {
                button.setAttribute('aria-label', 'Button');
            }
        });
        
        // Add ARIA roles
        document.querySelectorAll('.modal').forEach(modal => {
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
        });
        
        // Add live regions for dynamic content
        this.createLiveRegion();
    }
    
    createLiveRegion() {
        const liveRegion = document.createElement('div');
        liveRegion.id = 'live-region';
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.style.cssText = `
            position: absolute;
            left: -10000px;
            width: 1px;
            height: 1px;
            overflow: hidden;
        `;
        document.body.appendChild(liveRegion);
    }
    
    announceToScreenReader(message) {
        const liveRegion = document.getElementById('live-region');
        if (liveRegion) {
            liveRegion.textContent = message;
            
            // Clear after announcement
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        }
    }
    
    setupFocusManagement() {
        // Visible focus indicators
        const style = document.createElement('style');
        style.textContent = `
            .focus-visible {
                outline: 2px solid #007cba;
                outline-offset: 2px;
            }
            
            button:focus-visible,
            a:focus-visible,
            input:focus-visible,
            select:focus-visible,
            textarea:focus-visible {
                outline: 2px solid #007cba;
                outline-offset: 2px;
            }
        `;
        document.head.appendChild(style);
    }
    
    setPreference(key, value) {
        this.preferences[key] = value;
        this.applyPreferences();
        
        // Save to localStorage
        localStorage.setItem('accessibility-preferences', JSON.stringify(this.preferences));
    }
    
    loadPreferences() {
        const saved = localStorage.getItem('accessibility-preferences');
        if (saved) {
            this.preferences = { ...this.preferences, ...JSON.parse(saved) };
            this.applyPreferences();
        }
    }
}

// ==========================================
// APLICACIÓN PRINCIPAL MEJORADA
// ==========================================
class UltimateOptimizedApp extends OptimizedApp {
    constructor() {
        super();
        
        // Replace managers with enhanced versions
        this.domCache = new EnhancedDOMCache();
        this.audioManager = new EnhancedAudioManager();
        this.visualEffects = new EnhancedVisualEffectsManager();
        
        // Add new managers
        this.connectivityManager = new ConnectivityManager();
        this.accessibilityManager = new AccessibilityManager();
        
        // Setup advanced features
        this.setupAdvancedFeatures();
    }
    
    setupAdvancedFeatures() {
        // Setup computed state properties
        this.setupComputedStates();
        
        // Setup performance monitoring
        this.setupPerformanceMonitoring();
        
        // Setup error recovery
        this.setupErrorRecovery();
        
        // Setup lazy loading
        this.setupLazyLoading();
    }
    
    setupComputedStates() {
        // App health computed property
        stateManager.addComputed('appHealth', (state) => {
            const errors = errorManager.errors.length;
            const memory = performanceManager.getMetrics('memory');
            const connectivity = state.connectivity?.online ?? true;
            
            let health = 100;
            if (errors > 5) health -= 20;
            if (memory.length > 0 && memory[memory.length - 1].value.used > 50 * 1024 * 1024) health -= 30;
            if (!connectivity) health -= 25;
            
            return Math.max(0, health);
        }, ['app', 'connectivity']);
        
        // Performance score
        stateManager.addComputed('performanceScore', (state) => {
            const cacheStats = cacheManager.getAllStats();
            const avgHitRate = Object.values(cacheStats).reduce((sum, stat) => {
                return sum + parseFloat(stat.hitRate);
            }, 0) / Object.keys(cacheStats).length;
            
            return Math.round(avgHitRate);
        }, ['app']);
    }
    
    setupPerformanceMonitoring() {
        // Monitor app health
        stateManager.subscribe('computed_appHealth', (health) => {
            if (health < 50) {
                console.warn(`App health is low: ${health}%`);
                this.performHealthRecovery();
            }
        });
        
        // Monitor performance score
        stateManager.subscribe('computed_performanceScore', (score) => {
            if (score < 70) {
                console.warn(`Performance score is low: ${score}%`);
                this.optimizePerformance();
            }
        });
    }
    
    setupErrorRecovery() {
        // Auto-recovery for critical errors
        stateManager.subscribe('app', (appState) => {
            if (appState.error && appState.error.critical) {
                console.log('Attempting auto-recovery from critical error...');
                this.performEmergencyRecovery();
            }
        });
    }
    
    setupLazyLoading() {
        // Setup lazy loading for modals and content
        document.querySelectorAll('[data-lazy]').forEach(element => {
            lazyLoadManager.observe(element);
        });
    }
    
    performHealthRecovery() {
        console.log('Performing health recovery...');
        
        // Clear caches
        cacheManager.caches.forEach((cache, name) => {
            if (cache.data.size > cache.maxSize * 0.8) {
                cacheManager.clear(name);
            }
        });
        
        // Reduce visual effects
        if (visualEffects.isGenerating) {
            visualEffects.enablePerformanceMode();
        }
        
        // Clear old errors
        errorManager.clearErrors();
        
        // Force garbage collection if available
        if (window.gc) {
            window.gc();
        }
        
        stateManager.setState('app', { 
            lastHealthRecovery: Date.now(),
            error: null 
        });
    }
    
    optimizePerformance() {
        console.log('Optimizing performance...');
        
        // Enable performance mode for visual effects
        visualEffects.enablePerformanceMode();
        
        // Reduce cache sizes temporarily
        cacheManager.caches.forEach((cache, name) => {
            cache.maxSize = Math.floor(cache.maxSize * 0.7);
        });
        
        // Cleanup DOM cache
        this.domCache.cacheManager.clear('dom');
        
        stateManager.setState('app', { 
            lastOptimization: Date.now(),
            performanceMode: true 
        });
    }
    
    performEmergencyRecovery() {
        console.log('Performing emergency recovery...');
        
        try {
            // Stop all activities
            intervalManager.clearAll();
            visualEffects.cleanup();
            
            // Clear all caches
            Object.keys(cacheManager.caches).forEach(name => {
                cacheManager.clear(name);
            });
            
            // Reset state
            stateManager.setState('app', {
                initialized: true,
                loading: false,
                error: null,
                emergencyRecovery: Date.now()
            });
            
            // Restart essential services
            setTimeout(() => {
                this.init();
            }, 1000);
            
            // Notify user
            this.accessibilityManager.announceToScreenReader('La aplicación se ha recuperado de un error crítico');
            
        } catch (recoveryError) {
            console.error('Emergency recovery failed:', recoveryError);
            
            // Last resort: reload page
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }
    }
    
    // Enhanced debugging and monitoring
    getDetailedState() {
        return {
            ...super.getState(),
            performance: performanceManager.getAllMetrics(),
            errors: errorManager.getErrorReport(),
            cache: cacheManager.getAllStats(),
            connectivity: this.connectivityManager.getQueueStatus(),
            accessibility: this.accessibilityManager.preferences,
            health: stateManager.getState('computed_appHealth'),
            performanceScore: stateManager.getState('computed_performanceScore')
        };
    }
    
    generateHealthReport() {
        const state = this.getDetailedState();
        
        return {
            timestamp: new Date().toISOString(),
            health: state.health,
            performance: state.performanceScore,
            uptime: Date.now() - (stateManager.getState('app')?.startTime || Date.now()),
            errors: state.errors.errors.length,
            memory: state.performance.memory?.[state.performance.memory.length - 1]?.value,
            cache: state.cache,
            connectivity: state.connectivity.isOnline,
            recommendations: this.generateRecommendations(state)
        };
    }
    
    generateRecommendations(state) {
        const recommendations = [];
        
        if (state.health < 70) {
            recommendations.push('Consider clearing browser cache and reloading the page');
        }
        
        if (state.errors.errors.length > 10) {
            recommendations.push('Multiple errors detected - check console for details');
        }
        
        if (!state.connectivity.isOnline) {
            recommendations.push('Check internet connection');
        }
        
        if (state.performance.memory?.used > 100 * 1024 * 1024) {
            recommendations.push('High memory usage detected - consider closing other tabs');
        }
        
        return recommendations;
    }
    
    cleanup() {
        console.log('Performing ultimate cleanup...');
        
        // Cleanup all managers
        super.cleanup();
        performanceManager.cleanup();
        errorManager.clearErrors();
        stateManager.clearHistory();
        cacheManager.caches.forEach((cache, name) => cacheManager.clear(name));
        lazyLoadManager.cleanup();
        
        console.log('Ultimate cleanup completed');
    }
}

// ==========================================
// INICIALIZACIÓN FINAL Y HERRAMIENTAS DE DEBUG
// ==========================================

// Replace global instances
const ultimateApp = new UltimateOptimizedApp();

// Enhanced global functions with error handling
const createSafeFunction = (fn, name) => {
    return function(...args) {
        try {
            return fn.apply(this, args);
        } catch (error) {
            console.error(`Error in ${name}:`, error);
            errorManager.handleError({
                type: 'function',
                message: `Error in ${name}: ${error.message}`,
                function: name,
                args: args,
                timestamp: Date.now()
            });
        }
    };
};

// Safe modal functions
window.modalitiesGet = createSafeFunction(() => modalManager.showModal('modalities'), 'modalitiesGet');
window.boardGet = createSafeFunction(() => modalManager.showModal('board'), 'boardGet');
window.gamesGet = createSafeFunction(() => modalManager.loadModal('games', site_url + 'games/gamesGet'), 'gamesGet');
window.awardsGet = createSafeFunction(() => modalManager.loadModal('awards', site_url + 'boards/awardsGet'), 'awardsGet');
window.awardsGameGet = createSafeFunction(() => modalManager.loadModal('awards', site_url + 'boards/awardsGameGet'), 'awardsGameGet');
window.gameAdd = createSafeFunction(() => modalManager.loadModal('add-game', site_url + 'games/add'), 'gameAdd');
window.modalityAdd = createSafeFunction(() => modalManager.loadModal('add-modality', site_url + 'games/addmodality'), 'modalityAdd');
window.playersGet = createSafeFunction(() => modalManager.loadModal('players', site_url + 'boards/playersGet'), 'playersGet');
window.paymentsGet = createSafeFunction(() => modalManager.loadModal('payments', site_url + 'payments/paymentsGet'), 'paymentsGet');
window.rechargeGet = createSafeFunction(() => modalManager.loadModal('recharge', site_url + 'payments/rechargeGet'), 'rechargeGet');
window.retireGet = createSafeFunction(() => modalManager.loadModal('retire', site_url + 'payments/retireGet'), 'retireGet');
window.settingswalletGet = createSafeFunction(() => modalManager.loadModal('settings', site_url + 'payments/settingswalletGet'), 'settingswalletGet');
window.settingsGet = createSafeFunction(() => modalManager.loadModal('settings', site_url + 'home/settingsGet'), 'settingsGet');
window.ViewSliders = createSafeFunction(() => uiManager.toggleSliders(), 'ViewSliders');

// Initialize app
document.addEventListener('DOMContentLoaded', () => {
    ultimateApp.init();
    stateManager.setState('app', { startTime: Date.now() });
});

$(function() {
    ultimateApp.init();
});

// ==========================================
// HERRAMIENTAS DE DEBUGGING AVANZADAS
// ==========================================
if (typeof DEBUG !== 'undefined' && DEBUG) {
    window.UltimateAppDebug = {
        // State management
        getState: () => ultimateApp.getDetailedState(),
        setState: (key, value) => stateManager.setState(key, value),
        getStateHistory: (key) => stateManager.getHistory(key),
        
        // Performance monitoring
        getPerformanceMetrics: () => performanceManager.getAllMetrics(),
        getHealthReport: () => ultimateApp.generateHealthReport(),
        forceHealthRecovery: () => ultimateApp.performHealthRecovery(),
        
        // Error management
        getErrors: () => errorManager.getErrorReport(),
        clearErrors: () => errorManager.clearErrors(),
        simulateError: (type = 'test') => {
            throw new Error(`Simulated ${type} error for testing`);
        },
        
        // Cache management
        getCacheStats: () => cacheManager.getAllStats(),
        clearCache: (name) => cacheManager.clear(name),
        clearAllCaches: () => {
            Object.keys(cacheManager.caches).forEach(name => cacheManager.clear(name));
        },
        
        // Connectivity
        getConnectivityStatus: () => ultimateApp.connectivityManager.getQueueStatus(),
        simulateOffline: () => ultimateApp.connectivityManager.handleOffline(),
        simulateOnline: () => ultimateApp.connectivityManager.handleOnline(),
        
        // Accessibility
        getAccessibilityPrefs: () => ultimateApp.accessibilityManager.preferences,
        setAccessibilityPref: (key, value) => ultimateApp.accessibilityManager.setPreference(key, value),
        announceToScreenReader: (message) => ultimateApp.accessibilityManager.announceToScreenReader(message),
        
        // Visual effects
        getEffectsState: () => ({
            isGenerating: visualEffects.isGenerating,
            performanceMode: visualEffects.performanceMode,
            activeStars: visualEffects.starPool.activeStars.size,
            poolSize: visualEffects.starPool.pool.length
        }),
        
        // Utilities
        forceCleanup: () => ultimateApp.cleanup(),
        forceGarbageCollection: () => {
            if (window.gc) window.gc();
            else console.warn('Garbage collection not available');
        },
        
        // Utilities (continuación)
        exportState: () => {
            const state = ultimateApp.getDetailedState();
            const blob = new Blob([JSON.stringify(state, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `app-state-${new Date().toISOString()}.json`;
            a.click();
            URL.revokeObjectURL(url);
        },
        
        importState: (stateData) => {
            try {
                const state = typeof stateData === 'string' ? JSON.parse(stateData) : stateData;
                Object.keys(state).forEach(key => {
                    if (key !== 'performance' && key !== 'errors') {
                        stateManager.setState(key, state[key]);
                    }
                });
                console.log('State imported successfully');
            } catch (error) {
                console.error('Failed to import state:', error);
            }
        },
        
        // Performance testing
        runPerformanceTest: () => {
            console.log('Running performance test...');
            const startTime = performance.now();
            
            // Test DOM operations
            for (let i = 0; i < 100; i++) {
                ultimateApp.domCache.get('body');
            }
            
            // Test state operations
            for (let i = 0; i < 50; i++) {
                stateManager.setState('test', { value: i });
            }
            
            // Test cache operations
            for (let i = 0; i < 100; i++) {
                cacheManager.set('test', `key${i}`, `value${i}`);
                cacheManager.get('test', `key${i}`);
            }
            
            const endTime = performance.now();
            console.log(`Performance test completed in ${endTime - startTime}ms`);
            
            return {
                duration: endTime - startTime,
                domCacheStats: cacheManager.getStats('dom'),
                testCacheStats: cacheManager.getStats('test')
            };
        },
        
        // Memory testing
        runMemoryTest: () => {
            console.log('Running memory test...');
            const initialMemory = performance.memory ? performance.memory.usedJSHeapSize : 0;
            
            // Create memory pressure
            const testData = [];
            for (let i = 0; i < 1000; i++) {
                testData.push(new Array(1000).fill(Math.random()));
            }
            
            const peakMemory = performance.memory ? performance.memory.usedJSHeapSize : 0;
            
            // Cleanup
            testData.length = 0;
            if (window.gc) window.gc();
            
            const finalMemory = performance.memory ? performance.memory.usedJSHeapSize : 0;
            
            return {
                initialMemory,
                peakMemory,
                finalMemory,
                memoryIncrease: peakMemory - initialMemory,
                memoryRecovered: peakMemory - finalMemory
            };
        },
        
        // Stress testing
        runStressTest: async () => {
            console.log('Running stress test...');
            const results = {
                errors: [],
                performance: [],
                memory: []
            };
            
            try {
                // Stress test modals
                for (let i = 0; i < 10; i++) {
                    await modalManager.showModal('test-modal');
                    await new Promise(resolve => setTimeout(resolve, 100));
                    modalManager.hideModal('test-modal');
                }
                
                // Stress test visual effects
                visualEffects.startGeneration(document.body);
                await new Promise(resolve => setTimeout(resolve, 2000));
                visualEffects.stopGeneration();
                
                // Stress test state changes
                for (let i = 0; i < 100; i++) {
                    stateManager.setState('stress-test', { iteration: i, data: new Array(100).fill(i) });
                }
                
                // Stress test cache
                for (let i = 0; i < 500; i++) {
                    cacheManager.set('stress', `key${i}`, { data: new Array(50).fill(i) });
                }
                
                results.performance.push(ultimateApp.generateHealthReport());
                
            } catch (error) {
                results.errors.push(error);
            }
            
            console.log('Stress test completed');
            return results;
        }
    };
    
    // Console styling for debug messages
    const debugStyle = 'color: #00ff00; font-weight: bold; background: #000; padding: 2px 4px; border-radius: 2px;';
    console.log('%cUltimate App Debug Tools Available', debugStyle);
    console.log('%cUse UltimateAppDebug object for debugging', debugStyle);
    console.log('%cExample: UltimateAppDebug.getState()', 'color: #ffff00;');
}

// ==========================================
// SISTEMA DE PLUGINS Y EXTENSIONES
// ==========================================
class PluginManager {
    constructor() {
        this.plugins = new Map();
        this.hooks = new Map();
        this.loadedPlugins = new Set();
    }
    
    registerPlugin(name, plugin) {
        if (this.plugins.has(name)) {
            console.warn(`Plugin ${name} is already registered`);
            return false;
        }
        
        // Validate plugin structure
        if (!this.validatePlugin(plugin)) {
            console.error(`Plugin ${name} is invalid`);
            return false;
        }
        
        this.plugins.set(name, plugin);
        console.log(`Plugin ${name} registered successfully`);
        return true;
    }
    
    validatePlugin(plugin) {
        return typeof plugin === 'object' &&
               typeof plugin.init === 'function' &&
               typeof plugin.name === 'string' &&
               typeof plugin.version === 'string';
    }
    
    async loadPlugin(name) {
        const plugin = this.plugins.get(name);
        if (!plugin) {
            console.error(`Plugin ${name} not found`);
            return false;
        }
        
        if (this.loadedPlugins.has(name)) {
            console.warn(`Plugin ${name} is already loaded`);
            return true;
        }
        
        try {
            // Check dependencies
            if (plugin.dependencies) {
                for (const dep of plugin.dependencies) {
                    if (!this.loadedPlugins.has(dep)) {
                        console.error(`Plugin ${name} requires ${dep} to be loaded first`);
                        return false;
                    }
                }
            }
            
            // Initialize plugin
            await plugin.init(ultimateApp);
            this.loadedPlugins.add(name);
            
            // Register hooks
            if (plugin.hooks) {
                Object.keys(plugin.hooks).forEach(hookName => {
                    this.registerHook(hookName, plugin.hooks[hookName]);
                });
            }
            
            console.log(`Plugin ${name} loaded successfully`);
            return true;
            
        } catch (error) {
            console.error(`Failed to load plugin ${name}:`, error);
            return false;
        }
    }
    
    unloadPlugin(name) {
        const plugin = this.plugins.get(name);
        if (!plugin || !this.loadedPlugins.has(name)) {
            return false;
        }
        
        try {
            // Call cleanup if available
            if (typeof plugin.cleanup === 'function') {
                plugin.cleanup();
            }
            
            // Remove hooks
            if (plugin.hooks) {
                Object.keys(plugin.hooks).forEach(hookName => {
                    this.unregisterHook(hookName, plugin.hooks[hookName]);
                });
            }
            
            this.loadedPlugins.delete(name);
            console.log(`Plugin ${name} unloaded successfully`);
            return true;
            
        } catch (error) {
            console.error(`Failed to unload plugin ${name}:`, error);
            return false;
        }
    }
    
    registerHook(name, callback) {
        if (!this.hooks.has(name)) {
            this.hooks.set(name, new Set());
        }
        this.hooks.get(name).add(callback);
    }
    
    unregisterHook(name, callback) {
        const hooks = this.hooks.get(name);
        if (hooks) {
            hooks.delete(callback);
            if (hooks.size === 0) {
                this.hooks.delete(name);
            }
        }
    }
    
    async executeHook(name, ...args) {
        const hooks = this.hooks.get(name);
        if (!hooks) return;
        
        const results = [];
        for (const hook of hooks) {
            try {
                const result = await hook(...args);
                results.push(result);
            } catch (error) {
                console.error(`Error executing hook ${name}:`, error);
            }
        }
        return results;
    }
    
    getLoadedPlugins() {
        return Array.from(this.loadedPlugins);
    }
    
    getAvailablePlugins() {
        return Array.from(this.plugins.keys());
    }
}

// ==========================================
// PLUGINS DE EJEMPLO
// ==========================================

// Plugin de Analytics
const AnalyticsPlugin = {
    name: 'Analytics',
    version: '1.0.0',
    description: 'Advanced analytics and tracking',
    
    init(app) {
        this.app = app;
        this.setupTracking();
        console.log('Analytics plugin initialized');
    },
    
    setupTracking() {
        // Track page views
        this.trackPageView();
        
        // Track user interactions
        document.addEventListener('click', (e) => {
            if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A') {
                this.trackEvent('click', {
                    element: e.target.tagName,
                    text: e.target.textContent.trim().substring(0, 50),
                    timestamp: Date.now()
                });
            }
        });
        
        // Track errors
        stateManager.subscribe('app', (state) => {
            if (state.error) {
                this.trackEvent('error', state.error);
            }
        });
        
        // Track performance
        intervalManager.setInterval('analyticsPerformance', () => {
            const health = stateManager.getState('computed_appHealth');
            if (health < 80) {
                this.trackEvent('performance_warning', { health });
            }
        }, 60000);
    },
    
    trackPageView() {
        this.trackEvent('page_view', {
            url: window.location.href,
            title: document.title,
            timestamp: Date.now()
        });
    },
    
    trackEvent(eventName, data) {
        // Send to analytics service
        console.log(`Analytics: ${eventName}`, data);
        
        // Store locally for offline sync
        const events = JSON.parse(localStorage.getItem('analytics_events') || '[]');
        events.push({
            event: eventName,
            data,
            timestamp: Date.now()
        });
        
        // Keep only last 100 events
        if (events.length > 100) {
            events.splice(0, events.length - 100);
        }
        
        localStorage.setItem('analytics_events', JSON.stringify(events));
    },
    
    cleanup() {
        intervalManager.clearInterval('analyticsPerformance');
        console.log('Analytics plugin cleaned up');
    },
    
    hooks: {
        'modal_opened': (modalId) => {
            AnalyticsPlugin.trackEvent('modal_opened', { modalId });
        },
        'audio_toggled': (isPlaying) => {
            AnalyticsPlugin.trackEvent('audio_toggled', { isPlaying });
        }
    }
};

// Plugin de Temas
const ThemePlugin = {
    name: 'Theme',
    version: '1.0.0',
    description: 'Advanced theming system',
    
    themes: {
        default: {
            name: 'Default',
            colors: {
                primary: '#007cba',
                secondary: '#6c757d',
                success: '#28a745',
                danger: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            }
        },
        dark: {
            name: 'Dark',
            colors: {
                primary: '#0d6efd',
                secondary: '#6c757d',
                success: '#198754',
                danger: '#dc3545',
                warning: '#ffc107',
                info: '#0dcaf0'
            },
            css: `
                body { background-color: #121212; color: #ffffff; }
                .modal-content { background-color: #1e1e1e; color: #ffffff; }
                .btn-primary { background-color: #0d6efd; border-color: #0d6efd; }
            `
        },
        highContrast: {
            name: 'High Contrast',
            colors: {
                primary: '#000000',
                secondary: '#ffffff',
                success: '#00ff00',
                danger: '#ff0000',
                warning: '#ffff00',
                info: '#00ffff'
            },
            css: `
                body { background-color: #ffffff; color: #000000; }
                button { border: 2px solid #000000; }
                a { color: #0000ff; text-decoration: underline; }
            `
        }
    },
    
    currentTheme: 'default',
    
    init(app) {
        this.app = app;
        this.loadSavedTheme();
        this.setupThemeControls();
        console.log('Theme plugin initialized');
    },
    
    loadSavedTheme() {
        const saved = localStorage.getItem('selected_theme');
        if (saved && this.themes[saved]) {
            this.applyTheme(saved);
        }
    },
    
    setupThemeControls() {
        // Add theme selector to settings modal
        const themeSelector = document.createElement('div');
        themeSelector.className = 'theme-selector';
        themeSelector.innerHTML = `
            <h5>Tema</h5>
            <select id="theme-select" class="form-control">
                ${Object.keys(this.themes).map(key => 
                    `<option value="${key}" ${key === this.currentTheme ? 'selected' : ''}>
                        ${this.themes[key].name}
                    </option>`
                ).join('')}
            </select>
        `;
        
        // Add to settings modal when it opens
        stateManager.subscribe('ui', (uiState) => {
            if (uiState.activeModal === 'settings') {
                setTimeout(() => {
                    const settingsModal = document.querySelector('#settings .modal-body');
                    if (settingsModal && !settingsModal.querySelector('.theme-selector')) {
                        settingsModal.appendChild(themeSelector);
                        
                        document.getElementById('theme-select').addEventListener('change', (e) => {
                            this.applyTheme(e.target.value);
                        });
                    }
                }, 100);
            }
        });
    },
    
    applyTheme(themeName) {
        const theme = this.themes[themeName];
        if (!theme) return;
        
        this.currentTheme = themeName;
        
        // Remove previous theme styles
        const existingStyle = document.getElementById('theme-styles');
        if (existingStyle) {
            existingStyle.remove();
        }
        
        // Apply new theme
        if (theme.css) {
            const style = document.createElement('style');
            style.id = 'theme-styles';
            style.textContent = theme.css;
            document.head.appendChild(style);
        }
        
        // Apply CSS custom properties
        const root = document.documentElement;
        Object.keys(theme.colors).forEach(key => {
            root.style.setProperty(`--color-${key}`, theme.colors[key]);
        });
        
        // Update body class
        document.body.className = document.body.className.replace(/theme-\w+/g, '');
        document.body.classList.add(`theme-${themeName}`);
        
        // Save preference
        localStorage.setItem('selected_theme', themeName);
        
        // Update state
        stateManager.setState('ui', { theme: themeName });
        
        console.log(`Applied theme: ${theme.name}`);
    },
    
    getAvailableThemes() {
        return Object.keys(this.themes).map(key => ({
            key,
            name: this.themes[key].name
        }));
    },
    
    getCurrentTheme() {
        return this.currentTheme;
    },
    
    cleanup() {
        const existingStyle = document.getElementById('theme-styles');
        if (existingStyle) {
            existingStyle.remove();
        }
        console.log('Theme plugin cleaned up');
    }
};

// Plugin de Notificaciones
const NotificationPlugin = {
    name: 'Notifications',
    version: '1.0.0',
    description: 'Advanced notification system',
    
    notifications: [],
    container: null,
    
    init(app) {
        this.app = app;
        this.createContainer();
        this.setupNotificationHandlers();
        console.log('Notifications plugin initialized');
    },
    
    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        this.container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 350px;
        `;
        document.body.appendChild(this.container);
    },
    
    setupNotificationHandlers() {
        // Listen for app events
        stateManager.subscribe('app', (state) => {
            if (state.error) {
                this.show('Error en la aplicación', state.error.message, 'error');
            }
        });
        
        // Listen for connectivity changes
        stateManager.subscribe('connectivity', (state) => {
            if (state.online === false) {
                this.show('Sin conexión', 'Se perdió la conexión a internet', 'warning', 0);
            } else if (state.online === true) {
                this.show('Conectado', 'Conexión restaurada', 'success', 3000);
            }
        });
    },
    
    show(title, message, type = 'info', duration = 5000) {
        const notification = this.createNotification(title, message, type);
        this.container.appendChild(notification);
        this.notifications.push(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Auto-remove
        if (duration > 0) {
            setTimeout(() => {
                this.remove(notification);
            }, duration);
        }
        
        return notification;
    },
    
    createNotification(title, message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: ${this.getTypeColor(type)};
            color: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            cursor: pointer;
        `;
        
        notification.innerHTML = `
            <div style="font-weight: bold; margin-bottom: 5px;">${title}</div>
            <div style="font-size: 14px;">${message}</div>
            <div style="position: absolute; top: 5px; right: 10px; cursor: pointer; font-size: 18px;">×</div>
        `;
        
        // Add close handler
        notification.addEventListener('click', () => {
            this.remove(notification);
        });
        
        return notification;
    },
    
    getTypeColor(type) {
        const colors = {
            info: '#2196F3',
            success: '#4CAF50',
            warning: '#FF9800',
            error: '#f44336'
        };
        return colors[type] || colors.info;
    },
    
    remove(notification) {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
            const index = this.notifications.indexOf(notification);
            if (index > -1) {
                this.notifications.splice(index, 1);
            }
        }, 300);
    },
    
    clear() {
        this.notifications.forEach(notification => {
            this.remove(notification);
        });
    },
    
    cleanup() {
        this.clear();
        if (this.container && this.container.parentNode) {
            this.container.parentNode.removeChild(this.container);
        }
        console.log('Notifications plugin cleaned up');
    }
};

// ==========================================
// INICIALIZACIÓN DEL SISTEMA DE PLUGINS
// ==========================================
const pluginManager = new PluginManager();

// Registrar plugins
pluginManager.registerPlugin('analytics', AnalyticsPlugin);
pluginManager.registerPlugin('theme', ThemePlugin);
pluginManager.registerPlugin('notifications', NotificationPlugin);

// Cargar plugins automáticamente
document.addEventListener('DOMContentLoaded', async () => {
    await pluginManager.loadPlugin('notifications');
    await pluginManager.loadPlugin('theme');
    await pluginManager.loadPlugin('analytics');
});

// ==========================================
// INTEGRACIÓN DE HOOKS EN LA APLICACIÓN
// ==========================================

// Integrar hooks en modalManager
const originalShowModal = modalManager.showModal;
modalManager.showModal = function(modalId) {
    const result = originalShowModal.call(this, modalId);
    pluginManager.executeHook('modal_opened', modalId);
    return result;
};

// Integrar hooks en audioManager
const originalToggleSoundtrack = ultimateApp.audioManager.toggleSoundtrack;
ultimateApp.audioManager.toggleSoundtrack = function() {
    const result = originalToggleSoundtrack.call(this);
    pluginManager.executeHook('audio_toggled', result);
    return result;
};

// ==========================================
// SISTEMA DE CONFIGURACIÓN AVANZADA
// ==========================================
class ConfigManager {
    constructor() {
        this.config = new Map();
        this.watchers = new Map();
        this.loadDefaultConfig();
        this.loadUserConfig();
    }
    
    loadDefaultConfig() {
        const defaultConfig = {
            // Performance settings
            performance: {
                enableVisualEffects: true,
                maxStars: 50,
                frameRate: 60,
                enablePerformanceMode: false,
                memoryThreshold: 50 * 1024 * 1024
            },
            
            // UI settings
            ui: {
                theme: 'default',
                enableAnimations: true,
                showNotifications: true,
                autoCloseModals: false,
                keyboardNavigation: true
            },
            
            // Audio settings
            audio: {
                enabled: true,
                volume: 1.0,
                autoplay: false,
                enableSoundEffects: true
            },
            
            // Accessibility settings
            accessibility: {
                reducedMotion: false,
                highContrast: false,
                largeText: false,
                screenReaderSupport: true
            },
            
            // Debug settings
            debug: {
                enableLogging: false,
                enablePerformanceMonitoring: true,
                enableErrorReporting: true,
                logLevel: 'warn'
            }
        };
        
        Object.keys(defaultConfig).forEach(section => {
            this.config.set(section, defaultConfig[section]);
        });
    }
    
    loadUserConfig() {
        try {
            const userConfig = localStorage.getItem('app_config');
            if (userConfig) {
                const parsed = JSON.parse(userConfig);
                Object.keys(parsed).forEach(section => {
                    if (this.config.has(section)) {
                        this.config.set(section, { ...this.config.get(section), ...parsed[section] });
                    }
                });
            }
        } catch (error) {
            console.error('Failed to load user config:', error);
        }
    }
    
    get(section, key = null) {
        const sectionConfig = this.config.get(section);
        if (!sectionConfig) return null;
        
        return key ? sectionConfig[key] : sectionConfig;
    }
    
    set(section, key, value = null) {
        if (typeof key === 'object' && value === null) {
            // Setting entire section
            this.config.set(section, { ...this.config.get(section), ...key });
        } else {
            // Setting individual key
            const sectionConfig = this.config.get(section) || {};
            sectionConfig[key] = value;
            this.config.set(section, sectionConfig);
        }
        
        this.saveUserConfig();
        this.notifyWatchers(section, key);
    }
    
    watch(section, callback) {
        if (!this.watchers.has(section)) {
            this.watchers.set(section, new Set());
        }
        this.watchers.get(section).add(callback);
        
        // Return unwatch function
        return () => {
            const watchers = this.watchers.get(section);
            if (watchers) {
                watchers.delete(callback);
            }
        };
    }
    
    notifyWatchers(section, key) {
        const watchers = this.watchers.get(section);
        if (watchers) {
            const sectionConfig = this.config.get(section);
            watchers.forEach(callback => {
                try {
                    callback(sectionConfig, key);
                } catch (error) {
                    console.error('Error in config watcher:', error);
                }
            });
        }
    }
    
    saveUserConfig() {
        try {
            const configObject = {};
            this.config.forEach((value, key) => {
                configObject[key] = value;
            });
            localStorage.setItem('app_config', JSON.stringify(configObject));
        } catch (error) {
            console.error('Failed to save user config:', error);
        }
    }
    
    reset(section = null) {
        if (section) {
            this.loadDefaultConfig();
            this.notifyWatchers(section);
        } else {
            this.config.clear();
            this.loadDefaultConfig();
            this.watchers.forEach((watchers, section) => {
                this.notifyWatchers(section);
            });
        }
        this.saveUserConfig();
    }
    
    export() {
        const configObject = {};
        this.config.forEach((value, key) => {
            configObject[key] = value;
        });
        return configObject;
    }
    
    import(configData) {
        try {
            Object.keys(configData).forEach(section => {
                this.config.set(section, configData[section]);
                this.notifyWatchers(section);
            });
            this.saveUserConfig();
            return true;
        } catch (error) {
            console.error('Failed to import config:', error);
            return false;
        }
    }
}

// Instancia global del gestor de configuración
const configManager = new ConfigManager();

// Aplicar configuración inicial
configManager.watch('performance', (config) => {
    if (config.enableVisualEffects !== visualEffects.isGenerating) {
        if (config.enableVisualEffects) {
            visualEffects.startGeneration(document.body);
        } else {
            visualEffects.stopGeneration();
        }
    }
});

configManager.watch('ui', (config) => {
    if (config.theme !== stateManager.getState('ui')?.theme) {
        if (pluginManager.loadedPlugins.has('theme')) {
            ThemePlugin.applyTheme(config.theme);
        }
    }
});

configManager.watch('audio', (config) => {
    ultimateApp.audioManager.isEnabled = config.enabled;
    if (ultimateApp.audioManager.soundtrack) {
        ultimateApp.audioManager.soundtrack.volume = config.volume;
    }
});

// ==========================================
// EXPORTACIÓN FINAL Y LIMPIEZA
// ==========================================

// Función de limpieza global mejorada
window.addEventListener('beforeunload', () => {
    console.log('Performing final cleanup...');
    
    // Cleanup app
    ultimateApp.cleanup();
    
    // Cleanup plugins
    pluginManager.getLoadedPlugins().forEach(pluginName => {
        pluginManager.unloadPlugin(pluginName);
    });
    
    // Save final state
    configManager.saveUserConfig();
    
    console.log('Final cleanup completed');
});

// Exponer APIs globales para uso externo
window.UltimateApp = {
    // Core app
    app: ultimateApp,
    
    // Managers
    state: stateManager,
    cache: cacheManager,
    config: configManager,
    plugins: pluginManager,
    
    // Utilities
    utils: {
        createSafeFunction,
        intervalManager,
        domCache: ultimateApp.domCache
    },
    
    // API methods
    getState: () => ultimateApp.getDetailedState(),
    getHealthReport: () => ultimateApp.generateHealthReport(),
    cleanup: () => ultimateApp.cleanup()
};

console.log('%c🚀 Ultimate Optimized App Loaded Successfully! 🚀', 
    'color: #00ff00; font-size: 16px; font-weight: bold; background: #000; padding: 5px; border-radius: 5px;');
console.log('%cVersion: 2.0.0 Ultimate Edition', 'color: #ffff00; font-weight: bold;');
console.log('%cFeatures: Advanced State Management, Performance Monitoring, Plugin System, Accessibility, Offline Support', 'color: #00ffff;');
