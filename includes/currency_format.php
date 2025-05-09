<?php
/**
 * Format a number as currency with DA symbol
 * 
 * @param float $amount The amount to format
 * @return string Formatted amount with currency symbol
 */
function formatCurrency($amount) {
    return number_format($amount, 2) . ' DA';
}
?>
