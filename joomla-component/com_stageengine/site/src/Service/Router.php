<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_stageengine
 */

namespace Joomla\Component\Stageengine\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterBase;

final class Router extends RouterBase
{
    public function build(&$query): array
    {
        if (isset($query['view'])) {
            unset($query['view']);
        }

        return [];
    }

    public function parse(&$segments): array
    {
        return ['view' => 'dashboard'];
    }
}
