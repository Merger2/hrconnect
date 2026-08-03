/*!
 * App pull-to-refresh helper
 * Refactored for the absensi-gps-barcode UI theme, accessibility, and touch safety.
 */
(function (global, factory) {
  if (typeof exports === 'object' && typeof module !== 'undefined') {
    module.exports = factory();
    return;
  }

  if (typeof define === 'function' && define.amd) {
    define(factory);
    return;
  }

  global = global || self;
  global.PullToRefresh = factory();
}(this, function () {
  'use strict';

  var shared = {
    handlers: [],
    active: null,
    events: null,
    passive: false,
    pointerEventsEnabled: false,
    supportsPassive: false,
    supportsPointerEvents: typeof window !== 'undefined' && 'PointerEvent' in window,
    styleRegistry: {}
  };

  try {
    window.addEventListener('ptr-passive-test', null, {
      get passive() {
        shared.supportsPassive = true;
        return true;
      }
    });
  } catch (error) {
    // Passive listener support is optional.
  }

  function getDocumentLanguage() {
    if (typeof document === 'undefined' || !document.documentElement) {
      return 'en';
    }

    return (document.documentElement.getAttribute('lang') || 'en').toLowerCase();
  }

  function getDefaultMessages() {
    var isIndonesian = getDocumentLanguage().indexOf('id') === 0;

    if (isIndonesian) {
      return {
        pull: 'Tarik untuk sinkronkan halaman',
        release: 'Lepas untuk segarkan data',
        refreshing: 'Menyinkronkan data',
        hintPull: 'Mulai dari bagian paling atas halaman.',
        hintRelease: 'Lepaskan sekarang untuk memperbarui konten.',
        hintRefreshing: 'Konten sedang disegarkan sebentar.'
      };
    }

    return {
      pull: 'Pull to sync this page',
      release: 'Release to refresh data',
      refreshing: 'Syncing data',
      hintPull: 'Start from the very top of the page.',
      hintRelease: 'Release now to update the content.',
      hintRefreshing: 'Content is refreshing for a moment.'
    };
  }

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function noop() {}

  function getScrollTop(element) {
    if (!element || element === document.body || element === document.documentElement) {
      return Math.max(window.scrollY || 0, document.documentElement.scrollTop || 0, document.body.scrollTop || 0);
    }

    return element.scrollTop;
  }

  function resolveElement(value) {
    if (typeof value === 'string') {
      return document.querySelector(value);
    }

    return value || null;
  }

  function getEventScreenY(event) {
    if (shared.pointerEventsEnabled && shared.supportsPointerEvents && typeof event.screenY === 'number') {
      return event.screenY;
    }

    if (event.touches && event.touches[0]) {
      return event.touches[0].screenY;
    }

    if (event.changedTouches && event.changedTouches[0]) {
      return event.changedTouches[0].screenY;
    }

    return 0;
  }

  function shouldIgnoreStartEvent(event) {
    if (!event) {
      return true;
    }

    if (typeof event.button === 'number' && event.button !== 0) {
      return true;
    }

    if (typeof event.pointerType === 'string' && event.pointerType === 'mouse') {
      return true;
    }

    return false;
  }

  function normalizePrefix(prefix) {
    return String(prefix || 'ptr--').replace(/[^a-z0-9_-]/gi, '-');
  }

  function buildStyleId(prefix) {
    return 'pull-to-refresh-style-' + normalizePrefix(prefix);
  }

  function getMarkup() {
    return [
      '<div class="__PREFIX__surface" role="status" aria-live="polite" aria-atomic="true">',
      '  <div class="__PREFIX__copy">',
      '    <p class="__PREFIX__title"></p>',
      '    <p class="__PREFIX__hint"></p>',
      '  </div>',
      '</div>'
    ].join('');
  }

  function getStyles() {
    return [
      '.__PREFIX__ptr {',
      '  --ptr-progress: 0;',
      '  --ptr-border: rgba(148, 163, 184, 0.28);',
      '  --ptr-border-strong: rgba(87, 148, 74, 0.38);',
      '  --ptr-surface: rgba(255, 255, 255, 0.82);',
      '  --ptr-surface-dark: rgba(15, 23, 42, 0.78);',
      '  --ptr-fill: rgba(87, 148, 74, 0.16);',
      '  --ptr-fill-brand: rgba(6, 182, 212, 0.13);',
      '  --ptr-text: #0f172a;',
      '  --ptr-text-muted: #475569;',
      '  --ptr-accent: #57944a;',
      '  --ptr-brand: #06b6d4;',
      '  --ptr-shadow: 0 16px 36px -30px rgba(15, 23, 42, 0.62);',
      '  pointer-events: none;',
      '  position: relative;',
      '  z-index: 30;',
      '  display: flex;',
      '  width: 100%;',
      '  min-height: 0;',
      '  max-height: 0;',
      '  overflow: hidden;',
      '  align-items: flex-end;',
      '  justify-content: center;',
      '  transition: min-height 220ms ease, max-height 220ms ease;',
      '}',
      '.__PREFIX__surface {',
      '  box-sizing: border-box;',
      '  position: relative;',
      '  isolation: isolate;',
      '  width: fit-content;',
      '  max-width: calc(100% - 1rem);',
      '  min-height: 2.5rem;',
      '  margin: 0.55rem auto 0;',
      '  display: inline-flex;',
      '  align-items: center;',
      '  justify-content: center;',
      '  border-radius: 9999px;',
      '  border: 1px solid var(--ptr-border);',
      '  background: var(--ptr-surface);',
      '  box-shadow: var(--ptr-shadow), 0 0 0 calc(var(--ptr-progress) * 2px) rgba(87, 148, 74, 0.08);',
      '  padding: 0.56rem 1rem;',
      '  overflow: hidden;',
      '  backdrop-filter: blur(16px) saturate(140%);',
      '  transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;',
      '}',
      '.__PREFIX__surface::before {',
      '  content: "";',
      '  position: absolute;',
      '  inset: 0;',
      '  z-index: -2;',
      '  width: calc(var(--ptr-progress) * 100%);',
      '  border-radius: inherit;',
      '  background: linear-gradient(90deg, var(--ptr-fill), var(--ptr-fill-brand));',
      '  transition: width 120ms ease;',
      '}',
      '.__PREFIX__surface::after {',
      '  content: "";',
      '  position: absolute;',
      '  inset: -1px;',
      '  z-index: -1;',
      '  border-radius: inherit;',
      '  background: linear-gradient(110deg, transparent 0 34%, rgba(255,255,255,0.34) 45%, transparent 58% 100%);',
      '  opacity: calc(var(--ptr-progress) * 0.75);',
      '  transform: translateX(calc((var(--ptr-progress) * 44%) - 22%));',
      '  transition: opacity 160ms ease, transform 160ms ease;',
      '}',
      '.__PREFIX__copy {',
      '  min-width: 0;',
      '  position: relative;',
      '  z-index: 1;',
      '}',
      '.__PREFIX__title {',
      '  margin: 0;',
      '  max-width: min(18rem, 72vw);',
      '  overflow: hidden;',
      '  text-overflow: ellipsis;',
      '  white-space: nowrap;',
      '  font-size: 0.84rem;',
      '  font-weight: 700;',
      '  letter-spacing: 0;',
      '  line-height: 1.15;',
      '  color: var(--ptr-text);',
      '}',
      '.__PREFIX__hint {',
      '  position: absolute;',
      '  height: 1px;',
      '  width: 1px;',
      '  overflow: hidden;',
      '  clip: rect(0, 0, 0, 0);',
      '  white-space: nowrap;',
      '}',
      '.__PREFIX__pull {',
      '  transition: none;',
      '}',
      '.__PREFIX__release .__PREFIX__surface {',
      '  border-color: var(--ptr-border-strong);',
      '  transform: translateY(1px);',
      '}',
      '.__PREFIX__refresh .__PREFIX__surface {',
      '  border-color: rgba(6, 182, 212, 0.34);',
      '  animation: __PREFIX__pill 980ms ease-in-out infinite;',
      '}',
      '.__PREFIX__refresh .__PREFIX__surface::before {',
      '  width: 100%;',
      '}',
      '.__PREFIX__refresh .__PREFIX__surface::after {',
      '  opacity: 0.9;',
      '  animation: __PREFIX__sheen 980ms ease-in-out infinite;',
      '}',
      '.__PREFIX__top {',
      '  touch-action: pan-x pan-down pinch-zoom;',
      '}',
      '@keyframes __PREFIX__pill {',
      '  0%, 100% { box-shadow: var(--ptr-shadow), 0 0 0 2px rgba(87, 148, 74, 0.08); }',
      '  50% { box-shadow: var(--ptr-shadow), 0 0 0 3px rgba(6, 182, 212, 0.12); }',
      '}',
      '@keyframes __PREFIX__sheen {',
      '  0% { transform: translateX(-64%); }',
      '  100% { transform: translateX(64%); }',
      '}',
      'html.dark .__PREFIX__surface {',
      '  background: var(--ptr-surface-dark);',
      '  border-color: rgba(132, 193, 120, 0.16);',
      '  box-shadow: 0 18px 40px -32px rgba(0, 0, 0, 0.78);',
      '}',
      'html.dark .__PREFIX__surface::after {',
      '  background: linear-gradient(110deg, transparent 0 34%, rgba(255,255,255,0.12) 45%, transparent 58% 100%);',
      '}',
      'html.dark .__PREFIX__title {',
      '  color: #f8fafc;',
      '}',
      'html.dark .__PREFIX__hint {',
      '  color: #cbd5e1;',
      '}',
      '@media (max-width: 639px) {',
      '  .__PREFIX__surface {',
      '    max-width: calc(100% - 0.75rem);',
      '    min-height: 2.35rem;',
      '    padding: 0.5rem 0.88rem;',
      '  }',
      '  .__PREFIX__title {',
      '    font-size: 0.8rem;',
      '  }',
      '}',
      '@media (prefers-reduced-motion: reduce) {',
      '  .__PREFIX__ptr,',
      '  .__PREFIX__surface {',
      '    transition: none !important;',
      '    animation: none !important;',
      '  }',
      '}',
      '@media (forced-colors: active) {',
      '  .__PREFIX__surface {',
      '    border: 1px solid CanvasText;',
      '    background: Canvas;',
      '    box-shadow: none;',
      '  }',
      '  .__PREFIX__title,',
      '  .__PREFIX__hint {',
      '    color: CanvasText;',
      '  }',
      '}'
    ].join('\n');
  }

  var localizedMessages = getDefaultMessages();

  var defaults = {
    distThreshold: 72,
    distMax: 108,
    distReload: 64,
    distIgnore: 8,
    mainElement: 'body',
    triggerElement: 'body',
    ptrElement: null,
    classPrefix: 'ptr--',
    cssProp: 'min-height',
    instructionsPullToRefresh: localizedMessages.pull,
    instructionsReleaseToRefresh: localizedMessages.release,
    instructionsRefreshing: localizedMessages.refreshing,
    instructionsPullHint: localizedMessages.hintPull,
    instructionsReleaseHint: localizedMessages.hintRelease,
    instructionsRefreshingHint: localizedMessages.hintRefreshing,
    refreshTimeout: 240,
    getMarkup: getMarkup,
    getStyles: getStyles,
    onInit: noop,
    onRefresh: function onRefresh() {
      return location.reload();
    },
    resistanceFunction: function resistanceFunction(value) {
      return Math.min(1, value / 2.15);
    },
    shouldPullToRefresh: function shouldPullToRefresh() {
      if (document.body && document.body.classList.contains('is-native-scanning')) {
        return false;
      }

      return getScrollTop(this.mainElement) <= 0;
    }
  };

  function ensureStyles(handler) {
    var styleId = buildStyleId(handler.classPrefix);

    if (shared.styleRegistry[styleId] || document.getElementById(styleId)) {
      shared.styleRegistry[styleId] = true;
      return;
    }

    var style = document.createElement('style');
    style.id = styleId;
    style.textContent = handler.getStyles(handler).replace(/__PREFIX__/g, handler.classPrefix);
    document.head.appendChild(style);
    shared.styleRegistry[styleId] = true;
  }

  function getStateCopy(handler, state) {
    if (state === 'refreshing') {
      return {
        title: handler.instructionsRefreshing,
        hint: handler.instructionsRefreshingHint
      };
    }

    if (state === 'releasing') {
      return {
        title: handler.instructionsReleaseToRefresh,
        hint: handler.instructionsReleaseHint
      };
    }

    return {
      title: handler.instructionsPullToRefresh,
      hint: handler.instructionsPullHint
    };
  }

  function updateUI(handler) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    var state = handler.state || 'pending';
    var copy = getStateCopy(handler, state);
    var root = handler.ptrElement;
    var titleEl = root.querySelector('.' + handler.classPrefix + 'title');
    var hintEl = root.querySelector('.' + handler.classPrefix + 'hint');
    var surfaceEl = root.querySelector('.' + handler.classPrefix + 'surface');

    root.setAttribute('data-ptr-state', state);

    if (titleEl) {
      titleEl.textContent = copy.title;
    }

    if (hintEl) {
      hintEl.textContent = copy.hint;
    }

    if (surfaceEl) {
      surfaceEl.setAttribute('aria-busy', state === 'refreshing' ? 'true' : 'false');
    }
  }

  function setState(handler, nextState) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    handler.state = nextState;
    handler.ptrElement.classList.toggle(handler.classPrefix + 'pull', nextState === 'pulling');
    handler.ptrElement.classList.toggle(handler.classPrefix + 'release', nextState === 'releasing');
    handler.ptrElement.classList.toggle(handler.classPrefix + 'refresh', nextState === 'refreshing');
    updateUI(handler);
  }

  function setVisualDistance(handler, distance) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    var safeDistance = Math.max(0, Math.round(distance));
    var progress = clamp(safeDistance / handler.distThreshold, 0, 1);

    handler.ptrElement.style[handler.cssProp] = safeDistance + 'px';
    handler.ptrElement.style.maxHeight = safeDistance + 'px';
    handler.ptrElement.style.setProperty('--ptr-progress', progress.toFixed(3));
  }

  function teardownDOM(handler) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    var element = handler.ptrElement;
    handler.ptrElement = null;

    if (element.parentNode) {
      element.parentNode.removeChild(element);
    }
  }

  function resetActiveState() {
    shared.active = null;
  }

  function scheduleTeardown(handler, delay) {
    window.setTimeout(function () {
      if (shared.active && shared.active.handler === handler && handler.state === 'refreshing') {
        return;
      }

      teardownDOM(handler);

      if (!shared.active || shared.active.handler === handler) {
        resetActiveState();
      }
    }, typeof delay === 'number' ? delay : 240);
  }

  function collapse(handler) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    setVisualDistance(handler, 0);
    setState(handler, 'pending');
    scheduleTeardown(handler, 220);
  }

  function finishRefresh(handler) {
    if (!handler) {
      return;
    }

    collapse(handler);
  }

  function beginRefresh(handler) {
    if (!handler || !handler.ptrElement) {
      return;
    }

    setState(handler, 'refreshing');
    setVisualDistance(handler, handler.distReload);

    window.setTimeout(function () {
      var doneCalled = false;

      function done() {
        if (doneCalled) {
          return;
        }

        doneCalled = true;
        finishRefresh(handler);
      }

      var result = handler.onRefresh(done);

      if (result && typeof result.then === 'function') {
        result.then(done, done);
        return;
      }

      if (!result && handler.onRefresh.length === 0) {
        done();
      }
    }, handler.refreshTimeout);
  }

  function setupDOM(handler) {
    if (handler.ptrElement) {
      return handler;
    }

    var container = document.createElement('div');
    var parent = handler.mainElement === document.body ? document.body : handler.mainElement.parentNode;

    if (!parent) {
      return handler;
    }

    container.className = handler.classPrefix + 'ptr';
    container.innerHTML = handler.getMarkup(handler).replace(/__PREFIX__/g, handler.classPrefix);

    if (handler.mainElement !== document.body) {
      parent.insertBefore(container, handler.mainElement);
    } else {
      parent.insertBefore(container, parent.firstChild);
    }

    handler.ptrElement = container;
    ensureStyles(handler);
    handler.onInit(handler);
    updateUI(handler);

    return handler;
  }

  function findHandler(target) {
    var index;

    for (index = 0; index < shared.handlers.length; index += 1) {
      if (shared.handlers[index].contains(target)) {
        return shared.handlers[index];
      }
    }

    return null;
  }

  function createEventBindings() {
    var passiveOptions = shared.supportsPassive ? { passive: shared.passive } : false;

    function onStart(event) {
      if (shouldIgnoreStartEvent(event)) {
        return;
      }

      var handler = findHandler(event.target);

      if (!handler || handler.state === 'refreshing') {
        return;
      }

      setupDOM(handler);

      shared.active = {
        handler: handler,
        startY: handler.shouldPullToRefresh() ? getEventScreenY(event) : null,
        currentY: null,
        dist: 0,
        distResisted: 0
      };

      handler.ptrElement.classList.toggle(handler.classPrefix + 'top', handler.shouldPullToRefresh());
      setState(handler, 'pending');
      setVisualDistance(handler, 0);
    }

    function onMove(event) {
      if (!shared.active || !shared.active.handler) {
        return;
      }

      var active = shared.active;
      var handler = active.handler;

      if (!handler.ptrElement) {
        return;
      }

      if (!active.startY && handler.shouldPullToRefresh()) {
        active.startY = getEventScreenY(event);
      }

      if (!active.startY) {
        return;
      }

      active.currentY = getEventScreenY(event);

      if (active.currentY <= active.startY) {
        return;
      }

      active.dist = active.currentY - active.startY;

      if (handler.state === 'refreshing') {
        if (event.cancelable && handler.shouldPullToRefresh()) {
          event.preventDefault();
        }

        return;
      }

      if (!handler.shouldPullToRefresh()) {
        return;
      }

      var extraDistance = active.dist - handler.distIgnore;

      if (extraDistance <= 0) {
        return;
      }

      active.distResisted = handler.resistanceFunction(extraDistance / handler.distThreshold) * Math.min(handler.distMax, extraDistance);

      if (event.cancelable) {
        event.preventDefault();
      }

      setVisualDistance(handler, active.distResisted);

      if (active.distResisted >= handler.distThreshold) {
        setState(handler, 'releasing');
      } else {
        setState(handler, 'pulling');
      }
    }

    function onEnd() {
      if (!shared.active || !shared.active.handler) {
        return;
      }

      var handler = shared.active.handler;
      var distResisted = shared.active.distResisted || 0;

      if (handler.state === 'releasing' && distResisted >= handler.distThreshold) {
        beginRefresh(handler);
      } else if (handler.state !== 'refreshing') {
        collapse(handler);
      }

      if (!shared.active || shared.active.handler === handler) {
        shared.active = handler.state === 'refreshing' ? { handler: handler } : null;
      }
    }

    function onScroll() {
      var index;

      for (index = 0; index < shared.handlers.length; index += 1) {
        var handler = shared.handlers[index];

        if (handler.ptrElement) {
          handler.ptrElement.classList.toggle(handler.classPrefix + 'top', handler.shouldPullToRefresh());
        }
      }
    }

    if (shared.pointerEventsEnabled && shared.supportsPointerEvents) {
      window.addEventListener('pointerdown', onStart, passiveOptions);
      window.addEventListener('pointermove', onMove, passiveOptions);
      window.addEventListener('pointerup', onEnd, passiveOptions);
      window.addEventListener('pointercancel', onEnd, passiveOptions);
    } else {
      window.addEventListener('touchstart', onStart, passiveOptions);
      window.addEventListener('touchmove', onMove, passiveOptions);
      window.addEventListener('touchend', onEnd, passiveOptions);
      window.addEventListener('touchcancel', onEnd, passiveOptions);
    }

    window.addEventListener('scroll', onScroll, passiveOptions);

    return {
      destroy: function destroy() {
        if (shared.pointerEventsEnabled && shared.supportsPointerEvents) {
          window.removeEventListener('pointerdown', onStart, passiveOptions);
          window.removeEventListener('pointermove', onMove, passiveOptions);
          window.removeEventListener('pointerup', onEnd, passiveOptions);
          window.removeEventListener('pointercancel', onEnd, passiveOptions);
        } else {
          window.removeEventListener('touchstart', onStart, passiveOptions);
          window.removeEventListener('touchmove', onMove, passiveOptions);
          window.removeEventListener('touchend', onEnd, passiveOptions);
          window.removeEventListener('touchcancel', onEnd, passiveOptions);
        }

        window.removeEventListener('scroll', onScroll, passiveOptions);
      }
    };
  }

  function setupHandler(options) {
    var handler = {};
    var key;

    options = options || {};

    for (key in defaults) {
      if (Object.prototype.hasOwnProperty.call(defaults, key)) {
        handler[key] = Object.prototype.hasOwnProperty.call(options, key) ? options[key] : defaults[key];
      }
    }

    handler.classPrefix = normalizePrefix(handler.classPrefix);
    handler.refreshTimeout = typeof options.refreshTimeout === 'number' ? options.refreshTimeout : defaults.refreshTimeout;
    handler.mainElement = resolveElement(handler.mainElement);
    handler.triggerElement = resolveElement(handler.triggerElement);
    handler.ptrElement = resolveElement(handler.ptrElement);
    handler.state = 'pending';

    if (!handler.mainElement || !handler.triggerElement) {
      throw new Error('PullToRefresh could not resolve mainElement or triggerElement.');
    }

    if (!shared.events) {
      shared.events = createEventBindings();
    }

    handler.contains = function contains(target) {
      return !!(handler.triggerElement && target && handler.triggerElement.contains(target));
    };

    handler.destroy = function destroy() {
      teardownDOM(handler);

      var index = shared.handlers.indexOf(handler);

      if (index >= 0) {
        shared.handlers.splice(index, 1);
      }

      if (shared.active && shared.active.handler === handler) {
        resetActiveState();
      }
    };

    return handler;
  }

  var api = {
    setPassiveMode: function setPassiveMode(isPassive) {
      shared.passive = !!isPassive;
    },

    setPointerEventsMode: function setPointerEventsMode(isEnabled) {
      shared.pointerEventsEnabled = !!isEnabled;
    },

    destroyAll: function destroyAll() {
      while (shared.handlers.length) {
        shared.handlers[0].destroy();
      }

      if (shared.events) {
        shared.events.destroy();
        shared.events = null;
      }

      resetActiveState();
    },

    init: function init(options) {
      var handler = setupHandler(options);
      shared.handlers.push(handler);
      return handler;
    },

    defaults: defaults,

    _: {
      setupHandler: setupHandler,
      setupDOM: setupDOM,
      updateUI: updateUI,
      collapse: collapse,
      finishRefresh: finishRefresh
    }
  };

  return api;
}));
