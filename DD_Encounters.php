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

    public static function enqueueAdminScripts()
    {
        $page = $_GET['page'] ?? null;
        if (!in_array($page, ['dd_encounters'])) {
            return;
        }
        switch ($page) {
            case 'dd_encounters':
                self::enquireFieldsManagerScripts();
                break;
        }
    }

    private static function enquireFieldsManagerScripts()
    {
        $activeTab = $_GET['tab'] ?? 'shared';
        wp_enqueue_script('mp-ssv-fields-manager', SSV_Forms::URL . '/js/fields-manager.js', ['jquery']);
        wp_localize_script('mp-ssv-fields-manager', 'mp_ssv_fields_manager_params', [
            'urls'       => [
                'plugins'  => plugins_url(),
                'ajax'     => admin_url('admin-ajax.php'),
                'base'     => get_home_url(),
                'basePath' => ABSPATH,
            ],
            'actions'    => [
                'save'   => 'mp_general_forms_save_field',
                'delete' => 'mp_general_forms_delete_field',
            ],
            'isShared'   => $activeTab === 'shared',
            'roles'      => array_keys(get_editable_roles()),
            'inputTypes' => BaseFunctions::getInputTypes($activeTab === 'shared' ? ['role_checkbox', 'role_select'] : []),
            'formId'     => $_GET['id'] ?? null,
        ]);
    }

    private static function enquireFormEditorScripts()
    {
        wp_enqueue_script('mp-ssv-form-editor', SSV_Forms::URL . '/js/form-editor.js', ['jquery']);
    }

    private static function enquireFormsManagerScripts()
    {
        wp_enqueue_script('mp-ssv-forms-manager', SSV_Forms::URL . '/js/forms-manager.js', ['jquery']);
        wp_localize_script('mp-ssv-forms-manager', 'mp_ssv_forms_manager_params', [
            'urls'    => [
                'plugins'  => plugins_url(),
                'ajax'     => admin_url('admin-ajax.php'),
                'base'     => get_home_url(),
                'basePath' => ABSPATH,
            ],
            'actions' => [
                'delete' => 'mp_general_forms_delete_form',
            ],
            'formId'  => $_GET['id'] ?? null,
        ]);
    }
}

add_filter('the_content', [DD_Encounters::class, 'filterContent']);
register_activation_hook(__FILE__, [DD_Encounters::class, 'setup']);
register_deactivation_hook(__FILE__, [DD_Encounters::class, 'deactivate']);
