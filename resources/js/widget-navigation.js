function getAllWidgets() {
    const sections = document.querySelectorAll("section[aria-labelledby]");
    if (sections.length > 0) {
        return Array.from(sections);
    }

    return Array.from(
        document.querySelectorAll(".widget, [id$='-widget'], h3[id^='-widget']")
    );
}

function getCurrentWidgetIndex(widgets) {
    let currentIndex = -1;

    widgets.forEach((widget, index) => {
        if (
            widget === document.activeElement ||
            widget.contains(document.activeElement)
        ) {
            currentIndex = index;
        }
    });

    return currentIndex;
}

function focusWidget(widget) {
    if (widget.tagName === "H3") {
        widget.focus();
    } else {
        const heading = widget.querySelector("h3, [id$='-title']");
        if (heading) {
            heading.focus();
        } else {
            const focusable = widget.querySelector(
                "button, [tabindex='0'], a, input, select, textarea"
            );
            if (focusable) {
                focusable.focus();
            } else {
                widget.setAttribute("tabindex", "-1");
                widget.focus();
            }
        }
    }
}

function skipToNextWidget() {
    const widgets = getAllWidgets();
    const currentIndex = getCurrentWidgetIndex(widgets);

    if (currentIndex >= 0 && currentIndex < widgets.length - 1) {
        const nextWidget = widgets[currentIndex + 1];
        focusWidget(nextWidget);
    }
}

function skipToPreviousWidget() {
    const widgets = getAllWidgets();
    const currentIndex = getCurrentWidgetIndex(widgets);

    if (currentIndex > 0) {
        const prevWidget = widgets[currentIndex - 1];
        focusWidget(prevWidget);
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const prevButtons = document.querySelectorAll(".skip-button-prev");
    const nextButtons = document.querySelectorAll(".skip-button-next");

    function setupButton(button, handler) {
        if (!button) return;

        button.addEventListener("click", handler);

        button.addEventListener("keydown", function (event) {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                handler();
            }
        });
    }

    prevButtons.forEach((btn) => setupButton(btn, skipToPreviousWidget));
    nextButtons.forEach((btn) => setupButton(btn, skipToNextWidget));
});
