<?php
/**
 * Class NovaQRCodeSVG
 *
 * @created      08.03.2022
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2022 smiley
 * @license      MIT
 */

namespace codemasher\NovaQRCode;

use chillerlan\QRCode\Output\QRMarkupSVG;
use function ceil;
use function file_get_contents;
use function sprintf;

/**
 * Create SVG QR Codes with embedded logos (that are also SVG)
 */
class NovaQRCodeSVG extends QRMarkupSVG{

	/**
	 * @inheritDoc
	 */
	protected function paths():string{

		if($this->options->clearLogoSpace){
			$size = (int)ceil($this->moduleCount * $this->options->svgLogoScale);

			$this->matrix->setLogoSpace($size, $size);
		}

		$svg = parent::paths();

		if($this->options->svgLogo !== null){

			$svg .= $this->getLogo();

		}
		return $svg;
	}

	/**
	 * returns a <g> element that contains the SVG logo and positions it properly within the QR Code
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Element/g
	 * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/transform
	 */
	protected function getLogo():string{
		// @todo: customize the <g> element to your liking (css class, style...)
		return sprintf(
			'%5$s<g transform="translate(%1$s %1$s) scale(%2$s)" class="%3$s">%5$s	%4$s%5$s</g>',
			($this->moduleCount - ($this->moduleCount * $this->options->svgLogoScale)) / 2,
			$this->options->svgLogoScale,
			$this->options->svgLogoCssClass,
			file_get_contents($this->options->svgLogo),
			$this->options->eol
		);
	}

}
