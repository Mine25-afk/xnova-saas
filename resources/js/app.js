import './bootstrap';

// Alpine lo provee Livewire (@livewireScripts) y WireUI (<wireui:scripts />).
// NO importar ni iniciar Alpine aquí: hacerlo arranca una segunda instancia
// ("Detected multiple instances of Alpine running") y rompe los componentes de
// WireUI (wireui_dialog is not defined, etc.).

function resolveJQueryTarget(target) {
    if (typeof window.jQuery === 'undefined') {
        return null;
    }

    if (!target) {
        return window.jQuery(document);
    }

    if (target.jquery) {
        return target;
    }

    return window.jQuery(target);
}



window.formUtils = {
    clearErrors(target) {
        const $target = resolveJQueryTarget(target);

        if (!$target) {
            return;
        }

        $target.find('[data-error-for]').text('');
    },

    setErrors(errors, target) {
        const $target = resolveJQueryTarget(target);

        if (!$target) {
            return;
        }

        window.jQuery.each(errors || {}, function (field, messages) {
            const firstMessage = Array.isArray(messages) ? (messages[0] || '') : (messages || '');

            $target.find('[data-error-for="' + field + '"]').text(firstMessage);
        });
    },

    setLoadingState(isLoading, target) {
        const $target = resolveJQueryTarget(target);

        if (!$target) {
            return;
        }

        $target.prop('disabled', isLoading);
        $target.toggleClass('opacity-70', isLoading);
        $target.attr('aria-busy', isLoading ? 'true' : 'false');
    },

    closeActionModal(target) {
    const $target = resolveJQueryTarget(target);

    if (!$target) {
        return;
    }

    $target.removeClass('is-open').attr('aria-hidden', 'true');
    }
};

window.listUtils = {
    createStateHtml(config = {}) {
        const entity = config.entity || 'registros';
        const colSpan = config.colSpan || 1;
        const loadingText = config.loadingText || `Cargando ${entity}...`;
        const emptyText = config.emptyText || `No se encontraron ${entity}.`;

        return {
            loadingRow: `<tr><td colspan="${colSpan}" class="list-loading-row">${loadingText}</td></tr>`,
            emptyRow: `<tr><td colspan="${colSpan}" class="list-empty-row">${emptyText}</td></tr>`,
            loadingCards: `<div class="list-loading">${loadingText}</div>`,
            emptyCards: `<div class="list-empty">${emptyText}</div>`,
        };
    },

    bindPersistentFilters(config = {}) {
        const storageKey = config.storageKey;
        const inputs = config.inputs || {};

        if (!storageKey || typeof window.localStorage === 'undefined') {
            return {};
        }

        const fullKey = `xnova.finanzas.filters.${storageKey}`;
        const entries = Object.entries(inputs)
            .map(([name, target]) => [name, resolveJQueryTarget(target)])
            .filter(([, $input]) => $input && $input.length);

        let saved = {};

        try {
            saved = JSON.parse(window.localStorage.getItem(fullKey) || '{}') || {};
        } catch (e) {
            saved = {};
        }

        // Restaura los valores guardados sin disparar carga.
        entries.forEach(function ([name, $input]) {
            if (Object.prototype.hasOwnProperty.call(saved, name) && saved[name] !== null && saved[name] !== undefined) {
                $input.val(String(saved[name]));
            }
        });

        function persist() {
            const data = {};

            entries.forEach(function ([name, $input]) {
                data[name] = $input.val();
            });

            try {
                window.localStorage.setItem(fullKey, JSON.stringify(data));
            } catch (e) {
                // Ignora cuotas/llaves no disponibles.
            }
        }

        // Guarda en cada cambio (incluye escritura en el buscador).
        entries.forEach(function ([, $input]) {
            $input.on('input.persistFilters change.persistFilters', persist);
        });

        return saved;
    },

    bindFilterControls(config = {}) {
        const $searchInput = resolveJQueryTarget(config.searchInput);
        const $perPageInput = resolveJQueryTarget(config.perPageInput);
        const $pagination = resolveJQueryTarget(config.pagination);
        const debounceMs = Number(config.debounceMs) || 350;
        const loadPage = typeof config.loadPage === 'function' ? config.loadPage : function () {};
        const getCurrentPage = typeof config.getCurrentPage === 'function'
            ? config.getCurrentPage
            : function () {
                return null;
            };
        const namespace = `.listControls${Date.now()}${Math.floor(Math.random() * 1000)}`;

        let debounceTimer = null;

        if ($searchInput && $searchInput.length) {
            $searchInput.on(`input${namespace}`, function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    loadPage(1);
                }, debounceMs);
            });
        }

        if ($perPageInput && $perPageInput.length) {
            $perPageInput.on(`change${namespace}`, function () {
                loadPage(1);
            });
        }

        if ($pagination && $pagination.length) {
            $pagination.on(`click${namespace}`, '[data-page]', function () {
                const nextPage = Number(window.jQuery(this).data('page'));

                if (!nextPage || nextPage === getCurrentPage()) {
                    return;
                }

                loadPage(nextPage);
            });
        }

        return {
            destroy() {
                clearTimeout(debounceTimer);

                if ($searchInput && $searchInput.length) {
                    $searchInput.off(namespace);
                }

                if ($perPageInput && $perPageInput.length) {
                    $perPageInput.off(namespace);
                }

                if ($pagination && $pagination.length) {
                    $pagination.off(namespace);
                }
            },
        };
    },
};

window.utilidadesFormulario = window.utilidadesFormulario || {
    limpiarErrores(target) {
        window.formUtils.clearErrors(target);
    },

    mostrarErrores(errors, target) {
        window.formUtils.setErrors(errors, target);
    },

    cambiarEstadoCarga(isLoading, target) {
        window.formUtils.setLoadingState(isLoading, target);
    },

    cerrarModal(target) {
        window.formUtils.closeActionModal(target);
    },
};

window.utilidadesListado = window.utilidadesListado || {
    crearEstadoHtml(config = {}) {
        return window.listUtils.createStateHtml(config);
    },

    vincularControlesFiltros(config = {}) {
        return window.listUtils.bindFilterControls(config);
    },
};

window.utilidadesSelect = window.utilidadesSelect || {
    cargarOpciones(config = {}) {
        const $select = resolveJQueryTarget(config.select);
        const placeholder = config.placeholder || 'Selecciona una opcion';
        const textoCarga = config.textoCarga || 'Cargando opciones...';
        const textoError = config.textoError || 'No se pudieron cargar las opciones';
        const textoVacio = config.textoVacio || placeholder;
        const valorSeleccionado = config.selected ?? ($select ? ($select.data('selected') ?? $select.val() ?? '') : '');

        if (!$select || !$select.length || !config.url) {
            return window.jQuery.Deferred().reject().promise();
        }

        $select.prop('disabled', true).empty().append(
            window.jQuery('<option>', {
                value: '',
                text: textoCarga,
            }),
        );

        return window.jQuery.ajax({
            url: config.url,
            method: 'GET',
            data: config.data || {},
        }).done(function (response) {
            const opciones = Array.isArray(response.opciones) ? response.opciones : [];

            $select.empty().append(
                window.jQuery('<option>', {
                    value: '',
                    text: opciones.length ? placeholder : textoVacio,
                }),
            );

            window.jQuery.each(opciones, function (_, opcion) {
                const valor = opcion.valor ?? opcion.value ?? opcion.id ?? '';
                const texto = opcion.texto ?? opcion.label ?? opcion.nombre ?? '';

                const $option = window.jQuery('<option>', {
                    value: valor,
                    text: texto,
                });

                if (String(valor) === String(valorSeleccionado ?? '')) {
                    $option.prop('selected', true);
                }

                $select.append($option);
            });

            if (valorSeleccionado !== null && valorSeleccionado !== undefined && String(valorSeleccionado) !== '') {
                $select.val(String(valorSeleccionado));
            }
        }).fail(function () {
            $select.empty().append(
                window.jQuery('<option>', {
                    value: '',
                    text: textoError,
                }),
            );
        }).always(function () {
            $select.prop('disabled', false);
        });
    },
};



window.stringBool = function stringToBool(str) {
    if (typeof str !== 'string') {
        return null;
    }

    const normalized = str.trim().toLowerCase();

    if (normalized === 'true') {
        return true;
    }

    if (normalized === 'false') {
        return false;
    }

    return null;
};
