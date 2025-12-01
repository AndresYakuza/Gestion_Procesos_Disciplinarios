(() => {
  const fill = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.value = val ?? "";
  };
  const cedula = document.getElementById("cedula");
  const btnBuscar = document.getElementById("btnBuscarEmpleado");
  const iconoBuscar = btnBuscar?.querySelector("i");

  const globalLoader = document.getElementById("globalLoader");
  const showGlobalLoader = () => globalLoader?.classList.remove("d-none");
  const hideGlobalLoader = () => globalLoader?.classList.add("d-none");

  const evidenciasInput = document.getElementById("evidencias");
  const evidenciasPreview = document.getElementById("evidenciasPreview");
  const MAX_FILE_SIZE = 16 * 1024 * 1024; // 16 MB
  const ALLOWED_EXT = [
    "pdf",
    "jpg",
    "jpeg",
    "png",
    "heic",
    "doc",
    "docx",
    "xlsx",
    "xls",
  ];

    // 🧮 Contador de caracteres para inputs/textarea
  const setupCharCounter = (fieldId, counterId, max) => {
    const field = document.getElementById(fieldId);
    const counter = document.getElementById(counterId);
    if (!field || !counter) return;

    const update = () => {
      const len = field.value.length;
      // formato "123/2000"
      counter.textContent = `${len}/${max}`;

      // Colores según cercanía al límite
      counter.classList.remove("text-danger", "text-warning");
      if (len > max * 0.9) {
        counter.classList.add("text-danger");
      } else if (len > max * 0.7) {
        counter.classList.add("text-warning");
      }
    };

    field.addEventListener("input", update);
    update(); // inicial (por si viene con old())
  };


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

  // ⌨️ Al presionar Enter en el campo cédula, NO hacer submit: solo buscar
  cedula?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault(); // evita el submit del formulario
      buscarEmpleado(); // o: btnBuscar?.click();
    }
  });

  // 🔎 Filtro de faltas (código + descripción, ignorando acentos)
  const filtro = document.getElementById("filtroFaltas");

  if (filtro) {
    const normalizar = (str) =>
      (str || "")
        .toString()
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, ""); // quita acentos

    const aplicarFiltro = () => {
      const q = normalizar(filtro.value.trim());

      document.querySelectorAll(".faltas-check").forEach((cb) => {
        const label = cb.closest("label.list-group-item");
        if (!label) return;

        const codigo = cb.dataset.codigo || "";
        const descripcion = cb.dataset.descripcion || "";
        const texto = normalizar(codigo + " " + descripcion);

        const match = !q || texto.includes(q);
        label.classList.toggle("d-none", !match);
      });
    };

    // filtra mientras escribes
    filtro.addEventListener("input", aplicarFiltro);

    // opcional: Esc para limpiar el filtro
    filtro.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        filtro.value = "";
        aplicarFiltro();
      }
    });
  }

  // 👉 Renderizar lista de archivos + barras de progreso (inicialmente en 0%)
  const renderEvidenciasPreview = () => {
    if (!evidenciasPreview || !evidenciasInput) return;
    evidenciasPreview.innerHTML = "";

    const files = Array.from(evidenciasInput.files || []);
    if (!files.length) return;

    files.forEach((file, idx) => {
      const row = document.createElement("div");
      row.className = "evidencia-row mb-1";

      const sizeMb = (file.size / (1024 * 1024)).toFixed(2);

      row.innerHTML = `
      <div class="d-flex w-100 align-items-center justify-content-between">
        <div class="me-2">
          <i class="bi bi-paperclip me-1"></i>
          <span class="file-name">${file.name}</span>
          <span class="text-muted ms-1">(${sizeMb} MB)</span>
        </div>
        <button type="button"
                class="btn btn-sm btn-link text-danger p-0 js-remove-file"
                data-file-idx="${idx}"
                title="Quitar archivo">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="progress mt-1" style="height: 4px;">
        <div class="progress-bar"
             role="progressbar"
             data-file-idx="${idx}"
             style="width: 0%;"></div>
      </div>
    `;

      evidenciasPreview.appendChild(row);
    });
  };

  // 👉 Validar archivos seleccionados (tipo + tamaño)
  const handleEvidenciasChange = () => {
    if (!evidenciasInput) return;
    const dt = new DataTransfer();

    Array.from(evidenciasInput.files || []).forEach((file) => {
      const ext = file.name.split(".").pop().toLowerCase();
      const isAllowedExt = ALLOWED_EXT.includes(ext);
      const isAllowedSize = file.size <= MAX_FILE_SIZE;

      if (!isAllowedExt) {
        showToast(
          `El archivo "${file.name}" no está permitido. Solo se permiten imágenes (JPG, JPEG, PNG, HEIC), PDF y archivos de Office (DOC, DOCX, XLS, XLSX).`,
          "warning"
        );
        return;
      }

      if (!isAllowedSize) {
        showToast(
          `El archivo "${file.name}" supera el límite de 16 MB y no se cargará.`,
          "warning"
        );
        return;
      }

      dt.items.add(file);
    });

    evidenciasInput.files = dt.files;
    renderEvidenciasPreview();
  };

  evidenciasInput?.addEventListener("change", handleEvidenciasChange);

  // 👉 Quitar archivo individual desde la vista previa
  evidenciasPreview?.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-remove-file");
    if (!btn || !evidenciasInput) return;

    const idx = parseInt(btn.dataset.fileIdx, 10);
    if (Number.isNaN(idx)) return;

    const dt = new DataTransfer();
    Array.from(evidenciasInput.files || []).forEach((file, i) => {
      if (i !== idx) dt.items.add(file); // dejamos todos menos el que se quiere quitar
    });

    evidenciasInput.files = dt.files;
    renderEvidenciasPreview();

    if (!dt.files.length) {
      showToast("Se han quitado todas las evidencias seleccionadas.", "info");
    }
  });

  // 👉 Meta para calcular progreso por archivo
  let uploadFilesMeta = [];

  const buildUploadMeta = () => {
    if (!evidenciasInput) {
      uploadFilesMeta = [];
      return 0;
    }

    const files = Array.from(evidenciasInput.files || []);
    let offset = 0;
    uploadFilesMeta = files.map((file, idx) => {
      const start = offset;
      const end = start + file.size;
      offset = end;
      return { index: idx, start, end, size: file.size };
    });

    return offset; // total bytes
  };

  const updateUploadProgressBars = (loaded) => {
    if (!uploadFilesMeta.length || !evidenciasPreview) return;

    uploadFilesMeta.forEach((meta) => {
      const bar = evidenciasPreview.querySelector(
        `.progress-bar[data-file-idx="${meta.index}"]`
      );
      if (!bar) return;

      let percent = 0;
      if (loaded <= meta.start) {
        percent = 0;
      } else if (loaded >= meta.end) {
        percent = 100;
      } else {
        percent = ((loaded - meta.start) / meta.size) * 100;
      }

      bar.style.width = `${percent}%`;
    });
  };

  // Botones de ayuda (info) para los labels con .btn-info-help
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-info-help');
    if (!btn) return;

    const title = btn.dataset.infoTitle || 'Información';
    const html  = btn.dataset.infoText || '';

    if (typeof Swal === 'undefined') {
      alert(title + '\n\n' + html.replace(/<[^>]+>/g, ''));
      return;
    }

    Swal.fire({
      icon: 'info',
      title: title,
      html: html,
      confirmButtonText: 'Entendido',
      confirmButtonColor: '#0d6efd',
      customClass: {
        popup: 'swal2-popup-help'
      }
    });
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

  // 🛑 Validación + loader + evitar doble envío + progreso de subida
  document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("furdForm");
    const btn = document.getElementById("btnGuardar");
    if (!form || !btn) return;

    const spin = btn.querySelector(".spinner-border");
    const txt = btn.querySelector(".btn-text");
    let sending = false;

    form.addEventListener("submit", (e) => {
      e.preventDefault();

      // 1) Validación de faltas
      const faltas = document.querySelectorAll(".faltas-check:checked").length;
      if (faltas === 0) {
        showToast("Debes seleccionar al menos una falta.", "warning");
        return; // 👉 importante: NO activar el loader
      }

      // 2) Evitar doble envío
      if (sending) return;
      sending = true;

      // 3) Activar loader en botón + overlay central
      btn.disabled = true;
      if (spin) spin.classList.remove("d-none");
      if (txt) txt.textContent = "Guardando...";
      showGlobalLoader();

      // 4) Construir FormData
      const formData = new FormData(form);

      // 5) Preparar meta y barras de progreso por archivo
      const totalBytes = buildUploadMeta();

      const xhr = new XMLHttpRequest();
      xhr.open(form.method || "POST", form.action);

      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

      // 6) Progreso de subida
      xhr.upload.onprogress = (evt) => {
        if (!evt.lengthComputable) return;
        const loaded = evt.loaded;
        updateUploadProgressBars(loaded);
      };

      // 7) Respuesta OK → navegamos a la URL final (incluyendo redirects)
      xhr.onload = () => {
        hideGlobalLoader();

        const contentType = xhr.getResponseHeader("Content-Type") || "";
        let data = null;

        // Si viene JSON desde el backend, lo intentamos parsear
        if (contentType.includes("application/json")) {
          try {
            data = JSON.parse(xhr.responseText || "{}");
          } catch (e) {
            data = null;
          }
        }

        // ✅ Caso 1: JSON de nuestra API
        if (data) {
          // Éxito: ok = true
          if (data.ok && data.redirectTo) {
            window.location.href = data.redirectTo;
            return;
          }

          // Error de validación: ok = false
          if (data.ok === false && data.errors) {
            const allErrors = Object.values(data.errors);
            const firstError =
              allErrors.length > 0
                ? allErrors[0]
                : "Revisa los campos obligatorios.";

            showToast(firstError, "warning");

            // Volvemos a habilitar el botón y el texto
            sending = false;
            btn.disabled = false;
            if (spin) spin.classList.add("d-none");
            if (txt) txt.textContent = "Guardar registro";
            return;
          }

          // JSON raro → tratamos como error genérico
          showToast("Error inesperado al guardar el FURD.", "error");
          sending = false;
          btn.disabled = false;
          if (spin) spin.classList.add("d-none");
          if (txt) txt.textContent = "Guardar registro";
          return;
        }

        // ✅ Caso 2: no es JSON → comportamiento fallback
        if (xhr.status >= 200 && xhr.status < 400) {
          const finalURL = xhr.responseURL || form.action;
          window.location.href = finalURL;
        } else {
          showToast("Ocurrió un error al guardar el FURD.", "error");
          sending = false;
          btn.disabled = false;
          if (spin) spin.classList.add("d-none");
          if (txt) txt.textContent = "Guardar registro";
        }
      };

      xhr.onerror = () => {
        hideGlobalLoader();
        showToast(
          "No se pudo conectar con el servidor. Revisa tu conexión.",
          "error"
        );
        sending = false;
        btn.disabled = false;
        if (spin) spin.classList.add("d-none");
        if (txt) txt.textContent = "Guardar registro";
      };

      xhr.send(formData);
    });
  });

  const hechoField = document.getElementById('hecho');
if (hechoField) {
  const MAX_WORD = 120;
  let lastValid = hechoField.value;

  const checkHechoWords = () => {
    const words = (hechoField.value || '').split(/\s+/);
    const tooLong = words.some(w => w.length > MAX_WORD);

    if (tooLong) {
      // volvemos al valor anterior
      hechoField.value = lastValid;
      hechoField.selectionStart = hechoField.selectionEnd = hechoField.value.length;

      if (typeof showToast === 'function') {
        showToast(
          `No se permiten palabras de más de ${MAX_WORD} caracteres sin espacios.`,
          'warning'
        );
      } else {
        alert(`No se permiten palabras de más de ${MAX_WORD} caracteres sin espacios.`);
      }
    } else {
      lastValid = hechoField.value;
    }
  };

  hechoField.addEventListener('input', checkHechoWords);
}

    document.addEventListener("DOMContentLoaded", () => {
    setupCharCounter("superior", "superiorCount", 60);
    setupCharCounter("hecho", "hechoCount", 5000);
  });

  refresh();
})();
