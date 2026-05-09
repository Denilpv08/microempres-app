document.addEventListener("DOMContentLoaded", () => {
  const loader = document.getElementById("page-loader");
  const flash = document.getElementById("page-flash");

  const showLoader = () => {
    if (!loader) {
      return;
    }

    loader.hidden = false;
    loader.classList.add("is-visible");
  };

  if (flash && flash.dataset.message) {
    flash.classList.add("is-visible");
    window.setTimeout(() => {
      flash.classList.remove("is-visible");
    }, 3200);
  }

  document.querySelectorAll("form[data-confirm]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      const message = form.dataset.confirm || "";
      if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
      }

      showLoader();
    });
  });

  document.querySelectorAll("form[data-loading-form]").forEach((form) => {
    if (form.dataset.confirm) {
      return;
    }

    form.addEventListener("submit", () => {
      showLoader();
    });
  });

  document.querySelectorAll("a[data-loading-link]").forEach((link) => {
    link.addEventListener("click", (event) => {
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      if (link.target === "_blank" || link.hasAttribute("download")) {
        return;
      }

      const url = new URL(link.href, window.location.href);
      if (url.origin !== window.location.origin) {
        return;
      }

      showLoader();
    });
  });

  const toggleBtn = document.getElementById("sidebar-toggle");
  const sidebar = document.querySelector(".menu");
  const overlay = document.getElementById("sidebar-overlay");

  if (toggleBtn && sidebar && overlay) {
    const closeSidebar = () => {
      sidebar.classList.add("collapsed");
      overlay.classList.remove("active");
      toggleBtn.classList.remove("active");
    };

    toggleBtn.addEventListener("click", () => {
      const isOpen = !sidebar.classList.contains("collapsed");
      if (isOpen) {
        closeSidebar();
      } else {
        sidebar.classList.remove("collapsed");
        overlay.classList.add("active");
        toggleBtn.classList.add("active");
      }
    });

    overlay.addEventListener("click", closeSidebar);

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !sidebar.classList.contains("collapsed")) {
        closeSidebar();
      }
    });
  }
});
