
/* =========================================================
   KHAN SOLUTIONS — PORTFOLIO V2
   FINAL FRONTEND CONTROLLER
   ========================================================= */

"use strict";


/* ==========================================================
   01. DOM READY
   ========================================================== */

document.addEventListener("DOMContentLoaded", () => {

    initMobileNavigation();
    initActiveNavigation();
    initSmoothScrolling();
    initLightbox();
    initServiceSelection();
    initServiceRequestForm();
    initContactForm();
    initScrollReveal();

});


/* =========================================================
   02. MOBILE NAVIGATION
   ========================================================= */

function initMobileNavigation() {

    const menuToggle =
        document.getElementById("menu-toggle");

    const navLinks =
        document.getElementById("nav-links");


    if (!menuToggle || !navLinks) {
        return;
    }


    const navItems =
        navLinks.querySelectorAll("a");


    function closeMenu() {

        navLinks.classList.remove("active");

        menuToggle.setAttribute(
            "aria-expanded",
            "false"
        );


        const icon =
            menuToggle.querySelector("i");


        if (icon) {

            icon.className =
                "fas fa-bars";

        }

    }


    function openMenu() {

        navLinks.classList.add("active");

        menuToggle.setAttribute(
            "aria-expanded",
            "true"
        );


        const icon =
            menuToggle.querySelector("i");


        if (icon) {

            icon.className =
                "fas fa-times";

        }

    }


    menuToggle.addEventListener(
        "click",
        () => {

            const isOpen =
                navLinks.classList.contains("active");


            if (isOpen) {

                closeMenu();

            } else {

                openMenu();

            }

        }
    );


    navItems.forEach(link => {

        link.addEventListener(
            "click",
            () => {

                closeMenu();

            }
        );

    });


    document.addEventListener(
        "click",
        event => {

            const clickedInsideMenu =
                navLinks.contains(event.target);

            const clickedToggle =
                menuToggle.contains(event.target);


            if (
                !clickedInsideMenu &&
                !clickedToggle &&
                navLinks.classList.contains("active")
            ) {

                closeMenu();

            }

        }
    );


    document.addEventListener(
        "keydown",
        event => {

            if (event.key === "Escape") {

                closeMenu();

            }

        }
    );

}


/* =========================================================
   03. ACTIVE NAVIGATION
   ========================================================= */

function initActiveNavigation() {

    const sections =
        document.querySelectorAll(
            "section[id]"
        );


    const navLinks =
        document.querySelectorAll(
            ".nav-links a[href^='#']"
        );


    if (
        !sections.length ||
        !navLinks.length
    ) {

        return;

    }


    /*
     * Modern IntersectionObserver method.
     */

    if ("IntersectionObserver" in window) {

        const observer =
            new IntersectionObserver(
                entries => {

                    entries.forEach(
                        entry => {

                            if (
                                !entry.isIntersecting
                            ) {

                                return;

                            }


                            const id =
                                entry.target
                                    .getAttribute(
                                        "id"
                                    );


                            navLinks.forEach(
                                link => {

                                    link.classList
                                        .remove(
                                            "active"
                                        );


                                    if (
                                        link.getAttribute(
                                            "href"
                                        ) ===
                                        `#${id}`
                                    ) {

                                        link.classList
                                            .add(
                                                "active"
                                            );

                                    }

                                }
                            );

                        }
                    );

                },
                {
                    rootMargin:
                        "-35% 0px -55% 0px"
                }
            );


        sections.forEach(
            section => {

                observer.observe(
                    section
                );

            }
        );

    }


    /*
     * Fallback scroll method.
     * Keeps compatibility with the original portfolio.
     */

    window.addEventListener(
        "scroll",
        () => {

            let current = "";


            sections.forEach(
                section => {

                    const top =
                        section.offsetTop;

                    const height =
                        section.clientHeight;


                    if (
                        window.pageYOffset >=
                        top - 200
                    ) {

                        current =
                            section.getAttribute(
                                "id"
                            );

                    }

                }
            );


            navLinks.forEach(
                link => {

                    link.classList.remove(
                        "active"
                    );


                    if (
                        link.getAttribute(
                            "href"
                        ) ===
                        "#" + current
                    ) {

                        link.classList.add(
                            "active"
                        );

                    }

                }
            );

        }
    );

}


/* =========================================================
   04. SMOOTH SCROLLING
   ========================================================= */

function initSmoothScrolling() {

    const links =
        document.querySelectorAll(
            'a[href^="#"]'
        );


    links.forEach(
        link => {

            link.addEventListener(
                "click",
                event => {

                    const targetId =
                        link.getAttribute(
                            "href"
                        );


                    if (
                        !targetId ||
                        targetId === "#" ||
                        targetId.length <= 1
                    ) {

                        return;

                    }


                    const target =
                        document.querySelector(
                            targetId
                        );


                    if (!target) {

                        return;

                    }


                    event.preventDefault();


                    target.scrollIntoView({
                        behavior:
                            "smooth",
                        block:
                            "start"
                    });

                }
            );

        }
    );

}


/* =========================================================
   05. GALLERY LIGHTBOX
   ========================================================= */

function initLightbox() {

    const galleryImages =
        document.querySelectorAll(
            ".gallery-container img"
        );


    const lightbox =
        document.getElementById(
            "lightbox"
        );


    const lightboxImage =
        document.getElementById(
            "lightbox-img"
        );


    const closeButton =
        document.getElementById(
            "close-btn"
        );


    if (
        !galleryImages.length ||
        !lightbox ||
        !lightboxImage ||
        !closeButton
    ) {

        return;

    }


    function openLightbox(image) {

        lightboxImage.src =
            image.src;


        lightboxImage.alt =
            image.alt ||
            "Project preview";


        /*
         * V2 CSS uses .active.
         */

        lightbox.classList.add(
            "active"
        );


        /*
         * Compatibility with old CSS.
         */

        lightbox.style.display =
            "flex";


        document.body.style.overflow =
            "hidden";


        closeButton.focus();

    }


    function closeLightbox() {

        lightbox.classList.remove(
            "active"
        );


        lightbox.style.display =
            "none";


        document.body.style.overflow =
            "";

    }


    galleryImages.forEach(
        image => {

            image.addEventListener(
                "click",
                () => {

                    openLightbox(
                        image
                    );

                }
            );

        }
    );


    closeButton.addEventListener(
        "click",
        closeLightbox
    );


    lightbox.addEventListener(
        "click",
        event => {

            if (
                event.target ===
                lightbox
            ) {

                closeLightbox();

            }

        }
    );


    document.addEventListener(
        "keydown",
        event => {

            if (
                event.key === "Escape" &&
                lightbox.classList.contains(
                    "active"
                )
            ) {

                closeLightbox();

            }

        }
    );

}


/* =========================================================
   06. SERVICE SELECTION
   ========================================================= */

function initServiceSelection() {

    const serviceLinks =
        document.querySelectorAll(
            ".service-link[data-service]"
        );


    const serviceSelect =
        document.getElementById(
            "service-type"
        );


    if (
        !serviceSelect
    ) {

        return;

    }


    serviceLinks.forEach(
        link => {

            link.addEventListener(
                "click",
                () => {

                    const selectedService =
                        link.dataset.service;


                    if (!selectedService) {

                        return;

                    }


                    const option =
                        [...serviceSelect.options]
                            .find(
                                item =>
                                    item.value ===
                                    selectedService
                            );


                    if (option) {

                        serviceSelect.value =
                            selectedService;


                        serviceSelect.dispatchEvent(
                            new Event(
                                "change"
                            )
                        );

                    }

                }
            );

        }
    );


    serviceSelect.addEventListener(
        "change",
        () => {

            updateServiceFields(
                serviceSelect.value
            );

        }
    );


    /*
     * Run once if a service is already selected.
     */

    if (serviceSelect.value) {

        updateServiceFields(
            serviceSelect.value
        );

    }

}


/* =========================================================
   07. SERVICE-SPECIFIC UI
   ========================================================= */

function updateServiceFields(service) {

    const titleInput =
        document.getElementById(
            "request-title"
        );


    const messageInput =
        document.getElementById(
            "request-message"
        );


    if (
        !titleInput ||
        !messageInput
    ) {

        return;

    }


    const placeholders = {

        "Electrical": {

            title:
                "Example: House wiring / Electrical fault",

            message:
                "Describe the electrical installation, fault or service you need..."

        },


        "Electronics": {

            title:
                "Example: TV repair / PCB fault",

            message:
                "Describe the electronic device, fault or repair required..."

        },


        "Embedded Systems": {

            title:
                "Example: ESP32 IoT project",

            message:
                "Describe your embedded, microcontroller, sensor, automation or IoT project..."

        },


        "Software": {

            title:
                "Example: Student Management System",

            message:
                "Describe the website, web system, software, database or API you need..."

        }

    };


    const data =
        placeholders[service];


    if (!data) {

        titleInput.placeholder =
            "Example: House wiring / ESP32 project / Website";


        messageInput.placeholder =
            "Explain the problem, project or service you need...";


        return;

    }


    titleInput.placeholder =
        data.title;


    messageInput.placeholder =
        data.message;

}


/* =========================================================
   08. SERVICE REQUEST FORM
   ========================================================= */

function initServiceRequestForm() {

    const form =
        document.getElementById(
            "service-request-form"
        );


    const status =
        document.getElementById(
            "request-status"
        );


    if (!form) {

        return;

    }


    form.addEventListener(
        "submit",
        event => {

            event.preventDefault();


            if (!form.checkValidity()) {

                form.reportValidity();

                return;

            }


            const service =
                document.getElementById(
                    "service-type"
                )?.value;


            const name =
                document.getElementById(
                    "request-name"
                )?.value.trim();


            if (
                !service ||
                !name
            ) {

                showRequestStatus(
                    status,
                    "Please select a service and enter your name.",
                    "error"
                );

                return;

            }


            /*
             * Frontend validation only.
             *
             * Real database/API submission
             * will be added in the backend phase.
             */

            showRequestStatus(
                status,
                "Your service request has been prepared successfully.",
                "success"
            );

        }
    );

}


/* =========================================================
   09. REQUEST STATUS
   ========================================================= */

function showRequestStatus(
    element,
    message,
    type
) {

    if (!element) {

        return;

    }


    element.textContent =
        message;


    element.style.color =
        type === "error"
            ? "#ff5c70"
            : "#20d98b";


    setTimeout(
        () => {

            if (
                element.textContent ===
                message
            ) {

                element.textContent =
                    "";

            }

        },
        7000
    );

}


/* =========================================================
   10. CONTACT FORM + EMAILJS
   ========================================================= */

function initContactForm() {

    const form =
        document.getElementById(
            "contact-form"
        );


    const statusMessage =
        document.getElementById(
            "status-message"
        );


    if (!form) {

        return;

    }


    /*
     * EmailJS must be loaded in index.html.
     */

    if (
        typeof emailjs ===
        "undefined"
    ) {

        console.error(
            "EmailJS library is not loaded."
        );

        return;

    }


    /*
     * YOUR ORIGINAL EMAILJS CONFIGURATION
     * PRESERVED FROM OLD SCRIPT.
     */

    emailjs.init({
        publicKey:
            "o5KssM6CBtauGpN3K",

        blockHeadless:
            false
    });


    form.addEventListener(
        "submit",
        async function(e) {

            e.preventDefault();


            /*
             * Honeypot anti-spam field.
             */

            const company =
                this.querySelector(
                    '[name="company"]'
                );


            if (
                company &&
                company.value !== ""
            ) {

                return;

            }


            const btn =
                this.querySelector(
                    "button"
                );


            const originalButtonText =
                btn
                    ? btn.innerText
                    : "Send Message";


            if (btn) {

                btn.disabled =
                    true;


                btn.innerText =
                    "Sending...";

            }


            if (statusMessage) {

                statusMessage.innerHTML =
                    "Sending your message...";


                statusMessage.style.color =
                    "#aeb9c8";

            }


            try {

                await emailjs.sendForm(

                    "service_jngw8ge",

                    "template_hwtl8le",

                    this

                );


                if (statusMessage) {

                    statusMessage.innerHTML =
                        "✅ Thank you! Your message has been sent.";


                    statusMessage.style.color =
                        "#20d98b";

                }


                this.reset();


            } catch (error) {

                console.error(
                    "FULL ERROR:",
                    error
                );


                if (statusMessage) {

                    statusMessage.innerHTML =
                        "❌ Message could not be sent. Please try again.";

                    statusMessage.style.color =
                        "#ff5c70";

                }


                alert(
                    "Status: " +
                    (error.status || "Unknown") +
                    "\nText: " +
                    (error.text || "Unknown error")
                );


            } finally {

                if (btn) {

                    btn.disabled =
                        false;


                    btn.innerText =
                        originalButtonText;

                }

            }

        }
    );

}


/* =========================================================
   11. SCROLL REVEAL
   ========================================================= */

function initScrollReveal() {

    if (
        typeof ScrollReveal ===
        "undefined"
    ) {

        console.warn(
            "ScrollReveal library is not loaded."
        );

        return;

    }


    const prefersReducedMotion =
        window.matchMedia(
            "(prefers-reduced-motion: reduce)"
        ).matches;


    if (prefersReducedMotion) {

        return;

    }


    const reveal =
        ScrollReveal({

            distance:
                "35px",

            duration:
                850,

            easing:
                "cubic-bezier(.2,.8,.2,1)",

            interval:
                90,

            reset:
                false,

            mobile:
                true

        });


    /*
     * Original animations.
     */

    reveal.reveal(
        ".service-card",
        {

            delay:
                200,

            distance:
                "50px",

            origin:
                "bottom",

            interval:
                100

        }
    );


    reveal.reveal(
        ".project-card",
        {

            delay:
                300,

            distance:
                "50px",

            origin:
                "bottom",

            interval:
                100

        }
    );


    /*
     * V2 animations.
     */

    reveal.reveal(
        ".section-heading",
        {

            origin:
                "bottom"

        }
    );


    reveal.reveal(
        ".about-image",
        {

            origin:
                "left",

            delay:
                100

        }
    );


    reveal.reveal(
        ".about-content",
        {

            origin:
                "right",

            delay:
                150

        }
    );


    reveal.reveal(
        ".gallery-container img",
        {

            origin:
                "bottom",

            interval:
                70

        }
    );


    reveal.reveal(
        ".request-intro",
        {

            origin:
                "left"

        }
    );


    reveal.reveal(
        ".request-card",
        {

            origin:
                "right",

            delay:
                100

        }
    );


    reveal.reveal(
        ".contact-info",
        {

            origin:
                "left"

        }
    );


    reveal.reveal(
        ".contact-form",
        {

            origin:
                "right",

            delay:
                100

        }
    );

}


/* =========================================================
   12. LOADING SCREEN
   ========================================================= */

function initLoader() {

    const loader =
        document.getElementById(
            "loader"
        );


    if (!loader) {

        return;

    }


    window.addEventListener(
        "load",
        () => {

            setTimeout(
                () => {

                    loader.style.opacity =
                        "0";

                    loader.style.visibility =
                        "hidden";


                    setTimeout(
                        () => {

                            if (
                                loader.parentNode
                            ) {

                                loader.remove();

                            }

                        },
                        550
                    );

                },
                350
            );

        }
    );

}


/* =========================================================
   13. SERVICE CARD KEYBOARD ACCESSIBILITY
   ========================================================= */

document.addEventListener(
    "keydown",
    event => {

        if (
            event.key !== "Enter" &&
            event.key !== " "
        ) {

            return;

        }


        const target =
            event.target.closest(
                ".service-link"
            );


        if (!target) {

            return;

        }


        event.preventDefault();

        target.click();

    }
);


/* =========================================================
   14. GLOBAL ERROR SAFETY
   ========================================================= */

window.addEventListener(
    "error",
    event => {

        console.warn(
            "Portfolio runtime warning:",
            event.message
        );

    }
);