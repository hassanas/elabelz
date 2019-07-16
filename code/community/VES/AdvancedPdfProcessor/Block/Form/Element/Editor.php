<?php
class VES_AdvancedPdfProcessor_Block_Form_Element_Editor extends Varien_Data_Form_Element_Editor
{
	public function getElementHtml()
    {
        $js = '
            <script type="text/javascript">
            //<![CDATA[
                openEditorPopup = function(url, name, specs, parent) {
                    if ((typeof popups == "undefined") || popups[name] == undefined || popups[name].closed) {
                        if (typeof popups == "undefined") {
                            popups = new Array();
                        }
                        var opener = (parent != undefined ? parent : window);
                        popups[name] = opener.open(url, name, specs);
                    } else {
                        popups[name].focus();
                    }
                    return popups[name];
                }

                closeEditorPopup = function(name) {
                    if ((typeof popups != "undefined") && popups[name] != undefined && !popups[name].closed) {
                        popups[name].close();
                    }
                }
            //]]>
            </script>';

        if($this->isEnabled())
        {
            // add Firebug notice translations
            $warn = 'Firebug is known to make the WYSIWYG editor slow unless it is turned off or configured properly.';
            $this->getConfig()->addData(array(
                'firebug_warning_title'  => $this->translate('Warning'),
                'firebug_warning_text'   => $this->translate($warn),
                'firebug_warning_anchor' => $this->translate('Hide'),
            ));

            $translatedString = array(
                'Insert Image...' => $this->translate('Insert Image...'),
                'Insert Media...' => $this->translate('Insert Media...'),
                'Insert File...'  => $this->translate('Insert File...')
            );

            $jsSetupObject = 'wysiwyg' . $this->getHtmlId();

            $forceLoad = '';
            if (!$this->isHidden()) {
                if ($this->getForceLoad()) {
                    $forceLoad = $jsSetupObject . '.setup("exact");';
                } else {
                    $forceLoad = 'Event.observe(window, "load", '
                                . $jsSetupObject . '.setup.bind(' . $jsSetupObject . ', "exact"));';
                }
            }

            $html = $this->_getButtonsHtml()
                . '<textarea name="' . $this->getName() . '" title="' . $this->getTitle()
                . '" id="' . $this->getHtmlId() . '"'
                . ' class="textarea ' . $this->getClass() . '" '
                . $this->serialize($this->getHtmlAttributes()) . ' >' . $this->getEscapedValue() . '</textarea>'
                . $js . '
                <script type="text/javascript">
                //<![CDATA[
                    if ("undefined" != typeof(Translator)) {
                        Translator.add(' . Zend_Json::encode($translatedString) . ');
                    }'
                    . $jsSetupObject . ' = new tinyMceWysiwygSetup("' . $this->getHtmlId() . '", vesEditorConfigJSON);'
                    . $forceLoad.'
                    editorFormValidationHandler = ' . $jsSetupObject . '.onFormValidation.bind(' . $jsSetupObject . ');
                    Event.observe("toggle' . $this->getHtmlId() . '", "click", '
                        . $jsSetupObject . '.toggle.bind('.$jsSetupObject.'));
                    varienGlobalEvents.attachEventHandler("formSubmit", editorFormValidationHandler);
                    varienGlobalEvents.attachEventHandler("tinymceBeforeSetContent", '
                        . $jsSetupObject . '.beforeSetContent.bind(' . $jsSetupObject . '));
                    varienGlobalEvents.attachEventHandler("tinymceSaveContent", '
                        . $jsSetupObject . '.saveContent.bind(' . $jsSetupObject . '));
                    varienGlobalEvents.clearEventHandlers("open_browser_callback");
                    varienGlobalEvents.attachEventHandler("open_browser_callback", '
                        . $jsSetupObject . '.openFileBrowser.bind(' . $jsSetupObject . '));
                //]]>
                </script>';

            $html = $this->_wrapIntoContainer($html);
            $html .= $this->getAfterElementHtml();
            return $html;
        } else {
            // Display only buttons to additional features
            if ($this->getConfig('widget_window_url')) {
                $html = $this->_getButtonsHtml() . $js . parent::getElementHtml();
                $html = $this->_wrapIntoContainer($html);
                return $html;
            }
            return parent::getElementHtml();
        }
    }
}