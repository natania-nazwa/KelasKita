const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

function initReveal() {
    const targets = document.querySelectorAll("[data-reveal], [data-reveal-stagger] > *");

    if (!targets.length) {
        return;
    }

    if (reducedMotion || !("IntersectionObserver" in window)) {
        targets.forEach((el) => el.classList.add("is-reveal"));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add("is-reveal");
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.12, rootMargin: "0px 0px -6% 0px" },
    );

    targets.forEach((el) => observer.observe(el));
}

function initScrollProgress() {
    const bar = document.querySelector("[data-scroll-progress]");

    if (!bar || reducedMotion) {
        return;
    }

    let ticking = false;

    const update = () => {
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = scrollable > 0 ? window.scrollY / scrollable : 0;

        bar.style.transform = `scaleX(${Math.min(Math.max(ratio, 0), 1)})`;
        ticking = false;
    };

    window.addEventListener(
        "scroll",
        () => {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(update);
        },
        { passive: true },
    );

    window.addEventListener("resize", update, { passive: true });

    update();
}

function initNavSpy() {
    const links = Array.prototype.slice.call(document.querySelectorAll(".nav-link[href^='#']"));

    if (!links.length) {
        return;
    }

    const sections = links
        .map((link) => document.querySelector(link.getAttribute("href")))
        .filter(Boolean);

    if (!sections.length) {
        return;
    }

    const activate = (id) => {
        links.forEach((link) => {
            link.classList.toggle("active", link.getAttribute("href") === `#${id}`);
        });
    };

    const current = () => {
        const offset = window.innerHeight * 0.35;

        return sections.reduce((acc, section) => (section.getBoundingClientRect().top <= offset ? section.id : acc), "");
    };

    let ticking = false;

    window.addEventListener(
        "scroll",
        () => {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(() => {
                activate(current());
                ticking = false;
            });
        },
        { passive: true },
    );

    activate(current());
}

initReveal();
initScrollProgress();
initNavSpy();
