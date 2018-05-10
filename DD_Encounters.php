<?php

namespace dd_encounters;

use mp_general\base\BaseFunctions;

if (!defined('ABSPATH')) {
    exit;
}

abstract class DD_Encounters
{
    const PATH = DD_ENCOUNTERS_PATH;
    const URL  = DD_ENCOUNTERS_URL;

    public static function filterContent($content): string
    {
        if (BaseFunctions::isValidPOST(null)) {
            BaseFunctions::var_export($_POST, 1);
        } else {
            ob_start()
            ?>
            <form method="post">
                <div class="row">
                    <div class="col s3">
                        <label>
                            Actor
                            <select>
                                <option value="sam"></option>
                            </select>
                        </label>
                    </div>
                    <div class="col s3">
                        <label>
                            Target
                            <input type="text" name="target">
                        </label>
                    </div>
                    <div class="col s3">
                        <label>
                            Action
                            <input type="text" name="action">
                        </label>
                    </div>
                    <div class="col s3">
                        <label>
                            Damage
                            <input type="text" name="damage">
                        </label>
                    </div>
                </div>
            </form>
            <?php
            return ob_get_clean();
        }
        return $content . 'test';
    }
}

add_filter('the_content', [DD_Encounters::class, 'filterContent']);
