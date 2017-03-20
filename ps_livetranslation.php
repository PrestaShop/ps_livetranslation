<?php
/**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_Livetranslation extends Module
{
    /** Iso code for fake language in Crowdin */
    CONST LIVETRANSLATION_ISO = 'ud';

    /** Locale for fake language in Crowdin */
    CONST LIVETRANSLATION_LOCALE = 'en-UD';

    public function __construct()
    {
        $this->name = 'ps_livetranslation';
        $this->author = 'PrestaShop';
        $this->version = '1.0.0';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Live translation', array(), 'Modules.Livetranslation.Admin');
        $this->description = $this->trans('Live translation module with Crowdin integration!', array(), 'Modules.Livetranslation.Admin');

        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);

        if (!Tools::isSubmit('submitLiveTranslation')) {
            $this->checkLiveTranslation();
        }
    }

    public function install()
    {
        if ($success = Language::downloadAndInstallLanguagePack(self::LIVETRANSLATION_ISO, $version = _PS_VERSION_, $params = null, $install = true)) {
            Language::loadLanguages();

            Configuration::updateValue('LIVETRANSLATION_BACKOFFICE', false);
            Configuration::updateValue('LIVETRANSLATION_FRONTOFFICE', false);

            return parent::install()
                && $this->registerHook('displayHeader')
                && $this->registerHook('displayBackOfficeHeader');

        } else {
            $this->_errors[] = $this->trans('Unable to install the module because of translation Upside Down.', array(), 'Modules.Livetranslation.Admin');
            return false;
        }
    }

    public function uninstall()
    {
        Configuration::updateValue('LIVETRANSLATION_BACKOFFICE', false);
        Configuration::updateValue('LIVETRANSLATION_FRONTOFFICE', false);

        $this->disableLiveTranslation();

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitLiveTranslation')) {

            Configuration::updateValue('LIVETRANSLATION_BACKOFFICE', (bool) Tools::getValue('LIVETRANSLATION_BACKOFFICE'));
            Configuration::updateValue('LIVETRANSLATION_FRONTOFFICE', (bool) Tools::getValue('LIVETRANSLATION_FRONTOFFICE'));

            $output = $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success'));
        }

        return $output.$this->renderForm();
    }

    public function renderForm()
    {
        $defaultLanguage = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $liveTranslationLanguage = new Language((int)Language::getIdByIso(self::LIVETRANSLATION_ISO));

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Settings', array(), 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ),
                'description' => $this->trans('The Live Translation module makes it possible to translate PrestaShop right from its various pages (back and front office). It send these in-context translations directly to the PrestaShop translation project on Crowdin: this is for contributing to our community translations, not to translate or customize your own shop.', array(), 'Admin.Livetranslation.Admin'),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Enable live translation on your back office', array(), 'Modules.Livetranslation.Admin'),
                        'name' => 'LIVETRANSLATION_BACKOFFICE',
                        'class' => 'fixed-width-xs',
                        'desc' =>
                            $this->trans('After enabled this option, you can edit your back office by clicking [1]here[/1]', array(
                                '[1]' => '<a href="'.$this->context->link->getAdminLink('AdminModules', true, null, array('configure' => $this->name, 'live_translation' => 1)).'">',
                                '[/1]' => '</a>',
                                ),
                                'Modules.Livetranslation.Admin'
                            ).' '.
                            $this->trans('To disable live translation, [1]click here[/1].', array(
                                '[1]' => '<a href="'.$this->context->link->getAdminLink('AdminModules', true, null, array('configure' => $this->name, 'disable_live_translation' => 1)).'">',
                                '[/1]' => '</a>',
                            ),
                                'Modules.Livetranslation.Admin'
                            ),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', array(), 'Admin.Global'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('No', array(), 'Admin.Global'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Enable live translation on your front office', array(), 'Modules.Livetranslation.Admin'),
                        'name' => 'LIVETRANSLATION_FRONTOFFICE',
                        'class' => 'fixed-width-xs',
                        'desc' => $this->trans('Enable if you wish live translation to be active on your front office.', array(), 'Modules.Livetranslation.Admin').
                            '<br>'.
                            $this->trans('After enabled this option, you can edit your front office by clicking [1]here[/1].', array(
                                '[1]' => '<a href="'.$this->context->link->getBaseLink($this->context->shop->id).$liveTranslationLanguage->iso_code.'/?live_translation=1" target="_blank">',
                                '[/1]' => '</a>',
                                ),
                                'Modules.Livetranslation.Admin'
                            ).' '.
                            $this->trans('To disable live translation, [1]click here[/1].', array(
                                '[1]' => '<a href="'.$this->context->link->getBaseLink($this->context->shop->id).$defaultLanguage->iso_code.'/?disable_live_translation=1" target="_blank">',
                                '[/1]' => '</a>',
                            ),
                                'Modules.Livetranslation.Admin'
                            ),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', array(), 'Admin.Global'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('No', array(), 'Admin.Global'),
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                ),
            ),
        );

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitLiveTranslation';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Get config values for live translation
     *
     * @return array
     */
    public function getConfigFieldsValues()
    {
        return array(
            'LIVETRANSLATION_BACKOFFICE' => Tools::getValue('LIVETRANSLATION_BACKOFFICE', (bool) Configuration::get('LIVETRANSLATION_BACKOFFICE')),
            'LIVETRANSLATION_FRONTOFFICE' => Tools::getValue('LIVETRANSLATION_FRONTOFFICE', (bool) Configuration::get('LIVETRANSLATION_FRONTOFFICE')),
        );
    }

    /**
     * Hook for header shop (front)
     *
     * @param $params
     */
    public function hookDisplayHeader($params)
    {
        if ((bool) Configuration::get('LIVETRANSLATION_FRONTOFFICE') && $this->isLiveTranslationActive()) {
            $this->context->controller->registerJavascript('modules-livetranslation', 'modules/'.$this->name.'/js/livetranslation.js', ['position' => 'bottom', 'priority' => 110]);
        }
    }

    /**
     * Hook for Back office header
     *
     * @param $params
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        if ((bool) Configuration::get('LIVETRANSLATION_BACKOFFICE') && $this->isLiveTranslationActive()) {
            $this->context->controller->addJS($this->_path.'js/livetranslation.js', 'all');
        }
    }

    /**
     * Handle check live translation job
     */
    private function checkLiveTranslation()
    {
        if ((bool)Tools::getValue('live_translation')) {
            $this->enableLiveTranslation();
        } else if ((bool)Tools::getValue('disable_live_translation')) {
            $this->disableLiveTranslation();
        }
    }

    /**
     * Enable live translation mode (using cookie)
     */
    private function enableLiveTranslation()
    {
        $this->handleLiveTranslationMode(true, (int)Language::getIdByIso(self::LIVETRANSLATION_ISO));
    }

    /**
     * Disable live translation mode (using cookie)
     */
    private function disableLiveTranslation()
    {
        $this->handleLiveTranslationMode(false, (int)Configuration::get('PS_LANG_DEFAULT'));
    }

    /**
     * Used to enable/disable live translation mode with good language
     *
     * @param $state bool
     * @param $idLang int
     */
    private function handleLiveTranslationMode($state, $idLang)
    {
        $lang = new Language((int)$idLang);

        if (!empty($lang)) {
            $this->context->cookie->isLiveTranslationActive = $state;
            $this->context->cookie->id_lang = $lang->id;
            $this->context->language = $lang;

            if (isset($this->context->employee) && !empty($this->context->employee->id)) {
                $employee = new Employee($this->context->employee->id);
                $employee->id_lang = $lang->id;
                $employee->save();
            }
        }

        $this->redirectLiveTranslation($lang->iso_code);
    }

    /**
     * Check if live translation mode is enabled (checking cookie)
     *
     * @return bool
     */
    private function isLiveTranslationActive()
    {
        return !empty($this->context->cookie->isLiveTranslationActive);
    }

    /**
     * Redirect after changing live translation mode
     *
     * @param $isoCode string
     */
    private function redirectLiveTranslation($isoCode)
    {
        if (isset($this->context->employee) && !empty($this->context->employee->id)) {
            Tools::redirect($this->context->link->getAdminLink('AdminModules', true, null, array('configure' => $this->name)));
        } else {
            Tools::redirect($this->context->link->getBaseLink($this->context->shop->id).$isoCode.'/');
        }
    }
}
