<?php

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'blocks.php') !== false) {
    die('This file can not be used on its own!');
}

/**
 * Ensure the standard PayPal PHP blocks exist.
 *
 * Existing blocks are detected by phpblockfn and are never modified.
 * Missing blocks are created on the right side and may then be repositioned
 * normally from Geeklog's block administration.
 *
 * @param bool $enabled Initial enabled state for newly created blocks
 * @return array Names of blocks created during this call
 */
function PAYPAL_ensureBlocks($enabled = true)
{
    global $_TABLES, $_USER, $LANG_PAYPAL_1;

    if (empty($_TABLES['blocks'])) {
        return array();
    }

    $allUsersGroup = (int) DB_getItem(
        $_TABLES['groups'],
        'grp_id',
        "grp_name = 'All Users'"
    );
    if ($allUsersGroup <= 0) {
        $allUsersGroup = 1;
    }

    $ownerId = !empty($_USER['uid']) && (int) $_USER['uid'] > 1
        ? (int) $_USER['uid']
        : 2;

    $cartTitle = isset($LANG_PAYPAL_1['cart']) && $LANG_PAYPAL_1['cart'] !== ''
        ? $LANG_PAYPAL_1['cart']
        : 'Cart';
    $randomTitle = isset($LANG_PAYPAL_1['random_product']) && $LANG_PAYPAL_1['random_product'] !== ''
        ? $LANG_PAYPAL_1['random_product']
        : 'Random product';

    $definitions = array(
        array(
            'name' => 'paypal_cart',
            'title' => $cartTitle,
            'function' => 'phpblock_paypal_cart',
        ),
        array(
            'name' => 'paypal_random_product',
            'title' => $randomTitle,
            'function' => 'phpblock_paypal_randomBlock',
        ),
    );

    $maxOrder = (int) DB_getItem(
        $_TABLES['blocks'],
        'MAX(blockorder)',
        'onleft = 0'
    );
    $created = array();

    foreach ($definitions as $definition) {
        $function = DB_escapeString($definition['function']);
        $existingId = (int) DB_getItem(
            $_TABLES['blocks'],
            'bid',
            "type = 'phpblock' AND phpblockfn = '{$function}'"
        );

        if ($existingId > 0) {
            continue;
        }

        ++$maxOrder;

        $name = DB_escapeString($definition['name']);
        $title = DB_escapeString($definition['title']);

        DB_query(
            "INSERT INTO {$_TABLES['blocks']} "
            . "(is_enabled, name, type, title, blockorder, content, onleft, phpblockfn, "
            . "owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon) "
            . "VALUES ("
            . ($enabled ? '1' : '0') . ", "
            . "'{$name}', 'phpblock', '{$title}', {$maxOrder}, '', 0, '{$function}', "
            . "{$ownerId}, {$allUsersGroup}, 3, 3, 2, 2)"
        );

        if (!DB_error()) {
            $created[] = $definition['name'];
        }
    }

    return $created;
}
