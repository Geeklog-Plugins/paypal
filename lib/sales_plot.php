<?php

function PAYPAL_plot()
{
    global $_CONF, $_PAY_CONF, $_TABLES, $LANG_PAYPAL_1;

    $nowMonth = (int) date('n');
    $nowYear = (int) date('Y');
    $plots = array();
    $totalMonth = 0.0;
    $totalYear = 0.0;
    $totalPeriod = 0.0;

    for ($i = 11; $i >= 0; --$i) {
        $timestamp = strtotime('-' . $i . ' months');
        $key = date('Y-m-01', $timestamp);
        $plots[$key] = 0.0;
    }

    $sql = "SELECT p.purchase_date, i.ipn_data
        FROM {$_TABLES['paypal_purchases']} AS p
        LEFT JOIN {$_TABLES['paypal_ipnlog']} AS i
            ON p.txn_id = i.txn_id
        WHERE p.status = 'complete'
          AND p.purchase_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY p.txn_id, p.purchase_date, i.ipn_data
        ORDER BY p.purchase_date";

    $result = DB_query($sql);

    while ($A = DB_fetchArray($result)) {
        $serialized = isset($A['ipn_data']) ? $A['ipn_data'] : '';
        $normalized = preg_replace_callback(
            '!s:(\\d+):"(.*?)";!s',
            function ($matches) {
                return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";';
            },
            $serialized
        );

        $ipn = @unserialize($normalized);
        $gross = is_array($ipn) && isset($ipn['mc_gross']) && is_numeric($ipn['mc_gross'])
            ? (float) $ipn['mc_gross']
            : 0.0;

        $timestamp = strtotime($A['purchase_date']);
        if ($timestamp === false) {
            continue;
        }

        $key = date('Y-m-01', $timestamp);
        if (isset($plots[$key])) {
            $plots[$key] += $gross;
        }

        $year = (int) date('Y', $timestamp);
        $month = (int) date('n', $timestamp);

        if ($year === $nowYear) {
            $totalYear += $gross;
            if ($month === $nowMonth) {
                $totalMonth += $gross;
            }
        }

        $totalPeriod += $gross;
    }

    $max = !empty($plots) ? max($plots) : 0.0;
    $bars = '';

    foreach ($plots as $date => $amount) {
        $width = $max > 0 ? (int) round(($amount / $max) * 100) : 0;
        $label = date('Y-m', strtotime($date));
        $value = number_format(
            $amount,
            $_CONF['decimal_count'],
            $_CONF['decimal_separator'],
            $_CONF['thousand_separator']
        );

        $bars .= '<div class="paypal-sales-row">'
            . '<div class="paypal-sales-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>'
            . '<div class="paypal-sales-track"><div class="paypal-sales-bar" style="width:' . $width . '%"></div></div>'
            . '<div class="paypal-sales-value">' . $value . ' ' . htmlspecialchars($_PAY_CONF['currency'], ENT_QUOTES, 'UTF-8') . '</div>'
            . '</div>';
    }

    $summary = '<p>'
        . $LANG_PAYPAL_1['period_stat'] . ' ' . $_PAY_CONF['currency'] . ' '
        . number_format($totalPeriod, $_CONF['decimal_count'], $_CONF['decimal_separator'], $_CONF['thousand_separator'])
        . '&nbsp;&nbsp;|&nbsp;&nbsp;'
        . $LANG_PAYPAL_1['year_stat'] . ' ' . $_PAY_CONF['currency'] . ' '
        . number_format($totalYear, $_CONF['decimal_count'], $_CONF['decimal_separator'], $_CONF['thousand_separator'])
        . '&nbsp;&nbsp;|&nbsp;&nbsp;'
        . $LANG_PAYPAL_1['month_stat'] . ' ' . $_PAY_CONF['currency'] . ' '
        . number_format($totalMonth, $_CONF['decimal_count'], $_CONF['decimal_separator'], $_CONF['thousand_separator'])
        . '</p>';

    return $summary . '<div class="paypal-sales-chart">' . $bars . '</div>';
}
