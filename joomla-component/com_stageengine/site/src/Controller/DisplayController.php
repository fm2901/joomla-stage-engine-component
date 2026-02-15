<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_stageengine
 */

namespace Joomla\Component\Stageengine\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

final class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = []): BaseController
    {
        $this->input->set('view', $this->input->get('view', 'dashboard'));

        return parent::display($cachable, ['Itemid' => 'INT']);
    }
}
