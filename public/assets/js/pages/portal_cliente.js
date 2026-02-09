(function () {
  const root = document.getElementById("portalClienteRoot");
  if (!root) return;

  const emailPortal = (root.dataset.portalEmail || "").trim();
  const consecInicial = (root.dataset.portalConsecutivo || "").trim();
  // 🔗 URL base para buscar empleado (preferimos data-lookup-url)
  const baseLookupUrl =
    (root.dataset.lookupUrl || "").trim() ||
    (typeof BASE_LOOKUP_URL !== "undefined" ? BASE_LOOKUP_URL : "");

  const formFurd = document.getElementById("portalFurdForm");
  const alertFurd = document.getElementById("portalFurdAlert");
  const globalLoader = document.getElementById("globalLoader");
  const loaderText = globalLoader?.querySelector(".loader-text");
  const loaderDefaultText = loaderText?.textContent || "";

  const toastContainer = document.getElementById("portalToastContainer");

  const tbodyProcesos = document.getElementById("tbodyMisProcesos");
  const msgProcesos = document.getElementById("misProcesosMsg");

  const timelineHeader = document.getElementById("timelineHeader");
  const timelineContent = document.getElementById("timelineContent");
  const respuestaWrap = document.getElementById("respuestaClienteWrap");
  const respuestaForm = document.getElementById("respuestaClienteForm");
  const respuestaCorreo = document.getElementById("respuestaCorreoCliente");

  // Filtros / paginador de MIS PROCESOS
  const qMis = document.getElementById("qMisProcesos");
  const fEstadoMis = document.getElementById("fEstadoMisProcesos");
  const fDesdeMis = document.getElementById("fDesdeMisProcesos");
  const fHastaMis = document.getElementById("fHastaMisProcesos");
  const btnLimpiarMis = document.getElementById("btnLimpiarMisProcesos");
  const countTotalMis = document.getElementById("countTotalMisProcesos");
  const pagerMis = document.getElementById("pagerMisProcesos");

  let procesosCargados = false;
  let ultimoConsecutivoSeleccionado = consecInicial || null;

  // Datos en memoria para filtros/paginación
  let procesosData = [];
  let filteredProcesos = [];
  let currentPageMis = 1;
  const pageSizeMis = 10;

  // -----------------------
  // Helpers generales
  // -----------------------
  function showLoader(message) {
    if (globalLoader) {
      if (loaderText && message) {
        loaderText.textContent = message;
      }
      globalLoader.classList.remove("d-none");
    }
    document.body.style.overflow = "hidden";
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function hideLoader() {
    if (globalLoader) {
      if (loaderText) {
        loaderText.textContent = loaderDefaultText;
      }
      globalLoader.classList.add("d-none");
    }
    document.body.style.overflow = "";
  }

  function showToast(message, type = "success") {
    if (!toastContainer) {
      alert(message);
      return;
    }

    const toastEl = document.createElement("div");
    toastEl.className = `toast align-items-center text-bg-${type} border-0`;
    toastEl.role = "alert";
    toastEl.ariaLive = "assertive";
    toastEl.ariaAtomic = "true";

    toastEl.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">
          ${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                data-bs-dismiss="toast" aria-label="Cerrar"></button>
      </div>
    `;

    toastContainer.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    toastEl.addEventListener("hidden.bs.toast", () => toastEl.remove());
  }

  function setAlertFurd(msg, type = "danger") {
    if (!alertFurd) return;
    if (!msg) {
      alertFurd.classList.add("d-none");
      alertFurd.textContent = "";
      return;
    }
    alertFurd.className = `alert alert-${type}`;
    alertFurd.textContent = msg;
    alertFurd.classList.remove("d-none");
  }

  // 🔹 Helper nuevo, junto al matchDateRange
  function toIsoDate(raw) {
    if (!raw) return "";
    raw = String(raw).trim();

    // 1) Ya viene en ISO: "2026-01-13" o "2026-01-13 14:46:00"
    if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
      return raw.substring(0, 10);
    }

    // 2) Formato mostrado: "13/01/2026"
    const m = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (m) {
      const [, d, mm, yyyy] = m;
      return `${yyyy}-${mm.padStart(2, "0")}-${d.padStart(2, "0")}`;
    }

    // 3) Cualquier otra cosa, no filtramos por fecha
    return "";
  }

  function matchDateRange(valueDate, d1, d2) {
    // valueDate, d1, d2 en formato YYYY-MM-DD
    if (!valueDate) return true;
    if (!d1 && !d2) return true;

    const v = new Date(valueDate);
    const v1 = d1 ? new Date(d1) : null;
    const v2 = d2 ? new Date(d2) : null;

    if (v1 && v < v1) return false;
    if (v2 && v > v2) return false;
    return true;
  }

  // -----------------------
  // TAB 1: Registrar FURD
  // -----------------------

  // Prellenar correo_cliente con el del portal, si existe
  const correoClienteInput = document.getElementById("correo_cliente");
  if (correoClienteInput && emailPortal && !correoClienteInput.value) {
    correoClienteInput.value = emailPortal;
  }
  if (respuestaCorreo && emailPortal && !respuestaCorreo.value) {
    respuestaCorreo.value = emailPortal;
  }

  // Contadores de texto (superior / hecho)
  (function initCounters() {
    const supInput = document.getElementById("superior");
    const supCount = document.getElementById("superiorCount");
    const hecho = document.getElementById("hecho");
    const hechoCnt = document.getElementById("hechoCount");

    if (supInput && supCount) {
      const upd = () => (supCount.textContent = (supInput.value || "").length);
      supInput.addEventListener("input", upd);
      upd();
    }
    if (hecho && hechoCnt) {
      const updH = () =>
        (hechoCnt.textContent = `${(hecho.value || "").length}/5000`);
      hecho.addEventListener("input", updH);
      updH();
    }
  })();

  // Envío AJAX del FURD con progreso y barras por archivo
  if (formFurd) {
    formFurd.addEventListener("submit", (ev) => {
      ev.preventDefault();
      setAlertFurd("");

      // Validar que haya al menos una falta
      const faltas = document.querySelectorAll(".faltas-check:checked").length;
      if (faltas === 0) {
        setAlertFurd("Debes seleccionar al menos una falta.", "warning");
        return;
      }

      const btn = document.getElementById("btnEnviarFurd");
      const spinner = btn?.querySelector(".spinner-border");
      const btnText = btn?.querySelector(".btn-text");

      let sending = false;
      if (sending) return;
      sending = true;

      showLoader("Enviando FURD, por favor espera...");

      if (btn) btn.disabled = true;
      if (spinner) spinner.classList.remove("d-none");
      if (btnText) btnText.textContent = "Enviando...";

      const formData = new FormData(formFurd);

      // Construir meta y arrancar barras de progreso
      buildUploadMeta();

      const xhr = new XMLHttpRequest();
      xhr.open(formFurd.method || "POST", FURD_STORE_URL);
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

      // Progreso de subida → actualiza barras
      xhr.upload.onprogress = (evt) => {
        if (!evt.lengthComputable) return;
        updateUploadProgressBars(evt.loaded);
      };

      xhr.onload = () => {
        hideLoader();

        let data = null;
        const contentType = xhr.getResponseHeader("Content-Type") || "";

        if (contentType.includes("application/json")) {
          try {
            data = JSON.parse(xhr.responseText || "{}");
          } catch {
            data = null;
          }
        }

        // Esperamos JSON del backend
        if (data) {
          if (data.ok) {
            showToast(
              data.message || "FURD registrado correctamente.",
              "success",
            );
            formFurd.reset();
            setAlertFurd("");

            // limpiamos preview
            if (evidenciasPreview) evidenciasPreview.innerHTML = "";
            uploadFilesMeta = [];

            // refrescar lista de procesos
            procesosCargados = false;
            loadMisProcesos().catch(() => {});
          } else if (data.errors) {
            const summary = Object.values(data.errors).join(" ");
            setAlertFurd(summary, "danger");
          } else {
            setAlertFurd(data.msg || "No se pudo registrar el FURD.", "danger");
          }
        } else {
          setAlertFurd(
            "Respuesta inesperada del servidor al registrar el FURD.",
            "danger",
          );
        }

        sending = false;
        if (btn) btn.disabled = false;
        if (spinner) spinner.classList.add("d-none");
        if (btnText) btnText.textContent = "Enviar FURD";
      };

      xhr.onerror = () => {
        hideLoader();
        setAlertFurd(
          "Ocurrió un error de comunicación con el servidor.",
          "danger",
        );
        sending = false;
        if (btn) btn.disabled = false;
        if (spinner) spinner.classList.add("d-none");
        if (btnText) btnText.textContent = "Enviar FURD";
      };

      xhr.send(formData);
    });
  }

  // -----------------------
  // TAB 2: Mis procesos (data + filtros + paginador)
  // -----------------------

  function aplicarFiltrosMisProcesos() {
    if (!Array.isArray(procesosData) || !tbodyProcesos) return;

    const text = (qMis?.value || "").toLowerCase().trim();
    const est = (fEstadoMis?.value || "").toLowerCase().trim();
    const d1 = fDesdeMis?.value || "";
    const d2 = fHastaMis?.value || "";

    filteredProcesos = procesosData.filter((p) => {
      const estadoTexto = p.estado || "";
      const estadoBase = estadoTexto.split("/")[0].toLowerCase().trim();

      const fechaRaw = p.creado_en_iso || p.fecha_iso || p.fecha || "";
      const fechaCreadoIso = toIsoDate(fechaRaw);

      const blobs = [
        p.consecutivo || "",
        p.cedula || "",
        p.nombre || "",
        p.proyecto || "",
        estadoTexto || "",
      ].map((s) => (s || "").toString().toLowerCase());

      const textok = !text || blobs.join(" ").includes(text);
      const estok = !est || estadoBase === est;
      const dateok = matchDateRange(fechaCreadoIso, d1, d2);

      return textok && estok && dateok;
    });

    currentPageMis = 1;
    renderTablaMisProcesos();
  }

  function renderTablaMisProcesos() {
    if (!tbodyProcesos) return;

    tbodyProcesos.innerHTML = "";

    if (!filteredProcesos.length) {
      tbodyProcesos.innerHTML = `
        <tr>
          <td colspan="7" class="text-center text-muted py-4">
            No se encontraron procesos con los filtros seleccionados.
          </td>
        </tr>
      `;
      if (countTotalMis) countTotalMis.textContent = "0";
      if (pagerMis) pagerMis.innerHTML = "";
      return;
    }

    const total = filteredProcesos.length;
    const totalPages = Math.ceil(total / pageSizeMis) || 1;
    if (currentPageMis > totalPages) currentPageMis = totalPages;

    const start = (currentPageMis - 1) * pageSizeMis;
    const end = start + pageSizeMis;
    const pageItems = filteredProcesos.slice(start, end);

    pageItems.forEach((p) => {
      const tr = document.createElement("tr");
      tr.classList.add("cursor-pointer");
      tr.dataset.consecutivo = p.consecutivo;
      tr.tabIndex = 0;
      tr.setAttribute("role", "button");

      const estadoTexto = p.estado || "";
      const estadoBase = estadoTexto.split("/")[0].toLowerCase().trim();

      let badgeClass = "badge bg-light text-dark fw-semibold px-3 py-2";
      switch (estadoBase) {
        case "abierto":
          badgeClass =
            "badge bg-success-subtle text-success fw-semibold px-3 py-2";
          break;
        case "en proceso":
          badgeClass =
            "badge bg-warning-subtle text-warning fw-semibold px-3 py-2";
          break;
        case "cerrado":
          badgeClass =
            "badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2";
          break;
        case "archivado":
          badgeClass =
            "badge bg-danger-subtle text-danger fw-semibold px-3 py-2";
          break;
      }

      const fechaCreadoIso = (p.creado_en_iso || p.fecha_iso || "").substring(
        0,
        10,
      );

      tr.innerHTML = `
        <td data-key="consecutivo">${p.consecutivo}</td>
        <td data-key="cedula" class="text-mono">${p.cedula || ""}</td>
        <td data-key="nombre">${p.nombre || ""}</td>
        <td data-key="proyecto">${p.proyecto || ""}</td>
        <td data-key="fecha" data-fecha-creado="${fechaCreadoIso}">
          ${p.fecha || ""}
        </td>
        <td data-key="estado" data-estado="${estadoBase}">
          <span class="${badgeClass}">${(
            estadoTexto || ""
          ).toUpperCase()}</span>
        </td>
        <td data-key="actualizado">${p.actualizado_en || ""}</td>
      `;

      const abrirTimeline = () => {
        ultimoConsecutivoSeleccionado = p.consecutivo;
        loadTimelineFor(p.consecutivo);
        const tabBtn = document.querySelector("#tab-timeline-tab");
        if (tabBtn) {
          const tab = new bootstrap.Tab(tabBtn);
          tab.show();
        }
      };

      tr.addEventListener("click", abrirTimeline);
      tr.addEventListener("keydown", (ev) => {
        if (ev.key === "Enter" || ev.key === " ") {
          ev.preventDefault();
          abrirTimeline();
        }
      });

      tbodyProcesos.appendChild(tr);
    });

    if (countTotalMis) countTotalMis.textContent = String(total);
    renderPagerMisProcesos(totalPages);
  }

  function renderPagerMisProcesos(totalPages) {
    if (!pagerMis) return;

    pagerMis.innerHTML = "";
    if (totalPages <= 1) return;

    const nav = document.createElement("nav");
    const ul = document.createElement("ul");
    ul.className = "pagination pagination-sm mb-0";

    const addItem = (label, page, disabled, active) => {
      const li = document.createElement("li");
      li.className = "page-item";
      if (disabled) li.classList.add("disabled");
      if (active) li.classList.add("active");

      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "page-link";
      btn.textContent = label;

      if (!disabled) {
        btn.addEventListener("click", () => {
          currentPageMis = page;
          renderTablaMisProcesos();
        });
      }

      li.appendChild(btn);
      ul.appendChild(li);
    };

    addItem("«", Math.max(1, currentPageMis - 1), currentPageMis === 1, false);

    for (let p = 1; p <= totalPages; p++) {
      addItem(String(p), p, false, p === currentPageMis);
    }

    addItem(
      "»",
      Math.min(totalPages, currentPageMis + 1),
      currentPageMis === totalPages,
      false,
    );

    nav.appendChild(ul);
    pagerMis.appendChild(nav);
  }

  // Carga AJAX de procesos + enganche de filtros
  async function loadMisProcesos() {
    if (!tbodyProcesos) return;

    if (!emailPortal) {
      msgProcesos.className = "alert alert-warning";
      msgProcesos.textContent =
        "No se encontró el correo del cliente. Asegúrate de abrir el portal con el parámetro ?email=...";
      msgProcesos.classList.remove("d-none");
      return;
    }

    msgProcesos.classList.add("d-none");

    tbodyProcesos.innerHTML = `
      <tr><td colspan="7" class="text-center py-4 text-muted">
        Cargando procesos...
      </td></tr>
    `;

    try {
      const url = `${PORTAL_BASE_URL}/mis-procesos?email=${encodeURIComponent(
        emailPortal,
      )}`;

      const resp = await fetch(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const data = await resp.json();

      if (!data.ok) {
        tbodyProcesos.innerHTML = `
          <tr><td colspan="7" class="text-center py-4 text-muted">
            ${data.msg || "No fue posible obtener los procesos."}
          </td></tr>
        `;
        procesosData = [];
        filteredProcesos = [];
        if (countTotalMis) countTotalMis.textContent = "0";
        if (pagerMis) pagerMis.innerHTML = "";
        return;
      }

      const procesos = data.procesos || [];

      if (!procesos.length) {
        tbodyProcesos.innerHTML = `
          <tr><td colspan="7" class="text-center py-4 text-muted">
            No se encontraron procesos asociados a este correo.
          </td></tr>
        `;
        procesosData = [];
        filteredProcesos = [];
        if (countTotalMis) countTotalMis.textContent = "0";
        if (pagerMis) pagerMis.innerHTML = "";
        return;
      }

      procesosData = procesos;
      aplicarFiltrosMisProcesos();

      if (msgProcesos) {
        msgProcesos.className = "small text-muted mt-2";
        msgProcesos.textContent =
          "Haz clic en cualquier proceso para ver la línea de tiempo y responder a la decisión, si aplica.";
        msgProcesos.classList.remove("d-none");
      }

      procesosCargados = true;
    } catch (e) {
      console.error(e);
      tbodyProcesos.innerHTML = `
        <tr><td colspan="7" class="text-center py-4 text-muted">
          Error al consultar los procesos.
        </td></tr>
      `;
      procesosData = [];
      filteredProcesos = [];
      if (countTotalMis) countTotalMis.textContent = "0";
      if (pagerMis) pagerMis.innerHTML = "";
    }
  }

  // Inicializar filtros de Mis Procesos (eventos + flatpickr)
  (function initMisProcesosFiltros() {
    if (!tbodyProcesos) return;

    qMis?.addEventListener("input", aplicarFiltrosMisProcesos);
    fEstadoMis?.addEventListener("change", aplicarFiltrosMisProcesos);
    fDesdeMis?.addEventListener("change", aplicarFiltrosMisProcesos);
    fHastaMis?.addEventListener("change", aplicarFiltrosMisProcesos);

    btnLimpiarMis?.addEventListener("click", () => {
      if (qMis) qMis.value = "";
      if (fEstadoMis) fEstadoMis.value = "";
      if (fDesdeMis) {
        if (fDesdeMis._flatpickr) fDesdeMis._flatpickr.clear();
        fDesdeMis.value = "";
      }
      if (fHastaMis) {
        if (fHastaMis._flatpickr) fHastaMis._flatpickr.clear();
        fHastaMis.value = "";
      }
      aplicarFiltrosMisProcesos();
    });

    // Flatpickr (igual estilo que en seguimiento administrativo)
    if (typeof flatpickr !== "undefined") {
      const baseConfig = {
        locale: flatpickr.l10ns.es || "es",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        allowInput: false,
        disableMobile: true,
        monthSelectorType: "static",
        yearSelectorType: "dropdown",
      };

      let fpHasta;
      const fpDesde = flatpickr("#fDesdeMisProcesos", {
        ...baseConfig,
        onChange(selectedDates) {
          if (fpHasta && selectedDates[0]) {
            fpHasta.set("minDate", selectedDates[0]);
          }
        },
      });

      fpHasta = flatpickr("#fHastaMisProcesos", {
        ...baseConfig,
        onChange(selectedDates) {
          if (fpDesde && selectedDates[0]) {
            fpDesde.setDate(selectedDates[0], false);
            fpDesde.set("maxDate", selectedDates[0]);
          }
        },
      });
    }
  })();

  // -----------------------
  // TAB 3: Timeline + respuesta
  // -----------------------

  function escapeHtml(str) {
    return (str || "")
      .toString()
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function escapeAttr(str) {
    return escapeHtml(str).replace(/\n/g, "&#10;");
  }

  function formatFechaHora(str) {
    if (!str) return "";
    const s = str.replace(" ", "T");
    const d = new Date(s);
    if (Number.isNaN(d.getTime())) return str;
    return d.toLocaleString("es-CO", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  async function loadTimelineFor(consecutivo) {
    if (!timelineContent || !timelineHeader) return;

    // Placeholder mientras carga
    timelineHeader.innerHTML = `
    <div>
      <h5 class="mb-1">
        <i class="bi bi-activity text-success me-2"></i>
        Línea temporal
      </h5>
      <div class="small text-muted">Cargando información…</div>
    </div>
  `;
    timelineContent.innerHTML = `
    <div class="card animate-in">
      <div class="card-body text-center text-muted py-5">
        Cargando línea de tiempo…
      </div>
    </div>
  `;

    try {
      const url =
        `${PORTAL_BASE_URL}/furd/${encodeURIComponent(consecutivo)}/timeline` +
        `?email=${encodeURIComponent(emailPortal)}`;

      console.log("URL timeline =>", url); // opcional para verificar

      const resp = await fetch(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      if (!resp.ok) {
        throw new Error("No se pudo cargar la línea de tiempo");
      }

      const data = (await resp.json()) || {};
      console.log("DATA timeline portal =>", data); // solo depuración

      const proc = data.proceso || {};
      const items = Array.isArray(data.items)
        ? data.items
        : Array.isArray(data.etapas)
          ? data.etapas
          : Array.isArray(data.timeline)
            ? data.timeline
            : [];

      // ===== Encabezado igual al administrativo =====
      // ===== Encabezado adaptado a móvil (chips) =====
      timelineHeader.innerHTML = `
        <div class="timeline-header-inner">
          <h5 class="mb-1">
            <i class="bi bi-activity text-success me-2"></i>
            Línea temporal
          </h5>
          <div class="th-chips">
            <span class="th-chip">
              <span class="th-label">Consecutivo</span>
              <span class="th-value text-mono fw-semibold">
                ${proc.consecutivo || "-"}
              </span>
            </span>
            <span class="th-chip">
              <span class="th-label">Cédula</span>
              <span class="th-value text-mono">
                ${proc.cedula || "-"}
              </span>
            </span>
            <span class="th-chip">
              <span class="th-label">Nombre</span>
              <span class="th-value">
                ${proc.nombre || "-"}
              </span>
            </span>
            <span class="th-chip">
              <span class="th-label">Proyecto</span>
              <span class="th-value">
                ${proc.proyecto || "-"}
              </span>
            </span>
            <span class="th-chip">
              <span class="th-label">Estado</span>
              <span class="th-value fw-semibold">
                ${proc.estado || "-"}
              </span>
            </span>
          </div>
        </div>
      `;

      // ===== Contenido principal (misma card + timeline) =====
      if (!items.length) {
        timelineContent.innerHTML = `
        <div class="card animate-in">
          <div class="card-body text-center text-muted py-5">
            Sin información de etapas.
          </div>
        </div>
      `;
      } else {
        const card = document.createElement("div");
        card.className = "card animate-in";

        const body = document.createElement("div");
        body.className = "card-body";

        const timeline = document.createElement("div");
        timeline.className = "timeline";

        items.forEach((rawEtapa, index) => {
          const etapa = rawEtapa || {};
          const isLast = index === items.length - 1;

          // 👇 Normalizamos una sola vez
          const rawClave = (etapa.clave || "").toString().toLowerCase();
          const clave = rawClave
            .normalize("NFD") // quita tildes
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[\s_]+/g, "-");

          const itemEl = document.createElement("div");
          itemEl.className = `tl-item ${clave} ${etapa.fecha ? "done" : ""}`;

          // Nodo (punto + fecha)
          const nodeEl = document.createElement("div");
          nodeEl.className = "tl-node";
          nodeEl.innerHTML = `
          <span class="tl-dot"></span>
          <span class="tl-date text-mono">${etapa.fecha || "—"}</span>
        `;
          itemEl.appendChild(nodeEl);

          // Tarjeta de contenido
          const cardEl = document.createElement("div");
          cardEl.className = "tl-card";

          const badgeHtml = etapa.fecha
            ? '<span class="badge bg-success-subtle text-success">Completado</span>'
            : '<span class="badge bg-warning-subtle text-warning">Pendiente</span>';

          cardEl.innerHTML = `
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0 text-success fw-semibold">${
              etapa.titulo || "Etapa"
            }</h6>
            ${badgeHtml}
          </div>
        `;

          // --- Contenido de la etapa ---
          const isSoporte = etapa.clave === "soporte";
          const isCitacion = etapa.clave === "citacion";
          const partes = [];

          // Bloque especial para SOPORTE (misma lógica que en la vista admin)
          if (isSoporte) {
            const estadoCliente = etapa.cliente_estado || "pendiente";
            const respondidoAt = etapa.cliente_respondido || null;

            const decOriginal =
              etapa.decision_propuesta ||
              (etapa.meta && etapa.meta["Decisión propuesta"]) ||
              null;

            const decCliente = etapa.cliente_decision || null;
            const justOrig = etapa.justificacion_original || null;
            const justCliente = etapa.cliente_justificacion || null;
            const comentario = etapa.cliente_comentario || null;
            const urlRevision = etapa.url_revision || null;

            // Fechas de suspensión que ahora vienen separadas
            const fechaSuspIni = etapa.cliente_fecha_inicio_suspension || null;
            const fechaSuspFin = etapa.cliente_fecha_fin_suspension || null;

            const hayCambiosDecision =
              !!decCliente && !!decOriginal && decCliente !== decOriginal;
            const hayCambiosJustif =
              !!justCliente && !!justOrig && justCliente !== justOrig;

            let html = `
    <div class="mb-3">
      <p class="mb-1">
        <strong>Decisión propuesta:</strong>
        ${decOriginal || "—"}
      </p>
  `;

            if (justOrig) {
              html += `
      <p class="mb-2 small">
        <strong>Justificación original:</strong><br>
        <span style="white-space: pre-line;">${justOrig}</span>
      </p>
    `;
            }

            if (estadoCliente === "pendiente") {
              // 🔸 Cliente aún no responde
              html += `
      <div class="alert alert-warning small mb-2">
        <i class="bi bi-hourglass-split me-1"></i>
        A la espera de respuesta del cliente sobre la decisión propuesta.
      </div>
    `;

              // 👇 Aquí NO se muestran fechas de suspensión todavía (solo cuando ya respondió)

              if (urlRevision) {
                html += `
        <div class="mt-2">
          <a href="${urlRevision}"
             class="btn btn-sm btn-success"
             target="_blank"
             rel="noopener">
            Revisar y responder a la propuesta
          </a>
          <div class="form-text small text-muted mt-1">
            Se abrirá un formulario seguro para registrar tu respuesta.
          </div>
        </div>
      `;
              }
            } else {
              // 🔸 Cliente ya respondió (aprobó o rechazó)
              const badgeEstado =
                estadoCliente === "aprobado" ? "success" : "danger";
              const txtEstado =
                estadoCliente === "aprobado"
                  ? "Cliente APROBÓ"
                  : "Cliente RECHAZÓ";

              html += `
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-${badgeEstado}">${txtEstado}</span>
        ${
          respondidoAt
            ? `<small class="text-muted">el ${respondidoAt}</small>`
            : ""
        }
      </div>
    `;

              // Si es una suspensión y ya tenemos rango de fechas, se muestra aquí
              if (fechaSuspIni) {
                html += `
        <p class="small mb-2">
          <strong>Período de suspensión acordado:</strong><br>
          Desde ${fechaSuspIni}${fechaSuspFin ? ` hasta ${fechaSuspFin}` : ""}
        </p>
      `;
              }

              if (hayCambiosDecision || hayCambiosJustif) {
                html += `
        <div class="alert alert-info small mb-2">
          <div class="fw-semibold mb-1">
            <i class="bi bi-pencil-square me-1"></i>
            Cambios sugeridos por el cliente
          </div>
      `;

                if (hayCambiosDecision) {
                  html += `
          <div class="mb-2">
            <span class="text-muted">Decisión original:</span>
            <span class="text-decoration-line-through">${decOriginal}</span><br>
            <span class="text-muted">Decisión ajustada:</span>
            <span class="fw-semibold">${decCliente}</span>
          </div>
        `;
                }

                if (hayCambiosJustif) {
                  html += `
          <div>
            <span class="text-muted">Justificación ajustada por el cliente:</span>
            <span class="fw-semibold">${justCliente}</span>
          </div>
        `;
                }

                html += `</div>`;
              } else {
                html += `
        <div class="alert alert-success small mb-2">
          <i class="bi bi-hand-thumbs-up me-1"></i>
          El cliente aprobó la decisión sin solicitar cambios.
        </div>
      `;
              }

              if (comentario) {
                html += `
        <div class="small text-muted">
          <span class="fw-semibold">Comentario del cliente:</span><br>
          <span style="white-space: pre-line;">${comentario}</span>
        </div>
      `;
              }
            }

            html += `</div>`;
            partes.push(html);
          } else if (isCitacion) {
            // ==========================
            // BLOQUE ESPECIAL CITACIÓN
            // ==========================
            const hist = Array.isArray(etapa.citaciones)
              ? etapa.citaciones
              : [];

            if (hist.length) {
              // ✅ Hay historial: mostramos SOLO la línea de tiempo de citaciones
              const vigente = hist[hist.length - 1];

              const fechaVig =
                etapa.meta?.["Fecha citación vigente"] || vigente.fecha || "—";
              const horaVig =
                etapa.meta?.["Hora citación vigente"] || vigente.hora || "—";
              const numVig = vigente.numero || hist.length;

              let html = `
      <div class="tl-citacion-wrap mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
          <div>
            <div class="fw-semibold">Historial de citaciones</div>
            <div class="small text-muted">
              Visualiza la secuencia de citaciones y la que está vigente.
            </div>
          </div>
          <div class="text-end small tl-citacion-vigente">
            <div class="text-muted text-uppercase">Citación vigente</div>
            <div class="fw-semibold text-success">
              #${numVig} · ${fechaVig}${horaVig ? " · " + horaVig : ""}
            </div>
          </div>
        </div>
        <div class="tl-citaciones-list">
    `;

              hist.forEach((c, idx) => {
                const isLast = idx === hist.length - 1;
                const vigenteC = idx === hist.length - 1;

                html += `
        <div class="tl-citacion-item ${vigenteC ? "is-current" : ""}">
          <div class="tl-citacion-line">
            <span class="tl-citacion-dot"></span>
            ${!isLast ? '<span class="tl-citacion-spine"></span>' : ""}
          </div>
          <div class="tl-citacion-card">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge bg-light text-body fw-semibold">
                  Citación #${c.numero ?? idx + 1}
                </span>
                ${
                  c.medio
                    ? `<span class="badge bg-primary-subtle text-primary">${c.medio}</span>`
                    : ""
                }
                ${
                  vigenteC
                    ? '<span class="badge bg-success-subtle text-success">Vigente</span>'
                    : ""
                }
              </div>
              <div class="small text-muted text-end">
                ${c.fecha || "—"}${c.hora ? " · " + c.hora : ""}
              </div>
            </div>
            <div class="small mb-1">
              <span class="text-muted text-uppercase">Motivo</span><br>
              ${c.motivo || "—"}
            </div>
            ${
              c.motivo_recitacion
                ? `<div class="small">
                     <span class="text-muted text-uppercase">Motivo de nueva citación</span><br>
                     ${c.motivo_recitacion}
                   </div>`
                : ""
            }
          </div>
        </div>
      `;
              });

              html += `
        </div>
      </div>
    `;

              partes.push(html);

              // 👈 IMPORTANTE: aquí ya NO renderizamos bloque "Detalle",
              // toda la info ya está en el historial.
            } else {
              // ❗ No hay citaciones: usamos el bloque genérico de detalle
              const short = etapa.detalle || "";
              const full = etapa.detalle_full || short;
              const showButton = full && full !== short;

              if (short || full) {
                let detalleHtml = `
        <p class="mb-3 tl-detalle-text">
          <strong>Detalle:</strong>
          <span class="tl-detalle-resumen">${short}</span>
      `;

                if (showButton) {
                  detalleHtml += `
          <button
            type="button"
            class="btn btn-sm tl-detalle-ver-mas ms-2"
            data-bs-toggle="modal"
            data-bs-target="#modalDetalleEtapa"
            data-detalle-full="${escapeAttr(full)}"
            data-etapa="${escapeAttr(etapa.titulo || "Detalle")}">
            <span class="tl-detalle-pill-icon">
              <i class="bi bi-arrows-fullscreen"></i>
            </span>
            <span class="tl-detalle-pill-text">Ver completo</span>
          </button>
        `;
                }

                detalleHtml += `</p>`;
                partes.push(detalleHtml);
              }
            }
          } else {
            // ==========================
            // BLOQUE GENÉRICO (tal cual)
            // ==========================
            const short = etapa.detalle || "";
            const full = etapa.detalle_full || short;

            if (short || full) {
              const showButton = full && full !== short;

              let detalleHtml = `
      <p class="mb-3 tl-detalle-text">
        <strong>Detalle:</strong>
        <span class="tl-detalle-resumen">${short}</span>
    `;

              if (showButton) {
                detalleHtml += `
        <button
          type="button"
          class="btn btn-sm tl-detalle-ver-mas ms-2"
          data-bs-toggle="modal"
          data-bs-target="#modalDetalleEtapa"
          data-detalle-full="${escapeAttr(full)}"
          data-etapa="${escapeAttr(etapa.titulo || "Detalle")}">
          <span class="tl-detalle-pill-icon">
            <i class="bi bi-arrows-fullscreen"></i>
          </span>
          <span class="tl-detalle-pill-text">Ver completo</span>
        </button>
      `;
              }

              detalleHtml += `</p>`;
              partes.push(detalleHtml);
            }
          }

          // Faltas asociadas
          const faltas = Array.isArray(etapa.faltas) ? etapa.faltas : [];
          if (faltas.length) {
            const faltasHtml = faltas
              .map(
                (f) => `
                <li class="list-group-item px-0 py-1">
                  <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                  <strong>${f.codigo || ""}</strong> –
                  <span class="text-muted">${f.gravedad || ""}</span>:
                  ${f.desc || ""}
                </li>
              `,
              )
              .join("");

            partes.push(`
            <div class="mb-3">
              <strong>Faltas asociadas:</strong>
              <ul class="list-group list-group-flush small mt-1">
                ${faltasHtml}
              </ul>
            </div>
          `);
          }

          // Meta (datos extra)
          const meta =
            etapa.meta && typeof etapa.meta === "object" ? etapa.meta : {};
          const metaEntries = Object.entries(meta);
          if (metaEntries.length) {
            const metaHtml = metaEntries
              .map(
                ([k, v]) => `
                <dt class="col-sm-3 text-muted">${k}</dt>
                <dd class="col-sm-9">${v}</dd>
              `,
              )
              .join("");

            partes.push(`
            <dl class="row small mb-3">
              ${metaHtml}
            </dl>
          `);
          }

          // Adjuntos
          const adjuntos = Array.isArray(etapa.adjuntos) ? etapa.adjuntos : [];
          if (adjuntos.length) {
            const adjHtml = adjuntos
              .map((a) => {
                const nombre = a.nombre || a.filename || "Adjunto";
                const openUrl =
                  a.url_open || a.open_url || a.openUrl || a.url || "#";
                const downloadUrl =
                  a.url_download || a.download_url || a.downloadUrl || openUrl;

                const isDrive =
                  (a.provider || a.source || "").toString().toLowerCase() ===
                  "gdrive";

                return `
                <li class="tl-attach-item">
                  <div class="tl-attach-name text-truncate">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    <span class="tl-attach-filename text-truncate">${nombre}</span>
                    ${
                      isDrive
                        ? '<span class="badge bg-info-subtle text-info ms-1">Drive</span>'
                        : ""
                    }
                  </div>
                  <div class="tl-attach-actions">
                    <a href="${openUrl}" target="_blank" rel="noopener"
                       class="btn btn-xs btn-outline-secondary" title="Abrir">
                      <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <a href="${downloadUrl}"
                       class="btn btn-xs btn-outline-primary btn-download"
                       data-loading="Preparando descarga…">
                      <i class="bi bi-download"></i>
                    </a>
                  </div>
                </li>
              `;
              })
              .join("");

            partes.push(`
            <div class="small">
              <i class="bi bi-paperclip me-1"></i><strong>Adjuntos:</strong>
              <ul class="list-unstyled ms-3 mt-2 tl-attach-list">
                ${adjHtml}
              </ul>
            </div>
          `);
          }

          if (partes.length) {
            cardEl.insertAdjacentHTML(
              "beforeend",
              partes.join('<div class="tl-section-separator"></div>'),
            );
          }

          itemEl.appendChild(cardEl);

          // Spine (línea vertical) salvo en el último
          if (!isLast) {
            const spine = document.createElement("div");
            spine.className = "tl-spine";
            itemEl.appendChild(spine);
          }

          timeline.appendChild(itemEl);
        });

        body.appendChild(timeline);
        card.appendChild(body);

        timelineContent.innerHTML = "";
        timelineContent.appendChild(card);
      }
    } catch (err) {
      console.error("Error cargando timeline:", err);
      timelineHeader.innerHTML = `
      <div>
        <h5 class="mb-1">
          <i class="bi bi-activity text-danger me-2"></i>
          Línea temporal
        </h5>
        <div class="small text-muted">No fue posible cargar la información.</div>
      </div>
    `;
      timelineContent.innerHTML = `
      <div class="card animate-in">
        <div class="card-body text-center text-muted py-5">
          Ocurrió un error al cargar la línea de tiempo.
        </div>
      </div>
    `;
    }
  }

  function renderPortalTimeline(items) {
    if (!timelineContent) return;

    if (!items.length) {
      timelineContent.innerHTML = `
        <div class="text-center text-muted py-4">
          Sin información de etapas.
        </div>
      `;
      return;
    }

    let html = '<div class="timeline">';

    items.forEach((it, idx) => {
      const isLast = idx === items.length - 1;
      const clave = (it.clave || "").toLowerCase().replace(/[\s_]+/g, "-");
      const isDone = it.estado === "completado" || !!it.fecha;
      const badgeCls = isDone
        ? "bg-success-subtle text-success"
        : "bg-warning-subtle text-warning";
      const badgeTxt = isDone ? "Completado" : "Pendiente";

      const fecha = escapeHtml(it.fecha || "");
      const titulo = escapeHtml(it.titulo || "Etapa");
      const detalleFull = it.detalle_full || it.detalle || "";
      const detalleHtml = escapeHtml(detalleFull).replace(/\n/g, "<br>");

      let bodyHtml = "";

      if (it.clave === "soporte") {
        const estadoCliente = it.cliente_estado || "pendiente";
        const respondido = formatFechaHora(it.cliente_respondido);
        const comentario = it.cliente_comentario || "";

        bodyHtml += `
          <p class="mb-3 tl-detalle-text">
            <strong>Detalle:</strong>
            <span class="tl-detalle-resumen">${detalleHtml}</span>
          </p>
        `;

        if (estadoCliente === "pendiente") {
          bodyHtml += `
            <div class="alert alert-warning small mb-0">
              <i class="bi bi-hourglass-split me-1"></i>
              A la espera de respuesta del cliente sobre la decisión propuesta.
            </div>
          `;
        } else {
          const badgeEstado =
            estadoCliente === "aprobado" ? "success" : "danger";
          const txtEstado =
            estadoCliente === "aprobado" ? "Cliente APROBÓ" : "Cliente RECHAZÓ";

          bodyHtml += `
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="badge bg-${badgeEstado}">${txtEstado}</span>
              ${
                respondido
                  ? `<small class="text-muted">el ${escapeHtml(
                      respondido,
                    )}</small>`
                  : ""
              }
            </div>
          `;

          if (comentario) {
            bodyHtml += `
              <div class="small text-muted">
                <span class="fw-semibold">Comentario del cliente:</span><br>
                <span style="white-space: pre-line;">${escapeHtml(
                  comentario,
                )}</span>
              </div>
            `;
          }
        }
      } else {
        bodyHtml = `
          <p class="mb-0 tl-detalle-text">
            <strong>Detalle:</strong>
            <span class="tl-detalle-resumen">${detalleHtml}</span>
          </p>
        `;
      }

      html += `
        <div class="tl-item ${clave} ${isDone ? "done" : ""}">
          <div class="tl-node">
            <span class="tl-dot"></span>
            <span class="tl-date text-mono">${fecha || "—"}</span>
          </div>
          <div class="tl-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="mb-0 text-success fw-semibold">${titulo}</h6>
              <span class="badge ${badgeCls}">${badgeTxt}</span>
            </div>
            ${bodyHtml}
          </div>
          ${!isLast ? '<div class="tl-spine"></div>' : ""}
        </div>
      `;
    });

    html += "</div>";
    timelineContent.innerHTML = html;
  }

  // -----------------------
  // Eventos de tabs
  // -----------------------

  // ✅ Refrescar SOLO "Mis procesos" y quedarse en esa pestaña
  (function initBtnPortalRefresh() {
    const btnRefresh = document.getElementById("btnPortalRefresh");
    const tabMisBtn = document.getElementById("tab-mis-procesos-tab");

    if (!btnRefresh) return;

    btnRefresh.addEventListener("click", async () => {
      // 1) Mantener/abrir la pestaña Mis procesos
      if (tabMisBtn) {
        const tab = new bootstrap.Tab(tabMisBtn);
        tab.show();
      }

      // 2) Reconsultar datos
      showLoader("Actualizando procesos...");
      try {
        // fuerza recarga aunque ya estuviera marcado como cargado
        procesosCargados = false;
        await loadMisProcesos();

        showToast("Procesos actualizados.", "success");
      } catch (e) {
        console.error(e);
        showToast("No se pudieron actualizar los procesos.", "danger");
      } finally {
        hideLoader();
      }
    });
  })();

  const tabMisProcesosBtn = document.getElementById("tab-mis-procesos-tab");
  const tabTimelineBtn = document.getElementById("tab-timeline-tab");

  if (tabMisProcesosBtn) {
    tabMisProcesosBtn.addEventListener("shown.bs.tab", () => {
      if (!procesosCargados) loadMisProcesos();
    });
  }

  if (tabTimelineBtn) {
    tabTimelineBtn.addEventListener("shown.bs.tab", () => {
      if (ultimoConsecutivoSeleccionado) {
        loadTimelineFor(ultimoConsecutivoSeleccionado);
      } else {
        timelineHeader.textContent =
          'Selecciona un proceso en la pestaña "Mis procesos".';
        timelineContent.innerHTML = "";
        if (respuestaWrap) {
          respuestaWrap.classList.add("d-none");
        }
      }
    });
  }

  // Si se pasó un consecutivo en la URL, intentamos cargar de una vez
  if (consecInicial && emailPortal) {
    loadMisProcesos().then(() => {
      ultimoConsecutivoSeleccionado = consecInicial;
      loadTimelineFor(consecInicial);
      const tabBtn = document.querySelector("#tab-timeline-tab");
      if (tabBtn) {
        const tab = new bootstrap.Tab(tabBtn);
        tab.show();
      }
    });
  }

  // -----------------------
  // Buscar empleado por cédula (lupa)
  // -----------------------
  const cedulaInput = document.getElementById("cedula");
  const btnBuscarEmp = document.getElementById("btnBuscarEmpleado");
  const iconBuscarEmp = btnBuscarEmp?.querySelector("i");

  const fillField = (id, value) => {
    const el = document.getElementById(id);
    if (el) el.value = value ?? "";
  };

  async function buscarEmpleadoPortal() {
    if (!cedulaInput) return;

    const ced = cedulaInput.value.trim();
    if (!ced) {
      showToast("Por favor ingresa una cédula antes de buscar.", "warning");
      return;
    }

    if (!baseLookupUrl) {
      console.warn(
        "baseLookupUrl no está definido. Configura data-lookup-url en #portalClienteRoot o BASE_LOOKUP_URL global.",
      );
      showToast("No se pudo realizar la búsqueda del empleado.", "danger");
      return;
    }

    if (iconBuscarEmp) {
      iconBuscarEmp.classList.remove("bi-search");
      iconBuscarEmp.classList.add("bi-arrow-repeat", "spin");
    }
    if (btnBuscarEmp) btnBuscarEmp.disabled = true;
    cedulaInput.classList.add("loading");

    try {
      const resp = await fetch(`${baseLookupUrl}/${encodeURIComponent(ced)}`);

      let data = null;
      try {
        data = await resp.json();
      } catch {
        data = null;
      }

      if (!resp.ok) {
        if (resp.status === 404) {
          fillField("nombre_completo", "");
          fillField("expedida_en", "");
          fillField("empresa_usuaria", "");
          fillField("correo", "");
          showToast("Empleado no encontrado.", "warning");
        } else {
          showToast("Error al buscar el empleado.", "danger");
        }
        return;
      }

      if (!data) {
        showToast("Error al buscar el empleado.", "danger");
        fillField("nombre_completo", "");
        fillField("expedida_en", "");
        fillField("empresa_usuaria", "");
        fillField("correo", "");
        return;
      }

      if (data.found && data.empleado) {
        fillField("nombre_completo", data.empleado.nombre_completo || "");
        fillField("expedida_en", data.empleado.ciudad_expide ?? "");
        fillField("correo", data.empleado.correo ?? "");

        if (data.contrato_activo) {
          fillField(
            "empresa_usuaria",
            data.contrato_activo.empresa_usuaria ?? "",
          );
          showToast("Empleado y contrato activo encontrados.", "success");
        } else {
          fillField("empresa_usuaria", "");
          showToast(
            "Empleado encontrado, pero sin contrato activo.",
            "warning",
          );
        }
      } else {
        fillField("nombre_completo", "");
        fillField("expedida_en", "");
        fillField("empresa_usuaria", "");
        fillField("correo", "");
        showToast("Empleado no encontrado.", "warning");
      }
    } catch (err) {
      console.error(err);
      showToast("Error al buscar el empleado.", "danger");
      fillField("nombre_completo", "");
      fillField("expedida_en", "");
      fillField("empresa_usuaria", "");
      fillField("correo", "");
    } finally {
      if (iconBuscarEmp) {
        iconBuscarEmp.classList.remove("bi-arrow-repeat", "spin");
        iconBuscarEmp.classList.add("bi-search");
      }
      if (btnBuscarEmp) btnBuscarEmp.disabled = false;
      cedulaInput.classList.remove("loading");
    }
  }

  btnBuscarEmp?.addEventListener("click", buscarEmpleadoPortal);
  cedulaInput?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      buscarEmpleadoPortal();
    }
  });

  // Modal "Ver completo" para detalle de etapas
  (function initModalDetalleEtapa() {
    const modalEl = document.getElementById("modalDetalleEtapa");
    if (!modalEl) return;

    const modalBody = document.getElementById("modalDetalleEtapaTexto");
    const modalTitle = document.getElementById("modalDetalleEtapaTitulo");

    modalEl.addEventListener("show.bs.modal", function (event) {
      const button = event.relatedTarget;
      if (!button) return;

      const full = button.getAttribute("data-detalle-full") || "(Sin texto)";
      const titulo = button.getAttribute("data-etapa") || "Detalle";

      modalTitle.textContent = titulo;
      modalBody.textContent = full;
    });
  })();

  // Loader para descargas de adjuntos en el portal
  (function initDownloadLoaderPortal() {
    if (!globalLoader) return;

    document.addEventListener("click", function (e) {
      const btn = e.target.closest(".btn-download");
      if (!btn) return;

      // Texto específico para la descarga (tomas el de data-loading si existe)
      const loadingMsg =
        btn.getAttribute("data-loading") ||
        "Preparando descarga, por favor espera...";

      btn.classList.add("disabled");
      btn.setAttribute("aria-disabled", "true");

      showLoader(loadingMsg);

      // Fallback: ocultar loader si el navegador no cierra la pestaña / no dispara navegación
      setTimeout(() => {
        hideLoader();
        btn.classList.remove("disabled");
        btn.removeAttribute("aria-disabled");
      }, 12000);
    });
  })();

  // Botones de ayuda (info) para los labels con .btn-info-help
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
      customClass: {
        popup: "swal2-popup-help",
      },
    });
  });

  // =============================
  // Evidencias: preview + validación
  // =============================
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

  // Renderiza la lista de archivos con barra de progreso en 0%
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

  // Valida tipo y tamaño, y vuelve a asignar el FileList aceptado
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

  // Quitar archivo individual desde el preview
  evidenciasPreview?.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-remove-file");
    if (!btn || !evidenciasInput) return;

    const idx = parseInt(btn.dataset.fileIdx, 10);
    if (Number.isNaN(idx)) return;

    const dt = new DataTransfer();
    Array.from(evidenciasInput.files || []).forEach((file, i) => {
      if (i !== idx) dt.items.add(file);
    });

    evidenciasInput.files = dt.files;
    renderEvidenciasPreview();

    if (!dt.files.length) {
      showToast("Se han quitado todas las evidencias seleccionadas.", "info");
    }
  });

  // Meta para progreso por archivo
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

    return offset; // total bytes (por si lo necesitas)
  };

  const updateUploadProgressBars = (loaded) => {
    if (!uploadFilesMeta.length || !evidenciasPreview) return;

    uploadFilesMeta.forEach((meta) => {
      const bar = evidenciasPreview.querySelector(
        `.progress-bar[data-file-idx="${meta.index}"]`,
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

})();
