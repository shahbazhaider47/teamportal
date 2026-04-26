<?php
/**
 * _invoice_row.php
 * Partial included by form.php for each existing line item.
 * Expects: $idx (int), $row (object|null from fin_invoice_items)
 * All field names map exactly to fin_invoice_items columns.
 */
defined('BASEPATH') or exit('No direct script access allowed');
$i = (int) $idx;
?>
<tr class="if-item-row">

    <!-- # -->
    <td>
        <div class="if-row-num"><?= $i + 1 ?></div>
        <input type="hidden" name="item_sort_order[]" data-role="sort-order" value="<?= $i ?>">
    </td>

    <!-- item_name + description (fin_invoice_items.item_name / .description) -->
    <td>
        <input type="text"
               name="item_name[]"
               class="if-cell"
               placeholder="Item name"
               style="margin-bottom:3px;"
               value="<?= html_escape($row->item_name ?? '') ?>">
        <input type="text"
               name="item_description[]"
               class="if-cell if-cell-desc"
               placeholder="Description (optional)"
               value="<?= html_escape($row->description ?? '') ?>">
    </td>

    <!-- unit (fin_invoice_items.unit) -->
    <td>
        <input type="text"
               name="item_unit[]"
               class="if-cell if-cell-sm"
               placeholder="hrs"
               value="<?= html_escape($row->unit ?? '') ?>">
    </td>

    <!-- quantity (fin_invoice_items.quantity) -->
    <td>
        <input type="number"
               name="item_quantity[]"
               class="if-cell"
               data-role="qty"
               step="0.01" min="0"
               value="<?= htmlspecialchars($row->quantity ?? 1) ?>">
    </td>

    <!-- unit_price (fin_invoice_items.unit_price) -->
    <td>
        <input type="number"
               name="item_unit_price[]"
               class="if-cell"
               data-role="price"
               step="0.01" min="0"
               value="<?= htmlspecialchars($row->unit_price ?? '0.00') ?>">
    </td>

    <!-- discount_type + discount_amount (fin_invoice_items.discount_type / .discount_amount) -->
    <!-- Visibility controlled by JS applyDiscountMode() -->
    <td class="if-row-perline">
        <select name="item_discount_type[]"
                class="if-cell if-cell-sm"
                data-role="disc-type"
                style="margin-bottom:3px;">
            <option value="none"    <?= ($row->discount_type ?? 'none') === 'none'    ? 'selected' : '' ?>>None</option>
            <option value="percent" <?= ($row->discount_type ?? 'none') === 'percent' ? 'selected' : '' ?>>% Percent</option>
            <option value="fixed"   <?= ($row->discount_type ?? 'none') === 'fixed'   ? 'selected' : '' ?>>$ Fixed</option>
        </select>
        <input type="number"
               name="item_discount_amount[]"
               class="if-cell if-cell-sm"
               data-role="disc-val"
               step="0.01" min="0"
               value="<?= htmlspecialchars($row->discount_amount ?? 0) ?>"
               placeholder="Value">
        <!-- Stores the calculated discount for the controller -->
        <input type="hidden"
               name="item_discount_calculated[]"
               data-role="disc-amt"
               value="0.00">
    </td>

    <!-- tax_rate (fin_invoice_items.tax_rate) -->
    <td class="if-row-perline">
        <input type="number"
               name="item_tax_rate[]"
               class="if-cell if-cell-sm"
               data-role="tax-rate"
               step="0.01" min="0" max="100"
               value="<?= htmlspecialchars($row->tax_rate ?? 0) ?>">
    </td>

    <!-- line_total (fin_invoice_items.line_total — read-only, JS-calculated) -->
    <td>
        <input type="number"
               name="item_line_total[]"
               class="if-cell if-cell-total"
               data-role="line-total"
               step="0.01"
               value="<?= htmlspecialchars($row->line_total ?? '0.00') ?>"
               readonly
               tabindex="-1">
    </td>

    <!-- Delete row button -->
    <td>
        <button type="button" class="if-del-btn" title="Remove this row">
            <i class="ti ti-trash"></i>
        </button>
    </td>

</tr>