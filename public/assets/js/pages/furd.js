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

  /* =========================================================
   ✅ Estados reales por paso + pills OK/pendientes
   ========================================================= */
  const pillOk = document.getElementById("pillOk");
  const pillWarn = document.getElementById("pillWarn");

  const stepStateEl = (n) =>
    document.querySelector(`.furd-step .state[data-state="${n}"]`);
  const stepBtnEl = (n) => stepStateEl(n)?.closest(".furd-step");

  let validationArmed = false; // se activa al intentar enviar (para mostrar rojo)

  const isValidEmail = (email) =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((email || "").trim());

  const setStepStatus = (n, status) => {
    const btn = stepBtnEl(n);
    const st = stepStateEl(n);
    if (!btn || !st) return;

    btn.classList.remove("is-ok", "is-warn", "is-err");
    st.classList.remove("is-ok", "is-warn", "is-err");

    btn.classList.add(`is-${status}`);
    st.classList.add(`is-${status}`);

    // íconos discretos
    if (status === "ok") st.innerHTML = `<i class="bi bi-check-lg"></i>`;
    else if (status === "err") st.innerHTML = `<i class="bi bi-x-lg"></i>`;
    else st.innerHTML = `<i class="bi bi-exclamation-lg"></i>`;
  };

  const computeWizardStatus = () => {
    // Paso 1
    const empleadoId = (
      document.getElementById("empleado_id")?.value || ""
    ).trim();
    const nombre = (
      document.getElementById("nombre_completo")?.value || ""
    ).trim();
    const ced = (document.getElementById("cedula")?.value || "").trim();
    const correo = (document.getElementById("correo")?.value || "").trim();

    const paso1RequiredOk =
      ced.length > 0 && (empleadoId.length > 0 || nombre.length > 0);
    const correoOk = correo.length === 0 || isValidEmail(correo);

    let s1 = "warn";
    if (!correoOk) s1 = validationArmed ? "err" : "warn";
    else if (paso1RequiredOk) s1 = "ok";
    else s1 = validationArmed ? "err" : "warn";

    // Paso 2
    const fecha = (document.getElementById("fecha")?.value || "").trim();
    const hora = (document.getElementById("hora")?.value || "").trim();
    const hecho = (document.getElementById("hecho")?.value || "").trim();

    const paso2Ok = fecha.length > 0 && hora.length > 0 && hecho.length > 0;
    const s2 = paso2Ok ? "ok" : validationArmed ? "err" : "warn";

    // Paso 3
    const faltas = document.querySelectorAll(".faltas-check:checked").length;
    const paso3Ok = faltas > 0;
    const s3 = paso3Ok ? "ok" : validationArmed ? "err" : "warn";

    return { 1: s1, 2: s2, 3: s3 };
  };

  const refreshWizardUI = () => {
    const st = computeWizardStatus();
    setStepStatus(1, st[1]);
    setStepStatus(2, st[2]);
    setStepStatus(3, st[3]);

    const okCount = [1, 2, 3].filter((n) => st[n] === "ok").length;
    const pending = 3 - okCount;

    if (pillOk) pillOk.textContent = `${okCount} OK`;
    if (pillWarn) pillWarn.textContent = `${pending} pendientes`;
  };

  ["cedula", "correo", "fecha", "hora", "hecho"].forEach((id) => {
    const el = document.getElementById(id);
    el?.addEventListener("input", refreshWizardUI);
    el?.addEventListener("change", refreshWizardUI);
  });

  // 🧮 Contador de caracteres para inputs/textarea
  const setupCharCounter = (fieldId, counterId, max) => {
    const field = document.getElementById(fieldId);
    const counter = document.getElementById(counterId);
    if (!field || !counter) return;

    const update = () => {
      const len = field.value.length;
      counter.textContent = `${len}/${max}`;
      counter.classList.remove("text-danger", "text-warning");
      if (len > max * 0.9) counter.classList.add("text-danger");
      else if (len > max * 0.7) counter.classList.add("text-warning");
    };

    field.addEventListener("input", update);
    update();
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
            "warning",
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
      if (iconoBuscar) {
        iconoBuscar.classList.remove("bi-arrow-repeat", "spin");
        iconoBuscar.classList.add("bi-search");
      }
      btnBuscar.disabled = false;
      cedula.classList.remove("loading");
      refreshWizardUI();
    }
  }

  btnBuscar?.addEventListener("click", buscarEmpleado);

  cedula?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      buscarEmpleado();
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
        .replace(/[\u0300-\u036f]/g, "");

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

    filtro.addEventListener("input", aplicarFiltro);
    filtro.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        filtro.value = "";
        aplicarFiltro();
      }
    });
  }

  // 👉 Renderizar lista de archivos + barras de progreso
  // const renderEvidenciasPreview = () => {
  //   if (!evidenciasPreview || !evidenciasInput) return;
  //   evidenciasPreview.innerHTML = "";

  //   const files = Array.from(evidenciasInput.files || []);
  //   if (!files.length) return;

  //   files.forEach((file, idx) => {
  //     const row = document.createElement("div");
  //     row.className = "evidencia-row mb-1";
  //     const sizeMb = (file.size / (1024 * 1024)).toFixed(2);

  //     row.innerHTML = `
  //       <div class="d-flex w-100 align-items-center justify-content-between">
  //         <div class="me-2">
  //           <i class="bi bi-paperclip me-1"></i>
  //           <span class="file-name">${file.name}</span>
  //           <span class="text-muted ms-1">(${sizeMb} MB)</span>
  //         </div>
  //         <button type="button"
  //                 class="btn btn-sm btn-link text-danger p-0 js-remove-file"
  //                 data-file-idx="${idx}"
  //                 title="Quitar archivo">
  //           <i class="bi bi-x-lg"></i>
  //         </button>
  //       </div>
  //       <div class="progress mt-1" style="height: 4px;">
  //         <div class="progress-bar"
  //              role="progressbar"
  //              data-file-idx="${idx}"
  //              style="width: 0%;"></div>
  //       </div>
  //     `;

  //     evidenciasPreview.appendChild(row);
  //   });
  // };

  /* =========================================================
   📎 Evidencias PREMIUM (icono + contador + tamaño + quitar todo + thumbs + modal)
   ========================================================= */
  let previewUrls = [];
  const revokePreviewUrls = () => {
    previewUrls.forEach((u) => URL.revokeObjectURL(u));
    previewUrls = [];
  };

  const getExt = (name) => (name || "").split(".").pop().toLowerCase();
  const isImg = (ext) => ["jpg", "jpeg", "png", "heic"].includes(ext);

  const iconByExt = (ext) => {
    if (ext === "pdf") return "bi-file-earmark-pdf-fill text-danger";
    if (ext === "doc" || ext === "docx")
      return "bi-file-earmark-word-fill text-primary";
    if (ext === "xls" || ext === "xlsx")
      return "bi-file-earmark-excel-fill text-success";
    if (isImg(ext)) return "bi-file-earmark-image-fill text-info";
    return "bi-paperclip";
  };

  const fmtMB = (bytes) => `${(bytes / (1024 * 1024)).toFixed(2)} MB`;

  const renderEvidenciasPreview = () => {
    if (!evidenciasPreview || !evidenciasInput) return;

    revokePreviewUrls();
    evidenciasPreview.innerHTML = "";

    const files = Array.from(evidenciasInput.files || []);
    if (!files.length) return;

    const totalBytes = files.reduce((acc, f) => acc + (f.size || 0), 0);

    // Top: resumen + quitar todo
    const top = document.createElement("div");
    top.className = "evidencias-top";

    top.innerHTML = `
    <div class="evidencias-summary">
      <i class="bi bi-files"></i>
      <span>${files.length} archivo(s) · ${fmtMB(totalBytes)}</span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger js-clear-files">
      <i class="bi bi-trash me-1"></i>Quitar todo
    </button>
  `;
    evidenciasPreview.appendChild(top);

    // Lista
    files.forEach((file, idx) => {
      const ext = getExt(file.name);
      const sizeMb = fmtMB(file.size || 0);

      const row = document.createElement("div");
      row.className = "evidencia-row mb-2";

      let leftVisual = `<i class="bi ${iconByExt(ext)} evidencia-icon"></i>`;
      if (isImg(ext)) {
        const url = URL.createObjectURL(file);
        previewUrls.push(url);
        leftVisual = `<img src="${url}" class="evidencia-thumb js-img-preview" data-file-idx="${idx}" alt="Vista previa">`;
      }

      row.innerHTML = `
      <div class="d-flex w-100 align-items-center justify-content-between gap-2">
        <div class="evidencia-left flex-grow-1">
          ${leftVisual}
          <div class="min-w-0">
            <span class="file-name">${file.name}</span>
            <span class="text-muted small">${sizeMb}</span>
          </div>
        </div>

        <button type="button"
                class="btn btn-sm btn-link text-danger p-0 js-remove-file"
                data-file-idx="${idx}"
                title="Quitar archivo">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="progress mt-2" style="height: 4px;">
        <div class="progress-bar"
             role="progressbar"
             data-file-idx="${idx}"
             style="width: 0%;"></div>
      </div>
    `;

      evidenciasPreview.appendChild(row);
    });
  };

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
          "warning",
        );
        return;
      }

      if (!isAllowedSize) {
        showToast(
          `El archivo "${file.name}" supera el límite de 16 MB y no se cargará.`,
          "warning",
        );
        return;
      }

      dt.items.add(file);
    });

    evidenciasInput.files = dt.files;
    renderEvidenciasPreview();
  };

  evidenciasInput?.addEventListener("change", handleEvidenciasChange);

  // evidenciasPreview?.addEventListener("click", (e) => {
  //   const btn = e.target.closest(".js-remove-file");
  //   if (!btn || !evidenciasInput) return;

  //   const idx = parseInt(btn.dataset.fileIdx, 10);
  //   if (Number.isNaN(idx)) return;

  //   const dt = new DataTransfer();
  //   Array.from(evidenciasInput.files || []).forEach((file, i) => {
  //     if (i !== idx) dt.items.add(file);
  //   });

  //   evidenciasInput.files = dt.files;
  //   renderEvidenciasPreview();

  //   if (!dt.files.length) {
  //     showToast("Se han quitado todas las evidencias seleccionadas.", "info");
  //   }
  // });

  evidenciasPreview?.addEventListener("click", (e) => {
    // Quitar todo
    const clearBtn = e.target.closest(".js-clear-files");
    if (clearBtn && evidenciasInput) {
      const dt = new DataTransfer();
      evidenciasInput.files = dt.files;
      renderEvidenciasPreview();
      showToast("Se han quitado todas las evidencias.", "info");
      return;
    }

    // Quitar uno
    const btn = e.target.closest(".js-remove-file");
    if (btn && evidenciasInput) {
      const idx = parseInt(btn.dataset.fileIdx, 10);
      if (Number.isNaN(idx)) return;

      const dt = new DataTransfer();
      Array.from(evidenciasInput.files || []).forEach((file, i) => {
        if (i !== idx) dt.items.add(file);
      });

      evidenciasInput.files = dt.files;
      renderEvidenciasPreview();

      if (!dt.files.length)
        showToast("Se han quitado todas las evidencias seleccionadas.", "info");
      return;
    }

    // Click en thumbnail (vista previa)
    const thumb = e.target.closest(".js-img-preview");
    if (thumb && evidenciasInput) {
      const idx = parseInt(thumb.dataset.fileIdx, 10);
      if (Number.isNaN(idx)) return;

      const files = Array.from(evidenciasInput.files || []);
      const file = files[idx];
      if (!file) return;

      const modalEl = document.getElementById("imgPreviewModal");
      const imgEl = document.getElementById("imgPreviewImg");
      const titleEl = document.getElementById("imgPreviewTitle");
      if (!modalEl || !imgEl) return;

      const url = URL.createObjectURL(file);
      imgEl.src = url;
      if (titleEl) titleEl.textContent = file.name;

      // al cerrar, libera el objectURL
      modalEl.addEventListener(
        "hidden.bs.modal",
        () => {
          URL.revokeObjectURL(url);
          imgEl.src = "";
        },
        { once: true },
      );

      if (window.bootstrap?.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
      } else {
        // fallback: si no está el JS de bootstrap por alguna razón
        window.open(url, "_blank");
      }
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

    return offset;
  };

  const updateUploadProgressBars = (loaded) => {
    if (!uploadFilesMeta.length || !evidenciasPreview) return;

    uploadFilesMeta.forEach((meta) => {
      const bar = evidenciasPreview.querySelector(
        `.progress-bar[data-file-idx="${meta.index}"]`,
      );
      if (!bar) return;

      let percent = 0;
      if (loaded <= meta.start) percent = 0;
      else if (loaded >= meta.end) percent = 100;
      else percent = ((loaded - meta.start) / meta.size) * 100;

      bar.style.width = `${percent}%`;
    });
  };

  // Botones de ayuda
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-info-help");
    if (!btn) return;

    const title = btn.dataset.infoTitle || "Información";
    const html = btn.dataset.infoText || "";

    if (typeof Swal === "undefined") {
      alert(title + "\n\n" + html.replace(/<[^>]+>/g, ""));
      return;
    }

    Swal.fire({
      icon: "info",
      title: title,
      html: html,
      confirmButtonText: "Entendido",
      confirmButtonColor: "#0d6efd",
      customClass: { popup: "swal2-popup-help" },
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
    if (e.target.classList?.contains("faltas-check")) {
      refresh();
      refreshWizardUI();
    }
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
      validationArmed = true;
      refreshWizardUI();

      const faltas = document.querySelectorAll(".faltas-check:checked").length;
      if (faltas === 0) {
        showToast("Debes seleccionar al menos una falta.", "warning");
        return;
      }

      if (sending) return;
      sending = true;

      btn.disabled = true;
      if (spin) spin.classList.remove("d-none");
      if (txt) txt.textContent = "Guardando...";
      showGlobalLoader();

      const formData = new FormData(form);
      buildUploadMeta();

      const xhr = new XMLHttpRequest();
      xhr.open(form.method || "POST", form.action);
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

      xhr.upload.onprogress = (evt) => {
        if (!evt.lengthComputable) return;
        updateUploadProgressBars(evt.loaded);
      };

      xhr.onload = () => {
        hideGlobalLoader();

        const contentType = xhr.getResponseHeader("Content-Type") || "";
        let data = null;

        if (contentType.includes("application/json")) {
          try {
            data = JSON.parse(xhr.responseText || "{}");
          } catch {
            data = null;
          }
        }

        if (data) {
          if (data.ok && data.redirectTo) {
            window.location.href = data.redirectTo;
            return;
          }

          if (data.ok === false && data.errors) {
            const allErrors = Object.values(data.errors);
            const firstError =
              allErrors.length > 0
                ? allErrors[0]
                : "Revisa los campos obligatorios.";
            showToast(firstError, "warning");

            sending = false;
            btn.disabled = false;
            if (spin) spin.classList.add("d-none");
            if (txt) txt.textContent = "Guardar registro";
            return;
          }

          showToast("Error inesperado al guardar el FURD.", "error");
          sending = false;
          btn.disabled = false;
          if (spin) spin.classList.add("d-none");
          if (txt) txt.textContent = "Guardar registro";
          return;
        }

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
          "error",
        );
        sending = false;
        btn.disabled = false;
        if (spin) spin.classList.add("d-none");
        if (txt) txt.textContent = "Guardar registro";
      };

      xhr.send(formData);
    });
  });

  // max palabra
  const hechoField = document.getElementById("hecho");
  if (hechoField) {
    const MAX_WORD = 120;
    let lastValid = hechoField.value;

    const checkHechoWords = () => {
      const words = (hechoField.value || "").split(/\s+/);
      const tooLong = words.some((w) => w.length > MAX_WORD);

      if (tooLong) {
        hechoField.value = lastValid;
        hechoField.selectionStart = hechoField.selectionEnd =
          hechoField.value.length;

        if (typeof showToast === "function") {
          showToast(
            `No se permiten palabras de más de ${MAX_WORD} caracteres sin espacios.`,
            "warning",
          );
        } else {
          alert(
            `No se permiten palabras de más de ${MAX_WORD} caracteres sin espacios.`,
          );
        }
      } else {
        lastValid = hechoField.value;
      }
    };

    hechoField.addEventListener("input", checkHechoWords);
  }

  document.addEventListener("DOMContentLoaded", () => {
    setupCharCounter("superior", "superiorCount", 60);
    setupCharCounter("hecho", "hechoCount", 5000);
  });

  refresh();
  refreshWizardUI();

  /* =========================================================
     PRO Single View: ScrollSpy + Progreso (robusto)
     (✅ FIX: define onScroll antes de usarlo)
     ========================================================= */
  (() => {
    function initScrollSpy() {
      const topbar = document.getElementById("furdTopbar");
      const bar = document.getElementById("furdBar");
      const steps = Array.from(document.querySelectorAll(".furd-step"));
      const sections = [
        document.getElementById("secTrabajador"),
        document.getElementById("secEvento"),
        document.getElementById("secFaltas"),
      ].filter(Boolean);

      if (!topbar || !bar || !steps.length || sections.length < 2) return;

      const getScrollContainer = (el) => {
        let p = el.parentElement;
        while (p) {
          const st = getComputedStyle(p);
          const oy = st.overflowY;
          const canScroll =
            oy === "auto" || oy === "scroll" || oy === "overlay";
          if (canScroll && p.scrollHeight > p.clientHeight + 2) return p;
          p = p.parentElement;
        }
        return window;
      };

      const scroller = getScrollContainer(topbar);

      const getNavHeight = () => {
        const nav = document.querySelector(
          ".navbar, .navbar-sticky, header, .topbar",
        );
        return nav ? nav.getBoundingClientRect().height || 0 : 0;
      };

      const getOffset = () => {
        const navH = getNavHeight();
        const topbarH =
          topbar.getBoundingClientRect().height || topbar.offsetHeight || 0;
        return navH + topbarH + 16;
      };

      const setActiveStep = (activeIndex) => {
        steps.forEach((s, i) =>
          s.classList.toggle("is-active", i === activeIndex),
        );
        sections.forEach((sec, i) =>
          sec.classList.toggle("is-current", i === activeIndex),
        );
        const pct = Math.round(((activeIndex + 1) / steps.length) * 100);
        bar.style.width = pct + "%";
      };

      const isAtEnd = () => {
        if (scroller === window) {
          const doc = document.documentElement;
          return window.innerHeight + window.scrollY >= doc.scrollHeight - 8;
        }
        return (
          scroller.scrollTop + scroller.clientHeight >=
          scroller.scrollHeight - 8
        );
      };

      const getViewport = () => {
        if (scroller === window)
          return { top: getOffset() + 8, bottom: window.innerHeight };
        const r = scroller.getBoundingClientRect();
        return { top: Math.max(r.top, getOffset() + 8), bottom: r.bottom };
      };

      const computeIndex = () => {
        if (isAtEnd()) return sections.length - 1;

        const vp = getViewport();
        let bestIdx = 0;
        let bestVisible = -1;

        for (let i = 0; i < sections.length; i++) {
          const rect = sections[i].getBoundingClientRect();
          const visible = Math.max(
            0,
            Math.min(rect.bottom, vp.bottom) - Math.max(rect.top, vp.top),
          );
          if (visible > bestVisible) {
            bestVisible = visible;
            bestIdx = i;
          }
        }
        return bestIdx;
      };

      // ✅ FIX: ahora sí existe onScroll antes de usarlo
      let raf = 0;
      const onScroll = () => {
        if (raf) return;
        raf = requestAnimationFrame(() => {
          raf = 0;
          setActiveStep(computeIndex());
        });
      };

      // Scroll interno (faltas)
      document.querySelectorAll("#faltasList, .scroll-area").forEach((el) => {
        el.addEventListener("scroll", onScroll, { passive: true });
      });

      // Click steps
      steps.forEach((btn) => {
        btn.addEventListener("click", () => {
          const sel = btn.getAttribute("data-target");
          const el = sel ? document.querySelector(sel) : null;
          if (!el) return;

          const idx = steps.indexOf(btn);
          if (idx >= 0) setActiveStep(idx);

          const offset = getOffset() + 8;

          if (scroller === window) {
            const top =
              window.scrollY + el.getBoundingClientRect().top - offset;
            window.scrollTo({ top, behavior: "smooth" });
          } else {
            const scTop = scroller.scrollTop;
            const scRectTop = scroller.getBoundingClientRect().top;
            const top =
              scTop + (el.getBoundingClientRect().top - scRectTop) - offset;
            scroller.scrollTo({ top, behavior: "smooth" });
          }
        });
      });

      // Scroll del scroller real
      if (scroller === window)
        window.addEventListener("scroll", onScroll, { passive: true });
      else scroller.addEventListener("scroll", onScroll, { passive: true });

      window.addEventListener("resize", onScroll);
      document.addEventListener("furd:layout", onScroll);

      setActiveStep(computeIndex());
      onScroll();
    }

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initScrollSpy);
    } else {
      initScrollSpy();
    }
  })();

  // Dock del topbar
  (() => {
    const topbar = document.getElementById("furdTopbar");
    const spacer = document.getElementById("furdTopbarSpacer");
    if (!topbar || !spacer) return;

    const getNavHeight = () => {
      const nav = document.querySelector(
        ".navbar, .navbar-sticky, header, .topbar",
      );
      if (!nav) return 0;
      const r = nav.getBoundingClientRect();
      return r.height || 0;
    };

    const getTopOffset = () => getNavHeight() + 10;

    let lastDocked = null;
    let lastTop = null;

    const dockCheck = () => {
      const navTop = getTopOffset();
      const rect = topbar.getBoundingClientRect();
      const shouldDock = rect.top <= navTop;

      if (shouldDock && !topbar.classList.contains("is-docked")) {
        spacer.style.height = rect.height + "px";
        topbar.classList.add("is-docked");
        topbar.style.top = navTop + "px";
      }

      if (!shouldDock && topbar.classList.contains("is-docked")) {
        topbar.classList.remove("is-docked");
        topbar.style.top = "";
        spacer.style.height = "0px";
      }

      if (topbar.classList.contains("is-docked")) {
        topbar.style.top = getTopOffset() + "px";
      }

      const isDockedNow = topbar.classList.contains("is-docked");
      const topNow = topbar.style.top || "";

      if (isDockedNow !== lastDocked || topNow !== lastTop) {
        lastDocked = isDockedNow;
        lastTop = topNow;
        document.dispatchEvent(new Event("furd:layout"));
      }
    };

    window.addEventListener("scroll", dockCheck, { passive: true });
    window.addEventListener("resize", dockCheck);
    dockCheck();
  })();
})();
