/* NexusBuilder frontend animations — loaded only if page has animations */
(function () {
  'use strict';

  if (typeof gsap === 'undefined') return;

  // Register ScrollTrigger plugin
  gsap.registerPlugin(ScrollTrigger);

  // Parse animation data attributes from DOM
  document.querySelectorAll('[data-nexus-animation]').forEach(el => {
    let config;
    try {
      config = JSON.parse(el.dataset.nexusAnimation);
    } catch (e) {
      return;
    }

    buildAnimation(el, config);
  });

  function buildAnimation(el, config) {
    const {
      type     = 'fade-in',
      trigger  = 'viewport',
      duration = 0.8,
      delay    = 0,
      ease     = 'power2.out',
      start    = 'top 85%',
      stagger  = 0,
      repeat   = 0,
      yoyo     = false,
    } = config;

    const from = getFromVars(type);
    const to   = { opacity: 1, x: 0, y: 0, scale: 1, rotation: 0, filter: 'blur(0px)', duration, delay, ease, repeat, yoyo };

    if (trigger === 'viewport') {
      gsap.fromTo(el, from, {
        ...to,
        scrollTrigger: {
          trigger: el,
          start,
          toggleActions: 'play none none none',
          once: true,
        },
        ...(stagger > 0 && el.children.length > 0
          ? { stagger }
          : {})
      });
    } else if (trigger === 'load') {
      gsap.fromTo(el, from, to);
    } else if (trigger === 'scroll-position') {
      ScrollTrigger.create({
        trigger: el,
        start: config.scrollStart || 'top center',
        onEnter: () => gsap.fromTo(el, from, to),
      });
    }
  }

  function getFromVars(type) {
    const map = {
      'fade-in':       { opacity: 0 },
      'fade-in-up':    { opacity: 0, y: 40 },
      'fade-in-down':  { opacity: 0, y: -40 },
      'fade-in-left':  { opacity: 0, x: -50 },
      'fade-in-right': { opacity: 0, x: 50 },
      'zoom-in':       { opacity: 0, scale: 0.8 },
      'zoom-out':      { opacity: 0, scale: 1.2 },
      'flip-x':        { opacity: 0, rotationX: 90 },
      'flip-y':        { opacity: 0, rotationY: 90 },
      'blur-in':       { opacity: 0, filter: 'blur(12px)' },
      'slide-in-up':   { y: 60 },
      'bounce-in':     { opacity: 0, y: 30, ease: 'bounce.out' },
      'rotate-in':     { opacity: 0, rotation: -15, transformOrigin: 'left bottom' },
    };
    return map[type] || { opacity: 0 };
  }

  // ── Parallax layers ─────────────────────────────────────────────────
  document.querySelectorAll('[data-nexus-parallax]').forEach(el => {
    const config = JSON.parse(el.dataset.nexusParallax || '{}');
    const speed  = parseFloat(config.speed ?? 0.5);

    gsap.to(el, {
      yPercent: -100 * speed,
      ease: 'none',
      scrollTrigger: {
        trigger: el.parentElement,
        start: 'top bottom',
        end: 'bottom top',
        scrub: true,
      },
    });
  });

  // ── Mouse parallax ──────────────────────────────────────────────────
  document.querySelectorAll('[data-nexus-mouse-parallax]').forEach(section => {
    const layers = section.querySelectorAll('[data-parallax-depth]');

    section.addEventListener('mousemove', e => {
      const rect   = section.getBoundingClientRect();
      const xRatio = (e.clientX - rect.left - rect.width / 2)  / (rect.width / 2);
      const yRatio = (e.clientY - rect.top  - rect.height / 2) / (rect.height / 2);

      layers.forEach(layer => {
        const depth = parseFloat(layer.dataset.parallaxDepth || '0.1');
        gsap.to(layer, {
          x: xRatio * depth * 40,
          y: yRatio * depth * 40,
          duration: 0.6,
          ease: 'power2.out',
        });
      });
    });

    section.addEventListener('mouseleave', () => {
      layers.forEach(layer => {
        gsap.to(layer, { x: 0, y: 0, duration: 0.8, ease: 'power2.out' });
      });
    });
  });

  // ── Magnetic buttons ────────────────────────────────────────────────
  document.querySelectorAll('[data-nexus-magnetic]').forEach(btn => {
    btn.addEventListener('mousemove', e => {
      const rect     = btn.getBoundingClientRect();
      const centerX  = rect.left + rect.width  / 2;
      const centerY  = rect.top  + rect.height / 2;
      const strength = parseFloat(btn.dataset.magneticStrength || '0.4');

      gsap.to(btn, {
        x: (e.clientX - centerX) * strength,
        y: (e.clientY - centerY) * strength,
        duration: 0.3,
        ease: 'power2.out',
      });
    });

    btn.addEventListener('mouseleave', () => {
      gsap.to(btn, { x: 0, y: 0, duration: 0.5, ease: 'elastic.out(1,0.5)' });
    });
  });

  // ── Cursor trail ────────────────────────────────────────────────────
  const cursorConfig = window.NexusCursorConfig;
  if (cursorConfig?.type === 'trail') {
    initCursorTrail(cursorConfig);
  }

  function initCursorTrail({ color = '#7F77DD', size = 8, count = 10 }) {
    const dots = Array.from({ length: count }, () => {
      const d = document.createElement('div');
      d.style.cssText = `
        position:fixed; pointer-events:none; border-radius:50%;
        width:${size}px; height:${size}px;
        background:${color}; opacity:0.6; z-index:99999;
        transform:translate(-50%,-50%);
      `;
      document.body.appendChild(d);
      return d;
    });

    let mouseX = 0, mouseY = 0;
    const positions = dots.map(() => ({ x: 0, y: 0 }));

    document.addEventListener('mousemove', e => {
      mouseX = e.clientX;
      mouseY = e.clientY;
    });

    function animate() {
      positions[0].x += (mouseX - positions[0].x) * 0.3;
      positions[0].y += (mouseY - positions[0].y) * 0.3;

      for (let i = 1; i < dots.length; i++) {
        positions[i].x += (positions[i-1].x - positions[i].x) * 0.6;
        positions[i].y += (positions[i-1].y - positions[i].y) * 0.6;
      }

      dots.forEach((dot, i) => {
        const scale = (dots.length - i) / dots.length;
        dot.style.transform = `translate(${positions[i].x - size/2}px, ${positions[i].y - size/2}px) scale(${scale})`;
        dot.style.opacity   = String(scale * 0.6);
      });

      requestAnimationFrame(animate);
    }
    animate();
  }

})();
