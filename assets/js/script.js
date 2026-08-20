/* =========================================
   Niagora — Global Scripts
   Theme toggle, 3D tilt, orbs, scroll reveal,
   custom cursor, magnetic buttons
========================================= */

(function () {
    "use strict";

    /* ---------- THEME (siang / malam) ---------- */
    const THEME_KEY = "niagora-theme";

    function applyTheme(theme) {
        document.documentElement.setAttribute("data-theme", theme);
        try { localStorage.setItem(THEME_KEY, theme); } catch (e) { }
        document.querySelectorAll(".theme-toggle").forEach(function (btn) {
            btn.textContent = theme === "dark" ? "☀️" : "🌙";
            btn.setAttribute("aria-label", theme === "dark" ? "Mode siang" : "Mode malam");
        });
    }

    function initTheme() {
        let saved = null;
        try { saved = localStorage.getItem(THEME_KEY); } catch (e) { }
        const prefersDark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
        applyTheme(saved || (prefersDark ? "dark" : "light"));
    }

    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".theme-toggle");
        if (!btn) return;
        const current = document.documentElement.getAttribute("data-theme") || "light";
        applyTheme(current === "dark" ? "light" : "dark");
    });

    /* ---------- 3D TILT with depth layers ---------- */
    function initTilt() {
        document.querySelectorAll(".tilt-3d").forEach(function (card) {
            card.addEventListener("mousemove", function (e) {
                const rect = card.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width - 0.5;
                const y = (e.clientY - rect.top) / rect.height - 0.5;
                const rotateY = x * 12;
                const rotateX = -y * 12;
                card.style.transform =
                    "perspective(900px) rotateY(" + rotateY + "deg) rotateX(" + rotateX + "deg) translateY(-6px)";

                /* Move inner elements with translateZ for layered 3D */
                card.querySelectorAll(".product-image, .category-icon, .hero-product-image, .floating-icon").forEach(function (inner) {
                    inner.style.transform = "translateZ(40px)";
                });
            });
            card.addEventListener("mouseleave", function () {
                card.style.transform = "";
                card.querySelectorAll(".product-image, .category-icon, .hero-product-image, .floating-icon").forEach(function (inner) {
                    inner.style.transform = "";
                });
            });
        });
    }

    /* ---------- FLOATING ORBS ---------- */
    function initOrbs() {
        if (document.querySelector(".orb")) return;
        const body = document.body;
        const orbs = [
            { cls: "orb orb-1" },
            { cls: "orb orb-2" },
            { cls: "orb orb-3" }
        ];
        orbs.forEach(function (o) {
            const div = document.createElement("div");
            div.className = o.cls;
            body.appendChild(div);
        });
    }

    /* ---------- SCROLL REVEAL ---------- */
    function initReveal() {
        const els = document.querySelectorAll(".reveal");
        if (!els.length) return;
        if (!("IntersectionObserver" in window)) {
            els.forEach(function (el) { el.classList.add("visible"); });
            return;
        }
        const io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("visible");
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        els.forEach(function (el) { io.observe(el); });
    }

    /* ---------- COUNT-UP STATS ---------- */
    function initCountUp() {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                if (el.dataset.counted === "true") return;
                el.dataset.counted = "true";

                const target = parseFloat(el.getAttribute("data-count")) || 0;
                const suffix = el.getAttribute("data-suffix") || "";
                const duration = 1500;
                const start = performance.now();
                function tick(now) {
                    const p = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = Math.round(target * eased).toLocaleString("id-ID") + suffix;
                    if (p < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            });
        }, { threshold: 0.5 });

        document.querySelectorAll("[data-count]").forEach(function (el) {
            observer.observe(el);
        });
    }

    /* ---------- REVEAL STAGGER ---------- */
    function initRevealStagger() {
        document.querySelectorAll(".category-grid, .product-grid").forEach(function (group) {
            Array.prototype.forEach.call(group.children, function (child, i) {
                if (child.classList && child.classList.contains("reveal")) {
                    child.style.setProperty("--i", i);
                }
            });
        });
    }

    /* ---------- NAVBAR SCROLL STATE ---------- */
    function initNavScroll() {
        const nav = document.querySelector(".navbar");
        if (!nav) return;
        function update() {
            nav.classList.toggle("is-scrolled", window.scrollY > 8);
        }
        update();
        window.addEventListener("scroll", update, { passive: true });
    }

    /* ---------- HERO CURSOR SPOTLIGHT ---------- */
    function initSpotlight() {
        const hero = document.querySelector(".hero");
        if (!hero) return;
        hero.addEventListener("mousemove", function (e) {
            const rect = hero.getBoundingClientRect();
            hero.style.setProperty("--mx", (e.clientX - rect.left) + "px");
            hero.style.setProperty("--my", (e.clientY - rect.top) + "px");
        });
    }

    /* ---------- HERO VISUAL PARALLAX ---------- */
    function initParallax() {
        const stage = document.querySelector(".hero-visual");
        if (!stage) return;
        const layers = stage.querySelectorAll("[data-depth]");
        if (!layers.length) return;
        stage.addEventListener("mousemove", function (e) {
            const rect = stage.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            layers.forEach(function (layer) {
                const depth = parseFloat(layer.getAttribute("data-depth")) || 0;
                layer.style.transform = "translate3d(" + (x * depth) + "px," + (y * depth) + "px,0)";
            });
        });
        stage.addEventListener("mouseleave", function () {
            layers.forEach(function (layer) { layer.style.transform = ""; });
        });
    }

    /* ---------- RIPPLE ON CLICK ---------- */
    function initRipple() {
        const selector = ".btn-register, .business-button, .add-cart, .hero-search button";
        document.addEventListener("click", function (e) {
            const target = e.target.closest(selector);
            if (!target) return;
            const rect = target.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const span = document.createElement("span");
            span.className = "ripple-fx";
            span.style.width = span.style.height = size + "px";
            span.style.left = (e.clientX - rect.left - size / 2) + "px";
            span.style.top = (e.clientY - rect.top - size / 2) + "px";
            target.appendChild(span);
            span.addEventListener("animationend", function () { span.remove(); });

            if (target.classList.contains("add-cart")) {
                target.classList.remove("just-added");
                void target.offsetWidth;
                target.classList.add("just-added");
            }
        });
    }

    /* ---------- WISHLIST TOGGLE ---------- */
    function initWishlist() {
        document.addEventListener("click", function (e) {
            const btn = e.target.closest(".wishlist");
            if (!btn) return;
            btn.classList.toggle("active");
            btn.textContent = btn.classList.contains("active") ? "♥" : "♡";
        });
    }

    /* ---------- THEME TOGGLE SPIN ---------- */
    function initThemeToggleSpin() {
        document.addEventListener("click", function (e) {
            const btn = e.target.closest(".theme-toggle");
            if (!btn) return;
            btn.classList.remove("spin");
            void btn.offsetWidth;
            btn.classList.add("spin");
        });
    }

    /* ---------- CUSTOM CURSOR ---------- */
    function initCustomCursor() {
        const dot = document.getElementById("cursorDot");
        const ring = document.getElementById("cursorRing");
        if (!dot || !ring) return;

        let mouseX = 0, mouseY = 0;
        let ringX = 0, ringY = 0;

        document.addEventListener("mousemove", function (e) {
            mouseX = e.clientX;
            mouseY = e.clientY;
            dot.style.transform = "translate(" + (mouseX - 4) + "px, " + (mouseY - 4) + "px)";
        });

        function animateRing() {
            ringX += (mouseX - ringX) * 0.15;
            ringY += (mouseY - ringY) * 0.15;
            ring.style.transform = "translate(" + (ringX - 18) + "px, " + (ringY - 18) + "px)";
            requestAnimationFrame(animateRing);
        }
        animateRing();

        /* Grow cursor on hover over interactive elements */
        const interactiveSelectors = "a, button, .tilt-3d, input, .category-card, .product-card";
        document.querySelectorAll(interactiveSelectors).forEach(function (el) {
            el.addEventListener("mouseenter", function () {
                ring.classList.add("grow");
            });
            el.addEventListener("mouseleave", function () {
                ring.classList.remove("grow");
            });
        });
    }

    /* ---------- MAGNETIC BUTTONS ---------- */
    function initMagnetic() {
        document.querySelectorAll(".btn-register, .business-button, .hero-search button").forEach(function (btn) {
            btn.addEventListener("mousemove", function (e) {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                btn.style.transform = "translate(" + (x * 0.2) + "px, " + (y * 0.2) + "px)";
            });
            btn.addEventListener("mouseleave", function () {
                btn.style.transform = "";
            });
        });
    }

    /* ---------- SMOOTH SCROLL FOR ANCHOR LINKS ---------- */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener("click", function (e) {
                const targetId = this.getAttribute("href");
                if (targetId === "#") return;
                const target = document.querySelector(targetId);
                if (!target) return;
                e.preventDefault();
                const offset = 80;
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({
                    top: targetPosition,
                    behavior: "smooth"
                });
            });
        });
    }

    /* ---------- LIVE SEARCH FILTER ---------- */
    function initSearch() {
        const input = document.getElementById("searchProduct");
        const grid = document.getElementById("productGrid");
        if (!input || !grid) return;

        input.addEventListener("input", function () {
            const query = this.value.toLowerCase().trim();
            const cards = grid.querySelectorAll(".product-card");
            let visibleCount = 0;

            cards.forEach(function (card) {
                const name = (card.getAttribute("data-name") || card.querySelector("h3")?.textContent || "").toLowerCase();
                if (query === "" || name.includes(query)) {
                    card.style.display = "";
                    visibleCount++;
                } else {
                    card.style.display = "none";
                }
            });
        });
    }

    /* ---------- SCROLL INDICATOR FADE ---------- */
    function initScrollIndicator() {
        const indicator = document.querySelector(".scroll-indicator");
        if (!indicator) return;
        window.addEventListener("scroll", function () {
            if (window.scrollY > 100) {
                indicator.style.opacity = "0";
            } else {
                indicator.style.opacity = "1";
            }
        }, { passive: true });
    }

    /* ---------- PARALLAX ON SCROLL ---------- */
    function initScrollParallax() {
        const heroBg = document.querySelector(".hero-bg-deco");
        const heroContent = document.querySelector(".hero-content");
        const heroVisual = document.querySelector(".hero-visual");
        if (!heroBg) return;

        window.addEventListener("scroll", function () {
            const scrolled = window.scrollY;
            if (scrolled > 600) return;

            if (heroBg) {
                heroBg.style.transform = "translateY(" + (scrolled * 0.3) + "px)";
            }
            if (heroContent) {
                heroContent.style.transform = "translateY(" + (scrolled * 0.15) + "px)";
                heroContent.style.opacity = Math.max(0, 1 - scrolled / 500);
            }
            if (heroVisual) {
                heroVisual.style.transform = "translateY(" + (scrolled * 0.1) + "px)";
            }
        }, { passive: true });
    }

    /* ---------- INIT ---------- */
    document.addEventListener("DOMContentLoaded", function () {
        initTheme();
        initTilt();
        initOrbs();
        initRevealStagger();
        initReveal();
        initCountUp();
        initNavScroll();
        initSpotlight();
        initParallax();
        initRipple();
        initWishlist();
        initThemeToggleSpin();
        initCustomCursor();
        initMagnetic();
        initSmoothScroll();
        initSearch();
        initScrollIndicator();
        initScrollParallax();
    });
})();