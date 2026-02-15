<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_stageengine
 */

namespace Joomla\Component\Stageengine\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;

final class StageengineComponent extends MVCComponent implements RouterServiceInterface
{
    use RouterServiceTrait;
}
