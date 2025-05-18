document.addEventListener("DOMContentLoaded", function () {
    // Delay scroll restoration to ensure rendering is done
    setTimeout(restoreScrollPosition, 100);
    restoreFocus();
    setupSavePositionListeners();
});

function setupSavePositionListeners() {
    // Use pagehide in addition to beforeunload for better coverage
    window.addEventListener("beforeunload", savePositions);
    window.addEventListener("pagehide", savePositions);

    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", savePositions);
    });

    document.querySelectorAll("a").forEach((link) => {
        if (
            link.getAttribute("href") &&
            !link.getAttribute("href").startsWith("#") &&
            !link.getAttribute("href").startsWith("javascript:") &&
            !link.getAttribute("target")
        ) {
            link.addEventListener("click", savePositions);
        }
    });
}

function savePositions() {
    sessionStorage.setItem("scrollX", window.scrollX);
    sessionStorage.setItem("scrollY", window.scrollY);

    const activeElement = document.activeElement;
    if (activeElement && activeElement !== document.body) {
        const selector = createUniqueSelector(activeElement);
        if (selector) {
            sessionStorage.setItem("focusSelector", selector);

            if (["INPUT", "TEXTAREA"].includes(activeElement.tagName)) {
                sessionStorage.setItem(
                    "selectionStart",
                    activeElement.selectionStart
                );
                sessionStorage.setItem(
                    "selectionEnd",
                    activeElement.selectionEnd
                );
            }
        }
    }
}

function restoreScrollPosition() {
    const scrollX = sessionStorage.getItem("scrollX");
    const scrollY = sessionStorage.getItem("scrollY");

    if (scrollX !== null && scrollY !== null) {
        window.scrollTo(parseInt(scrollX), parseInt(scrollY));
        sessionStorage.removeItem("scrollX");
        sessionStorage.removeItem("scrollY");
    }
}

function restoreFocus() {
    const focusSelector = sessionStorage.getItem("focusSelector");

    if (focusSelector) {
        try {
            const elementToFocus = document.querySelector(focusSelector);

            if (elementToFocus) {
                setTimeout(() => {
                    // If element is not naturally focusable, make it temporarily focusable
                    if (!isFocusable(elementToFocus)) {
                        elementToFocus.setAttribute("tabindex", "-1");
                    }

                    elementToFocus.focus();

                    // Restore cursor position if input or textarea (your existing code)
                    if (
                        ["INPUT", "TEXTAREA"].includes(elementToFocus.tagName)
                    ) {
                        const selectionStart =
                            sessionStorage.getItem("selectionStart");
                        const selectionEnd =
                            sessionStorage.getItem("selectionEnd");
                        if (selectionStart !== null && selectionEnd !== null) {
                            elementToFocus.setSelectionRange(
                                parseInt(selectionStart),
                                parseInt(selectionEnd)
                            );
                        }
                    }

                    elementToFocus.scrollIntoView({
                        behavior: "auto",
                        block: "nearest",
                    });

                    // Optional: remove tabindex after focusing if you want to keep DOM clean
                    if (elementToFocus.getAttribute("tabindex") === "-1") {
                        elementToFocus.removeAttribute("tabindex");
                    }
                }, 100);
            }
        } catch (error) {
            console.error("Error restoring focus:", error);
        }

        // Clear sessionStorage keys
        sessionStorage.removeItem("focusSelector");
        sessionStorage.removeItem("selectionStart");
        sessionStorage.removeItem("selectionEnd");
    }
}

function isFocusable(element) {
    const focusableTags = ["INPUT", "TEXTAREA", "SELECT", "BUTTON", "A"];
    const tagName = element.tagName;

    if (focusableTags.includes(tagName)) {
        if (tagName === "A") {
            // Only focusable if it has href
            return (
                element.hasAttribute("href") &&
                element.getAttribute("href").trim() !== ""
            );
        }
        return true;
    }

    // Check if element has tabindex >= 0
    const tabindex = element.getAttribute("tabindex");
    return tabindex !== null && parseInt(tabindex) >= 0;
}

function createUniqueSelector(element) {
    if (
        !element ||
        element === document ||
        element === document.documentElement
    ) {
        return null;
    }
    if (element.id) {
        return "#" + element.id;
    }

    let selector = element.tagName.toLowerCase();

    if (element.name) {
        selector += `[name="${element.name}"]`;
    }

    if (element.className && typeof element.className === "string") {
        const classes = element.className.trim().split(/\s+/);
        for (const cls of classes) {
            if (cls) {
                selector += "." + cls;
            }
        }
    }

    if (element.parentNode) {
        const siblings = Array.from(element.parentNode.children).filter(
            (child) => child.tagName === element.tagName
        );
        if (siblings.length > 1) {
            const index = siblings.indexOf(element);
            selector += `:nth-of-type(${index + 1})`;
        }
    }

    return selector;
}
