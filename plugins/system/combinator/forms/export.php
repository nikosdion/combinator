<?php
/**
 * @package   Combinator
 * @copyright Copyright (c)2020-2020 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/**
 * Class JFormFieldExport
 *
 * @noinspection PhpUnused
 *
 * @since        1.0.0
 */
class JFormFieldExport extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'Export';

	/** @inheritDoc */
	public function getInput(): string
	{
		$uri = Uri::getInstance(Route::_($this->getAttribute('url', 'index.php'), false, Route::TLS_IGNORE, true));
		$app = Factory::getApplication();
		$uri->setVar($app->getSession()->getToken(), 1);

		return HTMLHelper::_('link', $uri->toString(), Text::_('PLG_SYSTEM_COMBINATOR_EXPORT_ANCHOR'), [
			'class' => 'btn btn-primary'
		]);
	}
}