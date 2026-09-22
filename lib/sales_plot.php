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
    $chartWidth = 960;
    $chartHeight = 260;
    $paddingLeft = 55;
    $paddingRight = 20;
    $paddingTop = 20;
    $paddingBottom = 45;
    $plotWidth = $chartWidth - $paddingLeft - $paddingRight;
    $plotHeight = $chartHeight - $paddingTop - $paddingBottom;

    $count = count($plots);
    $points = array();
    $labels = '';
    $index = 0;

    foreach ($plots as $date => $amount) {
        $x = $paddingLeft;
        if ($count > 1) {
            $x += ($plotWidth / ($count - 1)) * $index;
        }

        if ($max > 0) {
            $y = $paddingTop + $plotHeight - (($amount / $max) * $plotHeight);
        } else {
            // No sales: draw a visible flat zero line on the x axis.
            $y = $paddingTop + $plotHeight;
        }

        $points[] = round($x, 2) . ',' . round($y, 2);

        $label = date('Y-m', strtotime($date));
        $labels .= '<text x="' . round($x, 2) . '" y="' . ($chartHeight - 18)
            . '" text-anchor="middle" class="paypal-sales-axis-label">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</text>';

        ++$index;
    }

    $axisMax = $max > 0 ? $max : 0;
    $axisMaxLabel = number_format(
        $axisMax,
        $_CONF['decimal_count'],
        $_CONF['decimal_separator'],
        $_CONF['thousand_separator']
    );

    $svg = '<div class="paypal-sales-chart" role="img" aria-label="Sales history">'
        . '<svg viewBox="0 0 ' . $chartWidth . ' ' . $chartHeight . '" preserveAspectRatio="none">'
        . '<line x1="' . $paddingLeft . '" y1="' . $paddingTop . '" x2="' . $paddingLeft
        . '" y2="' . ($paddingTop + $plotHeight) . '" class="paypal-sales-axis"></line>'
        . '<line x1="' . $paddingLeft . '" y1="' . ($paddingTop + $plotHeight) . '" x2="'
        . ($chartWidth - $paddingRight) . '" y2="' . ($paddingTop + $plotHeight)
        . '" class="paypal-sales-axis"></line>'
        . '<text x="5" y="' . ($paddingTop + 5) . '" class="paypal-sales-axis-value">'
        . htmlspecialchars($axisMaxLabel . ' ' . $_PAY_CONF['currency'], ENT_QUOTES, 'UTF-8')
        . '</text>'
        . '<text x="15" y="' . ($paddingTop + $plotHeight) . '" class="paypal-sales-axis-value">0</text>'
        . '<polyline points="' . implode(' ', $points) . '" class="paypal-sales-line"></polyline>'
        . $labels
        . '</svg>'
        . '</div>';

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

    return $summary . $svg;
}
