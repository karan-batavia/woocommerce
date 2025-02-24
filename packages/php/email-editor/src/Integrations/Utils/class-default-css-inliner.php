<?php
/**
 * This file is part of the EmailEditor package.
 *
 * @package MailPoet\EmailEditor
 */

declare( strict_types = 1 );
namespace MailPoet\EmailEditor\Integrations\Utils;

use MailPoet\EmailEditor\Engine\Renderer\Css_Inliner;
use Pelago\Emogrifier\CssInliner;

class Default_Css_Inliner implements Css_Inliner {
	private CssInliner $inliner;

	public function __construct() {
		if (!class_exists('Pelago\Emogrifier\CssInliner')) {
			throw new \LogicException('Pelago\Emogrifier\CssInliner not found');
		}
	}


	public function from_html(string $unprocessed_html): self {
		$that = new self();
		$that->inliner = CssInliner::fromHtml($unprocessed_html);
		return $that;
	}

	public function inline_css(string $css = ''): self {
		if (!isset($this->inliner)) {
			throw new \LogicException('You must call from_html before calling inline_css');
		}
		$this->inliner->inlineCss($css);
		return $this;
	}

	public function render(): string {
		if (!isset($this->inliner)) {
			throw new \LogicException('You must call from_html before calling inline_css');
		}
		return $this->inliner->render();
	}
}
