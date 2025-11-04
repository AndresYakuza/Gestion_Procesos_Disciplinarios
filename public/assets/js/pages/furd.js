(() => {
  const fill = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.value = val ?? "";
  };
  const cedula = document.getElementById("cedula");
  const btnBuscar = document.getElementById("btnBuscarEmpleado");
  const iconoBuscar = btnBuscar?.querySelector("i");

  // 🧩 Mostrar notificaciones tipo toast (Bootstrap)
  function showToast(message, type = "info") {
    const colors = {
      success: "bg-success text-white",
      error: "bg-danger text-white",
      warning: "bg-warning text-dark",
      info: "bg-info text-dark",
    };
    const icon = {
      success: "bi-check-circle-fill",
      error: "bi-x-circle-fill",
      warning: "bi-exclamation-triangle-fill",
      info: "bi-info-circle-fill",
    };

    const toast = document.createElement("div");
    toast.className = `toast align-items-center border-0 show ${colors[type]} mt-2 shadow`;
    toast.role = "alert";
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi ${icon[type]} me-2"></i>${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    `;

    const container =
      document.getElementById("toastContainer") ||
      (() => {
        const c = document.createElement("div");
        c.id = "toastContainer";
        c.className = "toast-container position-fixed top-0 end-0 p-3";
        c.style.zIndex = 2000;
        document.body.appendChild(c);
        return c;
      })();

    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
  }

  // 🔍 Buscar empleado manualmente (con spinner)
  async function buscarEmpleado() {
    const ced = cedula.value.trim();
    if (!ced) {
      showToast("Por favor ingresa una cédula antes de buscar.", "warning");
      return;
    }

    // 🔄 Cambiar icono a spinner
    if (iconoBuscar) {
      iconoBuscar.classList.remove("bi-search");
      iconoBuscar.classList.add("bi-arrow-repeat", "spin");
    }
    btnBuscar.disabled = true;
    cedula.classList.add("loading");

    try {
      const res = await fetch(`${BASE_LOOKUP_URL}/${encodeURIComponent(ced)}`);

      let d = null;
      try {
        d = await res.json();
      } catch {
        d = null;
      }

      if (!res.ok) {
        if (res.status === 404) {
          fill("nombre_completo", "");
          fill("expedida_en", "");
          fill("empresa_usuaria", "");
          fill("correo", "");
          showToast("Empleado no encontrado.", "error");
        } else {
          showToast("Error al buscar el empleado.", "error");
        }
        return;
      }

      if (d.found && d.empleado) {
        fill("nombre_completo", d.empleado.nombre_completo);
        fill("expedida_en", d.empleado.ciudad_expide ?? "");
        fill("correo", d.empleado.correo ?? "");

        if (d.contrato_activo) {
          fill("empresa_usuaria", d.contrato_activo.empresa_usuaria ?? "");
          showToast("Empleado y contrato activo encontrados.", "success");
        } else {
          fill("empresa_usuaria", "");
          showToast(
            "Empleado encontrado, pero sin contrato activo.",
            "warning"
          );
        }
      } else {
        fill("nombre_completo", "");
        fill("expedida_en", "");
        fill("empresa_usuaria", "");
        fill("correo", "");
        showToast("Empleado no encontrado.", "error");
      }
    } catch (err) {
      console.error(err);
      showToast("Error al buscar el empleado.", "error");
    } finally {
      // 🔁 Restaurar icono original
      if (iconoBuscar) {
        iconoBuscar.classList.remove("bi-arrow-repeat", "spin");
        iconoBuscar.classList.add("bi-search");
      }
      btnBuscar.disabled = false;
      cedula.classList.remove("loading");
    }
  }

  // 🔎 Buscar solo al hacer clic
  btnBuscar?.addEventListener("click", buscarEmpleado);

  // 🔎 Filtro con debounce
  const filtro = document.getElementById("filtroFaltas");
  let timer;
  filtro?.addEventListener("input", (e) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const q = e.target.value.toLowerCase();
      document.querySelectorAll(".faltas-check").forEach((cb) => {
        const label = cb.closest("label");
        const text = (label?.innerText || "").toLowerCase();
        label.style.display = text.includes(q) ? "" : "none";
      });
    }, 200);
  });

  // 💊 Actualizar pills
  const pillsBox = document.getElementById("faltasPills");
  const selCount = document.getElementById("selCount");
  const sevClass = (s) => {
    const k = s
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase();
    if (k.includes("gravisim")) return "bg-danger-subtle text-danger-emphasis";
    if (k.includes("grave")) return "bg-warning-subtle text-warning-emphasis";
    if (k.includes("leve")) return "bg-success-subtle text-success-emphasis";
    return "bg-secondary-subtle text-secondary";
  };
  const refresh = () => {
    pillsBox.innerHTML = "";
    const checked = [...document.querySelectorAll(".faltas-check:checked")];
    checked.forEach((cb) => {
      const pill = document.createElement("span");
      pill.className = "faltas-pill " + sevClass(cb.dataset.gravedad);
      pill.textContent = cb.dataset.codigo || cb.value;
      pillsBox.appendChild(pill);
    });
    selCount.textContent = `${checked.length} seleccionadas`;
  };
  document.addEventListener("change", (e) => {
    if (e.target.classList?.contains("faltas-check")) refresh();
  });

  // 🛑 Validación antes de enviar
  document.getElementById("furdForm")?.addEventListener("submit", (e) => {
    const faltas = document.querySelectorAll(".faltas-check:checked").length;
    if (faltas === 0) {
      e.preventDefault();
      showToast("Debes seleccionar al menos una falta.", "warning");
    }
  });

  refresh();
})();
