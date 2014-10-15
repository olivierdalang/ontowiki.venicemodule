<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Controller for OntoWiki Venicemodule Module
 *
 * @category OntoWiki
 * @package  Extensions_Venicemodule
 * @author   {@link http://sebastian.tramp.name Sebastian Tramp}
 */
class VenicemoduleController extends OntoWiki_Controller_Component
{
    private $_store;
    private $_translate;
    private $_ac;
    private $_model;
    /* an array of arrays, each has type and text */
    private $_messages = array();
    /* the setup consists of state and config */
    private $_setup = null;
    private $_limit = 50;

    /*
     * Initializes Naviagation Controller,
     * creates class vars for current store, session and model
     */
    public function init()
    {
        parent::init();
        $this->_store     = $this->_owApp->erfurt->getStore();
        $this->_translate = $this->_owApp->translate;
        $this->_ac        = $this->_erfurt->getAc();

        $sessionKey         = 'Venicemodule' . (isset($config->session->identifier) ? $config->session->identifier : '');
        $this->stateSession = new Zend_Session_Namespace($sessionKey);

        $this->_model = $this->_owApp->selectedModel;
        if (isset($this->_request->m)) {
            $this->_model = $_store->getModel($this->_request->m);
        }
        if (empty($this->_model)) {
            throw new OntoWiki_Exception(
                'Missing parameter m (model) and no selected model in session!'
            );
        }
        // create title helper
        $this->titleHelper = new OntoWiki_Model_TitleHelper($this->_model);

        // Model Based Access Control
        if (!$this->_ac->isModelAllowed('view', $this->_model->getModelIri())) {
            throw new Erfurt_Ac_Exception('You are not allowed to read this model.');
        }

    }

    
}
