<?php

function PAYPAL_plot()
{
    global $_SCRIPTS, $_CONF, $_PAY_CONF, $_TABLES, $LANG_PAYPAL_1;

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

    $series = array();
    foreach ($plots as $date => $amount) {
        $series[] = array($date, round($amount, 2));
    }

    $json = json_encode($series);
    if ($json === false) {
        $json = '[]';
    }

    $js = "jQuery(function () {
        jQuery.jqplot('chartdiv', [" . $json . "], {
            axes: {
                xaxis: {
                    renderer: jQuery.jqplot.DateAxisRenderer,
                    tickInterval: '1 month',
                    tickOptions: {formatString: '%Y/%#m'}
                }
            }
        });
    });";
    $_SCRIPTS->setJavaScript($js, true);

    return '<p>'
        . $LANG_PAYPAL_1['period_stat'] . ' ' . $_PAY_CONF['currency'] . ' '
        . number_format($totalPeriod, $_CONF['decimal_count'], $_CONF['decimal_separator'], $_CONF['thousand_separator'])
        . '&nbsp;&nbsp;|&nbsp;&nbsp;'
        . $LANG_PAYPAL_1['year_stat'] . ' ' . $_PAY_CONF['currency'] . ' '
        . number_format($totalYear, $_CONF['decimal_count'], $_CONF['decimal_separator'], $_CONF['thousand_separator'])
        . '&nbsp;&nbsp;|&nbsp;&nbsp;'
        . $LANG_PAYPAL_1['month_stat'] . ' ' . $_PAY_CONF['currency'] . ' '
        . number_format($totalMonth, $_CONF['decimal_count'], $_CONF['decimal_separator'], $_CONF['thousand_separator'])
        . '</p><div style="background:#fff;padding:15px"><div id="chartdiv" style="height:200px;width:100%"></div></div>';
}
