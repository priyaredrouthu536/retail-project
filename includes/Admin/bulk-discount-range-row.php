<script type="text/html" id="bulk-discount-template">
    <tr class="wdr-discount-group awdr-bulk-group ui-sortable-handle" data-index="__INDEX__">
        <td>
            <label>
                <input type="number" name="bulk_adjustments[ranges][__INDEX__][from]" value="" class="bulk_discount_min small-text" placeholder="Min" min="0">
                <br><span class="wdr_desc_text">Minimum Qty</span>
            </label>
        </td>

        <td>
            <label>
                <input type="number" name="bulk_adjustments[ranges][__INDEX__][to]" value="" class="bulk_discount_max small-text" placeholder="Max" min="0">
                <br><span class="wdr_desc_text">Maximum Qty</span>
            </label>
        </td>

        <td>
            <label>
                <input type="number" name="bulk_adjustments[ranges][__INDEX__][value]" value="" class="small-text" placeholder="Discount" min="0">
                <br><span class="wdr_desc_text">Discount Value</span>
            </label>
        </td>

        <td>
            <span class="dashicons dashicons-no-alt wdr_discount_remove" style="cursor:pointer;" title="Remove"></span>
        </td>
    </tr>
</script>