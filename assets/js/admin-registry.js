/* CFDev — Fields registry admin page */
(function () {

    /* ── Tab switching ─────────────────────────────────────── */
    const tabs   = document.querySelectorAll(".cfdev-tabs-nav .nav-tab");
    const panels = document.querySelectorAll(".cfdev-tab-panel");
    tabs.forEach(function (tab) {
        tab.addEventListener("click", function (e) {
            e.preventDefault();
            const target = this.getAttribute("href");
            panels.forEach(function (p) { p.hidden = true; });
            document.querySelector(target).hidden = false;
            tabs.forEach(function (t) { t.classList.remove("nav-tab-active"); });
            this.classList.add("nav-tab-active");
        });
    });

    /* ── Group expand / collapse ───────────────────────────── */
    document.querySelectorAll(".cfdev-group-header").forEach(function (header) {
        function toggle() {
            const group  = header.closest(".cfdev-group");
            const body   = group.querySelector(".cfdev-group-body");
            const isOpen = !body.hidden;
            body.hidden = isOpen;
            group.classList.toggle("is-open", !isOpen);
            header.setAttribute("aria-expanded", String(!isOpen));
        }
        header.addEventListener("click", toggle);
        header.addEventListener("keydown", function (e) {
            if (e.key === "Enter" || e.key === " ") { e.preventDefault(); toggle(); }
        });
    });

    /* ── Bundle fields modal (REST page only) ─────────────── */
    const bModal = document.getElementById("cfdev-rest-bundle-modal");
    if (bModal) {
        const bKeyEl  = document.getElementById("cfdev-rest-bundle-key");
        const bBodyEl = document.getElementById("cfdev-rest-bundle-body");

        document.addEventListener("click", function (e) {
            const btn = e.target.closest(".cfdev-bundle-fields-btn");
            if (btn) {
                const key    = btn.dataset.cfdevBundleKey    || "";
                const fields = JSON.parse(btn.dataset.cfdevBundleFields || "[]");

                bKeyEl.textContent = key;

                let html = "<table class=\"widefat striped cfdev-rest-table\"><thead><tr>"
                         + "<th>Meta key</th><th>Label</th><th>REST type</th>"
                         + "</tr></thead><tbody>";

                fields.forEach(function (f) {
                    html += "<tr>"
                          + "<td><code>" + esc(f.id) + "</code></td>"
                          + "<td>" + esc(f.label) + "</td>"
                          + "<td><span class=\"cfdev-rule-badge\">" + esc(f.rest_type) + "</span></td>"
                          + "</tr>";
                });

                html += "</tbody></table>";
                bBodyEl.innerHTML = html;
                bModal.hidden = false;
                return;
            }
            if (e.target.closest("#cfdev-rest-bundle-modal .cfdev-modal-close")
                || e.target.closest("#cfdev-rest-bundle-modal .cfdev-modal-overlay")) {
                bModal.hidden = true;
            }
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && !bModal.hidden) { bModal.hidden = true; }
        });
    }

    /* ── Code modal ────────────────────────────────────────── */
    const codeModal      = document.getElementById("cfdev-code-modal");
    if (codeModal) {
        const codeGroupId    = document.getElementById("cfdev-code-group-id");
        const codeOutput     = document.getElementById("cfdev-code-output");
        const codeCopyBtn    = document.getElementById("cfdev-code-copy");
        const codeTabDisplay = document.getElementById("cfdev-code-tab-display");
        const codeTabRaw     = document.getElementById("cfdev-code-tab-raw");
        let curCodeBtn     = null;

        function setCodeTab(raw) {
            if (!curCodeBtn) return;
            codeOutput.textContent = raw
                ? (curCodeBtn.dataset.codeRaw || "")
                : (curCodeBtn.dataset.code    || "");
            codeTabDisplay.classList.toggle("is-active", !raw);
            codeTabRaw.classList.toggle("is-active",  raw);
        }

        document.querySelectorAll(".cfdev-btn-code").forEach(function (btn) {
            btn.addEventListener("click", function (e) {
                e.stopPropagation();
                curCodeBtn = this;
                codeGroupId.textContent = this.dataset.groupId || "";
                setCodeTab(false);
                codeModal.hidden = false;
            });
        });

        codeTabDisplay.addEventListener("click", function () { setCodeTab(false); });
        codeTabRaw.addEventListener("click",     function () { setCodeTab(true); });

        codeCopyBtn.addEventListener("click", function () {
            navigator.clipboard.writeText(codeOutput.textContent || "").then(function () {
                codeCopyBtn.textContent = "✓ Copied!";
                setTimeout(function () { codeCopyBtn.textContent = "⎘ Copy"; }, 1500);
            });
        });

        (function () {
            function closeCodeModal() { codeModal.hidden = true; }
            codeModal.querySelector(".cfdev-modal-close").addEventListener("click", closeCodeModal);
            codeModal.querySelector(".cfdev-modal-overlay").addEventListener("click", closeCodeModal);
            document.addEventListener("keydown", function (e) {
                if (e.key === "Escape" && !codeModal.hidden) { closeCodeModal(); }
            });
        }());
    }

    /* ── Inspector modal ───────────────────────────────────── */
    const AJAX_URL   = (window.cfdevInspect || {}).ajaxUrl     || "";
    const NONCE      = (window.cfdevInspect || {}).nonce       || "";
    const NONCE_SRCH = (window.cfdevInspect || {}).nonceSearch || "";
    const modal      = document.getElementById("cfdev-inspect-modal");
    if (!modal) return;

    const metaLabelEl = modal.querySelector(".cfdev-modal-meta-label");
    const groupIdEl   = modal.querySelector(".cfdev-modal-group-id");
    const forceBtn    = document.getElementById("cfdev-inspect-force");
    const cacheBadge  = document.getElementById("cfdev-inspect-cache-badge");
    const output      = document.getElementById("cfdev-inspect-output");
    const toolbar     = document.getElementById("cfdev-inspect-toolbar");
    const selectEl    = document.getElementById("cfdev-object-select");
    let nodeIdx     = 0;
    let curType     = "post";
    let curObjectId = 0;
    let curTax      = "";
    let curGroupId  = "";
    let curOpts     = [];

    /* Open — populate select and auto-load */
    document.querySelectorAll(".cfdev-btn-inspect").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.stopPropagation();
            curType     = this.dataset.metaType  || "post";
            curObjectId = parseInt(this.dataset.defaultId || "0", 10);
            curTax      = this.dataset.defaultTax || "";
            curGroupId  = this.dataset.groupId    || "";
            curOpts     = JSON.parse(this.dataset.options || "[]");

            const isFixed = this.dataset.fixed === "1";
            toolbar.hidden = isFixed;
            if (!isFixed) {
                selectEl.innerHTML = "";
                if (curOpts.length === 0) {
                    const ph = document.createElement("option");
                    ph.value = "0"; ph.textContent = "No objects available";
                    selectEl.appendChild(ph);
                } else {
                    curOpts.forEach(function (item) {
                        const opt = document.createElement("option");
                        opt.value       = item.id;
                        opt.textContent = item.label + (item.meta ? " · " + item.meta : "") + "  #" + item.id;
                        if (item.id === curObjectId) opt.selected = true;
                        selectEl.appendChild(opt);
                    });
                }
            }

            metaLabelEl.textContent = curType + (curTax ? " / " + curTax : "") + " #" + (curObjectId || "?");
            groupIdEl.textContent   = curGroupId;
            cacheBadge.hidden       = true;
            forceBtn.disabled       = false;
            modal.hidden            = false;

            if (curObjectId > 0) {
                output.innerHTML = "<p class=\"cfdev-inspect-hint\">Loading…</p>";
                loadData(false);
            } else {
                output.innerHTML = "<p class=\"cfdev-inspect-hint\">No objects available for this type.</p>";
            }
        });
    });

    /* Select change → load new object */
    selectEl.addEventListener("change", function () {
        curObjectId = parseInt(this.value, 10);
        const chosen  = curOpts.find(function (o) { return o.id === curObjectId; });
        if (chosen && curType === "term" && chosen.meta) curTax = chosen.meta;
        metaLabelEl.textContent = curType + (curTax ? " / " + curTax : "") + " #" + curObjectId;
        if (curObjectId > 0) {
            output.innerHTML = "<p class=\"cfdev-inspect-hint\">Chargement…</p>";
            loadData(false);
        }
    });

    /* Close */
    function closeModal() { modal.hidden = true; }
    modal.querySelector(".cfdev-modal-close").addEventListener("click", closeModal);
    modal.querySelector(".cfdev-modal-overlay").addEventListener("click", closeModal);
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && !modal.hidden) closeModal();
    });

    /* Force-regenerate */
    forceBtn.addEventListener("click", function () { loadData(true); });

    /* Load data */
    function loadData(force) {
        if (curObjectId < 1) return;
        output.innerHTML  = "<p class=\"cfdev-inspect-hint\">Loading…</p>";
        cacheBadge.hidden = true;
        forceBtn.disabled = true;

        const body = new FormData();
        body.append("action",      "cfdev_inspect");
        body.append("nonce",       NONCE);
        body.append("object_type", curType);
        body.append("object_id",   curObjectId);
        body.append("taxonomy",    curTax);
        body.append("group_id",    curGroupId);
        if (force) body.append("force", "1");

        fetch(AJAX_URL, { method: "POST", body: body })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                forceBtn.disabled = false;
                if (!res.success) {
                    output.innerHTML = "<p class=\"cfdev-inspect-error\">" + esc(res.data && res.data.message ? res.data.message : "Error") + "</p>";
                    return;
                }
                renderBadge(res.data.cache);
                renderTree(res.data.data);
            })
            .catch(function (err) {
                forceBtn.disabled = false;
                output.innerHTML = "<p class=\"cfdev-inspect-error\">Network error: " + esc(err.message) + "</p>";
            });
    }

    /* Cache badge */
    function renderBadge(cache) {
        let label, cls;
        if (!cache.enabled) {
            label = "CACHE OFF"; cls = "cfdev-cache-badge--off";
        } else if (cache.hit) {
            const a = cache.age, h = a < 60 ? a + "s" : Math.round(a / 60) + "min";
            label = "CACHE HIT — " + h + " ago"; cls = "cfdev-cache-badge--hit";
        } else {
            label = "GENERATED"; cls = "cfdev-cache-badge--miss";
        }
        cacheBadge.textContent = label;
        cacheBadge.className   = "cfdev-cache-badge " + cls;
        cacheBadge.hidden      = false;
    }

    /* Output click handler (toggle + copy) */
    output.addEventListener("click", function (e) {
        const copyBtn = e.target.closest(".cfdev-copy-btn");
        if (copyBtn) {
            e.stopPropagation();
            navigator.clipboard.writeText(copyBtn.dataset.copy || "").then(function () {
                const orig = copyBtn.textContent;
                copyBtn.textContent = "✓";
                setTimeout(function () { copyBtn.textContent = orig; }, 1200);
            });
            return;
        }
        const tg = e.target.closest(".cfdev-tree-toggle");
        if (!tg) return;
        const el = document.getElementById(tg.dataset.target);
        if (!el) return;
        el.hidden = !el.hidden;
        tg.querySelector(".cfdev-tree-caret").textContent = el.hidden ? "▶" : "▼";
    });

    /* ── Tree renderer ─────────────────────────────────────── */
    function buildSnippet() {
        const method = curType === "term" ? "term(" + curObjectId + ", '" + curTax + "')"
                   : curType === "user" ? "user(" + curObjectId + ")"
                   : "post(" + curObjectId + ")";
        return "$data  = (new \\Weblitzer\\CFDev\\Cache\\CacheManager())->" + method + ";\n"
             + "$group = $data['groups']['" + curGroupId + "'] ?? [];";
    }

    function renderTree(data) {
        nodeIdx = 0;
        output.innerHTML = "";

        const snip = buildSnippet();
        const wrap = document.createElement("div");
        wrap.className = "cfdev-snippet";
        wrap.innerHTML = "<pre class=\"cfdev-snippet-code\">" + esc(snip) + "</pre>"
                       + "<button class=\"cfdev-copy-btn cfdev-copy-global\" data-copy=\"" + esc(snip) + "\""
                       + " title=\"Copy snippet\">⎘</button>";
        output.appendChild(wrap);

        const ul = document.createElement("ul");
        ul.className = "cfdev-tree";
        Object.entries(data).forEach(function (kv) {
            ul.appendChild(makeNode(kv[0], kv[1], "$group"));
        });
        output.appendChild(ul);
    }

    function makeNode(key, value, basePath) {
        const path = basePath + "['" + key + "']";
        const li   = document.createElement("li");
        li.innerHTML = copyIcon(path)
                     + "<span class=\"cfdev-tree-key\">" + esc(String(key)) + "</span>"
                     + " <span class=\"cfdev-tree-arrow\">⇒</span> "
                     + renderValue(value, 0, path);
        return li;
    }

    function renderValue(v, depth, path) {
        if (v === null || v === undefined)
            return "<span class=\"cfdev-tv cfdev-tv--null\">null</span>";
        if (typeof v === "boolean")
            return "<span class=\"cfdev-tv cfdev-tv--bool\">" + (v ? "true" : "false") + "</span>";
        if (typeof v === "number")
            return "<span class=\"cfdev-tv cfdev-tv--num\">" + v + "</span>";
        if (typeof v === "string") {
            const preview = v.length > 100 ? v.slice(0, 100) + "…" : v;
            return "<span class=\"cfdev-tv cfdev-tv--str\">\"" + esc(preview) + "\"</span>"
                 + "<span class=\"cfdev-tv-meta\"> (" + v.length + ")</span>";
        }
        if (Array.isArray(v))
            return v.length === 0
                ? "<span class=\"cfdev-tv cfdev-tv--arr\">array(0)</span> <span class=\"cfdev-tv-meta\">[]</span>"
                : renderColl(v.map(function (x, i) { return [i, x]; }), "arr", depth, path || "");
        if (typeof v === "object") {
            const ks = Object.keys(v);
            return ks.length === 0
                ? "<span class=\"cfdev-tv cfdev-tv--obj\">object(0)</span> <span class=\"cfdev-tv-meta\">{}</span>"
                : renderColl(Object.entries(v), "obj", depth, path || "");
        }
        return esc(String(v));
    }

    function renderColl(entries, kind, depth, basePath) {
        const id    = "cfdev-n-" + (nodeIdx++);
        const label = kind === "arr" ? "array(" + entries.length + ")" : "object(" + entries.length + ")";
        const open  = depth < 1;
        const rows  = entries.map(function (kv) {
            const k    = kv[0];
            const path = basePath + (kind === "arr" ? "[" + k + "]" : "['" + k + "']");
            return "<li>" + copyIcon(path)
                 + "<span class=\"cfdev-tree-key\">" + esc(String(k)) + "</span>"
                 + " <span class=\"cfdev-tree-arrow\">⇒</span> "
                 + renderValue(kv[1], depth + 1, path) + "</li>";
        }).join("");
        return "<span class=\"cfdev-tree-toggle\" data-target=\"" + id + "\">"
             + "<span class=\"cfdev-tree-caret\">" + (open ? "▼" : "▶") + "</span> "
             + "<span class=\"cfdev-tv cfdev-tv--" + kind + "\">" + label + "</span>"
             + "</span>"
             + "<ul id=\"" + id + "\" class=\"cfdev-tree-children\"" + (open ? "" : " hidden") + ">"
             + rows + "</ul>";
    }

    function copyIcon(path) {
        return "<button class=\"cfdev-copy-btn cfdev-copy-path\" data-copy=\"" + esc(path) + "\""
             + " title=\"" + esc(path) + "\">⎘</button>";
    }

    function esc(s) {
        return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    /* ── Search objects (unused in UI but available for future use) ── */
    void NONCE_SRCH; // referenced to avoid lint warning

}());
