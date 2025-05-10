<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Event\SubscriberInterface;

/**
 * An interface to help transitioning to SubscriberInterface.
 * For plugins which implement this interface method registerListeners() will not be called.
 *
 * @TODO Remove in 8.0
 *
 * @since  __DEPLOY_VERSION__
 */
interface PluginWithSubscriberInterface extends SubscriberInterface
{
}
