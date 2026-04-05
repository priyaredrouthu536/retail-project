jQuery(function ($) {

    /* ============================================================
     * FIND VARIATION WRAPPER FROM A CLICKED ELEMENT
     * - support multiple possible wrappers used by different WC versions
     * ============================================================ */
    function getVariationWrapper(el) {
        // Look for the common variation wrappers used in WooCommerce admin
        // If nothing found, return an empty jQuery object
        return $(el).closest(".woocommerce_variation, .wc-metabox, .variation");
    }

    /* ============================================================
     * SIMPLE PRODUCT → ADD TYPE RULE
     * ============================================================ */
    $(document).on("click", "#wc-dtp-add-simple-row", function (e) {
        e.preventDefault();
        let tpl = $("#wc-dtp-simple-row-template").html();
        tpl = tpl.replace(/__ROWID__/g, "dt_" + Date.now() + "_" + Math.floor(Math.random() * 10000));
        $("#wc-dtp-simple-table tbody").append(tpl);

        // initialize global (simple product) state
        initializeGroupState($(document));
    });

    /* ============================================================
     * SIMPLE PRODUCT → ADD DATE RULE
     * ============================================================ */
    $(document).on("click", "#wc-dtp-add-date-row", function (e) {
        e.preventDefault();
        let tpl = $("#wc-dtp-date-row-template").html();
        tpl = tpl.replace(/__ROWID__/g, "dd_" + Date.now() + "_" + Math.floor(Math.random() * 10000));
        $("#wc-dtp-date-table tbody").append(tpl);

        // initialize global (simple product) state
        initializeGroupState($(document));
    });


    /* ============================================================
     * VARIABLE PRODUCT ROW ADDERS
     * ============================================================ */
    $(document).on("woocommerce_variations_loaded woocommerce_variations_added woocommerce_variations_expanded", function () {

        // ADD TYPE RULE (per-variation)
        $(".wc-dtp-add-var-type-row").off("click").on("click", function (e) {
            e.preventDefault();

            // find wrapper for this button
            let wrap = getVariationWrapper(this);
            let vid = $(this).data("variation_id");
            let tpl = $("#wc-dtp-var-type-template-" + vid).html();
            tpl = tpl.replace(/__ROWID__/g, "vdt_" + Date.now() + "_" + Math.floor(Math.random() * 10000));

            // If we have a wrapper, append into its table. Otherwise append by id fallback.
            if (wrap && wrap.length) {
                // find table inside wrapper first
                let table = wrap.find("#wc-dtp-var-type-table-" + vid);
                if (table.length) {
                    table.find("tbody").append(tpl);
                } else {
                    // fallback
                    $("#wc-dtp-var-type-table-" + vid + " tbody").append(tpl);
                }
                initializeGroupState(wrap);
            } else {
                $("#wc-dtp-var-type-table-" + vid + " tbody").append(tpl);
                initializeGroupState($(document));
            }
        });

        // ADD DATE RULE (per-variation)
        $(".wc-dtp-add-var-date-row").off("click").on("click", function (e) {
            e.preventDefault();

            let wrap = getVariationWrapper(this);
            let vid = $(this).data("variation_id");
            let tpl = $("#wc-dtp-var-date-template-" + vid).html();
            tpl = tpl.replace(/__ROWID__/g, "vdd_" + Date.now() + "_" + Math.floor(Math.random() * 10000));

            if (wrap && wrap.length) {
                let table = wrap.find("#wc-dtp-var-date-table-" + vid);
                if (table.length) {
                    table.find("tbody").append(tpl);
                } else {
                    // fallback
                    $("#wc-dtp-var-date-table-" + vid + " tbody").append(tpl);
                }
                initializeGroupState(wrap);
            } else {
                $("#wc-dtp-var-date-table-" + vid + " tbody").append(tpl);
                initializeGroupState($(document));
            }
        });

        // Re-initialize all variation wrappers when WooCommerce triggers the event
        $(".woocommerce_variation, .wc-metabox, .variation").each(function () {
            initializeGroupState($(this));
        });
    });


    /* ============================================================
     * REMOVE ANY ROW
     * ============================================================ */
    $(document).on("click", ".wc-dtp-remove-row", function (e) {
        e.preventDefault();
        let wrap = getVariationWrapper(this);
        $(this).closest("tr").remove();

        // If wrapper found, init that wrapper; otherwise init global
        if (wrap && wrap.length) {
            initializeGroupState(wrap);
        } else {
            initializeGroupState($(document));
        }
    });


    /* ============================================================
     * ACTIVATE TYPE / DATE GROUP (LOCALLY PER VARIATION)
     * ============================================================ */
    function activateTypeGroup(wrap) {
        // If wrap is document (no wrapper), operate globally
        let scope = (wrap && wrap.length) ? wrap : $(document);

        scope.find(".wc-dtp-type-row .wc-dtp-active-checkbox").prop("disabled", false);
        scope.find(".wc-dtp-date-row .wc-dtp-active-checkbox").prop("checked", false).prop("disabled", true);
    }

    function activateDateGroup(wrap) {
        let scope = (wrap && wrap.length) ? wrap : $(document);

        scope.find(".wc-dtp-date-row .wc-dtp-active-checkbox").prop("disabled", false);
        scope.find(".wc-dtp-type-row .wc-dtp-active-checkbox").prop("checked", false).prop("disabled", true);
    }


    /* ============================================================
     * CHECKBOX LOGIC – FIXED PER VARIATION
     * ============================================================ */

    $(document).on("change", ".wc-dtp-type-row .wc-dtp-active-checkbox", function () {
        let wrap = getVariationWrapper(this);

        // if no wrapper (simple product), use document as scope
        if (!wrap || !wrap.length) wrap = $(document);

        if ($(this).is(":checked")) {
            activateTypeGroup(wrap);
        } else {
            if (wrap.find(".wc-dtp-type-row .wc-dtp-active-checkbox:checked").length === 0) {
                wrap.find(".wc-dtp-date-row .wc-dtp-active-checkbox").prop("disabled", false);
            }
        }
    });

    $(document).on("change", ".wc-dtp-date-row .wc-dtp-active-checkbox", function () {
        let wrap = getVariationWrapper(this);

        if (!wrap || !wrap.length) wrap = $(document);

        if ($(this).is(":checked")) {
            activateDateGroup(wrap);
        } else {
            if (wrap.find(".wc-dtp-date-row .wc-dtp-active-checkbox:checked").length === 0) {
                wrap.find(".wc-dtp-type-row .wc-dtp-active-checkbox").prop("disabled", false);
            }
        }
    });


    /* ============================================================
     * INITIAL LOAD LOGIC — PER VARIATION (or global for simple)
     * ============================================================ */
    function initializeGroupState(wrap) {

        // If no wrapper provided, use document (simple product)
        if (!wrap || wrap.length === 0) {
            wrap = $(document);
        }

        let typeActive = wrap.find(".wc-dtp-type-row .wc-dtp-active-checkbox:checked").length;
        let dateActive = wrap.find(".wc-dtp-date-row .wc-dtp-active-checkbox:checked").length;

        if (typeActive > 0) {
            activateTypeGroup(wrap);
            return;
        }

        if (dateActive > 0) {
            activateDateGroup(wrap);
            return;
        }

        // No rules selected — enable all in this scope
        wrap.find(".wc-dtp-active-checkbox").prop("disabled", false);
    }


    /* ============================================================
     * INITIALIZE ALL VARIATIONS ON PAGE LOAD
     * ============================================================ */
    $(document).ready(function () {
        // Initialize every variation wrapper
        $(".woocommerce_variation, .wc-metabox, .variation").each(function () {
            initializeGroupState($(this));
        });

        // Also initialize global/simple scope
        initializeGroupState($(document));
    });

    /* ============================================================
     * PRICE FIELD INPUT: allow only numbers + dot (works with dynamic rows)
     * ============================================================ */
    $(document).on("input", ".wc-dtp-price-field", function () {

        let val = $(this).val();

        // Remove all invalid characters
        val = val.replace(/[^0-9.]/g, "");

        // Prevent more than one decimal point
        val = val.replace(/(\..*)\./g, "$1");

        $(this).val(val);
    });

});
