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

    private $languageIsInstalled;

    public function __construct()
    {
        $this->name = 'ps_livetranslation';
        $this->author = 'PrestaShop';
        $this->version = '1.0.3';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Live translation', array(), 'Modules.Livetranslation.Admin');
        $this->description = $this->trans('Contribute to the translation of PrestaShop directly from your own shop and administration panel.', array(), 'Modules.Livetranslation.Admin');

        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);

        $this->checkLiveTranslation();
    }

    public function install()
    {
        if ($success = Language::downloadAndInstallLanguagePack(self::LIVETRANSLATION_ISO, $version = _PS_VERSION_, $params = null, $install = true)) {
            Language::loadLanguages();
        } else {
            $this->_errors['cannot_install'] = $this->trans('Unable to install the module: the English Upside Down language cannot be installed.', array(), 'Modules.Livetranslation.Admin');
            return false;
        }

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBanner')
            && $this->registerHook('displayAdminAfterHeader')
            && $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        $this->disableLiveTranslation(true);

        return parent::uninstall();
    }

    public function getContent()
    {
        $this->languageIsInstalled = $this->checkIfLanguageIsInstalled();

        $output = '<div class="panel">';
        $output .= '<div class="alert alert-info">' .
            '<p>'.$this->trans('This module makes it possible to translate PrestaShop directly from its various pages (back and front office).', array(), 'Modules.Livetranslation.Admin').'</p>';

        $output .= '<p>'.$this->trans('It sends these in-context translations immediately to the [1]PrestaShop translation project[/1] on Crowdin: this is to contribute to our community translations, not to translate or customize your own shop.', array(
                '[1]' => '<a href="https://crowdin.com/project/prestashop-official" target="_blank">',
                '[/1]' => '</a>',
            ), 'Modules.Livetranslation.Admin').'</p>';

        $output .= '<p>'.$this->trans('If you don’t have a Crowdin account yet, [1]join now[/1]!', array(
                '[1]' => '<a href="https://crowdin.com/project/prestashop-official" target="_blank">',
                '[/1]' => '</a>',
            ), 'Modules.Livetranslation.Admin').'</p>';

        $output .= '<p>'.$this->trans('To be able to work, the module needs the virtual language “English Upside Down”. It is installed in your shop, but is not active, and not available to your customers.', array(), 'Modules.Livetranslation.Admin').'</p>';

        $output .= '</div>';

        if ($this->languageIsInstalled) {
            $output .= '<h4>'.$this->trans('How to proceed', array(), 'Modules.Livetranslation.Admin').'</h4>';

            $output .= '<ol>
                <li>'.$this->trans('Log in to your [1]Crowdin account[/1]', array(
                    '[1]' => '<a href="https://crowdin.com/project/prestashop-official" target="_blank">',
                    '[/1]' => '</a>',
                ), 'Modules.Livetranslation.Admin').'</li>
                <li>'.$this->trans('Translate PrestaShop using the live translation module', array(), 'Modules.Livetranslation.Admin').'</li>
            </ol>';

            $output .= '<form class="form-horizontal"><div class="form-group">
                <label class="control-label col-lg-1">'.$this->trans('Back office', array(), 'Modules.Livetranslation.Admin').'</label>
                <div class="col-lg-4">
                    <a class="btn btn-primary btn-sm" 
                        href="'.$this->context->link->getAdminLink('AdminModules', true, null, array('configure' => $this->name, 'live_translation' => 1)).'" 
                        title="'.$this->trans('Translate', array(), 'Modules.Livetranslation.Admin').'">' .
                        $this->trans('Translate', array(), 'Modules.Livetranslation.Admin') .
                    '</a>
                </div>
            </div>';

            Language::loadLanguages();
            $liveTranslationLanguage = new Language((int)Language::getIdByIso(self::LIVETRANSLATION_ISO));
            $output .= '<div class="form-group">
                <label class="control-label col-lg-1">'.$this->trans('Front office', array(), 'Modules.Livetranslation.Admin').'</label>
                <div class="col-lg-4">
                    <a class="btn btn-primary btn-sm" 
                        href="'.$this->context->link->getBaseLink($this->context->shop->id).$liveTranslationLanguage->iso_code.'/?live_translation=1" 
                        title="'.$this->trans('Translate', array(), 'Modules.Livetranslation.Admin').'"
                        target="_blank">' .
                    $this->trans('Translate', array(), 'Modules.Livetranslation.Admin') .
                    '</a>
                </div>
            </div></form>';
        }

        $output .= $this->displayErrors();

        $output .= '</div>';

        return $output;
    }

    private function displayErrors()
    {
        $output = '';

        if (!empty($this->_errors)) {
            $output .= '<div class="alert alert-danger">';
            foreach ($this->_errors as $error) {
                $output .= '<p>'.$error.'</p>';
            }
            $output .= '</div>';
        }

        return $output;
    }

    public function hookDisplayHeader($params)
    {
        if ($this->isLiveTranslationActive()) {
            $this->context->controller->registerJavascript('modules-livetranslation', 'modules/'.$this->name.'/js/livetranslation.js', ['position' => 'bottom', 'priority' => 110]);
        }
    }

    public function hookDisplayBanner($params)
    {
        if ($this->isLiveTranslationActive()) {
            $defaultLanguage = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

            return $this->displayBannerTpl(
                array('link' => $this->context->link->getBaseLink($this->context->shop->id).$defaultLanguage->iso_code.'/?disable_live_translation=1')
            );
        }

        return false;
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        if ($this->isLiveTranslationActive()) {
            $this->context->controller->addJS($this->_path.'js/livetranslation.js', 'all');
        }
    }

    public function hookDisplayAdminAfterHeader($params)
    {
        if ($this->isLiveTranslationActive()) {
            return $this->displayBannerTpl(
                array('link' => $this->context->link->getAdminLink('AdminModules', true, null, array('configure' => $this->name, 'disable_live_translation' => 1)))
            );
        }

        return false;
    }

    private function displayBannerTpl($data)
    {
        $this->smarty->assign($data);

        return $this->display(__FILE__, 'views/templates/hook/ps_livetranslation.tpl');
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
     * Check if upside down language is installed, if not, try to install it, then make an error
     *
     * @return bool
     */
    private function checkIfLanguageIsInstalled()
    {
        if (!(int)Language::getIdByIso(self::LIVETRANSLATION_ISO)) {
            if ($success = Language::downloadAndInstallLanguagePack(self::LIVETRANSLATION_ISO, $version = _PS_VERSION_, $params = null, $install = true)) {
                Language::loadLanguages();
            } else {
                $this->_errors['cannot_install'] = $this->trans('Live translation cannot be enabled. The English Upside Down language is missing and cannot be installed. Please reset the module to try again. ', array(), 'Modules.Livetranslation.Admin');
                return false;
            }
        }

        return true;
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
     *
     * @param $onInstall bool
     */
    private function disableLiveTranslation($onInstall = false)
    {
        $this->handleLiveTranslationMode(false, (int)Configuration::get('PS_LANG_DEFAULT'), $onInstall);
    }

    /**
     * Used to enable/disable live translation mode with good language
     *
     * @param $state bool
     * @param $idLang int
     * @param $onInstall bool
     */
    private function handleLiveTranslationMode($state, $idLang, $onInstall = false)
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

        // only redirect when is not on install
        if (true !== $onInstall && $state === false) {
            $this->redirectLiveTranslation();
        }
    }

    /**
     * Redirect after changing live translation mode
     */
    private function redirectLiveTranslation()
    {
        Tools::redirect($this->context->link->getAdminLink('AdminModules', true, null, array('configure' => $this->name)));
    }
}
